<?php
/**
 * Admin page: Cost & Budgets.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Page_Cost {

	private AIGIS_DB_Cost $db_cost;

	public function __construct() {
		$this->db_cost = new AIGIS_DB_Cost();
	}

	public function render(): void {
		if ( ! current_user_can( AIGIS_Capabilities::VIEW_COSTS ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ai-governance-suite' ) );
		}

		if ( ! empty( $_POST['aigis_budget_nonce'] ) ) {
			$this->handle_save();
		}

		if ( isset( $_GET['delete_budget'] ) && current_user_can( AIGIS_Capabilities::MANAGE_BUDGETS ) ) {
			$this->handle_delete();
		}

		$budgets    = $this->db_cost->get_active_budgets();
		$db_usage   = new AIGIS_DB_Usage_Log();
		$mtd_cost   = $db_usage->get_mtd_cost();
		$cost_trend = $db_usage->get_sessions_over_time( 30 );

		// Annotate each budget with current actual spend.
		foreach ( $budgets as &$budget ) {
			$budget['actual_spend'] = $this->db_cost->get_actual_spend( $budget['scope_type'], $budget['scope_value'] ?? '', $budget['period_type'] );
			$budget['pct_used']     = $budget['budget_usd'] > 0
				? round( ( $budget['actual_spend'] / $budget['budget_usd'] ) * 100, 1 )
				: 0;
		}
		unset( $budget );

		wp_localize_script( 'aigis-charts', 'aigisChartData', [
			'costTrend' => [
				'labels' => wp_list_pluck( $cost_trend, 'date' ),
				'actual' => array_map( 'floatval', wp_list_pluck( $cost_trend, 'cost_usd' ) ),
			],
		] );

		include AIGIS_PLUGIN_DIR . 'admin/views/cost/cost.php';
	}

	private function handle_save(): void {
		if ( ! wp_verify_nonce( sanitize_key( $_POST['aigis_budget_nonce'] ), 'aigis_save_budget' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'ai-governance-suite' ) );
		}
		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_BUDGETS ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ai-governance-suite' ) );
		}

		$allowed_scope_types  = [ 'global', 'department', 'project' ];
		$allowed_period_types = [ 'monthly', 'custom' ];

		$data = [
			'scope_type'    => in_array( $_POST['scope'] ?? '', $allowed_scope_types, true ) ? sanitize_text_field( $_POST['scope'] ) : 'global',
			'scope_value'   => sanitize_text_field( wp_unslash( $_POST['scope_value'] ?? '' ) ),
			'period_type'   => in_array( $_POST['period'] ?? '', $allowed_period_types, true ) ? sanitize_text_field( $_POST['period'] ) : 'monthly',
			'budget_usd'    => round( (float) ( $_POST['budget_usd'] ?? 0 ), 2 ),
			'alert_pct_80'  => 1,
			'alert_pct_100' => 1,
			'created_by'    => get_current_user_id(),
		];

		$row_id = absint( $_POST['budget_id'] ?? 0 );

		if ( $row_id ) {
			$this->db_cost->update( $data, [ 'id' => $row_id ] );
		} else {
			$this->db_cost->insert( $data );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=aigis-cost&saved=1' ) );
		exit;
	}

	private function handle_delete(): void {
		$id = absint( $_GET['delete_budget'] );
		if ( ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ?? '' ), 'aigis_delete_budget_' . $id ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'ai-governance-suite' ) );
		}
		$this->db_cost->delete( [ 'id' => $id ] );
		wp_safe_redirect( admin_url( 'admin.php?page=aigis-cost&deleted=1' ) );
		exit;
	}
}
