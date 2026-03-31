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

		if ( ! empty( $_POST['aigis_stress_nonce'] ) ) {
			$this->handle_run();
		}

		global $wpdb;

		$variations_table = $wpdb->prefix . 'aigis_stress_test_variations';
		$runs_table       = $wpdb->prefix . 'aigis_stress_test_runs';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$variations = $wpdb->get_results( "SELECT * FROM `{$variations_table}` ORDER BY name ASC" ) ?: [];

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$recent_runs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, v.name AS variation_name, v.category AS variation_category, p.post_title AS prompt_title
				 FROM `{$runs_table}` r
				 LEFT JOIN `{$variations_table}` v ON v.id = r.variation_id
				 LEFT JOIN {$wpdb->posts} p ON p.ID = r.prompt_post_id
				 ORDER BY r.executed_at DESC
				 LIMIT %d",
				50
			)
		) ?: [];
		$runs = $recent_runs;

		$prompts = get_posts( [
			'post_type'      => 'aigis_prompt',
			'post_status'    => [ 'publish', 'aigis-staging', 'aigis-pending-review' ],
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		wp_add_inline_script(
			'aigis-admin',
			'var aigisStressData = ' . wp_json_encode( [
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'aigis_run_stress_test' ),
				'variations' => array_map( static fn( $v ) => [ 'id' => $v->id, 'name' => $v->name ], $variations ),
			] ) . ';',
			'before'
		);

		include AIGIS_PLUGIN_DIR . 'admin/views/stress-tests/stress-tests.php';
	}

	private function handle_run(): void {
		if ( ! wp_verify_nonce( sanitize_key( $_POST['aigis_stress_nonce'] ), 'aigis_run_stress_test' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'ai-governance-suite' ) );
		}
		if ( ! current_user_can( AIGIS_Capabilities::RUN_STRESS_TESTS ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ai-governance-suite' ) );
		}

		global $wpdb;
		$prompt_id      = absint( $_POST['prompt_id'] ?? 0 );
		$variation_ids  = array_filter( array_map( 'absint', (array) ( $_POST['variation_ids'] ?? [] ) ) );
		$prompt         = $prompt_id ? get_post( $prompt_id ) : null;
		if ( ! $prompt || empty( $variation_ids ) ) {
			$this->finish_request( admin_url( 'admin.php?page=aigis-stress-tests&error=1' ) );
		}

		$variations_table = $wpdb->prefix . 'aigis_stress_test_variations';
		$runs_table       = $wpdb->prefix . 'aigis_stress_test_runs';
		$run_batch_id     = wp_generate_uuid4();
		$model_label      = 'Unassigned';
		$provider_label   = 'manual';
		$model_id         = (int) get_post_meta( $prompt_id, '_aigis_model_id', true );
		if ( $model_id > 0 ) {
			$model = (array) ( ( new AIGIS_DB_Inventory() )->get( $model_id ) ?: [] );
			if ( ! empty( $model ) ) {
				$provider_label = strtolower( (string) ( $model['vendor_name'] ?? 'manual' ) );
				$model_label    = trim( (string) ( $model['vendor_name'] ?? '' ) . ' / ' . (string) ( $model['model_name'] ?? '' ), ' /' );
			}
		}

		$created = 0;
		foreach ( $variation_ids as $variation_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$variation = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$variations_table}` WHERE id = %d", $variation_id ) );
			if ( ! $variation ) {
				continue;
			}

			$seed            = abs( crc32( $prompt_id . ':' . $variation_id ) );
			$score           = round( 0.55 + ( ( $seed % 40 ) / 100 ), 2 );
			$flagged         = $score < 0.7 ? 1 : 0;
			$modified_prompt = trim( $prompt->post_content . "\n\n[Stress Variation] " . $variation->name . ': ' . $variation->description );
			$output          = $flagged
				? 'Synthetic run recorded. Review recommended due to elevated risk variation.'
				: 'Synthetic run recorded successfully. No major issue detected in this variation.';

			$inserted = $wpdb->insert( $runs_table, [
				'run_batch_id'     => $run_batch_id,
				'prompt_post_id'   => $prompt_id,
				'variation_id'     => $variation_id,
				'variation_params' => (string) ( $variation->parameter_schema ?? '' ),
				'modified_prompt'  => $modified_prompt,
				'provider'         => $provider_label,
				'model_used'       => $model_label,
				'output'           => $output,
				'score'            => $score,
				'flagged'          => $flagged,
				'flag_reason'      => $flagged ? 'Synthetic score threshold triggered review.' : '',
				'executed_by'      => get_current_user_id(),
				'executed_at'      => current_time( 'mysql', true ),
			] );
			if ( false !== $inserted ) {
				$created++;
			}
		}

		( new AIGIS_DB_Audit() )->log( 'stress_test.run', 'stress_test', $run_batch_id, sprintf( 'Created %d stress test run(s) for prompt #%d.', $created, $prompt_id ) );

		$this->finish_request( admin_url( 'admin.php?page=aigis-stress-tests&ran=' . $created ) );
	}

	private function finish_request( string $url ): void {
		if ( ! headers_sent() ) {
			wp_safe_redirect( $url );
			exit;
		}

		echo '<script>window.location = ' . wp_json_encode( $url ) . ';</script>';
		echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $url ) . '"></noscript>';
		exit;
	}
}
