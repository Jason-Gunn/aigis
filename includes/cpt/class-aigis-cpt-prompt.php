<?php
/**
 * CPT: aigis_prompt — Prompt Library.
 *
 * Registers the post type, taxonomy, custom statuses, and metaboxes
 * for the Prompt Library feature.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_CPT_Prompt {

	public function register( AIGIS_Loader $loader ): void {
		$loader->add_action( 'init', $this, 'register_post_type' );
		$loader->add_action( 'init', $this, 'register_taxonomy' );
		$loader->add_action( 'init', $this, 'register_post_statuses' );
		$loader->add_action( 'add_meta_boxes', $this, 'add_metaboxes' );
		$loader->add_action( 'save_post_aigis_prompt', $this, 'save_metaboxes', 10, 2 );
		$loader->add_action( 'post_submitbox_misc_actions', $this, 'inject_status_ui' );
		$loader->add_filter( 'display_post_states', $this, 'display_post_states', 10, 2 );
		$loader->add_action( 'wp_ajax_aigis_sandbox_test', $this, 'ajax_sandbox_test' );
		$loader->add_action( 'wp_ajax_aigis_promote_prompt', $this, 'ajax_promote_prompt' );
	}

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	public function register_post_type(): void {
		$labels = [
			'name'                  => _x( 'Prompts', 'post type general name', 'ai-governance-suite' ),
			'singular_name'         => _x( 'Prompt', 'post type singular name', 'ai-governance-suite' ),
			'add_new'               => __( 'Add New Prompt', 'ai-governance-suite' ),
			'add_new_item'          => __( 'Add New Prompt', 'ai-governance-suite' ),
			'edit_item'             => __( 'Edit Prompt', 'ai-governance-suite' ),
			'new_item'              => __( 'New Prompt', 'ai-governance-suite' ),
			'all_items'             => __( 'All Prompts', 'ai-governance-suite' ),
			'view_item'             => __( 'View Prompt', 'ai-governance-suite' ),
			'search_items'          => __( 'Search Prompts', 'ai-governance-suite' ),
			'not_found'             => __( 'No prompts found.', 'ai-governance-suite' ),
			'not_found_in_trash'    => __( 'No prompts found in Trash.', 'ai-governance-suite' ),
			'menu_name'             => __( 'Prompts', 'ai-governance-suite' ),
		];

		register_post_type( 'aigis_prompt', [
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'aigis-dashboard',
			'supports'            => [ 'title', 'editor', 'revisions', 'custom-fields', 'author' ],
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'capabilities'        => [
				'create_posts'   => AIGIS_Capabilities::MANAGE_PROMPTS,
				'edit_posts'     => AIGIS_Capabilities::MANAGE_PROMPTS,
				'delete_posts'   => AIGIS_Capabilities::MANAGE_PROMPTS,
				'publish_posts'  => AIGIS_Capabilities::APPROVE_PROMPTS,
			],
			'rewrite'             => false,
			'query_var'           => false,
			'has_archive'         => false,
			'hierarchical'        => false,
		] );
	}

	public function register_taxonomy(): void {
		$labels = [
			'name'          => _x( 'Prompt Tags', 'taxonomy general name', 'ai-governance-suite' ),
			'singular_name' => _x( 'Prompt Tag', 'taxonomy singular name', 'ai-governance-suite' ),
			'search_items'  => __( 'Search Tags', 'ai-governance-suite' ),
			'all_items'     => __( 'All Tags', 'ai-governance-suite' ),
			'edit_item'     => __( 'Edit Tag', 'ai-governance-suite' ),
			'update_item'   => __( 'Update Tag', 'ai-governance-suite' ),
			'add_new_item'  => __( 'Add New Tag', 'ai-governance-suite' ),
			'new_item_name' => __( 'New Tag Name', 'ai-governance-suite' ),
			'menu_name'     => __( 'Prompt Tags', 'ai-governance-suite' ),
		];

		register_taxonomy( 'aigis_prompt_tag', [ 'aigis_prompt' ], [
			'labels'            => $labels,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'hierarchical'      => false,
			'rewrite'           => false,
			'query_var'         => false,
		] );
	}

	public function register_post_statuses(): void {
		register_post_status( 'aigis-staging', [
			'label'                     => _x( 'Staging', 'post status', 'ai-governance-suite' ),
			'public'                    => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s = count of staging prompts */
			'label_count'               => _n_noop( 'Staging <span class="count">(%s)</span>', 'Staging <span class="count">(%s)</span>', 'ai-governance-suite' ),
		] );

		register_post_status( 'aigis-pending-review', [
			'label'                     => _x( 'Pending Review', 'post status', 'ai-governance-suite' ),
			'public'                    => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s = count */
			'label_count'               => _n_noop( 'Pending Review <span class="count">(%s)</span>', 'Pending Review <span class="count">(%s)</span>', 'ai-governance-suite' ),
		] );
	}

	// -----------------------------------------------------------------------
	// Metaboxes
	// -----------------------------------------------------------------------

	public function add_metaboxes(): void {
		add_meta_box(
			'aigis_prompt_settings',
			__( 'Prompt Settings', 'ai-governance-suite' ),
			[ $this, 'render_settings_metabox' ],
			'aigis_prompt',
			'normal',
			'high'
		);

		add_meta_box(
			'aigis_prompt_sandbox',
			__( 'Sandbox Test', 'ai-governance-suite' ),
			[ $this, 'render_sandbox_metabox' ],
			'aigis_prompt',
			'normal',
			'default'
		);

		add_meta_box(
			'aigis_prompt_promotion',
			__( 'Promotion Status', 'ai-governance-suite' ),
			[ $this, 'render_promotion_metabox' ],
			'aigis_prompt',
			'side',
			'default'
		);
	}

	public function render_settings_metabox( \WP_Post $post ): void {
		$model_id    = (string) get_post_meta( $post->ID, '_aigis_model_id', true );
		$department  = (string) get_post_meta( $post->ID, '_aigis_department', true );
		$max_tokens  = (int) get_post_meta( $post->ID, '_aigis_max_tokens', true );
		$temperature = (float) ( get_post_meta( $post->ID, '_aigis_temperature', true ) ?: 0.7 );

		$db_inventory = new AIGIS_DB_Inventory();
		$models       = $db_inventory->get_active_for_select();

		include AIGIS_PLUGIN_DIR . 'admin/views/prompts/metabox-settings.php';
	}

	public function render_sandbox_metabox( \WP_Post $post ): void {
		include AIGIS_PLUGIN_DIR . 'admin/views/prompts/metabox-sandbox.php';
	}

	public function render_promotion_metabox( \WP_Post $post ): void {
		$status   = get_post_status( $post->ID );
		$log      = get_post_meta( $post->ID, '_aigis_promotion_log', true ) ?: [];
		$can_approve = current_user_can( AIGIS_Capabilities::APPROVE_PROMPTS );

		include AIGIS_PLUGIN_DIR . 'admin/views/prompts/metabox-promotion.php';
	}

	public function save_metaboxes( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['aigis_prompt_settings_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_POST['aigis_prompt_settings_nonce'] ), 'aigis_prompt_settings' ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_POST['aigis_prompt_settings_nonce'] ), 'aigis_save_prompt_settings' ) ) {
			return;
		}
		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_PROMPTS ) ) {
			return;
		}

		update_post_meta( $post_id, '_aigis_model_id', absint( $_POST['aigis_model_id'] ?? 0 ) );
		update_post_meta( $post_id, '_aigis_department', sanitize_text_field( wp_unslash( $_POST['aigis_department'] ?? '' ) ) );
		update_post_meta( $post_id, '_aigis_max_tokens', absint( $_POST['aigis_max_tokens'] ?? 0 ) );
		update_post_meta( $post_id, '_aigis_temperature', (float) ( $_POST['aigis_temperature'] ?? 0.7 ) );

		// Audit the save.
		$audit = new AIGIS_DB_Audit();
		$audit->log(
			'prompt.saved',
			'prompt',
			(string) $post_id,
			sprintf( 'Prompt "%s" saved.', get_the_title( $post_id ) )
		);
	}

	// -----------------------------------------------------------------------
	// Submit-box injection for custom status transitions
	// -----------------------------------------------------------------------

	public function inject_status_ui( \WP_Post $post ): void {
		if ( $post->post_type !== 'aigis_prompt' ) {
			return;
		}
		$current_status = get_post_status( $post->ID );
		include AIGIS_PLUGIN_DIR . 'admin/views/prompts/submitbox-status.php';
	}

	public function display_post_states( array $states, \WP_Post $post ): array {
		if ( $post->post_type !== 'aigis_prompt' ) {
			return $states;
		}
		$status = get_post_status( $post );
		if ( $status === 'aigis-staging' ) {
			$states['aigis-staging'] = __( 'Staging', 'ai-governance-suite' );
		} elseif ( $status === 'aigis-pending-review' ) {
			$states['aigis-pending-review'] = __( 'Pending Review', 'ai-governance-suite' );
		}
		return $states;
	}

	// -----------------------------------------------------------------------
	// AJAX handlers
	// -----------------------------------------------------------------------

	public function ajax_sandbox_test(): void {
		check_ajax_referer( 'aigis_sandbox_test', 'nonce' );

		if ( ! current_user_can( AIGIS_Capabilities::USE_PROMPTS ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'ai-governance-suite' ) ], 403 );
		}

		$post_id   = absint( $_POST['post_id'] ?? 0 );
		$variables = is_array( $_POST['variables'] ?? null ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['variables'] ) ) : [];

		if ( ! $post_id || ! get_post( $post_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid prompt.', 'ai-governance-suite' ) ] );
		}

		$model_id  = (int) get_post_meta( $post_id, '_aigis_model_id', true );
		$prompt    = get_post_field( 'post_content', $post_id );

		// Replace {{variable}} tokens.
		foreach ( $variables as $key => $value ) {
			$prompt = str_replace( '{{' . $key . '}}', wp_kses_post( $value ), $prompt );
		}

		// PII check.
		$pii_blocking = (bool) get_post_meta( $post_id, '_aigis_pii_blocking', true );
		$detector     = new AIGIS_PII_Detector();
		if ( $pii_blocking && $detector->contains_pii( $prompt ) ) {
			wp_send_json_error( [
				'message'  => __( 'Prompt contains potential PII. Please remove it before testing.', 'ai-governance-suite' ),
				'pii_found' => true,
			] );
		}

		$provider = AIGIS_Provider_Abstract::make( $model_id );
		if ( ! $provider ) {
			wp_send_json_error( [ 'message' => __( 'No provider configured for this model.', 'ai-governance-suite' ) ] );
		}

		$result = $provider->send_prompt( $prompt, [] );
		wp_send_json_success( $result );
	}

	public function ajax_promote_prompt(): void {
		check_ajax_referer( 'aigis_promote_prompt', 'nonce' );

		$action_  = sanitize_key( $_POST['promotion_action'] ?? '' );
		$post_id  = absint( $_POST['post_id'] ?? 0 );
		$note     = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );

		if ( ! $post_id || ! get_post( $post_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid prompt.', 'ai-governance-suite' ) ] );
		}

		$allowed = [
			'request_review' => [ AIGIS_Capabilities::MANAGE_PROMPTS, 'aigis-pending-review' ],
			'approve'        => [ AIGIS_Capabilities::APPROVE_PROMPTS, 'publish' ],
			'reject'         => [ AIGIS_Capabilities::APPROVE_PROMPTS, 'aigis-staging' ],
		];

		if ( ! isset( $allowed[ $action_ ] ) ) {
			wp_send_json_error( [ 'message' => __( 'Unknown action.', 'ai-governance-suite' ) ] );
		}

		[ $required_cap, $new_status ] = $allowed[ $action_ ];

		if ( ! current_user_can( $required_cap ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'ai-governance-suite' ) ], 403 );
		}

		wp_update_post( [
			'ID'          => $post_id,
			'post_status' => $new_status,
		] );

		$log   = get_post_meta( $post_id, '_aigis_promotion_log', true ) ?: [];
		$log[] = [
			'action'  => $action_,
			'user_id' => get_current_user_id(),
			'note'    => $note,
			'at'      => current_time( 'c' ),
		];
		update_post_meta( $post_id, '_aigis_promotion_log', $log );

		$audit = new AIGIS_DB_Audit();
		$audit->log(
			'prompt.' . $action_,
			'prompt',
			(string) $post_id,
			sprintf( 'Prompt status changed to "%s" via promotion.', $new_status )
		);

		wp_send_json_success( [
			'new_status' => $new_status,
			'label'      => get_post_status_object( $new_status )->label ?? $new_status,
		] );
	}
}
