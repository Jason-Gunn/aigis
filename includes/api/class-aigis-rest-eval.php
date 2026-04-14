<?php
/**
 * REST endpoints: Evaluation results.
 *
 * POST /ai-governance/v1/eval-result     — record an automated eval run.
 * POST /ai-governance/v1/trace-validate  — validate a trace against policy.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_REST_Eval extends AIGIS_REST_Controller {

	public function register_routes(): void {
		register_rest_route( $this->namespace, '/eval-result', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_eval_result' ],
				'permission_callback' => [ $this, 'check_api_key' ],
			],
		] );

		register_rest_route( $this->namespace, '/trace-validate', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'validate_trace' ],
				'permission_callback' => [ $this, 'check_api_key' ],
			],
		] );
	}

	// -----------------------------------------------------------------------
	// POST /eval-result
	// -----------------------------------------------------------------------

	public function create_eval_result( $request ): \WP_REST_Response|\WP_Error {
		$params = $request->get_json_params() ?: $request->get_params();

		$prompt_id  = absint( $params['prompt_id'] ?? 0 );
		$model_id   = absint( $params['model_id'] ?? 0 );
		$result     = sanitize_key( $params['result'] ?? '' );
		$score      = isset( $params['score'] ) ? min( 1, max( 0, (float) $params['score'] ) ) : null;
		$evaluator  = sanitize_text_field( $params['evaluator'] ?? '' );
		$run_id     = sanitize_text_field( $params['run_id'] ?? '' );
		$details    = $params['details'] ?? [];
		$requires   = ! empty( $params['requires_review'] );

		$allowed_results = [ 'pass', 'fail', 'flagged', 'error' ];
		if ( ! in_array( $result, $allowed_results, true ) ) {
			return $this->error( 'aigis_eval_invalid_result', __( 'result must be one of: pass, fail, flagged, error.', 'ai-governance-suite' ) );
		}
		if ( $model_id === 0 ) {
			return $this->error( 'aigis_eval_missing_model', __( 'model_id is required.', 'ai-governance-suite' ) );
		}

		$db = new AIGIS_DB_Eval();
		$id = $db->insert( [
			'prompt_id'      => $prompt_id ?: null,
			'model_id'       => $model_id,
			'result'         => $result,
			'score'          => $score,
			'evaluator'      => $evaluator,
			'run_id'         => $run_id,
			'details'        => ! empty( $details ) ? wp_json_encode( $details ) : '',
			'requires_review' => $requires ? 1 : 0,
			'created_at'     => current_time( 'mysql', true ),
		] );

		if ( ! $id ) {
			return $this->error( 'aigis_eval_insert_failed', __( 'Failed to store eval result.', 'ai-governance-suite' ), 500 );
		}

		$audit = new AIGIS_DB_Audit();
		$audit->log(
			'eval.resultReceived',
			'eval_result',
			(string) $id,
			sprintf( 'Eval result "%s" recorded for model #%d.', $result, $model_id )
		);

		return new \WP_REST_Response( [ 'id' => $id ], 201 );
	}

	// -----------------------------------------------------------------------
	// POST /trace-validate
	// -----------------------------------------------------------------------

	public function validate_trace( $request ): \WP_REST_Response|\WP_Error {
		$params = $request->get_json_params() ?: $request->get_params();

		$trace        = $params['trace'] ?? null;
		$policy_id    = absint( $params['policy_id'] ?? 0 );
		$workflow_id  = absint( $params['workflow_id'] ?? 0 );

		if ( empty( $trace ) || ! is_array( $trace ) ) {
			return $this->error( 'aigis_trace_missing', __( 'trace payload is required.', 'ai-governance-suite' ) );
		}

		$violations = [];
		$passed     = true;

		// Run each registered trace validation filter.
		$violations = apply_filters( 'aigis_trace_validate', $violations, $trace, $policy_id, $workflow_id );

		if ( ! empty( $violations ) ) {
			$passed = false;
		}

		// Log the validation.
		$audit = new AIGIS_DB_Audit();
		$audit->log(
			'eval.traceValidated',
			'trace',
			(string) ( $workflow_id ?: $policy_id ),
			sprintf( 'Trace validated: %s. Violations: %d.', $passed ? 'passed' : 'failed', count( $violations ) ),
			[],
			[ 'violations' => $violations ]
		);

		return new \WP_REST_Response( [
			'passed'     => $passed,
			'violations' => $violations,
		], 200 );
	}
}
