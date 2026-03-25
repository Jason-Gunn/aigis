<?php
/**
 * Admin page: Evaluation Results.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Page_Eval {

	private AIGIS_DB_Eval $db;

	public function __construct() {
		$this->db = new AIGIS_DB_Eval();
	}

	public function render(): void {
		if ( ! current_user_can( AIGIS_Capabilities::VIEW_EVAL ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ai-governance-suite' ) );
		}

		if ( ! empty( $_POST['aigis_eval_verdict_nonce'] ) ) {
			$this->handle_verdict();
		}

		$active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], [ 'results', 'pending', 'trends' ], true )
			? sanitize_key( $_GET['tab'] )
			: 'results';

		$days        = absint( $_GET['days'] ?? 30 );
		$days        = in_array( $days, [ 7, 30, 90 ], true ) ? $days : 30;

		$summary    = $this->db->get_summary_stats( $days );
		$pending    = $this->db->get_pending_review();
		$trend_data = $this->db->get_pass_rate_trend( 0, $days );
		$fn_rate    = $this->db->get_false_negative_rate( 0, $days );

		wp_localize_script( 'aigis-charts', 'aigisChartData', [
			'evalTrend' => [
				'labels'    => wp_list_pluck( $trend_data, 'date' ),
				'pass_rate' => array_map( static fn( $r ) => round( $r->pass_rate * 100, 1 ), $trend_data ),
				'fail_rate' => array_map( static fn( $r ) => round( ( 1 - $r->pass_rate ) * 100, 1 ), $trend_data ),
			],
		] );

		include AIGIS_PLUGIN_DIR . 'admin/views/eval/eval.php';
	}

	private function handle_verdict(): void {
		if ( ! wp_verify_nonce( sanitize_key( $_POST['aigis_eval_verdict_nonce'] ), 'aigis_eval_verdict' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'ai-governance-suite' ) );
		}
		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_EVAL ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ai-governance-suite' ) );
		}

		$eval_id = absint( $_POST['eval_id'] ?? 0 );
		$verdict = sanitize_key( $_POST['human_verdict'] ?? '' );
		$note    = sanitize_textarea_field( wp_unslash( $_POST['reviewer_note'] ?? '' ) );

		$allowed_verdicts = [ 'pass', 'fail', 'inconclusive' ];
		if ( ! in_array( $verdict, $allowed_verdicts, true ) ) {
			wp_die( esc_html__( 'Invalid verdict.', 'ai-governance-suite' ) );
		}

		$this->db->update( [
			'human_verdict'  => $verdict,
			'reviewer_note'  => $note,
			'reviewer_id'    => get_current_user_id(),
			'reviewed_at'    => current_time( 'mysql', true ),
			'requires_review' => 0,
		], [ 'id' => $eval_id ] );

		$audit = new AIGIS_DB_Audit();
		$audit->log( 'eval.verdictSubmitted', 'eval_result', (string) $eval_id, sprintf( 'Human verdict "%s" submitted for eval result #%d.', $verdict, $eval_id ) );

		wp_safe_redirect( admin_url( 'admin.php?page=aigis-eval&tab=pending&saved=1' ) );
		exit;
	}
}
