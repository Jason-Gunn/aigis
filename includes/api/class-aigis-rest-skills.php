<?php
/**
 * REST endpoints: GET /ai-governance/v1/skills and /skills/{skill}
 *
 * Returns approved skills for integration testing and runtime consumption.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_REST_Skills extends AIGIS_REST_Controller {

	public function register_routes(): void {
		register_rest_route( $this->namespace, '/skills', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'check_skills_access' ],
				'args'                => [
					'page' => [
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					],
					'per_page' => [
						'type'              => 'integer',
						'default'           => 20,
						'sanitize_callback' => 'absint',
					],
					'tier' => [
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'tag' => [
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_title',
					],
					'search' => [
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			],
		] );

		register_rest_route( $this->namespace, '/skills/(?P<skill>[\w\-]+)', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'check_skills_access' ],
				'args'                => [
					'skill' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			],
		] );
	}

	public function check_skills_access( \WP_REST_Request $request ): bool|\WP_Error {
		$auth = $this->check_api_key( $request );
		if ( is_wp_error( $auth ) ) {
			return $auth;
		}

		if ( is_user_logged_in() && ! current_user_can( AIGIS_Capabilities::VIEW_SKILLS ) ) {
			return $this->error( 'aigis_skill_rest_forbidden', __( 'You do not have permission to view skills.', 'ai-governance-suite' ), 403 );
		}

		return true;
	}

	public function get_items( $request ) {
		$page     = max( 1, absint( $request->get_param( 'page' ) ?: 1 ) );
		$per_page = min( 100, max( 1, absint( $request->get_param( 'per_page' ) ?: 20 ) ) );

		$query_args = [
			'post_type'      => 'aigis_skill',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		];

		$meta_query = [];
		$tier = sanitize_text_field( (string) $request->get_param( 'tier' ) );
		if ( '' !== $tier ) {
			$meta_query[] = [
				'key'   => '_aigis_skill_tier',
				'value' => $tier,
			];
		}

		$search = sanitize_text_field( (string) $request->get_param( 'search' ) );
		if ( '' !== $search ) {
			$query_args['s'] = $search;
		}

		$tag = sanitize_title( (string) $request->get_param( 'tag' ) );
		if ( '' !== $tag ) {
			$query_args['tax_query'] = [
				[
					'taxonomy' => 'aigis_skill_tag',
					'field'    => 'slug',
					'terms'    => $tag,
				],
			];
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$query = new \WP_Query( $query_args );
		$items = array_map( [ $this, 'build_skill_summary' ], $query->posts );

		( new AIGIS_DB_Audit() )->log(
			'api.skills.list',
			'skill',
			'0',
			sprintf( 'Approved skills list fetched (page %d).', $page )
		);

		return new \WP_REST_Response(
			[
				'items'      => $items,
				'pagination' => [
					'page'        => $page,
					'per_page'    => $per_page,
					'total_items' => (int) $query->found_posts,
					'total_pages' => (int) $query->max_num_pages,
				],
			],
			200
		);
	}

	public function get_item( $request ) {
		$identifier = sanitize_text_field( (string) $request->get_param( 'skill' ) );
		$posts      = ctype_digit( $identifier )
			? get_posts( [ 'post_type' => 'aigis_skill', 'post_status' => 'publish', 'include' => [ absint( $identifier ) ], 'numberposts' => 1 ] )
			: get_posts( [ 'post_type' => 'aigis_skill', 'post_status' => 'publish', 'name' => sanitize_title( $identifier ), 'numberposts' => 1 ] );

		if ( empty( $posts ) || ! $posts[0] instanceof \WP_Post ) {
			return $this->error( 'aigis_skill_not_found', __( 'Approved skill not found.', 'ai-governance-suite' ), 404 );
		}

		$skill = $posts[0];

		( new AIGIS_DB_Audit() )->log(
			'api.skills.get',
			'skill',
			(string) $skill->ID,
			sprintf( 'Approved skill "%s" fetched via REST.', get_the_title( $skill ) )
		);

		return new \WP_REST_Response( $this->build_skill_detail( $skill ), 200 );
	}

	private function build_skill_summary( \WP_Post $skill ): array {
		return [
			'id'               => $skill->ID,
			'slug'             => $skill->post_name,
			'title'            => get_the_title( $skill ),
			'description'      => (string) get_post_meta( $skill->ID, '_aigis_skill_description', true ),
			'tier'             => (string) get_post_meta( $skill->ID, '_aigis_skill_tier', true ),
			'version'          => (string) get_post_meta( $skill->ID, '_aigis_skill_version', true ),
			'format'           => (string) get_post_meta( $skill->ID, '_aigis_skill_format', true ),
			'readiness_score'  => (int) get_post_meta( $skill->ID, '_aigis_skill_readiness_score', true ),
			'updated_at_gmt'   => get_post_modified_time( 'c', true, $skill ),
			'tags'             => $this->get_term_names( $skill->ID, 'aigis_skill_tag' ),
			'detail_endpoint'  => rest_url( $this->namespace . '/skills/' . $skill->post_name ),
		];
	}

	private function build_skill_detail( \WP_Post $skill ): array {
		$markdown = (string) get_post_meta( $skill->ID, '_aigis_skill_markdown_export', true );

		return [
			'id'               => $skill->ID,
			'slug'             => $skill->post_name,
			'title'            => get_the_title( $skill ),
			'description'      => (string) get_post_meta( $skill->ID, '_aigis_skill_description', true ),
			'tier'             => (string) get_post_meta( $skill->ID, '_aigis_skill_tier', true ),
			'version'          => (string) get_post_meta( $skill->ID, '_aigis_skill_version', true ),
			'format'           => (string) get_post_meta( $skill->ID, '_aigis_skill_format', true ),
			'team'             => (string) get_post_meta( $skill->ID, '_aigis_skill_team', true ),
			'trigger_phrases'  => (string) get_post_meta( $skill->ID, '_aigis_skill_trigger_phrases', true ),
			'output_contract'  => (string) get_post_meta( $skill->ID, '_aigis_skill_output_contract', true ),
			'edge_cases'       => (string) get_post_meta( $skill->ID, '_aigis_skill_edge_cases', true ),
			'examples'         => (string) get_post_meta( $skill->ID, '_aigis_skill_examples', true ),
			'methodology'      => (string) $skill->post_content,
			'readiness_score'  => (int) get_post_meta( $skill->ID, '_aigis_skill_readiness_score', true ),
			'production_ready' => get_post_meta( $skill->ID, '_aigis_skill_production_ready', true ) === '1',
			'tags'             => $this->get_term_names( $skill->ID, 'aigis_skill_tag' ),
			'relationships'    => [
				'prompts'   => $this->get_related_titles( $skill->ID, '_aigis_linked_prompt_ids', 'aigis_prompt' ),
				'workflows' => $this->get_related_titles( $skill->ID, '_aigis_linked_workflow_ids', 'aigis_workflow' ),
				'policies'  => $this->get_related_titles( $skill->ID, '_aigis_linked_policy_ids', 'aigis_policy' ),
				'incidents' => $this->get_related_titles( $skill->ID, '_aigis_linked_incident_ids', 'aigis_incident' ),
			],
			'markdown_export'  => $markdown,
			'updated_at_gmt'   => get_post_modified_time( 'c', true, $skill ),
		];
	}

	private function get_related_titles( int $post_id, string $meta_key, string $post_type ): array {
		$related_ids = get_post_meta( $post_id, $meta_key, true );
		if ( ! is_array( $related_ids ) || empty( $related_ids ) ) {
			return [];
		}

		$posts = get_posts(
			[
				'post_type'      => $post_type,
				'post__in'       => array_values( array_filter( array_map( 'absint', $related_ids ) ) ),
				'posts_per_page' => -1,
				'orderby'        => 'post__in',
			]
		);

		return array_values(
			array_filter(
				array_map(
					static function ( \WP_Post $related_post ): string {
						return trim( (string) get_the_title( $related_post ) );
					},
					$posts
				)
			)
		);
	}

	private function get_term_names( int $post_id, string $taxonomy ): array {
		$terms = wp_get_object_terms( $post_id, $taxonomy, [ 'fields' => 'names' ] );
		return is_wp_error( $terms ) ? [] : array_values( array_filter( array_map( 'strval', $terms ) ) );
	}
}
