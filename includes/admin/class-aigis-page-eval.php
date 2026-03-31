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
		$results    = $this->db->get_recent_results( 50 );
		$pending    = $this->db->get_pending_review();
		$trend_data = $this->db->get_pass_rate_trend( 0, $days );
		$fn_rate    = $this->db->get_false_negative_rate( 0, $days );
		$considered = (int) $summary->passed + (int) $summary->failed;
		$stats      = [
			'total'            => (int) $summary->total,
			'pass_rate'        => $considered > 0 ? round( ( $summary->passed / $considered ) * 100, 1 ) : 0.0,
			'fail_rate'        => $considered > 0 ? round( ( $summary->failed / $considered ) * 100, 1 ) : 0.0,
			'pending'          => (int) $summary->pending,
			'false_negatives'  => (int) $summary->false_negatives,
			'false_negative_rate' => round( $fn_rate * 100, 1 ),
		];

		wp_add_inline_script(
			'aigis-charts',
			'var aigisChartData = ' . wp_json_encode( [
				'evalTrend' => [
					'labels'    => wp_list_pluck( $trend_data, 'date' ),
					'pass_rate' => array_map( static fn( $r ) => round( $r->pass_rate * 100, 1 ), $trend_data ),
					'fail_rate' => array_map( static fn( $r ) => round( ( 1 - $r->pass_rate ) * 100, 1 ), $trend_data ),
				],
			] ) . ';',
			'before'
		);

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

		$allowed_verdicts = [ 'pass', 'fail' ];
		if ( ! in_array( $verdict, $allowed_verdicts, true ) ) {
			wp_die( esc_html__( 'Invalid verdict.', 'ai-governance-suite' ) );
		}

		$this->db->update( [
			'pass_fail'       => $verdict,
			'reviewer_notes'  => $note,
			'reviewer_id'     => get_current_user_id(),
			'reviewed_at'     => current_time( 'mysql', true ),
		], [ 'id' => $eval_id ] );

		$audit = new AIGIS_DB_Audit();
		$audit->log( 'eval.verdictSubmitted', 'eval_result', (string) $eval_id, sprintf( 'Human verdict "%s" submitted for eval result #%d.', $verdict, $eval_id ) );

		wp_safe_redirect( admin_url( 'admin.php?page=aigis-eval&tab=pending&saved=1' ) );
		exit;
	}
}
