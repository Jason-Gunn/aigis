<?php
/**
 * CPT: aigis_incident — Incident Management.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_CPT_Incident {

	public function register( AIGIS_Loader $loader ): void {
		$loader->add_action( 'init', $this, 'register_post_type' );
		$loader->add_action( 'init', $this, 'register_post_statuses' );
		$loader->add_action( 'pre_get_posts', $this, 'include_custom_statuses_in_admin_list' );
		$loader->add_action( 'add_meta_boxes', $this, 'add_metaboxes' );
		$loader->add_action( 'save_post_aigis_incident', $this, 'save_metaboxes', 10, 2 );
		$loader->add_filter( 'display_post_states', $this, 'display_post_states', 10, 2 );
		$loader->add_action( 'wp_ajax_aigis_set_incident_status', $this, 'ajax_set_status' );
	}

	public function register_post_type(): void {
		$labels = [
			'name'               => _x( 'Incidents', 'post type general name', 'ai-governance-suite' ),
			'singular_name'      => _x( 'Incident', 'post type singular name', 'ai-governance-suite' ),
			'add_new'            => __( 'Add New Incident', 'ai-governance-suite' ),
			'add_new_item'       => __( 'Add New Incident', 'ai-governance-suite' ),
			'edit_item'          => __( 'Edit Incident', 'ai-governance-suite' ),
			'all_items'          => __( 'All Incidents', 'ai-governance-suite' ),
			'search_items'       => __( 'Search Incidents', 'ai-governance-suite' ),
			'not_found'          => __( 'No incidents found.', 'ai-governance-suite' ),
			'not_found_in_trash' => __( 'No incidents found in Trash.', 'ai-governance-suite' ),
			'menu_name'          => __( 'Incidents', 'ai-governance-suite' ),
		];

		register_post_type( 'aigis_incident', [
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'aigis-dashboard',
			'supports'        => [ 'title', 'editor', 'revisions', 'author', 'custom-fields' ],
			'capability_type' => 'post',
			'map_meta_cap'    => false,
			'capabilities'    => [
				'create_posts'           => AIGIS_Capabilities::MANAGE_INCIDENTS,
				'edit_post'              => AIGIS_Capabilities::MANAGE_INCIDENTS,
				'edit_posts'             => AIGIS_Capabilities::MANAGE_INCIDENTS,
				'edit_others_posts'      => AIGIS_Capabilities::MANAGE_INCIDENTS,
				'edit_private_posts'     => AIGIS_Capabilities::MANAGE_INCIDENTS,
				'edit_published_posts'   => AIGIS_Capabilities::MANAGE_INCIDENTS,
				'read_post'              => AIGIS_Capabilities::VIEW_INCIDENTS,
				'read_private_posts'     => AIGIS_Capabilities::MANAGE_INCIDENTS,
				'delete_post'            => AIGIS_Capabilities::MANAGE_INCIDENTS,
				'delete_posts'           => AIGIS_Capabilities::MANAGE_INCIDENTS,
				'delete_private_posts'   => AIGIS_Capabilities::MANAGE_INCIDENTS,
				'delete_published_posts' => AIGIS_Capabilities::MANAGE_INCIDENTS,
				'delete_others_posts'    => AIGIS_Capabilities::MANAGE_INCIDENTS,
				'publish_posts'          => AIGIS_Capabilities::MANAGE_INCIDENTS,
			],
			'rewrite'         => false,
			'query_var'       => false,
			'has_archive'     => false,
		] );
	}

	public function register_post_statuses(): void {
		$statuses = [
			'aigis-open'          => [ 'label' => _x( 'Open', 'incident status', 'ai-governance-suite' ), 'noop' => _n_noop( 'Open <span class="count">(%s)</span>', 'Open <span class="count">(%s)</span>', 'ai-governance-suite' ) ],
			'aigis-investigating' => [ 'label' => _x( 'Investigating', 'incident status', 'ai-governance-suite' ), 'noop' => _n_noop( 'Investigating <span class="count">(%s)</span>', 'Investigating <span class="count">(%s)</span>', 'ai-governance-suite' ) ],
			'aigis-resolved'      => [ 'label' => _x( 'Resolved', 'incident status', 'ai-governance-suite' ), 'noop' => _n_noop( 'Resolved <span class="count">(%s)</span>', 'Resolved <span class="count">(%s)</span>', 'ai-governance-suite' ) ],
		];

		foreach ( $statuses as $slug => $args ) {
			register_post_status( $slug, [
				'label'                     => $args['label'],
				'public'                    => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => $args['noop'],
			] );
		}
	}

	// -----------------------------------------------------------------------
	// Metaboxes
	// -----------------------------------------------------------------------

	public function add_metaboxes(): void {
		add_meta_box(
			'aigis_incident_details',
			__( 'Incident Details', 'ai-governance-suite' ),
			[ $this, 'render_details_metabox' ],
			'aigis_incident',
			'normal',
			'high'
		);

		add_meta_box(
			'aigis_incident_linked',
			__( 'Linked Records', 'ai-governance-suite' ),
			[ $this, 'render_linked_metabox' ],
			'aigis_incident',
			'side',
			'default'
		);

		add_meta_box(
			'aigis_incident_investigation',
			__( 'Investigation Notes', 'ai-governance-suite' ),
			[ $this, 'render_investigation_metabox' ],
			'aigis_incident',
			'normal',
			'default'
		);

		add_meta_box(
			'aigis_incident_postmortem',
			__( 'Post-Mortem', 'ai-governance-suite' ),
			[ $this, 'render_postmortem_metabox' ],
			'aigis_incident',
			'normal',
			'low'
		);
	}

	public function render_details_metabox( \WP_Post $post ): void {
		wp_nonce_field( 'aigis_incident_details', 'aigis_incident_details_nonce' );
		$severity   = get_post_meta( $post->ID, '_aigis_severity', true ) ?: 'medium';
		$category   = get_post_meta( $post->ID, '_aigis_incident_category', true );
		$detected   = get_post_meta( $post->ID, '_aigis_detected_at', true );
		$resolved   = get_post_meta( $post->ID, '_aigis_resolved_at', true );

		include AIGIS_PLUGIN_DIR . 'admin/views/incidents/metabox-details.php';
	}

	public function render_linked_metabox( \WP_Post $post ): void {
		$linked_prompt_id  = (int) get_post_meta( $post->ID, '_aigis_linked_prompt_id', true );
		$linked_policy_ids = get_post_meta( $post->ID, '_aigis_linked_policy_ids', true ) ?: [];
		$linked_model_id   = (int) get_post_meta( $post->ID, '_aigis_linked_model_id', true );

		include AIGIS_PLUGIN_DIR . 'admin/views/incidents/metabox-linked.php';
	}

	public function render_investigation_metabox( \WP_Post $post ): void {
		$notes = get_post_meta( $post->ID, '_aigis_investigation_notes', true );
		include AIGIS_PLUGIN_DIR . 'admin/views/incidents/metabox-investigation.php';
	}

	public function render_postmortem_metabox( \WP_Post $post ): void {
		$postmortem = get_post_meta( $post->ID, '_aigis_postmortem', true );
		include AIGIS_PLUGIN_DIR . 'admin/views/incidents/metabox-postmortem.php';
	}

	public function save_metaboxes( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['aigis_incident_details_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_POST['aigis_incident_details_nonce'] ), 'aigis_incident_details' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_INCIDENTS ) ) {
			return;
		}

		$allowed_severities = [ 'low', 'medium', 'high', 'critical' ];
		if ( isset( $_POST['aigis_severity'] ) && in_array( $_POST['aigis_severity'], $allowed_severities, true ) ) {
			update_post_meta( $post_id, '_aigis_severity', sanitize_key( $_POST['aigis_severity'] ) );
		}

		if ( isset( $_POST['aigis_incident_category'] ) ) {
			update_post_meta( $post_id, '_aigis_incident_category', sanitize_text_field( wp_unslash( $_POST['aigis_incident_category'] ) ) );
		}
		if ( isset( $_POST['aigis_detected_at'] ) ) {
			update_post_meta( $post_id, '_aigis_detected_at', sanitize_text_field( wp_unslash( $_POST['aigis_detected_at'] ) ) );
		}
		if ( isset( $_POST['aigis_investigation_notes'] ) ) {
			update_post_meta( $post_id, '_aigis_investigation_notes', sanitize_textarea_field( wp_unslash( $_POST['aigis_investigation_notes'] ) ) );
		}
		if ( isset( $_POST['aigis_postmortem'] ) ) {
			update_post_meta( $post_id, '_aigis_postmortem', sanitize_textarea_field( wp_unslash( $_POST['aigis_postmortem'] ) ) );
		}
		if ( isset( $_POST['aigis_linked_prompt_id'] ) ) {
			update_post_meta( $post_id, '_aigis_linked_prompt_id', absint( $_POST['aigis_linked_prompt_id'] ) );
		}
		if ( isset( $_POST['aigis_linked_model_id'] ) ) {
			update_post_meta( $post_id, '_aigis_linked_model_id', absint( $_POST['aigis_linked_model_id'] ) );
		}
		if ( isset( $_POST['aigis_linked_policy_ids'] ) && is_array( $_POST['aigis_linked_policy_ids'] ) ) {
			update_post_meta( $post_id, '_aigis_linked_policy_ids', array_map( 'absint', $_POST['aigis_linked_policy_ids'] ) );
		}

		$audit = new AIGIS_DB_Audit();
		$audit->log( 'incident.saved', 'incident', (string) $post_id, sprintf( 'Incident "%s" saved.', get_the_title( $post_id ) ) );
	}

	public function ajax_set_status(): void {
		check_ajax_referer( 'aigis_incident_status', 'nonce' );

		$post_id    = absint( $_POST['post_id'] ?? 0 );
		$new_status = sanitize_key( $_POST['new_status'] ?? '' );
		$note       = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );

		$allowed = [ 'aigis-open', 'aigis-investigating', 'aigis-resolved', 'draft' ];
		if ( ! in_array( $new_status, $allowed, true ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid status.', 'ai-governance-suite' ) ] );
		}
		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_INCIDENTS ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'ai-governance-suite' ) ], 403 );
		}

		if ( $new_status === 'aigis-resolved' ) {
			update_post_meta( $post_id, '_aigis_resolved_at', current_time( 'mysql', true ) );
		}

		wp_update_post( [ 'ID' => $post_id, 'post_status' => $new_status ] );

		$audit = new AIGIS_DB_Audit();
		$audit->log( 'incident.statusChange', 'incident', (string) $post_id, sprintf( 'Incident status changed to "%s".', $new_status ) );

		wp_send_json_success( [
			'new_status' => $new_status,
			'label'      => get_post_status_object( $new_status )->label ?? $new_status,
		] );
	}

	public function display_post_states( array $states, \WP_Post $post ): array {
		if ( $post->post_type !== 'aigis_incident' ) {
			return $states;
		}
		$custom = [ 'aigis-open' => __( 'Open', 'ai-governance-suite' ), 'aigis-investigating' => __( 'Investigating', 'ai-governance-suite' ), 'aigis-resolved' => __( 'Resolved', 'ai-governance-suite' ) ];
		$status = get_post_status( $post );
		if ( isset( $custom[ $status ] ) ) {
			$states[ $status ] = $custom[ $status ];
		}
		return $states;
	}

	public function include_custom_statuses_in_admin_list( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		global $pagenow;
		if ( $pagenow !== 'edit.php' ) {
			return;
		}

		if ( $query->get( 'post_type' ) !== 'aigis_incident' ) {
			return;
		}

		$post_status = $query->get( 'post_status' );
		if ( $post_status && $post_status !== 'all' ) {
			return;
		}

		$query->set( 'post_status', [ 'draft', 'publish', 'future', 'pending', 'private', 'aigis-open', 'aigis-investigating', 'aigis-resolved' ] );
	}
}
