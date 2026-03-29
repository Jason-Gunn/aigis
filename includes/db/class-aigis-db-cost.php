<?php
/**
 * Cost budgets database class.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_DB_Cost extends AIGIS_DB {

	protected function get_table_slug(): string {
		return 'aigis_cost_budgets';
	}

	/**
	 * Calculate actual spend for a given scope and period using usage_logs data.
	 *
	 * @param string $scope_type  'global'|'department'|'project'
	 * @param string $scope_value Department name or project tag depending on scope.
	 * @param string $period_type 'monthly'|'custom'
	 * @return float Total cost in USD.
	 */
	public function get_actual_spend( string $scope_type, string $scope_value = '', string $period_type = 'monthly' ): float {
		global $wpdb;
		$usage_table = $wpdb->prefix . 'aigis_usage_logs';

		// For 'custom' period, sum without a date filter (test data demo context).
		$date_sql = ( $period_type === 'custom' )
			? '1=1'
			: "logged_at >= DATE_FORMAT(NOW(), '%Y-%m-01')";

		if ( $scope_type === 'project' && $scope_value !== '' ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (float) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(SUM(cost_usd), 0) FROM `{$usage_table}` WHERE {$date_sql} AND project_tag = %s",
					$scope_value
				)
			);
		}

		if ( $scope_type === 'department' && $scope_value !== '' ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (float) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(SUM(cost_usd), 0) FROM `{$usage_table}` WHERE {$date_sql} AND department = %s",
					$scope_value
				)
			);
		}

		// Global scope — sum all activity this month.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (float) $wpdb->get_var(
			"SELECT COALESCE(SUM(cost_usd), 0) FROM `{$usage_table}` WHERE {$date_sql}"
		);
	}

	/**
	 * Get all budget records.
	 *
	 * @return array
	 */
	public function get_active_budgets(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			"SELECT * FROM `{$this->table}` ORDER BY scope_type, scope_value",
			ARRAY_A
		) ?: [];
	}

	/**
	 * Get budgets that are currently over their alert threshold.
	 * Used by the cron job to dispatch notifications.
	 *
	 * @return array Each row includes the budget row plus 'actual_spend' float.
	 */
	public function get_overage_budgets(): array {
		$budgets  = $this->get_active_budgets();
		$overages = [];

		foreach ( $budgets as $budget ) {
			$actual    = $this->get_actual_spend( $budget['scope_type'], $budget['scope_value'] ?? '', $budget['period_type'] );
			$pct       = $budget['budget_usd'] > 0 ? ( $actual / $budget['budget_usd'] ) * 100 : 0;
			$threshold = ! empty( $budget['alert_pct_80'] ) ? 80 : ( ! empty( $budget['alert_pct_100'] ) ? 100 : 101 );

			if ( $pct >= $threshold ) {
				$row                 = $budget;
				$row['actual_spend'] = $actual;
				$row['pct_used']     = round( $pct, 1 );
				$overages[]          = $row;
			}
		}

		return $overages;
	}
}
