<?php
/**
 * Admin page: Analytics.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Page_Analytics {

	public function render(): void {
		if ( ! current_user_can( AIGIS_Capabilities::VIEW_ANALYTICS ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ai-governance-suite' ) );
		}

		$days     = absint( $_GET['days'] ?? 30 );
		$days     = in_array( $days, [ 7, 30, 90 ], true ) ? $days : 30;

		$db       = new AIGIS_DB_Usage_Log();
		$kpi      = $db->get_kpi_stats( $days );
		$trend    = $db->get_sessions_over_time( $days );
		$models   = $db->get_model_breakdown( $days );
		$by_dept  = $db->get_cost_by_department( $days );
		$top_prompts = $db->get_top_prompts( 10 );

		wp_localize_script( 'aigis-charts', 'aigisChartData', [
			'usageTrend' => [
				'labels'   => wp_list_pluck( $trend, 'date' ),
				'sessions' => wp_list_pluck( $trend, 'sessions' ),
				'tokens'   => wp_list_pluck( $trend, 'tokens' ),
			],
			'modelBreakdown' => [
				'labels' => array_map( static fn( $r ) => $r->vendor_name . ' / ' . $r->model_name, $models ),
				'tokens' => wp_list_pluck( $models, 'tokens' ),
				'calls'  => wp_list_pluck( $models, 'calls' ),
			],
			'deptCost' => [
				'labels' => wp_list_pluck( $by_dept, 'department' ),
				'costs'  => wp_list_pluck( $by_dept, 'cost_usd' ),
			],
		] );

		include AIGIS_PLUGIN_DIR . 'admin/views/analytics/analytics.php';
	}
}
