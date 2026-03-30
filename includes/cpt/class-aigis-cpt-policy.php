<?php
/**
 * CPT: aigis_policy — Policy Governance.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_CPT_Policy {

	public function register( AIGIS_Loader $loader ): void {
		$loader->add_action( 'init', $this, 'register_post_type' );
		$loader->add_action( 'init', $this, 'register_post_statuses' );
		$loader->add_action( 'add_meta_boxes', $this, 'add_metaboxes' );
		$loader->add_action( 'save_post_aigis_policy', $this, 'save_metaboxes', 10, 2 );
		$loader->add_filter( 'display_post_states', $this, 'display_post_states', 10, 2 );
		$loader->add_action( 'wp_ajax_aigis_set_policy_status', $this, 'ajax_set_status' );
	}

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	public function register_post_type(): void {
		$labels = [
			'name'               => _x( 'Policies', 'post type general name', 'ai-governance-suite' ),
			'singular_name'      => _x( 'Policy', 'post type singular name', 'ai-governance-suite' ),
			'add_new'            => __( 'Add New Policy', 'ai-governance-suite' ),
			'add_new_item'       => __( 'Add New Policy', 'ai-governance-suite' ),
			'edit_item'          => __( 'Edit Policy', 'ai-governance-suite' ),
			'all_items'          => __( 'Policies', 'ai-governance-suite' ),
			'search_items'       => __( 'Search Policies', 'ai-governance-suite' ),
			'not_found'          => __( 'No policies found.', 'ai-governance-suite' ),
			'not_found_in_trash' => __( 'No policies found in Trash.', 'ai-governance-suite' ),
			'menu_name'          => __( 'Policies', 'ai-governance-suite' ),
		];

		register_post_type( 'aigis_policy', [
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'aigis-dashboard',
			'supports'        => [ 'title', 'editor', 'revisions', 'author', 'custom-fields' ],
			'capability_type' => 'post',
			'map_meta_cap'    => false,
			'capabilities'    => [
				'create_posts'           => AIGIS_Capabilities::MANAGE_POLICIES,
				'edit_post'              => AIGIS_Capabilities::MANAGE_POLICIES,
				'edit_posts'             => AIGIS_Capabilities::MANAGE_POLICIES,
				'edit_others_posts'      => AIGIS_Capabilities::MANAGE_POLICIES,
				'edit_private_posts'     => AIGIS_Capabilities::MANAGE_POLICIES,
				'edit_published_posts'   => AIGIS_Capabilities::MANAGE_POLICIES,
				'read_post'              => AIGIS_Capabilities::VIEW_POLICIES,
				'read_private_posts'     => AIGIS_Capabilities::MANAGE_POLICIES,
				'delete_post'            => AIGIS_Capabilities::MANAGE_POLICIES,
				'delete_posts'           => AIGIS_Capabilities::MANAGE_POLICIES,
				'delete_private_posts'   => AIGIS_Capabilities::MANAGE_POLICIES,
				'delete_published_posts' => AIGIS_Capabilities::MANAGE_POLICIES,
				'delete_others_posts'    => AIGIS_Capabilities::MANAGE_POLICIES,
				'publish_posts'          => AIGIS_Capabilities::APPROVE_POLICIES,
			],
			'rewrite'         => false,
			'query_var'       => false,
			'has_archive'     => false,
		] );
	}

	public function register_post_statuses(): void {
		$statuses = [
			'aigis-in-review' => [
				'label'  => _x( 'In Review', 'post status', 'ai-governance-suite' ),
				'noop'   => _n_noop( 'In Review <span class="count">(%s)</span>', 'In Review <span class="count">(%s)</span>', 'ai-governance-suite' ),
			],
			'aigis-approved' => [
				'label'  => _x( 'Approved', 'post status', 'ai-governance-suite' ),
				'noop'   => _n_noop( 'Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>', 'ai-governance-suite' ),
			],
			'aigis-retired' => [
				'label'  => _x( 'Retired', 'post status', 'ai-governance-suite' ),
				'noop'   => _n_noop( 'Retired <span class="count">(%s)</span>', 'Retired <span class="count">(%s)</span>', 'ai-governance-suite' ),
			],
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
			'aigis_policy_details',
			__( 'Policy Details', 'ai-governance-suite' ),
			[ $this, 'render_details_metabox' ],
			'aigis_policy',
			'normal',
			'high'
		);

		add_meta_box(
			'aigis_policy_approval',
			__( 'Approval Workflow', 'ai-governance-suite' ),
			[ $this, 'render_approval_metabox' ],
			'aigis_policy',
			'side',
			'default'
		);

		add_meta_box(
			'aigis_policy_incidents',
			__( 'Linked Incidents', 'ai-governance-suite' ),
			[ $this, 'render_incidents_metabox' ],
			'aigis_policy',
			'side',
			'low'
		);
	}

	public function render_details_metabox( \WP_Post $post ): void {
		$version        = get_post_meta( $post->ID, '_aigis_policy_version', true ) ?: '1.0';
		$effective_date = get_post_meta( $post->ID, '_aigis_policy_effective_date', true );
		$review_date    = get_post_meta( $post->ID, '_aigis_policy_expiry_date', true );
		$owner          = get_post_meta( $post->ID, '_aigis_policy_owner', true );

		include AIGIS_PLUGIN_DIR . 'admin/views/policies/metabox-details.php';
	}

	public function render_approval_metabox( \WP_Post $post ): void {
		$status      = get_post_status( $post->ID );
		$log         = get_post_meta( $post->ID, '_aigis_policy_approval_log', true ) ?: [];
		$can_approve = current_user_can( AIGIS_Capabilities::APPROVE_POLICIES );

		include AIGIS_PLUGIN_DIR . 'admin/views/policies/metabox-approval.php';
	}

	public function render_incidents_metabox( \WP_Post $post ): void {
		$linked_ids = get_post_meta( $post->ID, '_aigis_linked_incident_ids', true ) ?: [];
		$incidents  = [];
		if ( ! empty( $linked_ids ) ) {
			$incidents = get_posts( [
				'post_type'      => 'aigis_incident',
				'post__in'       => array_map( 'absint', $linked_ids ),
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			] );
		}
		include AIGIS_PLUGIN_DIR . 'admin/views/policies/metabox-incidents.php';
	}

	public function save_metaboxes( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['aigis_policy_details_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_POST['aigis_policy_details_nonce'] ), 'aigis_policy_details' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_POLICIES ) ) {
			return;
		}

		if ( isset( $_POST['aigis_policy_version'] ) ) {
			update_post_meta( $post_id, '_aigis_policy_version', sanitize_text_field( wp_unslash( $_POST['aigis_policy_version'] ) ) );
		}
		$effective_date = $_POST['aigis_policy_effective_date'] ?? $_POST['aigis_effective_date'] ?? null;
		if ( null !== $effective_date ) {
			update_post_meta( $post_id, '_aigis_policy_effective_date', sanitize_text_field( wp_unslash( $effective_date ) ) );
		}
		$review_date = $_POST['aigis_policy_expiry_date'] ?? $_POST['aigis_review_date'] ?? null;
		if ( null !== $review_date ) {
			update_post_meta( $post_id, '_aigis_policy_expiry_date', sanitize_text_field( wp_unslash( $review_date ) ) );
		}
		if ( isset( $_POST['aigis_policy_owner'] ) ) {
			update_post_meta( $post_id, '_aigis_policy_owner', sanitize_text_field( wp_unslash( $_POST['aigis_policy_owner'] ) ) );
		}

		$audit = new AIGIS_DB_Audit();
		$audit->log( 'policy.saved', 'policy', (string) $post_id, sprintf( 'Policy "%s" saved.', get_the_title( $post_id ) ) );
	}

	// -----------------------------------------------------------------------
	// AJAX status transitions
	// -----------------------------------------------------------------------

	public function ajax_set_status(): void {
		check_ajax_referer( 'aigis_policy_status', 'nonce' );

		$post_id    = absint( $_POST['post_id'] ?? 0 );
		$new_status = sanitize_key( $_POST['new_status'] ?? '' );
		$note       = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );

		$allowed = [
			'aigis-in-review' => AIGIS_Capabilities::MANAGE_POLICIES,
			'aigis-approved'  => AIGIS_Capabilities::APPROVE_POLICIES,
			'aigis-retired'   => AIGIS_Capabilities::APPROVE_POLICIES,
			'draft'           => AIGIS_Capabilities::MANAGE_POLICIES,
		];

		if ( ! array_key_exists( $new_status, $allowed ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid status.', 'ai-governance-suite' ) ] );
		}
		if ( ! current_user_can( $allowed[ $new_status ] ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'ai-governance-suite' ) ], 403 );
		}

		wp_update_post( [ 'ID' => $post_id, 'post_status' => $new_status ] );

		$log   = get_post_meta( $post_id, '_aigis_policy_approval_log', true ) ?: [];
		$log[] = [
			'status'  => $new_status,
			'user_id' => get_current_user_id(),
			'note'    => $note,
			'at'      => current_time( 'c' ),
		];
		update_post_meta( $post_id, '_aigis_policy_approval_log', $log );

		$audit = new AIGIS_DB_Audit();
		$audit->log( 'policy.statusChange', 'policy', (string) $post_id, sprintf( 'Policy status changed to "%s".', $new_status ) );

		wp_send_json_success( [
			'new_status' => $new_status,
			'label'      => get_post_status_object( $new_status )->label ?? $new_status,
		] );
	}

	public function display_post_states( array $states, \WP_Post $post ): array {
		if ( $post->post_type !== 'aigis_policy' ) {
			return $states;
		}
		$custom = [ 'aigis-in-review' => __( 'In Review', 'ai-governance-suite' ), 'aigis-approved' => __( 'Approved', 'ai-governance-suite' ), 'aigis-retired' => __( 'Retired', 'ai-governance-suite' ) ];
		$status = get_post_status( $post );
		if ( isset( $custom[ $status ] ) ) {
			$states[ $status ] = $custom[ $status ];
		}
		return $states;
	}
}
