<?php
/**
 * Admin page: Dashboard.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Page_Dashboard {

	public function render(): void {
		if ( ! current_user_can( AIGIS_Capabilities::VIEW_AI_INVENTORY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ai-governance-suite' ) );
		}

		$db_usage = new AIGIS_DB_Usage_Log();
		$db_inventory = new AIGIS_DB_Inventory();
		$db_eval  = new AIGIS_DB_Eval();

		$kpi_raw    = $db_usage->get_kpi_stats( 30 );
		$usage_data = $db_usage->get_sessions_over_time( 30 );
		$model_data = $db_usage->get_model_breakdown( 30 );
		$eval_stats = $db_eval->get_summary_stats( 30 );
		$mtd_cost   = $db_usage->get_mtd_cost();

		$incidents = get_posts( [
			'post_type'      => 'aigis_incident',
			'post_status'    => [ 'aigis-open', 'aigis-investigating' ],
			'posts_per_page' => 5,
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );

		$policies = get_posts( [
			'post_type'      => 'aigis_policy',
			'post_status'    => [ 'aigis-approved', 'publish' ],
			'posts_per_page' => 5,
			'meta_key'       => '_aigis_policy_expiry_date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		] );

		$active_models = $db_inventory->count_filtered( [ 'status' => 'active' ] );

		$kpi = [
			'total_sessions' => (int) ( $kpi_raw->total_calls ?? 0 ),
			'total_tokens'   => (int) ( $kpi_raw->total_tokens ?? 0 ),
			'total_cost_usd' => (float) ( $kpi_raw->total_cost_usd ?? 0 ),
			'avg_latency_ms' => (float) ( $kpi_raw->avg_latency_ms ?? 0 ),
			'unique_users'   => (int) ( $kpi_raw->unique_users ?? 0 ),
			'mtd_cost'       => (float) $mtd_cost,
			'open_incidents' => count( $incidents ),
			'active_models'  => $active_models,
		];

		$chart_data = [
			'labels'   => wp_list_pluck( $usage_data, 'date' ),
			'sessions' => wp_list_pluck( $usage_data, 'sessions' ),
			'tokens'   => wp_list_pluck( $usage_data, 'tokens' ),
		];

		wp_add_inline_script(
			'aigis-charts',
			'var aigisChartData = ' . wp_json_encode( [
				'usageTrend' => $chart_data,
				'modelBreakdown' => [
					'labels' => array_map( static fn( $r ) => $r->vendor_name . ' / ' . $r->model_name, $model_data ),
					'tokens' => wp_list_pluck( $model_data, 'tokens' ),
				],
			] ) . ';',
			'before'
		);

		include AIGIS_PLUGIN_DIR . 'admin/views/dashboard/dashboard.php';
	}
}
