<?php
/**
 * Fired during plugin activation.
 *
 * Creates all custom database tables, registers capabilities and roles,
 * seeds default data, and flushes rewrite rules.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Activator {

	public static function activate(): void {
		self::create_tables();
		AIGIS_Capabilities::register_roles_and_caps();
		self::seed_defaults();
		self::schedule_cron_jobs();
		flush_rewrite_rules();

		update_option( 'aigis_version', AIGIS_VERSION );
		update_option( 'aigis_db_version', '1.0.0' );
	}

	/**
	 * Create all custom database tables using dbDelta().
	 */
	private static function create_tables(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix;

		// --- AI Inventory ---
		$sql = "CREATE TABLE {$prefix}aigis_ai_inventory (
			id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			vendor_name     VARCHAR(255)        NOT NULL,
			model_name      VARCHAR(255)        NOT NULL,
			model_version   VARCHAR(100)        NOT NULL DEFAULT '',
			integration_type ENUM('custom-agent','api-model','on-prem') NOT NULL DEFAULT 'api-model',
			api_endpoint    VARCHAR(500)        NOT NULL DEFAULT '',
			agent_identifier VARCHAR(255)       NOT NULL DEFAULT '',
			owner_user_id   BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			data_categories TEXT                NOT NULL,
			risk_level      ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
			status          ENUM('active','deprecated','under-review') NOT NULL DEFAULT 'active',
			notes           LONGTEXT            NOT NULL,
			created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_status (status),
			KEY idx_risk_level (risk_level),
			KEY idx_integration_type (integration_type),
			KEY idx_owner (owner_user_id),
			UNIQUE KEY idx_agent_identifier (agent_identifier)
		) $charset_collate;";
		dbDelta( $sql );

		// --- Usage Logs ---
		$sql = "CREATE TABLE {$prefix}aigis_usage_logs (
			id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			agent_id        VARCHAR(255)        NOT NULL DEFAULT '',
			inventory_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			user_id         BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			prompt_post_id  BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			session_id      VARCHAR(255)        NOT NULL DEFAULT '',
			department      VARCHAR(255)        NOT NULL DEFAULT '',
			project_tag     VARCHAR(255)        NOT NULL DEFAULT '',
			input_hash      VARCHAR(64)         NOT NULL DEFAULT '',
			input_tokens    INT UNSIGNED        NOT NULL DEFAULT 0,
			output_tokens   INT UNSIGNED        NOT NULL DEFAULT 0,
			latency_ms      INT UNSIGNED        NOT NULL DEFAULT 0,
			cost_usd        DECIMAL(10,6)       NOT NULL DEFAULT 0.000000,
			status          ENUM('success','error','timeout','guardrail-blocked') NOT NULL DEFAULT 'success',
			error_code      VARCHAR(100)        NOT NULL DEFAULT '',
			logged_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_agent_id (agent_id),
			KEY idx_user_id (user_id),
			KEY idx_inventory_id (inventory_id),
			KEY idx_logged_at (logged_at),
			KEY idx_status (status),
			KEY idx_department (department),
			KEY idx_project_tag (project_tag)
		) $charset_collate;";
		dbDelta( $sql );

		// --- Audit Trail ---
		$sql = "CREATE TABLE {$prefix}aigis_audit_trail (
			id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			event_type      VARCHAR(100)        NOT NULL DEFAULT '',
			object_type     VARCHAR(100)        NOT NULL DEFAULT '',
			object_id       VARCHAR(255)        NOT NULL DEFAULT '',
			actor_user_id   BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			actor_ip        VARCHAR(45)         NOT NULL DEFAULT '',
			summary         VARCHAR(500)        NOT NULL DEFAULT '',
			before_state    LONGTEXT            NOT NULL,
			after_state     LONGTEXT            NOT NULL,
			occurred_at     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_event_type (event_type),
			KEY idx_object (object_type, object_id(100)),
			KEY idx_actor (actor_user_id),
			KEY idx_occurred_at (occurred_at)
		) $charset_collate;";
		dbDelta( $sql );

		// --- Cost Budgets ---
		$sql = "CREATE TABLE {$prefix}aigis_cost_budgets (
			id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			label           VARCHAR(255)        NOT NULL DEFAULT '',
			scope_type      ENUM('department','project','global') NOT NULL DEFAULT 'department',
			scope_value     VARCHAR(255)        NOT NULL DEFAULT '',
			inventory_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			period_type     ENUM('monthly','custom') NOT NULL DEFAULT 'monthly',
			period_start    DATE                NOT NULL,
			period_end      DATE                NOT NULL,
			budget_usd      DECIMAL(12,2)       NOT NULL DEFAULT 0.00,
			alert_pct_80    TINYINT(1)          NOT NULL DEFAULT 1,
			alert_pct_100   TINYINT(1)          NOT NULL DEFAULT 1,
			created_by      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_scope (scope_type, scope_value(100)),
			KEY idx_period (period_start, period_end)
		) $charset_collate;";
		dbDelta( $sql );

		// --- Stress Test Variations ---
		$sql = "CREATE TABLE {$prefix}aigis_stress_test_variations (
			id               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name             VARCHAR(255)        NOT NULL DEFAULT '',
			slug             VARCHAR(100)        NOT NULL DEFAULT '',
			category         VARCHAR(100)        NOT NULL DEFAULT '',
			description      TEXT                NOT NULL,
			parameter_schema LONGTEXT            NOT NULL,
			created_by       BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			created_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY idx_slug (slug)
		) $charset_collate;";
		dbDelta( $sql );

		// --- Stress Test Runs ---
		$sql = "CREATE TABLE {$prefix}aigis_stress_test_runs (
			id               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			run_batch_id     VARCHAR(36)         NOT NULL DEFAULT '',
			prompt_post_id   BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			variation_id     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			variation_params LONGTEXT            NOT NULL,
			modified_prompt  LONGTEXT            NOT NULL,
			provider         VARCHAR(100)        NOT NULL DEFAULT '',
			model_used       VARCHAR(100)        NOT NULL DEFAULT '',
			output           LONGTEXT            NOT NULL,
			score            DECIMAL(5,2)        NOT NULL DEFAULT 0.00,
			flagged          TINYINT(1)          NOT NULL DEFAULT 0,
			flag_reason      VARCHAR(500)        NOT NULL DEFAULT '',
			executed_by      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			executed_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_batch (run_batch_id),
			KEY idx_prompt (prompt_post_id),
			KEY idx_variation (variation_id),
			KEY idx_flagged (flagged)
		) $charset_collate;";
		dbDelta( $sql );

		// --- Eval Results ---
		$sql = "CREATE TABLE {$prefix}aigis_eval_results (
			id                BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			agent_id          VARCHAR(255)        NOT NULL DEFAULT '',
			inventory_id      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			input_hash        VARCHAR(64)         NOT NULL DEFAULT '',
			expected_output   LONGTEXT            NOT NULL,
			actual_output     LONGTEXT            NOT NULL,
			evaluator_version VARCHAR(50)         NOT NULL DEFAULT '',
			pass_fail         ENUM('pass','fail','pending-review') NOT NULL DEFAULT 'pass',
			false_negative    TINYINT(1)          NOT NULL DEFAULT 0,
			reviewer_id       BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			reviewed_at       DATETIME            DEFAULT NULL,
			reviewer_notes    LONGTEXT            NOT NULL,
			rulebook_version  VARCHAR(50)         NOT NULL DEFAULT '',
			submitted_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_agent_id (agent_id),
			KEY idx_pass_fail (pass_fail),
			KEY idx_false_negative (false_negative),
			KEY idx_submitted_at (submitted_at)
		) $charset_collate;";
		dbDelta( $sql );

		// --- Guardrail Triggers ---
		$sql = "CREATE TABLE {$prefix}aigis_guardrail_triggers (
			id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			agent_id        VARCHAR(255)        NOT NULL DEFAULT '',
			inventory_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			guardrail_name  VARCHAR(255)        NOT NULL DEFAULT '',
			input_hash      VARCHAR(64)         NOT NULL DEFAULT '',
			matched_rule    VARCHAR(500)        NOT NULL DEFAULT '',
			risk_taxonomy   VARCHAR(255)        NOT NULL DEFAULT '',
			is_keyword_only TINYINT(1)          NOT NULL DEFAULT 0,
			severity        ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
			triggered_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_agent_id (agent_id),
			KEY idx_guardrail (guardrail_name),
			KEY idx_risk_taxonomy (risk_taxonomy),
			KEY idx_triggered_at (triggered_at)
		) $charset_collate;";
		dbDelta( $sql );
	}

	/**
	 * Seed default plugin options and stress test variation types.
	 */
	private static function seed_defaults(): void {
		// General defaults
		if ( false === get_option( 'aigis_audit_retention_days' ) ) {
			add_option( 'aigis_audit_retention_days', 365 );
		}
		if ( false === get_option( 'aigis_pii_detection_enabled' ) ) {
			add_option( 'aigis_pii_detection_enabled', true );
		}
		if ( false === get_option( 'aigis_trust_proxy_headers' ) ) {
			add_option( 'aigis_trust_proxy_headers', false );
		}
		if ( false === get_option( 'aigis_dev_mode' ) ) {
			add_option( 'aigis_dev_mode', false );
		}
		if ( false === get_option( 'aigis_openai_default_model' ) ) {
			add_option( 'aigis_openai_default_model', 'gpt-4o' );
		}
		if ( false === get_option( 'aigis_anthropic_default_model' ) ) {
			add_option( 'aigis_anthropic_default_model', 'claude-opus-4-5' );
		}
		if ( false === get_option( 'aigis_ollama_endpoint' ) ) {
			add_option( 'aigis_ollama_endpoint', 'http://localhost:11434' );
		}
		if ( false === get_option( 'aigis_ollama_default_model' ) ) {
			add_option( 'aigis_ollama_default_model', 'llama3' );
		}
		if ( false === get_option( 'aigis_alert_rules' ) ) {
			add_option( 'aigis_alert_rules', [] );
		}
		if ( false === get_option( 'aigis_policy_expiry_alert_days' ) ) {
			add_option( 'aigis_policy_expiry_alert_days', 30 );
		}
		if ( false === get_option( 'aigis_notification_inbox_cap' ) ) {
			add_option( 'aigis_notification_inbox_cap', 100 );
		}
		if ( false === get_option( 'aigis_eval_sample_rate_pct' ) ) {
			add_option( 'aigis_eval_sample_rate_pct', 5 );
		}
		if ( false === get_option( 'aigis_eval_rulebook' ) ) {
			add_option( 'aigis_eval_rulebook', [] );
		}
		if ( false === get_option( 'aigis_tracev_rules' ) ) {
			add_option( 'aigis_tracev_rules', [] );
		}

		// Default risk taxonomy
		if ( false === get_option( 'aigis_risk_taxonomy' ) ) {
			add_option( 'aigis_risk_taxonomy', [
				[ 'id' => 'data-exfiltration',    'label' => 'Data Exfiltration',    'description' => 'Unauthorized extraction of sensitive data.' ],
				[ 'id' => 'prompt-injection',     'label' => 'Prompt Injection',     'description' => 'Malicious input designed to override agent instructions.' ],
				[ 'id' => 'pii-exposure',         'label' => 'PII Exposure',         'description' => 'Disclosure of personally identifiable information.' ],
				[ 'id' => 'privilege-escalation', 'label' => 'Privilege Escalation', 'description' => 'Attempt to gain unauthorized access or capabilities.' ],
				[ 'id' => 'harmful-content',      'label' => 'Harmful Content',      'description' => 'Generation of content that could cause harm.' ],
				[ 'id' => 'misinformation',       'label' => 'Misinformation',       'description' => 'Generation of factually incorrect or misleading content.' ],
			] );
		}

		// Default stake levels
		if ( false === get_option( 'aigis_stake_levels' ) ) {
			add_option( 'aigis_stake_levels', [
				[ 'id' => 'low',      'label' => 'Low' ],
				[ 'id' => 'medium',   'label' => 'Medium' ],
				[ 'id' => 'high',     'label' => 'High' ],
				[ 'id' => 'critical', 'label' => 'Critical' ],
			] );
		}

		// Generate a plugin API key if not already set.
		if ( false === get_option( 'aigis_api_key_hash' ) ) {
			$raw_key = wp_generate_password( 48, false );
			add_option( 'aigis_api_key_hash', password_hash( $raw_key, PASSWORD_BCRYPT ) );
			// Store the raw key once in a transient so the admin can copy it.
			set_transient( 'aigis_initial_api_key', $raw_key, 300 );
		}

		// Seed built-in stress test variation types.
		self::seed_stress_test_variations();
	}

	/**
	 * Seed the built-in stress test variation types.
	 */
	private static function seed_stress_test_variations(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'aigis_stress_test_variations';

		$variations = [
			[
				'name'             => 'Social Anchoring',
				'slug'             => 'social-anchoring',
				'category'         => 'cognitive-bias',
				'description'      => 'Applies an authority figure and confidence framing to test whether the agent is inappropriately deferential.',
				'parameter_schema' => wp_json_encode( [
					'authority_figure'  => [ 'type' => 'string', 'label' => 'Authority Figure', 'default' => 'CEO' ],
					'confidence_level'  => [ 'type' => 'enum',   'label' => 'Confidence Level', 'options' => [ 'low', 'medium', 'high' ], 'default' => 'high' ],
				] ),
			],
			[
				'name'             => 'Time Pressure',
				'slug'             => 'time-pressure',
				'category'         => 'stress',
				'description'      => 'Adds urgency framing to the prompt to expose performance degradation under simulated time pressure.',
				'parameter_schema' => wp_json_encode( [
					'urgency_level' => [ 'type' => 'enum', 'label' => 'Urgency Level', 'options' => [ 'mild', 'moderate', 'extreme' ], 'default' => 'moderate' ],
				] ),
			],
			[
				'name'             => 'Conflicting Instructions',
				'slug'             => 'conflicting-instructions',
				'category'         => 'robustness',
				'description'      => 'Introduces a contradiction between the system prompt intent and the user request.',
				'parameter_schema' => wp_json_encode( [
					'conflict_type' => [ 'type' => 'enum', 'label' => 'Conflict Type', 'options' => [ 'role', 'safety', 'factual' ], 'default' => 'safety' ],
				] ),
			],
			[
				'name'             => 'Tool Failure Simulation',
				'slug'             => 'tool-failure',
				'category'         => 'resilience',
				'description'      => 'Simulates a downstream tool or API being unavailable, slow, or returning wrong output.',
				'parameter_schema' => wp_json_encode( [
					'failure_mode' => [ 'type' => 'enum', 'label' => 'Failure Mode', 'options' => [ 'unavailable', 'slow', 'wrong-output' ], 'default' => 'unavailable' ],
				] ),
			],
			[
				'name'             => 'Ambiguous Input',
				'slug'             => 'ambiguous-input',
				'category'         => 'edge-case',
				'description'      => 'Introduces intentional ambiguity in the prompt to test how the agent handles uncertainty.',
				'parameter_schema' => wp_json_encode( [
					'ambiguity_type' => [ 'type' => 'enum', 'label' => 'Ambiguity Type', 'options' => [ 'pronoun', 'scope', 'format' ], 'default' => 'scope' ],
				] ),
			],
			[
				'name'             => 'Role Escalation Attempt',
				'slug'             => 'role-escalation',
				'category'         => 'security',
				'description'      => 'Tests agent resistance to jailbreak, override, or persona-shift attempts.',
				'parameter_schema' => wp_json_encode( [
					'escalation_vector' => [ 'type' => 'enum', 'label' => 'Escalation Vector', 'options' => [ 'jailbreak', 'override', 'persona-shift' ], 'default' => 'jailbreak' ],
				] ),
			],
		];

		foreach ( $variations as $variation ) {
			// Only seed if slug doesn't already exist.
			$exists = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM `{$table}` WHERE slug = %s", $variation['slug'] )
			);
			if ( ! $exists ) {
				$wpdb->insert( $table, $variation );
			}
		}
	}

	/**
	 * Schedule WP-Cron jobs.
	 */
	private static function schedule_cron_jobs(): void {
		if ( ! wp_next_scheduled( 'aigis_prune_audit_log' ) ) {
			wp_schedule_event( time(), 'daily', 'aigis_prune_audit_log' );
		}
		if ( ! wp_next_scheduled( 'aigis_prune_usage_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'aigis_prune_usage_logs' );
		}
		if ( ! wp_next_scheduled( 'aigis_check_policy_expiry' ) ) {
			wp_schedule_event( time(), 'daily', 'aigis_check_policy_expiry' );
		}
		if ( ! wp_next_scheduled( 'aigis_check_budget_alerts' ) ) {
			wp_schedule_event( time(), 'daily', 'aigis_check_budget_alerts' );
		}
		if ( ! wp_next_scheduled( 'aigis_sample_eval_runs' ) ) {
			wp_schedule_event( time(), 'daily', 'aigis_sample_eval_runs' );
		}
	}
}
