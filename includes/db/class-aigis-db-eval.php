<?php
/**
 * Evaluation results database class.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_DB_Eval extends AIGIS_DB {

	protected function get_table_slug(): string {
		return 'aigis_eval_results';
	}

	/**
	 * Get recent evaluation results with inventory labels.
	 *
	 * @param int $limit  Max results.
	 * @param int $offset Offset.
	 * @return array
	 */
	public function get_recent_results( int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT er.*, inv.vendor_name, inv.model_name
				 FROM `{$this->table}` er
				 LEFT JOIN {$wpdb->prefix}aigis_ai_inventory inv ON inv.id = er.inventory_id
				 ORDER BY er.submitted_at DESC
				 LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		) ?: [];
	}

	/**
	 * Get evaluation results pending human review.
	 *
	 * @param int $limit  Max results.
	 * @param int $offset Offset.
	 * @return array
	 */
	public function get_pending_review( int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT er.*, inv.vendor_name, inv.model_name
				 FROM `{$this->table}` er
				 LEFT JOIN {$wpdb->prefix}aigis_ai_inventory inv ON inv.id = er.inventory_id
				 WHERE er.pass_fail = 'pending-review' AND er.reviewed_at IS NULL
				 ORDER BY er.submitted_at ASC
				 LIMIT %d OFFSET %d",
				$limit, $offset
			)
		) ?: [];
	}

	/**
	 * Calculate the false-negative rate for a prompt (or all prompts if prompt_id = 0).
	 * False negatives = 'pass' result later marked as incorrect.
	 *
	 * @param int $prompt_id  Prompt post ID. 0 = all prompts.
	 * @param int $days       Look-back window.
	 * @return float Rate 0.0–1.0
	 */
	public function get_false_negative_rate( int $prompt_id = 0, int $days = 30 ): float {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$this->table}` WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);

		if ( $total === 0 ) {
			return 0.0;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$false_negatives = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$this->table}` WHERE false_negative = 1 AND submitted_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);

		return round( $false_negatives / $total, 4 );
	}

	/**
	 * Get pass-rate trend grouped by day.
	 *
	 * @param int $prompt_id Prompt post ID. 0 = all prompts.
	 * @param int $days      Look-back window.
	 * @return array[] Each row: { date: 'YYYY-MM-DD', total: int, passed: int, pass_rate: float }
	 */
	public function get_pass_rate_trend( int $prompt_id = 0, int $days = 30 ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					DATE(submitted_at) AS `date`,
					COUNT(*) AS total,
					SUM(CASE WHEN pass_fail = 'pass' THEN 1 ELSE 0 END) AS passed,
					SUM(CASE WHEN pass_fail = 'fail' THEN 1 ELSE 0 END) AS failed
				 FROM `{$this->table}`
				 WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
				 GROUP BY DATE(submitted_at)
				 ORDER BY `date` ASC",
				$days
			)
		) ?: [];

		foreach ( $rows as &$row ) {
			$considered     = (int) $row->passed + (int) $row->failed;
			$row->pass_rate = $considered > 0 ? round( $row->passed / $considered, 4 ) : 0.0;
		}
		unset( $row );

		return $rows;
	}

	/**
	 * Get overall summary stats.
	 *
	 * @param int $days Look-back window.
	 * @return object { total: int, passed: int, failed: int, pending: int, false_negatives: int }
	 */
	public function get_summary_stats( int $days = 30 ): object {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) AS total,
					SUM(CASE WHEN pass_fail = 'pass' THEN 1 ELSE 0 END) AS passed,
					SUM(CASE WHEN pass_fail = 'fail' THEN 1 ELSE 0 END) AS failed,
					SUM(CASE WHEN pass_fail = 'pending-review' THEN 1 ELSE 0 END) AS pending,
					SUM(CASE WHEN false_negative = 1 THEN 1 ELSE 0 END) AS false_negatives
				 FROM `{$this->table}`
				 WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		) ?? (object) [ 'total' => 0, 'passed' => 0, 'failed' => 0, 'pending' => 0, 'false_negatives' => 0 ];
	}
}
