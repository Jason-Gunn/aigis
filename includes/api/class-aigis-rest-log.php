<?php
/**
 * REST endpoint: POST /ai-governance/v1/log
 *
 * Accepts usage log entries from external agents/integrations.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_REST_Log extends AIGIS_REST_Controller {

	public function register_routes(): void {
		register_rest_route( $this->namespace, '/log', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'check_api_key' ],
				'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
			],
		] );
	}

	public function create_item( $request ): \WP_REST_Response|\WP_Error {
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$model_id    = absint( $params['model_id'] ?? 0 );
		$prompt_id   = absint( $params['prompt_id'] ?? 0 );
		$user_id     = absint( $params['user_id'] ?? 0 );
		$total_tokens = absint( $params['total_tokens'] ?? 0 );
		$prompt_tokens = absint( $params['prompt_tokens'] ?? 0 );
		$completion_tokens = absint( $params['completion_tokens'] ?? 0 );
		$latency_ms  = absint( $params['latency_ms'] ?? 0 );
		$cost_usd    = round( (float) ( $params['cost_usd'] ?? 0 ), 6 );
		$status      = sanitize_text_field( $params['status'] ?? 'success' );
		$session_id  = sanitize_text_field( $params['session_id'] ?? '' );

		if ( $model_id === 0 ) {
			return $this->error( 'aigis_log_missing_model', __( 'model_id is required.', 'ai-governance-suite' ) );
		}

		$allowed_statuses = [ 'success', 'error', 'timeout', 'rate_limited' ];
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'success';
		}

		$db = new AIGIS_DB_Usage_Log();
		$id = $db->insert( [
			'model_id'          => $model_id,
			'prompt_id'         => $prompt_id ?: null,
			'user_id'           => $user_id ?: null,
			'session_id'        => $session_id,
			'total_tokens'      => $total_tokens,
			'prompt_tokens'     => $prompt_tokens,
			'completion_tokens' => $completion_tokens,
			'latency_ms'        => $latency_ms,
			'cost_usd'          => $cost_usd,
			'status'            => $status,
			'occurred_at'       => current_time( 'mysql', true ),
		] );

		if ( ! $id ) {
			return $this->error( 'aigis_log_insert_failed', __( 'Failed to write usage log entry.', 'ai-governance-suite' ), 500 );
		}

		return new \WP_REST_Response( [ 'id' => $id ], 201 );
	}

	public function get_item_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'aigis-usage-log',
			'type'       => 'object',
			'properties' => [
				'model_id'          => [ 'type' => 'integer', 'required' => true ],
				'prompt_id'         => [ 'type' => 'integer' ],
				'user_id'           => [ 'type' => 'integer' ],
				'session_id'        => [ 'type' => 'string' ],
				'total_tokens'      => [ 'type' => 'integer' ],
				'prompt_tokens'     => [ 'type' => 'integer' ],
				'completion_tokens' => [ 'type' => 'integer' ],
				'latency_ms'        => [ 'type' => 'integer' ],
				'cost_usd'          => [ 'type' => 'number' ],
				'status'            => [ 'type' => 'string', 'enum' => [ 'success', 'error', 'timeout', 'rate_limited' ] ],
			],
		];
	}
}
