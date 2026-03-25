<?php
/**
 * REST endpoint: POST /ai-governance/v1/guardrail-trigger
 *
 * Called by agents to record a guardrail activation event.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_REST_Guardrail extends AIGIS_REST_Controller {

	public function register_routes(): void {
		register_rest_route( $this->namespace, '/guardrail-trigger', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'check_api_key' ],
				'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
			],
		] );
	}

	public function create_item( $request ): \WP_REST_Response|\WP_Error {
		$params = $request->get_json_params() ?: $request->get_params();

		$guardrail_type = sanitize_text_field( $params['guardrail_type'] ?? '' );
		$trigger_value  = sanitize_textarea_field( $params['trigger_value'] ?? '' );
		$model_id       = absint( $params['model_id'] ?? 0 );
		$prompt_id      = absint( $params['prompt_id'] ?? 0 );
		$user_id        = absint( $params['user_id'] ?? 0 );
		$session_id     = sanitize_text_field( $params['session_id'] ?? '' );
		$action_taken   = sanitize_text_field( $params['action_taken'] ?? 'blocked' );
		$context        = $params['context'] ?? [];

		if ( empty( $guardrail_type ) ) {
			return $this->error( 'aigis_guardrail_missing_type', __( 'guardrail_type is required.', 'ai-governance-suite' ) );
		}

		$allowed_actions = [ 'blocked', 'flagged', 'warned', 'allowed' ];
		if ( ! in_array( $action_taken, $allowed_actions, true ) ) {
			$action_taken = 'flagged';
		}

		global $wpdb;
		$table = $wpdb->prefix . 'aigis_guardrail_triggers';

		$id = $wpdb->insert( $table, [ // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			'guardrail_type' => $guardrail_type,
			'trigger_value'  => $trigger_value,
			'model_id'       => $model_id ?: null,
			'prompt_id'      => $prompt_id ?: null,
			'user_id'        => $user_id ?: null,
			'session_id'     => $session_id,
			'action_taken'   => $action_taken,
			'context'        => ! empty( $context ) ? wp_json_encode( $context ) : '',
			'triggered_at'   => current_time( 'mysql', true ),
		] );

		if ( ! $id ) {
			return $this->error( 'aigis_guardrail_insert_failed', __( 'Failed to record guardrail trigger.', 'ai-governance-suite' ), 500 );
		}

		$inserted_id = (int) $wpdb->insert_id;

		// Mirror to audit trail for full visibility.
		$audit = new AIGIS_DB_Audit();
		$audit->log(
			'guardrail.triggered',
			'guardrail',
			(string) $inserted_id,
			sprintf( 'Guardrail "%s" fired — action: %s.', $guardrail_type, $action_taken )
		);

		return new \WP_REST_Response( [ 'id' => $inserted_id ], 201 );
	}

	public function get_item_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'aigis-guardrail-trigger',
			'type'       => 'object',
			'properties' => [
				'guardrail_type' => [ 'type' => 'string', 'required' => true ],
				'trigger_value'  => [ 'type' => 'string' ],
				'model_id'       => [ 'type' => 'integer' ],
				'prompt_id'      => [ 'type' => 'integer' ],
				'user_id'        => [ 'type' => 'integer' ],
				'session_id'     => [ 'type' => 'string' ],
				'action_taken'   => [ 'type' => 'string', 'enum' => [ 'blocked', 'flagged', 'warned', 'allowed' ] ],
				'context'        => [ 'type' => 'object' ],
			],
		];
	}
}
