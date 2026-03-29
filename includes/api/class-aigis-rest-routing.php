<?php
/**
 * REST endpoint: GET /ai-governance/v1/routing/{agent_id}
 *
 * Returns the approved prompt and model configuration for a registered agent.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_REST_Routing extends AIGIS_REST_Controller {

	public function register_routes(): void {
		register_rest_route( $this->namespace, '/routing/(?P<agent_id>[\w\-]+)', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'check_api_key' ],
				'args'                => [
					'agent_id' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			],
		] );
	}

	public function get_item( $request ): \WP_REST_Response|\WP_Error {
		$agent_id = sanitize_text_field( $request->get_param( 'agent_id' ) );

		// Look up the agent in the workflow registry.
		$workflow = get_posts( [
			'post_type'   => 'aigis_workflow',
			'post_status' => 'publish',
			'meta_key'    => '_aigis_agent_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'  => $agent_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'numberposts' => 1,
		] );

		if ( empty( $workflow ) ) {
			return $this->error( 'aigis_routing_not_found', sprintf( __( 'No workflow found for agent ID "%s".', 'ai-governance-suite' ), $agent_id ), 404 );
		}

		$wf = $workflow[0];

		$nodes       = get_post_meta( $wf->ID, '_aigis_workflow_nodes', true ) ?: [];
		$db_inv      = new AIGIS_DB_Inventory();

		$resolved_nodes = [];
		foreach ( $nodes as $node ) {
			$model = null;
			if ( ! empty( $node['model_id'] ) ) {
				$row   = $db_inv->get( (int) $node['model_id'] );
				$model = $row ? [
					'id'          => $row->id,
					'vendor_name' => $row->vendor_name,
					'model_name'  => $row->model_name,
					'access_type' => $row->integration_type,
					'endpoint_url' => $row->api_endpoint,
				] : null;
			}
			$resolved_nodes[] = [
				'id'          => $node['id'],
				'label'       => $node['label'],
				'type'        => $node['type'],
				'description' => $node['description'],
				'model'       => $model,
			];
		}

		$data = [
			'workflow_id'   => $wf->ID,
			'workflow_title' => get_the_title( $wf ),
			'agent_id'      => $agent_id,
			'nodes'         => $resolved_nodes,
			'mermaid_source' => get_post_meta( $wf->ID, '_aigis_mermaid_source', true ),
		];

		// Audit the lookup.
		$audit = new AIGIS_DB_Audit();
		$audit->log(
			'api.routingLookup',
			'workflow',
			(string) $wf->ID,
			sprintf( 'Routing config fetched for agent "%s".', $agent_id )
		);

		return new \WP_REST_Response( $data, 200 );
	}
}
