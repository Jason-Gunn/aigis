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
				"SELECT er.*, p.post_title AS prompt_title
				 FROM `{$this->table}` er
				 LEFT JOIN {$wpdb->posts} p ON p.ID = er.prompt_id
				 WHERE er.requires_review = 1 AND er.reviewed_at IS NULL
				 ORDER BY er.created_at ASC
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

		$where = $prompt_id > 0 ? $wpdb->prepare( 'AND prompt_id = %d', $prompt_id ) : '';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$this->table}` WHERE result = 'pass' AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY) {$where}",
				$days
			)
		);

		if ( $total === 0 ) {
			return 0.0;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$false_negatives = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$this->table}` WHERE result = 'pass' AND human_verdict = 'fail' AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY) {$where}",
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

		$where = $prompt_id > 0 ? $wpdb->prepare( 'AND prompt_id = %d', $prompt_id ) : '';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					DATE(created_at) AS `date`,
					COUNT(*) AS total,
					SUM(CASE WHEN result = 'pass' THEN 1 ELSE 0 END) AS passed
				 FROM `{$this->table}`
				 WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY) {$where}
				 GROUP BY DATE(created_at)
				 ORDER BY `date` ASC",
				$days
			)
		) ?: [];

		foreach ( $rows as &$row ) {
			$row->pass_rate = $row->total > 0 ? round( $row->passed / $row->total, 4 ) : 0.0;
		}
		unset( $row );

		return $rows;
	}

	/**
	 * Get overall summary stats — total runs, pass count, fail count, flagged count.
	 *
	 * @param int $days Look-back window.
	 * @return object { total: int, passed: int, failed: int, flagged: int }
	 */
	public function get_summary_stats( int $days = 30 ): object {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) AS total,
					SUM(CASE WHEN result = 'pass'    THEN 1 ELSE 0 END) AS passed,
					SUM(CASE WHEN result = 'fail'    THEN 1 ELSE 0 END) AS failed,
					SUM(CASE WHEN result = 'flagged' THEN 1 ELSE 0 END) AS flagged
				 FROM `{$this->table}`
				 WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		) ?? (object) [ 'total' => 0, 'passed' => 0, 'failed' => 0, 'flagged' => 0 ];
	}
}
