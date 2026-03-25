<?php
/**
 * Usage logs database class.
 *
 * APPEND-ONLY: update() and delete() throw RuntimeException.
 * Provides analytics aggregate methods for the dashboard.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_DB_Usage_Log extends AIGIS_DB {

	protected function get_table_slug(): string {
		return 'aigis_usage_logs';
	}

	/**
	 * Usage logs are immutable.
	 *
	 * @throws RuntimeException Always.
	 */
	public function update( array $data, array $where ): never {
		throw new RuntimeException( 'Usage logs are append-only and cannot be modified.' );
	}

	/**
	 * Usage log deletion is not permitted via this class.
	 *
	 * @throws RuntimeException Always.
	 */
	public function delete( array $where ): never {
		throw new RuntimeException( 'Usage logs are append-only. Use AIGIS_Cron::prune_usage_logs() for retention management.' );
	}

	/**
	 * Prune usage records older than the configured retention period.
	 * Called by WP-Cron only.
	 *
	 * @return int Rows deleted.
	 */
	public function prune_old_records(): int {
		global $wpdb;
		$days = (int) get_option( 'aigis_usage_retention_days', 365 );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$this->table}` WHERE logged_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);
	}

	/**
	 * Aggregate usage sessions grouped by day over the last N days.
	 *
	 * @param int $days Number of days to look back.
	 * @return array[] Each row: { date: 'YYYY-MM-DD', sessions: int, tokens: int, cost_usd: float }
	 */
	public function get_sessions_over_time( int $days = 30 ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(logged_at) AS `date`, COUNT(*) AS sessions, COALESCE(SUM(input_tokens + output_tokens), 0) AS tokens, COALESCE(SUM(cost_usd), 0) AS cost_usd
				 FROM `{$this->table}`
				 WHERE logged_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
				 GROUP BY DATE(logged_at)
				 ORDER BY `date` ASC",
				$days
			)
		) ?: [];
	}

	/**
	 * Top N prompts by usage count.
	 *
	 * @param int $limit Number of results.
	 * @return array[] Each row: { prompt_id: int, post_title: string, uses: int }
	 */
	public function get_top_prompts( int $limit = 10 ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ul.prompt_post_id, p.post_title, COUNT(*) AS uses
				 FROM `{$this->table}` ul
				 LEFT JOIN {$wpdb->posts} p ON p.ID = ul.prompt_post_id
				 WHERE ul.prompt_post_id > 0
				 GROUP BY ul.prompt_post_id, p.post_title
				 ORDER BY uses DESC
				 LIMIT %d",
				$limit
			)
		) ?: [];
	}

	/**
	 * Breakdown of token usage by model.
	 *
	 * @param int $days Look-back window.
	 * @return array[] Each row: { model_id: int, vendor_name: string, model_name: string, calls: int, tokens: int }
	 */
	public function get_model_breakdown( int $days = 30 ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ul.inventory_id, inv.vendor_name, inv.model_name, COUNT(*) AS calls,
						COALESCE(SUM(ul.input_tokens + ul.output_tokens), 0) AS tokens
				 FROM `{$this->table}` ul
				 LEFT JOIN {$wpdb->prefix}aigis_ai_inventory inv ON inv.id = ul.inventory_id
				 WHERE ul.logged_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
				 GROUP BY ul.inventory_id, inv.vendor_name, inv.model_name
				 ORDER BY tokens DESC",
				$days
			)
		) ?: [];
	}

	/**
	 * Estimated cost grouped by department (user meta or group).
	 * Uses cost_usd stored per log entry.
	 *
	 * @param int $days Look-back window.
	 * @return array[] Each row: { department: string, calls: int, cost_usd: float }
	 */
	public function get_cost_by_department( int $days = 30 ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
						COALESCE(NULLIF(ul.department, ''), 'Unknown') AS department,
						COUNT(*) AS calls,
						COALESCE(SUM(ul.cost_usd), 0) AS cost_usd
				 FROM `{$this->table}` ul
				 WHERE ul.logged_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
				 GROUP BY ul.department
				 ORDER BY cost_usd DESC",
				$days
			)
		) ?: [];
	}

	/**
	 * KPI summary stats for the dashboard header row.
	 *
	 * Returns: { total_calls, total_tokens, total_cost_usd, avg_latency_ms, unique_users }
	 *
	 * @param int $days Look-back window.
	 * @return object|null
	 */
	public function get_kpi_stats( int $days = 30 ): ?object {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
						COUNT(*) AS total_calls,
						COALESCE(SUM(input_tokens + output_tokens), 0) AS total_tokens,
						COALESCE(SUM(cost_usd), 0) AS total_cost_usd,
						COALESCE(AVG(latency_ms), 0) AS avg_latency_ms,
						COUNT(DISTINCT user_id) AS unique_users
				 FROM `{$this->table}`
				 WHERE logged_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);
	}

	/**
	 * Total cost in the current calendar month.
	 *
	 * @return float
	 */
	public function get_mtd_cost(): float {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (float) $wpdb->get_var(
			"SELECT COALESCE(SUM(cost_usd), 0) FROM `{$this->table}` WHERE logged_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
		);
	}
}
