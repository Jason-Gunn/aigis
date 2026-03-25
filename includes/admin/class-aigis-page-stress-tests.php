<?php
/**
 * Admin page: Stress Tests.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Page_Stress_Tests {

	public function render(): void {
		if ( ! current_user_can( AIGIS_Capabilities::RUN_STRESS_TESTS ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ai-governance-suite' ) );
		}

		global $wpdb;

		$variations_table = $wpdb->prefix . 'aigis_stress_test_variations';
		$runs_table       = $wpdb->prefix . 'aigis_stress_test_runs';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$variations = $wpdb->get_results( "SELECT * FROM `{$variations_table}` WHERE is_enabled = 1 ORDER BY name ASC" ) ?: [];

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$recent_runs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, v.name AS variation_name, p.post_title AS prompt_title
				 FROM `{$runs_table}` r
				 LEFT JOIN `{$variations_table}` v ON v.id = r.variation_id
				 LEFT JOIN {$wpdb->posts} p ON p.ID = r.prompt_id
				 ORDER BY r.started_at DESC
				 LIMIT %d",
				50
			)
		) ?: [];

		$prompts = get_posts( [
			'post_type'      => 'aigis_prompt',
			'post_status'    => [ 'publish', 'aigis-staging' ],
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => [ 'ID', 'post_title' ],
		] );

		wp_localize_script( 'aigis-admin', 'aigisStressData', [
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'aigis_run_stress_test' ),
			'variations' => array_map( static fn( $v ) => [ 'id' => $v->id, 'name' => $v->name ], $variations ),
		] );

		include AIGIS_PLUGIN_DIR . 'admin/views/stress-tests/stress-tests.php';
	}
}
