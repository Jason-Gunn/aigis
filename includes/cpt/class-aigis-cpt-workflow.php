<?php
/**
 * CPT: aigis_workflow — Workflow Registry.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_CPT_Workflow {

	public function register( AIGIS_Loader $loader ): void {
		$loader->add_action( 'init', $this, 'register_post_type' );
		$loader->add_action( 'add_meta_boxes', $this, 'add_metaboxes' );
		$loader->add_action( 'save_post_aigis_workflow', $this, 'save_metaboxes', 10, 2 );
	}

	public function register_post_type(): void {
		$labels = [
			'name'               => _x( 'Workflows', 'post type general name', 'ai-governance-suite' ),
			'singular_name'      => _x( 'Workflow', 'post type singular name', 'ai-governance-suite' ),
			'add_new'            => __( 'Add New Workflow', 'ai-governance-suite' ),
			'add_new_item'       => __( 'Add New Workflow', 'ai-governance-suite' ),
			'edit_item'          => __( 'Edit Workflow', 'ai-governance-suite' ),
			'all_items'          => __( 'All Workflows', 'ai-governance-suite' ),
			'search_items'       => __( 'Search Workflows', 'ai-governance-suite' ),
			'not_found'          => __( 'No workflows found.', 'ai-governance-suite' ),
			'not_found_in_trash' => __( 'No workflows found in Trash.', 'ai-governance-suite' ),
			'menu_name'          => __( 'Workflows', 'ai-governance-suite' ),
		];

		register_post_type( 'aigis_workflow', [
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'aigis-dashboard',
			'supports'        => [ 'title', 'editor', 'revisions', 'author', 'custom-fields' ],
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'capabilities'    => [
				'create_posts'  => AIGIS_Capabilities::MANAGE_WORKFLOWS,
				'edit_posts'    => AIGIS_Capabilities::MANAGE_WORKFLOWS,
				'delete_posts'  => AIGIS_Capabilities::MANAGE_WORKFLOWS,
			],
			'rewrite'         => false,
			'query_var'       => false,
			'has_archive'     => false,
		] );
	}

	public function add_metaboxes(): void {
		add_meta_box(
			'aigis_workflow_diagram',
			__( 'Workflow Diagram', 'ai-governance-suite' ),
			[ $this, 'render_diagram_metabox' ],
			'aigis_workflow',
			'normal',
			'high'
		);

		add_meta_box(
			'aigis_workflow_nodes',
			__( 'Node Registry', 'ai-governance-suite' ),
			[ $this, 'render_nodes_metabox' ],
			'aigis_workflow',
			'normal',
			'default'
		);
	}

	public function render_diagram_metabox( \WP_Post $post ): void {
		wp_nonce_field( 'aigis_workflow_diagram', 'aigis_workflow_diagram_nonce' );
		$diagram_source = get_post_meta( $post->ID, '_aigis_mermaid_source', true );

		include AIGIS_PLUGIN_DIR . 'admin/views/workflows/metabox-diagram.php';
	}

	public function render_nodes_metabox( \WP_Post $post ): void {
		$nodes = get_post_meta( $post->ID, '_aigis_workflow_nodes', true ) ?: [];
		include AIGIS_PLUGIN_DIR . 'admin/views/workflows/metabox-nodes.php';
	}

	public function save_metaboxes( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['aigis_workflow_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_POST['aigis_workflow_nonce'] ), 'aigis_save_workflow' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_WORKFLOWS ) ) {
			return;
		}

		if ( isset( $_POST['aigis_diagram_source'] ) ) {
			// Mermaid source is essentially code — store as-is after basic sanitation.
			update_post_meta( $post_id, '_aigis_mermaid_source', sanitize_textarea_field( wp_unslash( $_POST['aigis_diagram_source'] ) ) );
		}

		if ( isset( $_POST['aigis_workflow_nodes'] ) && is_array( $_POST['aigis_workflow_nodes'] ) ) {
			$nodes = [];
			foreach ( wp_unslash( $_POST['aigis_workflow_nodes'] ) as $node ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$nodes[] = [
					'id'          => sanitize_text_field( $node['id'] ?? '' ),
					'label'       => sanitize_text_field( $node['label'] ?? '' ),
					'type'        => sanitize_text_field( $node['type'] ?? '' ),
					'model_id'    => absint( $node['model_id'] ?? 0 ),
					'description' => sanitize_textarea_field( $node['description'] ?? '' ),
				];
			}
			update_post_meta( $post_id, '_aigis_workflow_nodes', $nodes );
		}

		$audit = new AIGIS_DB_Audit();
		$audit->log( 'workflow.saved', 'workflow', (string) $post_id, sprintf( 'Workflow "%s" saved.', get_the_title( $post_id ) ) );
	}
}
