<?php
/**
 * Admin menu registration and asset loading.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Admin {

	/** @var AIGIS_Notifications */
	private AIGIS_Notifications $notifications;

	public function __construct( AIGIS_Notifications $notifications ) {
		$this->notifications = $notifications;
	}

	public function register( AIGIS_Loader $loader ): void {
		$loader->add_action( 'admin_menu', $this, 'register_menus' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_assets' );
		$loader->add_action( 'admin_notices', $this, 'show_initial_api_key_notice' );
		$loader->add_action( 'wp_ajax_aigis_inbox_unread_count',    $this, 'ajax_inbox_unread_count' );
		$loader->add_action( 'wp_ajax_aigis_generate_test_data',    $this, 'ajax_generate_test_data' );
		$loader->add_action( 'wp_ajax_aigis_purge_test_data',       $this, 'ajax_purge_test_data' );
		$loader->add_action( 'wp_ajax_aigis_factory_reset',         $this, 'ajax_factory_reset' );
	}

	// -----------------------------------------------------------------------
	// Menu registration
	// -----------------------------------------------------------------------

	public function register_menus(): void {
		$unread = $this->notifications->unread_count();
		$badge  = $unread > 0 ? ' <span class="awaiting-mod">' . absint( $unread ) . '</span>' : '';

		add_menu_page(
			__( 'AI Governance', 'ai-governance-suite' ),
			__( 'AI Governance', 'ai-governance-suite' ) . $badge,
			AIGIS_Capabilities::VIEW_AI_INVENTORY,
			'aigis-dashboard',
			[ new AIGIS_Page_Dashboard(), 'render' ],
			'dashicons-shield-alt',
			30
		);

		$submenus = [
			[
				'parent' => 'aigis-dashboard',
				'title'  => __( 'Dashboard', 'ai-governance-suite' ),
				'cap'    => AIGIS_Capabilities::VIEW_AI_INVENTORY,
				'slug'   => 'aigis-dashboard',
				'cb'     => '',
			],
			[
				'parent' => 'aigis-dashboard',
				'title'  => __( 'AI Inventory', 'ai-governance-suite' ),
				'cap'    => AIGIS_Capabilities::VIEW_AI_INVENTORY,
				'slug'   => 'aigis-inventory',
				'cb'     => [ new AIGIS_Page_Inventory(), 'render' ],
			],
			[
				'parent' => 'aigis-dashboard',
				'title'  => __( 'Analytics', 'ai-governance-suite' ),
				'cap'    => AIGIS_Capabilities::VIEW_ANALYTICS,
				'slug'   => 'aigis-analytics',
				'cb'     => [ new AIGIS_Page_Analytics(), 'render' ],
			],
			[
				'parent' => 'aigis-dashboard',
				'title'  => __( 'Cost & Budgets', 'ai-governance-suite' ),
				'cap'    => AIGIS_Capabilities::VIEW_COSTS,
				'slug'   => 'aigis-cost',
				'cb'     => [ new AIGIS_Page_Cost(), 'render' ],
			],
			[
				'parent' => 'aigis-dashboard',
				'title'  => __( 'Stress Tests', 'ai-governance-suite' ),
				'cap'    => AIGIS_Capabilities::RUN_STRESS_TESTS,
				'slug'   => 'aigis-stress-tests',
				'cb'     => [ new AIGIS_Page_Stress_Tests(), 'render' ],
			],
			[
				'parent' => 'aigis-dashboard',
				'title'  => __( 'Evaluation', 'ai-governance-suite' ),
				'cap'    => AIGIS_Capabilities::VIEW_EVAL,
				'slug'   => 'aigis-eval',
				'cb'     => [ new AIGIS_Page_Eval(), 'render' ],
			],
			[
				'parent' => 'aigis-dashboard',
				'title'  => __( 'Audit Log', 'ai-governance-suite' ),
				'cap'    => AIGIS_Capabilities::VIEW_AUDIT_LOG,
				'slug'   => 'aigis-audit-log',
				'cb'     => [ new AIGIS_Page_Audit_Log(), 'render' ],
			],
			[
				'parent' => 'aigis-dashboard',
				'title'  => __( 'User Manual', 'ai-governance-suite' ),
				'cap'    => AIGIS_Capabilities::VIEW_AI_INVENTORY,
				'slug'   => 'aigis-manual',
				'cb'     => [ new AIGIS_Page_Manual(), 'render' ],
			],
			[
				'parent' => 'aigis-dashboard',
				'title'  => __( 'Settings', 'ai-governance-suite' ),
				'cap'    => AIGIS_Capabilities::MANAGE_SETTINGS,
				'slug'   => 'aigis-settings',
				'cb'     => [ new AIGIS_Page_Settings(), 'render' ],
			],
		];

		foreach ( $submenus as $item ) {
			add_submenu_page(
				$item['parent'],
				$item['title'],
				$item['title'],
				$item['cap'],
				$item['slug'],
				$item['cb']
			);
		}
	}

	// -----------------------------------------------------------------------
	// Asset loading
	// -----------------------------------------------------------------------

	public function enqueue_assets( string $hook ): void {
		// Only load on our own pages and on AIGIS CPT edit screens.
		if ( ! $this->is_aigis_screen( $hook ) ) {
			return;
		}

		wp_enqueue_style(
			'aigis-admin',
			AIGIS_PLUGIN_URL . 'admin/css/aigis-admin.css',
			[],
			AIGIS_VERSION
		);

		wp_enqueue_script(
			'aigis-admin',
			AIGIS_PLUGIN_URL . 'admin/js/aigis-admin.js',
			[ 'jquery' ],
			AIGIS_VERSION,
			true
		);

		wp_add_inline_script(
			'aigis-admin',
			'var aigisAdmin = ' . wp_json_encode( [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonces'  => [
					'sandboxTest'       => wp_create_nonce( 'aigis_sandbox_test' ),
					'promotePrompt'     => wp_create_nonce( 'aigis_promote_prompt' ),
					'skillStatus'       => wp_create_nonce( 'aigis_change_skill_status' ),
					'policyStatus'      => wp_create_nonce( 'aigis_policy_status' ),
					'incidentStatus'    => wp_create_nonce( 'aigis_incident_status' ),
					'inboxUnreadCount'  => wp_create_nonce( 'aigis_inbox_unread_count' ),
					'generateTestData'  => wp_create_nonce( 'aigis_generate_test_data' ),
					'purgeTestData'     => wp_create_nonce( 'aigis_purge_test_data' ),
					'factoryReset'      => wp_create_nonce( 'aigis_factory_reset' ),
				],
				'i18n'    => [
					'confirm_delete'           => __( 'Are you sure you want to delete this item?', 'ai-governance-suite' ),
					'confirmSkillStatus'       => __( 'Change this skill status?', 'ai-governance-suite' ),
					'skillStatusNotePrompt'    => __( 'Optional transition note:', 'ai-governance-suite' ),
					'saving'                   => __( 'Saving…', 'ai-governance-suite' ),
					'testing'                  => __( 'Running test…', 'ai-governance-suite' ),
					'confirmGenerateTestData'  => __( 'Generate sample data for all sections? This will populate every section of the plugin with realistic test records.', 'ai-governance-suite' ),
					'confirmPurgeTestData'     => __( 'Permanently delete ALL test data? This cannot be undone.', 'ai-governance-suite' ),
					'confirmFactoryReset1'     => __( 'FACTORY RESET: This will permanently delete every record created by this plugin and cannot be undone. Continue?', 'ai-governance-suite' ),
					'confirmFactoryReset2'     => __( 'Are you absolutely sure? Type YES to confirm.', 'ai-governance-suite' ),
					'generating'               => __( 'Generating…', 'ai-governance-suite' ),
					'purging'                  => __( 'Removing…', 'ai-governance-suite' ),
					'resetting'                => __( 'Resetting…', 'ai-governance-suite' ),
				],
			] ) . ';',
			'before'
		);

		// Chart.js — on dashboard, analytics, cost, and eval pages.
		if ( in_array( $hook, [ 'toplevel_page_aigis-dashboard', 'ai-governance_page_aigis-analytics', 'ai-governance_page_aigis-cost', 'ai-governance_page_aigis-eval' ], true ) ) {
			// Use the UMD build which creates a global Chart object.
			// The local vendor file is the ESM build which needs type="module".
			$local_umd = AIGIS_PLUGIN_DIR . 'admin/js/vendor/chart.umd.min.js';
			$chart_url = file_exists( $local_umd )
				? AIGIS_PLUGIN_URL . 'admin/js/vendor/chart.umd.min.js'
				: 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js';
			wp_enqueue_script(
				'chart-js',
				$chart_url,
				[],
				'4.4.3',
				true
			);
			wp_enqueue_script(
				'aigis-charts',
				AIGIS_PLUGIN_URL . 'admin/js/aigis-charts.js',
				[ 'chart-js' ],
				AIGIS_VERSION,
				true
			);
		}

		// Mermaid.js — only on workflow edit screens.
		if ( $this->is_workflow_editor_screen( $hook ) ) {
			wp_enqueue_script(
				'mermaid-js',
				AIGIS_PLUGIN_URL . 'admin/js/vendor/mermaid.min.js',
				[],
				'10.9.0',
				true
			);
			wp_enqueue_script(
				'aigis-workflow-diagram',
				AIGIS_PLUGIN_URL . 'admin/js/aigis-workflow-diagram.js',
				[ 'mermaid-js' ],
				AIGIS_VERSION,
				true
			);
		}
	}

	// -----------------------------------------------------------------------
	// Notices
	// -----------------------------------------------------------------------

	public function show_initial_api_key_notice(): void {
		$raw_key = get_transient( 'aigis_initial_api_key' );

		if ( ! $raw_key ) {
			return;
		}

		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_API_KEYS ) ) {
			return;
		}

		// Display once; the transient expires automatically after 5 minutes.
		?>
		<div class="notice notice-success aigis-api-key-notice">
			<p>
				<strong><?php esc_html_e( 'AI Governance Suite activated.', 'ai-governance-suite' ); ?></strong>
				<?php esc_html_e( 'Your REST API key has been generated. Copy it now — it will not be shown again:', 'ai-governance-suite' ); ?>
			</p>
			<p>
				<code class="aigis-api-key-display"><?php echo esc_html( $raw_key ); ?></code>
				<button type="button" class="button button-secondary aigis-copy-key" data-key="<?php echo esc_attr( $raw_key ); ?>">
					<?php esc_html_e( 'Copy', 'ai-governance-suite' ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	// -----------------------------------------------------------------------
	// AJAX handlers
	// -----------------------------------------------------------------------

	public function ajax_inbox_unread_count(): void {
		check_ajax_referer( 'aigis_inbox_unread_count', 'nonce' );
		wp_send_json_success( [ 'count' => $this->notifications->unread_count() ] );
	}

	public function ajax_generate_test_data(): void {
		check_ajax_referer( 'aigis_generate_test_data', 'nonce' );
		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_SETTINGS ) ) {
			wp_send_json_error( __( 'Permission denied.', 'ai-governance-suite' ) );
		}
		$result = AIGIS_Test_Data::generate();
		if ( false === $result ) {
			wp_send_json_error( __( 'Test data already exists. Remove it first.', 'ai-governance-suite' ) );
		}
		$total = array_sum( $result );
		( new AIGIS_DB_Audit() )->log( 'test_data.generated', 'settings', '0', "Generated {$total} test records." );
		wp_send_json_success( [
			'message' => sprintf(
				/* translators: %d: number of records */
				__( 'Generated %d test records. Refreshing…', 'ai-governance-suite' ),
				$total
			),
			'counts'  => $result,
		] );
	}

	public function ajax_purge_test_data(): void {
		check_ajax_referer( 'aigis_purge_test_data', 'nonce' );
		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_SETTINGS ) ) {
			wp_send_json_error( __( 'Permission denied.', 'ai-governance-suite' ) );
		}
		$result = AIGIS_Test_Data::purge();
		$total  = array_sum( $result );
		( new AIGIS_DB_Audit() )->log( 'test_data.purged', 'settings', '0', "Purged {$total} test records." );
		wp_send_json_success( [
			'message' => sprintf(
				/* translators: %d: number of records */
				__( 'Removed %d test records. Refreshing…', 'ai-governance-suite' ),
				$total
			),
			'counts'  => $result,
		] );
	}

	public function ajax_factory_reset(): void {
		check_ajax_referer( 'aigis_factory_reset', 'nonce' );
		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_SETTINGS ) ) {
			wp_send_json_error( __( 'Permission denied.', 'ai-governance-suite' ) );
		}

		global $wpdb;

		// 1. Delete all AIGIS CPT posts.
		$post_types = [ 'aigis_prompt', 'aigis_policy', 'aigis_workflow', 'aigis_incident', 'aigis_skill' ];
		$post_count = 0;
		foreach ( $post_types as $pt ) {
			$ids = get_posts( [ 'post_type' => $pt, 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids' ] );
			foreach ( $ids as $id ) {
				wp_delete_post( (int) $id, true );
				$post_count++;
			}
		}

		// 2. Truncate all custom tables.
		$tables = [
			$wpdb->prefix . 'aigis_ai_inventory',
			$wpdb->prefix . 'aigis_usage_logs',
			$wpdb->prefix . 'aigis_audit_trail',
			$wpdb->prefix . 'aigis_cost_budgets',
			$wpdb->prefix . 'aigis_stress_test_variations',
			$wpdb->prefix . 'aigis_stress_test_runs',
			$wpdb->prefix . 'aigis_eval_results',
			$wpdb->prefix . 'aigis_guardrail_triggers',
		];
		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "TRUNCATE TABLE `{$table}`" );
		}

		// 3. Delete all plugin options.
		$options = [
			'aigis_version', 'aigis_db_version', 'aigis_audit_retention_days',
			'aigis_pii_detection_enabled', 'aigis_trust_proxy_headers', 'aigis_api_key_hash',
			'aigis_dev_mode', 'aigis_openai_api_key', 'aigis_openai_default_model',
			'aigis_anthropic_api_key', 'aigis_anthropic_default_model', 'aigis_ollama_endpoint',
			'aigis_ollama_default_model', 'aigis_alert_rules', 'aigis_policy_expiry_alert_days',
			'aigis_notification_inbox_cap', 'aigis_notification_inbox', 'aigis_eval_sample_rate_pct',
			'aigis_eval_rulebook', 'aigis_tracev_rules', 'aigis_risk_taxonomy',
			'aigis_stake_levels', 'aigis_test_data_db_ids',
		];
		foreach ( $options as $opt ) {
			delete_option( $opt );
		}
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_aigis_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_aigis_' ) . '%'
			)
		);

		// 4. Re-run activation to restore tables, roles, and default options.
		AIGIS_Activator::activate();

		wp_send_json_success( [
			'message' => sprintf(
				/* translators: %d: number of CPT posts deleted */
				__( 'Factory reset complete. Deleted %d posts, truncated all tables, and restored plugin defaults. Refreshing…', 'ai-governance-suite' ),
				$post_count
			),
		] );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function is_aigis_screen( string $hook ): bool {
		if ( str_contains( $hook, 'aigis' ) ) {
			return true;
		}

		global $post;
		if ( isset( $post->post_type ) && str_starts_with( $post->post_type, 'aigis_' ) ) {
			return true;
		}

		return false;
	}

	private function is_workflow_editor_screen( string $hook ): bool {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return false;
		}

		$post_type = sanitize_key( $_GET['post_type'] ?? '' );
		if ( '' === $post_type && ! empty( $_GET['post'] ) ) {
			$post_type = (string) get_post_type( absint( $_GET['post'] ) );
		}

		global $post;
		if ( '' === $post_type && isset( $post->post_type ) ) {
			$post_type = (string) $post->post_type;
		}

		return $post_type === 'aigis_workflow';
	}
}
