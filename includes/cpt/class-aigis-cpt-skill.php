<?php
/**
 * CPT: aigis_skill — Agent Skills repository.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_CPT_Skill {

	private const VALIDATION_META_KEY = '_aigis_skill_validation';
	private const EXPORT_META_KEY     = '_aigis_skill_markdown_export';
	private const STATUS_LOG_META_KEY = '_aigis_skill_status_log';
	private const NOTICE_TRANSIENT_KEY = 'aigis_skill_notice_';

	public function register( AIGIS_Loader $loader ): void {
		$loader->add_action( 'init', $this, 'register_post_type' );
		$loader->add_action( 'init', $this, 'register_taxonomy' );
		$loader->add_action( 'init', $this, 'register_post_statuses' );
		$loader->add_action( 'admin_post_aigis_download_skill_markdown', $this, 'handle_download_markdown' );
		$loader->add_action( 'wp_ajax_aigis_change_skill_status', $this, 'ajax_change_skill_status' );
		$loader->add_action( 'pre_get_posts', $this, 'prepare_admin_list_query' );
		$loader->add_action( 'add_meta_boxes', $this, 'add_metaboxes' );
		$loader->add_action( 'admin_notices', $this, 'render_admin_notices' );
		$loader->add_action( 'restrict_manage_posts', $this, 'render_admin_filters' );
		$loader->add_action( 'save_post_aigis_skill', $this, 'save_metaboxes', 10, 2 );
		$loader->add_filter( 'display_post_states', $this, 'display_post_states', 10, 2 );
		$loader->add_filter( 'manage_edit-aigis_skill_columns', $this, 'register_admin_columns' );
		$loader->add_action( 'manage_aigis_skill_posts_custom_column', $this, 'render_admin_column', 10, 2 );
		$loader->add_filter( 'manage_edit-aigis_skill_sortable_columns', $this, 'register_sortable_columns' );
		$loader->add_filter( 'post_row_actions', $this, 'add_row_actions', 10, 2 );
		$loader->add_filter( 'use_block_editor_for_post_type', $this, 'disable_block_editor', 10, 2 );
	}

	public function disable_block_editor( bool $use_block_editor, string $post_type ): bool {
		return 'aigis_skill' === $post_type ? false : $use_block_editor;
	}

	public function register_post_type(): void {
		$labels = [
			'name'               => _x( 'Agent Skills', 'post type general name', 'ai-governance-suite' ),
			'singular_name'      => _x( 'Agent Skill', 'post type singular name', 'ai-governance-suite' ),
			'add_new'            => __( 'Add New Skill', 'ai-governance-suite' ),
			'add_new_item'       => __( 'Add New Skill', 'ai-governance-suite' ),
			'edit_item'          => __( 'Edit Skill', 'ai-governance-suite' ),
			'all_items'          => __( 'Agent Skills', 'ai-governance-suite' ),
			'view_item'          => __( 'View Skill', 'ai-governance-suite' ),
			'search_items'       => __( 'Search Skills', 'ai-governance-suite' ),
			'not_found'          => __( 'No skills found.', 'ai-governance-suite' ),
			'not_found_in_trash' => __( 'No skills found in Trash.', 'ai-governance-suite' ),
			'menu_name'          => __( 'Agent Skills', 'ai-governance-suite' ),
		];

		register_post_type( 'aigis_skill', [
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'aigis-dashboard',
			'supports'        => [ 'title', 'editor', 'revisions', 'author', 'custom-fields' ],
			'capability_type' => 'post',
			'map_meta_cap'    => false,
			'capabilities'    => [
				'create_posts'           => AIGIS_Capabilities::MANAGE_SKILLS,
				'edit_post'              => AIGIS_Capabilities::MANAGE_SKILLS,
				'edit_posts'             => AIGIS_Capabilities::MANAGE_SKILLS,
				'edit_others_posts'      => AIGIS_Capabilities::MANAGE_SKILLS,
				'edit_private_posts'     => AIGIS_Capabilities::MANAGE_SKILLS,
				'edit_published_posts'   => AIGIS_Capabilities::MANAGE_SKILLS,
				'read_post'              => AIGIS_Capabilities::VIEW_SKILLS,
				'read_private_posts'     => AIGIS_Capabilities::VIEW_SKILLS,
				'delete_post'            => AIGIS_Capabilities::MANAGE_SKILLS,
				'delete_posts'           => AIGIS_Capabilities::MANAGE_SKILLS,
				'delete_private_posts'   => AIGIS_Capabilities::MANAGE_SKILLS,
				'delete_published_posts' => AIGIS_Capabilities::MANAGE_SKILLS,
				'delete_others_posts'    => AIGIS_Capabilities::MANAGE_SKILLS,
				'publish_posts'          => AIGIS_Capabilities::APPROVE_SKILLS,
			],
			'rewrite'         => false,
			'query_var'       => false,
			'has_archive'     => false,
		] );
	}

	public function register_taxonomy(): void {
		$labels = [
			'name'          => _x( 'Skill Tags', 'taxonomy general name', 'ai-governance-suite' ),
			'singular_name' => _x( 'Skill Tag', 'taxonomy singular name', 'ai-governance-suite' ),
			'search_items'  => __( 'Search Skill Tags', 'ai-governance-suite' ),
			'all_items'     => __( 'All Skill Tags', 'ai-governance-suite' ),
			'edit_item'     => __( 'Edit Skill Tag', 'ai-governance-suite' ),
			'update_item'   => __( 'Update Skill Tag', 'ai-governance-suite' ),
			'add_new_item'  => __( 'Add New Skill Tag', 'ai-governance-suite' ),
			'new_item_name' => __( 'New Skill Tag Name', 'ai-governance-suite' ),
			'menu_name'     => __( 'Skill Tags', 'ai-governance-suite' ),
		];

		register_taxonomy( 'aigis_skill_tag', [ 'aigis_skill' ], [
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
		$statuses = [
			'aigis-pending-review' => [
				'label' => _x( 'Pending Review', 'skill status', 'ai-governance-suite' ),
				'noop'  => _n_noop( 'Pending Review <span class="count">(%s)</span>', 'Pending Review <span class="count">(%s)</span>', 'ai-governance-suite' ),
			],
			'aigis-staging' => [
				'label' => _x( 'Staging', 'skill status', 'ai-governance-suite' ),
				'noop'  => _n_noop( 'Staging <span class="count">(%s)</span>', 'Staging <span class="count">(%s)</span>', 'ai-governance-suite' ),
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

	public function add_metaboxes(): void {
		add_meta_box(
			'aigis_skill_specification',
			__( 'Skill Specification', 'ai-governance-suite' ),
			[ $this, 'render_specification_metabox' ],
			'aigis_skill',
			'normal',
			'high'
		);

		add_meta_box(
			'aigis_skill_relationships',
			__( 'Relationships', 'ai-governance-suite' ),
			[ $this, 'render_relationships_metabox' ],
			'aigis_skill',
			'normal',
			'default'
		);

		add_meta_box(
			'aigis_skill_review_actions',
			__( 'Review Actions', 'ai-governance-suite' ),
			[ $this, 'render_review_actions_metabox' ],
			'aigis_skill',
			'side',
			'high'
		);

		add_meta_box(
			'aigis_skill_validation',
			__( 'Validation & Readiness', 'ai-governance-suite' ),
			[ $this, 'render_validation_metabox' ],
			'aigis_skill',
			'side',
			'default'
		);

		add_meta_box(
			'aigis_skill_export',
			__( 'Markdown Export Preview', 'ai-governance-suite' ),
			[ $this, 'render_export_metabox' ],
			'aigis_skill',
			'side',
			'default'
		);

		add_meta_box(
			'aigis_skill_import',
			__( 'Markdown Import', 'ai-governance-suite' ),
			[ $this, 'render_import_metabox' ],
			'aigis_skill',
			'side',
			'default'
		);

		add_meta_box(
			'aigis_skill_status_history',
			__( 'Status History', 'ai-governance-suite' ),
			[ $this, 'render_status_history_metabox' ],
			'aigis_skill',
			'side',
			'default'
		);
	}

	public function render_specification_metabox( \WP_Post $post ): void {
		$description       = (string) get_post_meta( $post->ID, '_aigis_skill_description', true );
		$tier              = (string) get_post_meta( $post->ID, '_aigis_skill_tier', true );
		$version           = (string) get_post_meta( $post->ID, '_aigis_skill_version', true );
		$trigger_phrases   = (string) get_post_meta( $post->ID, '_aigis_skill_trigger_phrases', true );
		$output_contract   = (string) get_post_meta( $post->ID, '_aigis_skill_output_contract', true );
		$edge_cases        = (string) get_post_meta( $post->ID, '_aigis_skill_edge_cases', true );
		$examples          = (string) get_post_meta( $post->ID, '_aigis_skill_examples', true );
		$format            = (string) get_post_meta( $post->ID, '_aigis_skill_format', true );
		$lifecycle_status  = get_post_status( $post );
		$can_approve       = current_user_can( AIGIS_Capabilities::APPROVE_SKILLS );

		include AIGIS_PLUGIN_DIR . 'admin/views/skills/metabox-specification.php';
	}

	public function render_relationships_metabox( \WP_Post $post ): void {
		$inventory_id   = (int) get_post_meta( $post->ID, '_aigis_linked_inventory_id', true );
		$linked_prompts = get_post_meta( $post->ID, '_aigis_linked_prompt_ids', true );
		$linked_flows   = get_post_meta( $post->ID, '_aigis_linked_workflow_ids', true );
		$linked_policies  = get_post_meta( $post->ID, '_aigis_linked_policy_ids', true );
		$linked_incidents = get_post_meta( $post->ID, '_aigis_linked_incident_ids', true );
		$team           = (string) get_post_meta( $post->ID, '_aigis_skill_team', true );

		$linked_prompts = is_array( $linked_prompts ) ? array_map( 'absint', $linked_prompts ) : [];
		$linked_flows   = is_array( $linked_flows ) ? array_map( 'absint', $linked_flows ) : [];
		$linked_policies  = is_array( $linked_policies ) ? array_map( 'absint', $linked_policies ) : [];
		$linked_incidents = is_array( $linked_incidents ) ? array_map( 'absint', $linked_incidents ) : [];

		$models    = ( new AIGIS_DB_Inventory() )->get_active_for_select();
		$prompts   = get_posts( [ 'post_type' => 'aigis_prompt', 'post_status' => [ 'draft', 'publish', 'aigis-staging', 'aigis-pending-review' ], 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
		$workflows = get_posts( [ 'post_type' => 'aigis_workflow', 'post_status' => [ 'draft', 'publish', 'aigis-staging' ], 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
		$policies  = get_posts( [ 'post_type' => 'aigis_policy', 'post_status' => [ 'draft', 'publish', 'aigis-in-review', 'aigis-approved', 'aigis-retired' ], 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
		$incidents = get_posts( [ 'post_type' => 'aigis_incident', 'post_status' => [ 'draft', 'publish', 'aigis-open', 'aigis-investigating', 'aigis-resolved' ], 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC' ] );

		include AIGIS_PLUGIN_DIR . 'admin/views/skills/metabox-relationships.php';
	}

	public function render_validation_metabox( \WP_Post $post ): void {
		$validation = $this->get_validation_results( $post->ID, $post );
		include AIGIS_PLUGIN_DIR . 'admin/views/skills/metabox-validation.php';
	}

	public function render_review_actions_metabox( \WP_Post $post ): void {
		$current_status = get_post_status( $post );
		$can_manage     = current_user_can( AIGIS_Capabilities::MANAGE_SKILLS );
		$can_approve    = current_user_can( AIGIS_Capabilities::APPROVE_SKILLS );

		include AIGIS_PLUGIN_DIR . 'admin/views/skills/metabox-review-actions.php';
	}

	public function render_export_metabox( \WP_Post $post ): void {
		$export_markdown = (string) get_post_meta( $post->ID, self::EXPORT_META_KEY, true );
		if ( '' === $export_markdown ) {
			$export_markdown = $this->build_markdown_export( $post->ID, $post );
		}
		$download_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=aigis_download_skill_markdown&post_id=' . $post->ID ),
			'aigis_download_skill_markdown_' . $post->ID
		);

		include AIGIS_PLUGIN_DIR . 'admin/views/skills/metabox-export.php';
	}

	public function render_import_metabox( \WP_Post $post ): void {
		include AIGIS_PLUGIN_DIR . 'admin/views/skills/metabox-import.php';
	}

	public function render_status_history_metabox( \WP_Post $post ): void {
		$status_log = get_post_meta( $post->ID, self::STATUS_LOG_META_KEY, true );
		$status_log = is_array( $status_log ) ? $status_log : [];

		include AIGIS_PLUGIN_DIR . 'admin/views/skills/metabox-status-history.php';
	}

	public function save_metaboxes( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['aigis_skill_spec_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_POST['aigis_skill_spec_nonce'] ), 'aigis_save_skill' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_SKILLS ) ) {
			return;
		}

		$import_payload = null;
		if ( ! empty( $_POST['aigis_skill_import_apply'] ) ) {
			$import_markdown = (string) wp_unslash( $_POST['aigis_skill_import_markdown'] ?? '' );
			if ( '' !== trim( $import_markdown ) ) {
				$import_payload = $this->parse_markdown_import( $import_markdown );
				if ( is_wp_error( $import_payload ) ) {
					$this->queue_admin_notice( $import_payload->get_error_message(), 'error' );
					$import_payload = null;
				} else {
					$this->apply_imported_post_content( $post_id, $import_payload );
					$post = get_post( $post_id );
					if ( ! $post instanceof \WP_Post ) {
						return;
					}
					$this->queue_admin_notice( __( 'Markdown import applied to this skill.', 'ai-governance-suite' ) );
				}
			}
		}

		$description      = $import_payload ? $this->normalize_single_line( (string) ( $import_payload['description'] ?? '' ) ) : $this->normalize_single_line( (string) wp_unslash( $_POST['aigis_skill_description'] ?? '' ) );
		$tier             = $import_payload ? sanitize_text_field( (string) ( $import_payload['tier'] ?? 'methodology' ) ) : sanitize_text_field( wp_unslash( $_POST['aigis_skill_tier'] ?? 'methodology' ) );
		$version          = $import_payload ? sanitize_text_field( (string) ( $import_payload['version'] ?? '0.1.0' ) ) : sanitize_text_field( wp_unslash( $_POST['aigis_skill_version'] ?? '0.1.0' ) );
		$trigger_phrases  = $import_payload ? sanitize_textarea_field( (string) ( $import_payload['trigger_phrases'] ?? '' ) ) : sanitize_textarea_field( wp_unslash( $_POST['aigis_skill_trigger_phrases'] ?? '' ) );
		$output_contract  = $import_payload ? sanitize_textarea_field( (string) ( $import_payload['output_contract'] ?? '' ) ) : sanitize_textarea_field( wp_unslash( $_POST['aigis_skill_output_contract'] ?? '' ) );
		$edge_cases       = $import_payload ? sanitize_textarea_field( (string) ( $import_payload['edge_cases'] ?? '' ) ) : sanitize_textarea_field( wp_unslash( $_POST['aigis_skill_edge_cases'] ?? '' ) );
		$examples         = $import_payload ? sanitize_textarea_field( (string) ( $import_payload['examples'] ?? '' ) ) : sanitize_textarea_field( wp_unslash( $_POST['aigis_skill_examples'] ?? '' ) );
		$format           = $import_payload ? sanitize_text_field( (string) ( $import_payload['format'] ?? 'markdown' ) ) : sanitize_text_field( wp_unslash( $_POST['aigis_skill_format'] ?? 'markdown' ) );
		$inventory_id     = absint( $_POST['aigis_linked_inventory_id'] ?? 0 );
		$team             = $import_payload ? sanitize_text_field( (string) ( $import_payload['team'] ?? '' ) ) : sanitize_text_field( wp_unslash( $_POST['aigis_skill_team'] ?? '' ) );
		$linked_prompts   = $import_payload ? $this->resolve_related_asset_titles( (array) ( $import_payload['related_assets']['prompts'] ?? [] ), 'aigis_prompt' ) : ( isset( $_POST['aigis_linked_prompt_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['aigis_linked_prompt_ids'] ) ) : [] );
		$linked_workflows = $import_payload ? $this->resolve_related_asset_titles( (array) ( $import_payload['related_assets']['workflows'] ?? [] ), 'aigis_workflow' ) : ( isset( $_POST['aigis_linked_workflow_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['aigis_linked_workflow_ids'] ) ) : [] );
		$linked_policies  = $import_payload ? $this->resolve_related_asset_titles( (array) ( $import_payload['related_assets']['policies'] ?? [] ), 'aigis_policy' ) : ( isset( $_POST['aigis_linked_policy_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['aigis_linked_policy_ids'] ) ) : [] );
		$linked_incidents = $import_payload ? $this->resolve_related_asset_titles( (array) ( $import_payload['related_assets']['incidents'] ?? [] ), 'aigis_incident' ) : ( isset( $_POST['aigis_linked_incident_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['aigis_linked_incident_ids'] ) ) : [] );
		$lifecycle_status = sanitize_key( wp_unslash( $_POST['aigis_skill_lifecycle_status'] ?? get_post_status( $post_id ) ) );
		$previous_status  = get_post_status( $post_id );
		$status_note      = sanitize_textarea_field( wp_unslash( $_POST['aigis_skill_status_note'] ?? '' ) );

		update_post_meta( $post_id, '_aigis_skill_description', $description );
		update_post_meta( $post_id, '_aigis_skill_tier', $tier );
		update_post_meta( $post_id, '_aigis_skill_version', $version );
		update_post_meta( $post_id, '_aigis_skill_trigger_phrases', $trigger_phrases );
		update_post_meta( $post_id, '_aigis_skill_output_contract', $output_contract );
		update_post_meta( $post_id, '_aigis_skill_edge_cases', $edge_cases );
		update_post_meta( $post_id, '_aigis_skill_examples', $examples );
		update_post_meta( $post_id, '_aigis_skill_format', $format );
		update_post_meta( $post_id, '_aigis_linked_inventory_id', $inventory_id );
		update_post_meta( $post_id, '_aigis_skill_team', $team );
		update_post_meta( $post_id, '_aigis_linked_prompt_ids', array_values( array_filter( $linked_prompts ) ) );
		update_post_meta( $post_id, '_aigis_linked_workflow_ids', array_values( array_filter( $linked_workflows ) ) );
		update_post_meta( $post_id, '_aigis_linked_policy_ids', array_values( array_filter( $linked_policies ) ) );
		update_post_meta( $post_id, '_aigis_linked_incident_ids', array_values( array_filter( $linked_incidents ) ) );

		$validation = $this->build_validation_results( $post_id, $post );
		update_post_meta( $post_id, self::VALIDATION_META_KEY, $validation );
		update_post_meta( $post_id, '_aigis_skill_readiness_score', (int) $validation['score'] );
		update_post_meta( $post_id, '_aigis_skill_production_ready', ! empty( $validation['production_ready'] ) ? '1' : '0' );

		$export_markdown = $this->build_markdown_export( $post_id, $post );
		update_post_meta( $post_id, self::EXPORT_META_KEY, $export_markdown );

		$allowed_statuses = [ 'draft', 'aigis-pending-review', 'aigis-staging', 'publish' ];
		if ( 'publish' === $lifecycle_status && ! current_user_can( AIGIS_Capabilities::APPROVE_SKILLS ) ) {
			$lifecycle_status = get_post_status( $post_id );
		}
		if ( in_array( $lifecycle_status, $allowed_statuses, true ) && $lifecycle_status !== get_post_status( $post_id ) ) {
			remove_action( 'save_post_aigis_skill', [ $this, 'save_metaboxes' ], 10 );
			wp_update_post( [ 'ID' => $post_id, 'post_status' => $lifecycle_status ] );
			add_action( 'save_post_aigis_skill', [ $this, 'save_metaboxes' ], 10, 2 );
		}

		if ( $previous_status !== $lifecycle_status ) {
			$this->record_status_transition( $post_id, $previous_status, $lifecycle_status, $status_note );
		}

		( new AIGIS_DB_Audit() )->log(
			$import_payload ? 'skill.imported' : 'skill.saved',
			'skill',
			(string) $post_id,
			$import_payload ? sprintf( 'Skill "%s" imported from markdown.', get_the_title( $post_id ) ) : sprintf( 'Skill "%s" saved.', get_the_title( $post_id ) ),
			[],
			[
				'description' => $description,
				'tier'        => $tier,
				'version'     => $version,
				'status'      => $lifecycle_status,
				'imported'    => ! empty( $import_payload ),
			]
		);
	}

	public function render_admin_notices(): void {
		if ( ! is_admin() || ! current_user_can( AIGIS_Capabilities::MANAGE_SKILLS ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'aigis_skill' !== $screen->post_type ) {
			return;
		}

		$notice = get_transient( self::NOTICE_TRANSIENT_KEY . get_current_user_id() );
		if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
			return;
		}

		delete_transient( self::NOTICE_TRANSIENT_KEY . get_current_user_id() );
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $notice['type'] ?? 'success' ),
			esc_html( (string) $notice['message'] )
		);
	}

	public function display_post_states( array $states, \WP_Post $post ): array {
		if ( 'aigis_skill' !== $post->post_type ) {
			return $states;
		}

		$status = get_post_status( $post );
		if ( 'aigis-staging' === $status ) {
			$states['aigis-staging'] = __( 'Staging', 'ai-governance-suite' );
		} elseif ( 'aigis-pending-review' === $status ) {
			$states['aigis-pending-review'] = __( 'Pending Review', 'ai-governance-suite' );
		}

		return $states;
	}

	public function register_admin_columns( array $columns ): array {
		return [
			'cb'         => $columns['cb'] ?? '<input type="checkbox" />',
			'title'      => __( 'Skill', 'ai-governance-suite' ),
			'tier'       => __( 'Tier', 'ai-governance-suite' ),
			'readiness'  => __( 'Readiness', 'ai-governance-suite' ),
			'system'     => __( 'Linked AI System', 'ai-governance-suite' ),
			'version'    => __( 'Version', 'ai-governance-suite' ),
			'author'     => $columns['author'] ?? __( 'Author' ),
			'date'       => $columns['date'] ?? __( 'Date' ),
		];
	}

	public function render_admin_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'tier':
				$tier = (string) get_post_meta( $post_id, '_aigis_skill_tier', true );
				echo esc_html( $tier !== '' ? ucfirst( $tier ) : '—' );
				break;

			case 'readiness':
				$score = (int) get_post_meta( $post_id, '_aigis_skill_readiness_score', true );
				$ready = get_post_meta( $post_id, '_aigis_skill_production_ready', true ) === '1';
				echo esc_html( $score . '/100' . ( $ready ? ' • Ready' : '' ) );
				break;

			case 'system':
				$inventory_id = (int) get_post_meta( $post_id, '_aigis_linked_inventory_id', true );
				if ( $inventory_id > 0 ) {
					$record = ( new AIGIS_DB_Inventory() )->get( $inventory_id );
					if ( is_object( $record ) ) {
						echo esc_html( trim( (string) $record->model_name . ' (' . (string) $record->vendor_name . ')' ) );
						break;
					}
				}
				echo esc_html( '—' );
				break;

			case 'version':
				$version = (string) get_post_meta( $post_id, '_aigis_skill_version', true );
				echo esc_html( $version !== '' ? $version : '—' );
				break;
		}
	}

	public function register_sortable_columns( array $columns ): array {
		$columns['tier']      = 'tier';
		$columns['readiness'] = 'readiness';
		$columns['version']   = 'version';

		return $columns;
	}

	public function add_row_actions( array $actions, \WP_Post $post ): array {
		if ( 'aigis_skill' !== $post->post_type || ! current_user_can( AIGIS_Capabilities::VIEW_SKILLS ) ) {
			return $actions;
		}

		$actions['aigis_download_skill'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=aigis_download_skill_markdown&post_id=' . $post->ID ), 'aigis_download_skill_markdown_' . $post->ID ) ),
			esc_html__( 'Download Markdown', 'ai-governance-suite' )
		);

		return $actions;
	}

	public function render_admin_filters(): void {
		global $typenow;

		if ( 'aigis_skill' !== $typenow ) {
			return;
		}

		$current_tier      = sanitize_text_field( wp_unslash( $_GET['aigis_skill_tier'] ?? '' ) );
		$current_ready     = sanitize_text_field( wp_unslash( $_GET['aigis_skill_ready'] ?? '' ) );
		$current_inventory = absint( $_GET['aigis_skill_inventory'] ?? 0 );

		?>
		<select name="aigis_skill_tier">
			<option value=""><?php esc_html_e( 'All Tiers', 'ai-governance-suite' ); ?></option>
			<?php foreach ( [ 'standard', 'methodology', 'personal' ] as $tier ) : ?>
				<option value="<?php echo esc_attr( $tier ); ?>" <?php selected( $current_tier, $tier ); ?>><?php echo esc_html( ucfirst( $tier ) ); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="aigis_skill_ready">
			<option value=""><?php esc_html_e( 'All Readiness States', 'ai-governance-suite' ); ?></option>
			<option value="1" <?php selected( $current_ready, '1' ); ?>><?php esc_html_e( 'Production Ready', 'ai-governance-suite' ); ?></option>
			<option value="0" <?php selected( $current_ready, '0' ); ?>><?php esc_html_e( 'Needs Work', 'ai-governance-suite' ); ?></option>
		</select>

		<select name="aigis_skill_inventory">
			<option value=""><?php esc_html_e( 'All Linked Systems', 'ai-governance-suite' ); ?></option>
			<?php foreach ( ( new AIGIS_DB_Inventory() )->get_active_for_select() as $model ) : ?>
				<option value="<?php echo esc_attr( $model['id'] ); ?>" <?php selected( $current_inventory, (int) $model['id'] ); ?>>
					<?php echo esc_html( $model['model_name'] . ' (' . $model['vendor_name'] . ')' ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function prepare_admin_list_query( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'aigis_skill' !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( $query->get( 'post_status' ) === '' || null === $query->get( 'post_status' ) ) {
			$query->set( 'post_status', [ 'draft', 'publish', 'aigis-pending-review', 'aigis-staging' ] );
		}

		$meta_query = $query->get( 'meta_query' );
		$meta_query = is_array( $meta_query ) ? $meta_query : [];

		$tier = sanitize_text_field( wp_unslash( $_GET['aigis_skill_tier'] ?? '' ) );
		if ( $tier !== '' ) {
			$meta_query[] = [
				'key'   => '_aigis_skill_tier',
				'value' => $tier,
			];
		}

		$ready = sanitize_text_field( wp_unslash( $_GET['aigis_skill_ready'] ?? '' ) );
		if ( $ready === '0' || $ready === '1' ) {
			$meta_query[] = [
				'key'   => '_aigis_skill_production_ready',
				'value' => $ready,
			];
		}

		$inventory_id = absint( $_GET['aigis_skill_inventory'] ?? 0 );
		if ( $inventory_id > 0 ) {
			$meta_query[] = [
				'key'   => '_aigis_linked_inventory_id',
				'value' => $inventory_id,
			];
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}

		$orderby = $query->get( 'orderby' );
		if ( 'tier' === $orderby ) {
			$query->set( 'meta_key', '_aigis_skill_tier' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( 'readiness' === $orderby ) {
			$query->set( 'meta_key', '_aigis_skill_readiness_score' );
			$query->set( 'orderby', 'meta_value_num' );
		} elseif ( 'version' === $orderby ) {
			$query->set( 'meta_key', '_aigis_skill_version' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	public function handle_download_markdown(): void {
		$post_id = absint( $_GET['post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			wp_die( esc_html__( 'Invalid skill.', 'ai-governance-suite' ) );
		}
		if ( ! current_user_can( AIGIS_Capabilities::VIEW_SKILLS ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ai-governance-suite' ) );
		}
		check_admin_referer( 'aigis_download_skill_markdown_' . $post_id );

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || 'aigis_skill' !== $post->post_type ) {
			wp_die( esc_html__( 'Skill not found.', 'ai-governance-suite' ) );
		}

		$markdown = (string) get_post_meta( $post_id, self::EXPORT_META_KEY, true );
		if ( '' === $markdown ) {
			$markdown = $this->build_markdown_export( $post_id, $post );
		}

		( new AIGIS_DB_Audit() )->log(
			'skill.exported',
			'skill',
			(string) $post_id,
			sprintf( 'Skill "%s" markdown exported.', get_the_title( $post_id ) )
		);

		$filename = sanitize_title( $post->post_title ?: 'aigis-skill' ) . '.md';
		nocache_headers();
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	public function ajax_change_skill_status(): void {
		check_ajax_referer( 'aigis_change_skill_status', 'nonce' );

		$post_id    = absint( $_POST['post_id'] ?? 0 );
		$new_status = sanitize_key( $_POST['new_status'] ?? '' );
		$note       = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );
		$post       = get_post( $post_id );

		if ( ! $post_id || ! $post instanceof \WP_Post || 'aigis_skill' !== $post->post_type ) {
			wp_send_json_error( __( 'Invalid skill.', 'ai-governance-suite' ) );
		}

		$allowed = [
			'aigis-staging'       => AIGIS_Capabilities::MANAGE_SKILLS,
			'aigis-pending-review'=> AIGIS_Capabilities::MANAGE_SKILLS,
			'publish'             => AIGIS_Capabilities::APPROVE_SKILLS,
		];

		if ( ! isset( $allowed[ $new_status ] ) ) {
			wp_send_json_error( __( 'Unknown status transition.', 'ai-governance-suite' ) );
		}

		if ( ! current_user_can( $allowed[ $new_status ] ) ) {
			wp_send_json_error( __( 'Permission denied.', 'ai-governance-suite' ), 403 );
		}

		$previous_status = get_post_status( $post_id );
		wp_update_post( [
			'ID'          => $post_id,
			'post_status' => $new_status,
		] );

		$this->record_status_transition( $post_id, $previous_status, $new_status, $note );

		wp_send_json_success( [
			'new_status' => $new_status,
			'label'      => get_post_status_object( $new_status )->label ?? $new_status,
		] );
	}

	private function queue_admin_notice( string $message, string $type = 'success' ): void {
		set_transient(
			self::NOTICE_TRANSIENT_KEY . get_current_user_id(),
			[
				'message' => $message,
				'type'    => $type,
			],
			60
		);
	}

	private function apply_imported_post_content( int $post_id, array $import_payload ): void {
		remove_action( 'save_post_aigis_skill', [ $this, 'save_metaboxes' ], 10 );
		wp_update_post(
			[
				'ID'           => $post_id,
				'post_title'   => sanitize_text_field( (string) ( $import_payload['title'] ?? '' ) ),
				'post_content' => wp_kses_post( (string) ( $import_payload['methodology'] ?? '' ) ),
			]
		);
		add_action( 'save_post_aigis_skill', [ $this, 'save_metaboxes' ], 10, 2 );
	}

	private function parse_markdown_import( string $markdown ): array|\WP_Error {
		$markdown = trim( $markdown );
		if ( '' === $markdown ) {
			return new \WP_Error( 'aigis_skill_import_empty', __( 'Paste markdown before applying an import.', 'ai-governance-suite' ) );
		}

		$frontmatter = [];
		$body        = $markdown;

		if ( preg_match( '/\A---\R(.*?)\R---\R?/s', $markdown, $matches ) ) {
			$body = (string) substr( $markdown, strlen( $matches[0] ) );
			foreach ( preg_split( '/\R/', trim( (string) $matches[1] ) ) as $line ) {
				if ( preg_match( '/^([A-Za-z0-9_-]+):\s*(.*)$/', trim( (string) $line ), $frontmatter_match ) ) {
					$frontmatter[ strtolower( $frontmatter_match[1] ) ] = trim( (string) $frontmatter_match[2] );
				}
			}
		}

		$sections = $this->extract_markdown_sections( $body );
		$title    = sanitize_text_field( (string) ( $frontmatter['name'] ?? '' ) );

		if ( '' === $title ) {
			return new \WP_Error( 'aigis_skill_import_missing_name', __( 'Imported markdown must contain a name in the frontmatter.', 'ai-governance-suite' ) );
		}

		return [
			'title'           => $title,
			'description'     => sanitize_text_field( (string) ( $frontmatter['description'] ?? '' ) ),
			'tier'            => sanitize_text_field( (string) ( $frontmatter['tier'] ?? 'methodology' ) ),
			'version'         => sanitize_text_field( (string) ( $frontmatter['version'] ?? '0.1.0' ) ),
			'format'          => sanitize_text_field( (string) ( $frontmatter['format'] ?? 'markdown' ) ),
			'team'            => sanitize_text_field( (string) ( $frontmatter['team'] ?? '' ) ),
			'trigger_phrases' => sanitize_textarea_field( (string) ( $sections['trigger phrases'] ?? '' ) ),
			'output_contract' => sanitize_textarea_field( (string) ( $sections['output contract'] ?? '' ) ),
			'edge_cases'      => sanitize_textarea_field( (string) ( $sections['edge cases'] ?? '' ) ),
			'examples'        => sanitize_textarea_field( (string) ( $sections['examples'] ?? '' ) ),
			'methodology'     => wp_kses_post( (string) ( $sections['methodology'] ?? $body ) ),
			'related_assets'  => $this->parse_related_assets_section( (string) ( $sections['related assets'] ?? '' ) ),
		];
	}

	private function extract_markdown_sections( string $body ): array {
		$sections = [];
		if ( ! preg_match_all( '/^##\s+(.+)$/m', $body, $matches, PREG_OFFSET_CAPTURE ) ) {
			return $sections;
		}

		$total = count( $matches[0] );
		for ( $index = 0; $index < $total; $index++ ) {
			$heading     = strtolower( trim( (string) $matches[1][ $index ][0] ) );
			$start       = (int) $matches[0][ $index ][1] + strlen( (string) $matches[0][ $index ][0] );
			$end         = $index + 1 < $total ? (int) $matches[0][ $index + 1 ][1] : strlen( $body );
			$section_raw = trim( (string) substr( $body, $start, $end - $start ) );
			$sections[ $heading ] = $section_raw;
		}

		return $sections;
	}

	private function parse_related_assets_section( string $section ): array {
		if ( '' === trim( $section ) ) {
			return [];
		}

		$assets = [];
		$current_group = '';
		foreach ( preg_split( '/\R/', $section ) as $line ) {
			$line = trim( (string) $line );
			if ( preg_match( '/^###\s+(.+)$/', $line, $heading_match ) ) {
				$current_group = strtolower( trim( (string) $heading_match[1] ) );
				$assets[ $current_group ] = [];
				continue;
			}

			if ( '' !== $current_group && preg_match( '/^-\s+(.+)$/', $line, $item_match ) ) {
				$assets[ $current_group ][] = sanitize_text_field( (string) $item_match[1] );
			}
		}

		return [
			'prompts'   => $assets['prompts'] ?? [],
			'workflows' => $assets['workflows'] ?? [],
			'policies'  => $assets['policies'] ?? [],
			'incidents' => $assets['incidents'] ?? [],
		];
	}

	private function resolve_related_asset_titles( array $titles, string $post_type ): array {
		if ( empty( $titles ) ) {
			return [];
		}

		$posts = get_posts(
			[
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$title_map = [];
		foreach ( $posts as $related_post ) {
			$title_map[ strtolower( trim( (string) get_the_title( $related_post ) ) ) ] = (int) $related_post->ID;
		}

		$resolved_ids = [];
		foreach ( $titles as $title ) {
			$key = strtolower( trim( (string) $title ) );
			if ( isset( $title_map[ $key ] ) ) {
				$resolved_ids[] = $title_map[ $key ];
			}
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $resolved_ids ) ) ) );
	}

	private function record_status_transition( int $post_id, string $from_status, string $to_status, string $note = '' ): void {
		$status_log   = get_post_meta( $post_id, self::STATUS_LOG_META_KEY, true );
		$status_log   = is_array( $status_log ) ? $status_log : [];
		$status_log[] = [
			'from'      => $from_status,
			'to'        => $to_status,
			'note'      => $note,
			'user_id'   => get_current_user_id(),
			'timestamp' => current_time( 'mysql', true ),
		];
		update_post_meta( $post_id, self::STATUS_LOG_META_KEY, $status_log );

		( new AIGIS_DB_Audit() )->log(
			'skill.status_changed',
			'skill',
			(string) $post_id,
			sprintf( 'Skill "%s" status changed from %s to %s.', get_the_title( $post_id ), $from_status ?: 'none', $to_status ),
			[
				'status' => $from_status,
				'note'   => $note,
			],
			[
				'status' => $to_status,
				'note'   => $note,
			]
		);
	}

	private function get_validation_results( int $post_id, ?\WP_Post $post = null ): array {
		$validation = get_post_meta( $post_id, self::VALIDATION_META_KEY, true );
		if ( is_array( $validation ) ) {
			return $validation;
		}

		$post = $post ?: get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return [ 'errors' => [], 'warnings' => [], 'score' => 0, 'production_ready' => false ];
		}

		return $this->build_validation_results( $post_id, $post );
	}

	private function build_validation_results( int $post_id, \WP_Post $post ): array {
		$description     = (string) get_post_meta( $post_id, '_aigis_skill_description', true );
		$trigger_phrases = (string) get_post_meta( $post_id, '_aigis_skill_trigger_phrases', true );
		$output_contract = (string) get_post_meta( $post_id, '_aigis_skill_output_contract', true );
		$edge_cases      = (string) get_post_meta( $post_id, '_aigis_skill_edge_cases', true );
		$body            = trim( (string) $post->post_content );

		$errors   = [];
		$warnings = [];

		if ( '' === $description ) {
			$errors[] = __( 'Description is required.', 'ai-governance-suite' );
		}
		if ( preg_match( '/\r|\n/', $description ) ) {
			$errors[] = __( 'Description must stay on a single line for reliable triggering.', 'ai-governance-suite' );
		}
		if ( '' === $body ) {
			$errors[] = __( 'The main editor body is empty. Add the methodology and instructions there.', 'ai-governance-suite' );
		}
		if ( str_word_count( $description ) < 4 || strlen( $description ) < 20 ) {
			$warnings[] = __( 'Description is probably too vague. Include concrete trigger phrases and artifact intent.', 'ai-governance-suite' );
		}
		if ( '' === trim( $trigger_phrases ) ) {
			$warnings[] = __( 'Trigger phrases are empty. Agent routing will be less reliable without them.', 'ai-governance-suite' );
		}
		if ( '' === trim( $output_contract ) ) {
			$warnings[] = __( 'Output contract is missing. Specify the expected format and required sections.', 'ai-governance-suite' );
		}
		if ( '' === trim( $edge_cases ) ) {
			$warnings[] = __( 'Edge cases are missing. Capture the exceptions humans would normally handle implicitly.', 'ai-governance-suite' );
		}
		if ( $body !== '' && substr_count( $body, "\n" ) > 150 ) {
			$warnings[] = __( 'Instructions are getting long. Keep the core skill lean and move examples into supporting sections when possible.', 'ai-governance-suite' );
		}
		if ( $output_contract !== '' && ! preg_match( '/markdown|json|table|csv|excel|pdf|html|sections|fields|schema/i', $output_contract ) ) {
			$warnings[] = __( 'Output contract may be too loose. Name the format, fields, or sections explicitly.', 'ai-governance-suite' );
		}

		$score = max( 0, 100 - ( count( $errors ) * 30 ) - ( count( $warnings ) * 10 ) );

		return [
			'errors'           => $errors,
			'warnings'         => $warnings,
			'score'            => $score,
			'production_ready' => empty( $errors ) && count( $warnings ) <= 1,
		];
	}

	private function build_markdown_export( int $post_id, \WP_Post $post ): string {
		$title           = $post->post_title ?: __( 'Untitled Skill', 'ai-governance-suite' );
		$description     = $this->normalize_single_line( (string) get_post_meta( $post_id, '_aigis_skill_description', true ) );
		$tier            = (string) get_post_meta( $post_id, '_aigis_skill_tier', true );
		$version         = (string) get_post_meta( $post_id, '_aigis_skill_version', true );
		$trigger_phrases = trim( (string) get_post_meta( $post_id, '_aigis_skill_trigger_phrases', true ) );
		$output_contract = trim( (string) get_post_meta( $post_id, '_aigis_skill_output_contract', true ) );
		$edge_cases      = trim( (string) get_post_meta( $post_id, '_aigis_skill_edge_cases', true ) );
		$examples        = trim( (string) get_post_meta( $post_id, '_aigis_skill_examples', true ) );
		$format          = (string) get_post_meta( $post_id, '_aigis_skill_format', true );
		$team            = trim( (string) get_post_meta( $post_id, '_aigis_skill_team', true ) );
		$body            = trim( (string) $post->post_content );
		$related_assets  = $this->get_related_asset_map( $post_id );

		$parts   = [];
		$parts[] = '---';
		$parts[] = 'name: ' . $title;
		$parts[] = 'description: ' . $description;
		$parts[] = 'tier: ' . ( $tier ?: 'methodology' );
		$parts[] = 'version: ' . ( $version ?: '0.1.0' );
		$parts[] = 'format: ' . ( $format ?: 'markdown' );
		if ( '' !== $team ) {
			$parts[] = 'team: ' . $team;
		}
		$parts[] = '---';
		$parts[] = '';

		if ( ! empty( $related_assets ) ) {
			$parts[] = '## Related Assets';
			$parts[] = '';

			foreach ( $related_assets as $label => $items ) {
				if ( empty( $items ) ) {
					continue;
				}

				$parts[] = '### ' . $label;
				$parts[] = '';
				foreach ( $items as $item ) {
					$parts[] = '- ' . $item;
				}
				$parts[] = '';
			}
		}

		if ( $trigger_phrases !== '' ) {
			$parts[] = '## Trigger Phrases';
			$parts[] = '';
			$parts[] = $trigger_phrases;
			$parts[] = '';
		}

		if ( $output_contract !== '' ) {
			$parts[] = '## Output Contract';
			$parts[] = '';
			$parts[] = $output_contract;
			$parts[] = '';
		}

		if ( $edge_cases !== '' ) {
			$parts[] = '## Edge Cases';
			$parts[] = '';
			$parts[] = $edge_cases;
			$parts[] = '';
		}

		if ( $examples !== '' ) {
			$parts[] = '## Examples';
			$parts[] = '';
			$parts[] = $examples;
			$parts[] = '';
		}

		$parts[] = '## Methodology';
		$parts[] = '';
		$parts[] = $body !== '' ? $body : __( 'Add methodology in the WordPress editor.', 'ai-governance-suite' );

		return implode( "\n", $parts );
	}

	private function get_related_asset_map( int $post_id ): array {
		return array_filter(
			[
				__( 'Prompts', 'ai-governance-suite' )   => $this->get_related_post_titles( get_post_meta( $post_id, '_aigis_linked_prompt_ids', true ), 'aigis_prompt' ),
				__( 'Workflows', 'ai-governance-suite' ) => $this->get_related_post_titles( get_post_meta( $post_id, '_aigis_linked_workflow_ids', true ), 'aigis_workflow' ),
				__( 'Policies', 'ai-governance-suite' )  => $this->get_related_post_titles( get_post_meta( $post_id, '_aigis_linked_policy_ids', true ), 'aigis_policy' ),
				__( 'Incidents', 'ai-governance-suite' ) => $this->get_related_post_titles( get_post_meta( $post_id, '_aigis_linked_incident_ids', true ), 'aigis_incident' ),
			],
			static function ( $items ): bool {
				return ! empty( $items );
			}
		);
	}

	private function get_related_post_titles( $ids, string $post_type ): array {
		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return [];
		}

		$post_ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( empty( $post_ids ) ) {
			return [];
		}

		$posts = get_posts(
			[
				'post_type'      => $post_type,
				'post__in'       => $post_ids,
				'posts_per_page' => -1,
				'orderby'        => 'post__in',
			]
		);

		if ( empty( $posts ) ) {
			return [];
		}

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

	private function normalize_single_line( string $value ): string {
		$value = preg_replace( '/\s+/', ' ', trim( $value ) );
		return is_string( $value ) ? $value : '';
	}
}