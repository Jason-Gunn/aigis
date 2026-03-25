<?php
/**
 * Test data seeder and purger.
 *
 * Creates realistic sample records across every plugin section so developers
 * and administrators can explore and demo the interface without needing
 * production data. Every record is tagged for clean removal.
 *
 * CPT records   — tagged with post meta _aigis_test_data = '1'
 * DB table rows — primary key IDs tracked in WP option aigis_test_data_db_ids
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Test_Data {

	private const POST_META_KEY  = '_aigis_test_data';
	private const DB_IDS_OPTION  = 'aigis_test_data_db_ids';

	/** Known test agent identifiers — used for reliable purge and count fallbacks. */
	private const TEST_AGENT_IDS = [
		'test-openai-gpt4o',
		'test-anthropic-claude',
		'test-ollama-llama3',
	];

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Generate test records for every section.
	 * Returns a per-section count array, or false if test data already exists.
	 *
	 * @return array<string,int>|false
	 */
	public static function generate(): array|false {
		$existing = self::count_existing();

		if ( array_sum( $existing ) > 0 ) {
			return false;
		}

		$counts = [];
		$db_ids = [];

		// CPT posts.
		$counts['prompts']   = self::create_prompt_posts();
		$counts['policies']  = self::create_policy_posts();
		$counts['workflows'] = self::create_workflow_posts();
		$counts['incidents'] = self::create_incident_posts();

		// Database table rows.
		[ $counts['inventory'],    $db_ids['inventory'] ]    = self::insert_inventory();
		[ $counts['usage_logs'],   $db_ids['usage_logs'] ]   = self::insert_usage_logs( $db_ids['inventory'] );
		[ $counts['audit'],        $db_ids['audit'] ]        = self::insert_audit_trail();
		[ $counts['cost_budgets'], $db_ids['cost_budgets'] ] = self::insert_cost_budgets( $db_ids['inventory'] );
		[ $counts['eval_results'], $db_ids['eval_results'] ] = self::insert_eval_results( $db_ids['inventory'] );
		[ $counts['guardrail'],    $db_ids['guardrail'] ]    = self::insert_guardrail_triggers( $db_ids['inventory'] );

		update_option( self::DB_IDS_OPTION, $db_ids );

		return $counts;
	}

	/**
	 * Remove all test records. Returns a per-section count of deleted items.
	 *
	 * @return array<string,int>
	 */
	public static function purge(): array {
		$counts = [];
		$db_ids = get_option( self::DB_IDS_OPTION, [] );

		// CPT posts.
		$counts['posts'] = self::delete_test_posts();

		global $wpdb;

		// Build a reusable IN (...) clause for the known test agent identifiers.
		$agent_placeholders = implode( ',', array_fill( 0, count( self::TEST_AGENT_IDS ), '%s' ) );

		// --- Inventory: delete by tracked IDs then fall back to agent_identifier ---
		$counts['inventory'] = self::purge_by_ids( $wpdb, $db_ids['inventory'] ?? [], "{$wpdb->prefix}aigis_ai_inventory" );
		// Fallback: remove any orphaned test rows that weren't tracked in the option.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM `{$wpdb->prefix}aigis_ai_inventory` WHERE agent_identifier IN ({$agent_placeholders})",
			self::TEST_AGENT_IDS
		) );

		// --- Tables where agent_id stores the test identifier —-- delete by both tracked IDs and known agent_id.
		foreach (
			[
				'usage_logs'   => "{$wpdb->prefix}aigis_usage_logs",
				'eval_results' => "{$wpdb->prefix}aigis_eval_results",
				'guardrail'    => "{$wpdb->prefix}aigis_guardrail_triggers",
			]
			as $key => $table
		) {
			$counts[ $key ] = self::purge_by_ids( $wpdb, $db_ids[ $key ] ?? [], $table );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM `{$table}` WHERE agent_id IN ({$agent_placeholders})",
				self::TEST_AGENT_IDS
			) );
		}

		// --- Audit trail: delete by tracked IDs only (no agent_id column) ---
		$counts['audit'] = self::purge_by_ids( $wpdb, $db_ids['audit'] ?? [], "{$wpdb->prefix}aigis_audit_trail" );

		// --- Cost budgets: delete by tracked IDs then fall back to '[TEST]' label prefix ---
		$counts['cost_budgets'] = self::purge_by_ids( $wpdb, $db_ids['cost_budgets'] ?? [], "{$wpdb->prefix}aigis_cost_budgets" );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM `{$wpdb->prefix}aigis_cost_budgets` WHERE label LIKE %s",
			'[TEST]%'
		) );

		delete_option( self::DB_IDS_OPTION );

		return $counts;
	}

	/**
	 * Return current test record counts, indexed by section.
	 * Checks both the tracking option AND actual DB rows so it is accurate
	 * even when the option has drifted out of sync with the database.
	 *
	 * @return array<string,int>
	 */
	public static function count_existing(): array {
		global $wpdb;
		$counts = [];

		$post_type_map = [
			'prompts'   => 'aigis_prompt',
			'policies'  => 'aigis_policy',
			'workflows' => 'aigis_workflow',
			'incidents' => 'aigis_incident',
		];

		foreach ( $post_type_map as $label => $post_type ) {
			$q = new WP_Query( [
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'meta_key'       => self::POST_META_KEY,
				'meta_value'     => '1',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			] );
			$counts[ $label ] = $q->found_posts;
		}

		// For DB tables, query the actual rows — this is accurate even if the
		// tracking option was lost or was never saved correctly.
		$agent_placeholders = implode( ',', array_fill( 0, count( self::TEST_AGENT_IDS ), '%s' ) );

		// Inventory — identified by known agent_identifier values.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$counts['inventory'] = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$wpdb->prefix}aigis_ai_inventory` WHERE agent_identifier IN ({$agent_placeholders})",
			self::TEST_AGENT_IDS
		) );

		// Usage logs, eval results, guardrail triggers — identified by agent_id.
		foreach (
			[
				'usage_logs'   => "{$wpdb->prefix}aigis_usage_logs",
				'eval_results' => "{$wpdb->prefix}aigis_eval_results",
				'guardrail'    => "{$wpdb->prefix}aigis_guardrail_triggers",
			]
			as $key => $table
		) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$counts[ $key ] = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE agent_id IN ({$agent_placeholders})",
				self::TEST_AGENT_IDS
			) );
		}

		// Cost budgets — identified by '[TEST]' label prefix.
		$counts['cost_budgets'] = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$wpdb->prefix}aigis_cost_budgets` WHERE label LIKE %s",
			'[TEST]%'
		) );

		// Audit trail — no reliable content flag, fall back to the tracking option.
		$db_ids              = get_option( self::DB_IDS_OPTION, [] );
		$counts['audit']     = count( $db_ids['audit'] ?? [] );

		return $counts;
	}

	// -----------------------------------------------------------------------
	// CPT post creation
	// -----------------------------------------------------------------------

	private static function create_prompt_posts(): int {
		return self::insert_posts( 'aigis_prompt', [
			[
				'title'   => '[TEST] Customer Support Reply Generator',
				'content' => 'You are a professional customer support agent. Given the following customer message, write a helpful, empathetic, and concise reply that addresses their concern and offers a clear resolution path.',
				'status'  => 'publish',
				'meta'    => [
					'aigis_prompt_stage'       => 'production',
					'aigis_prompt_provider'    => 'openai',
					'aigis_prompt_model'       => 'gpt-4o',
					'aigis_prompt_temperature' => '0.3',
					'aigis_prompt_max_tokens'  => '512',
					'aigis_prompt_version'     => '1.2',
				],
			],
			[
				'title'   => '[TEST] Policy Document Summariser',
				'content' => 'Summarise the following policy document into 5 key bullet points written in plain English for a non-technical audience. Focus on obligations, prohibited actions, and consequences of non-compliance.',
				'status'  => 'publish',
				'meta'    => [
					'aigis_prompt_stage'       => 'staging',
					'aigis_prompt_provider'    => 'anthropic',
					'aigis_prompt_model'       => 'claude-opus-4-5',
					'aigis_prompt_temperature' => '0.2',
					'aigis_prompt_max_tokens'  => '1024',
					'aigis_prompt_version'     => '0.8',
				],
			],
			[
				'title'   => '[TEST] Code Security Review Assistant',
				'content' => 'Review the following code snippet for security vulnerabilities, performance issues, and logic errors. Provide actionable recommendations with corrected code examples where relevant.',
				'status'  => 'publish',
				'meta'    => [
					'aigis_prompt_stage'       => 'development',
					'aigis_prompt_provider'    => 'openai',
					'aigis_prompt_model'       => 'gpt-4o',
					'aigis_prompt_temperature' => '0.1',
					'aigis_prompt_max_tokens'  => '2048',
					'aigis_prompt_version'     => '0.3',
				],
			],
		] );
	}

	private static function create_policy_posts(): int {
		return self::insert_posts( 'aigis_policy', [
			[
				'title'   => '[TEST] Acceptable Use Policy for AI Models',
				'content' => 'This policy establishes the acceptable use of AI systems within the organisation. All AI-assisted outputs must be reviewed by a human before acting on them. Personal data may not be submitted to external AI providers without prior approval from the Data Protection Officer.',
				'status'  => 'aigis-approved',
				'meta'    => [
					'_aigis_policy_version'       => '1.0.0',
					'_aigis_policy_effective_date' => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
					'_aigis_policy_expiry_date'    => gmdate( 'Y-m-d', strtotime( '+335 days' ) ),
				],
			],
			[
				'title'   => '[TEST] Data Privacy Policy for AI Processing',
				'content' => 'This policy governs how personal data is handled when processed by AI systems. Data minimisation principles must apply. Anonymisation or pseudonymisation must be applied before any data is submitted to a third-party AI model API.',
				'status'  => 'aigis-in-review',
				'meta'    => [
					'_aigis_policy_version'       => '0.1.0',
					'_aigis_policy_effective_date' => gmdate( 'Y-m-d', strtotime( '+14 days' ) ),
					'_aigis_policy_expiry_date'    => gmdate( 'Y-m-d', strtotime( '+379 days' ) ),
				],
			],
		] );
	}

	private static function create_workflow_posts(): int {
		return self::insert_posts( 'aigis_workflow', [
			[
				'title'   => '[TEST] Customer Escalation Workflow',
				'content' => 'Incoming query → Sentiment analysis → Route: negative triggers human escalation, positive/neutral routes to automated reply → Log interaction.',
				'status'  => 'publish',
				'meta'    => [
					'_aigis_mermaid_source' => "graph TD\n    A[User Query] --> B{Sentiment}\n    B -->|Negative| C[Human Agent]\n    B -->|Positive| D[Auto Reply]\n    D --> E[Log Interaction]",
				],
			],
			[
				'title'   => '[TEST] Content Review Pipeline',
				'content' => 'Draft content → PII scan → Guardrail check → Policy compliance check → Approved or Rejected.',
				'status'  => 'publish',
				'meta'    => [
					'_aigis_mermaid_source' => "graph LR\n    A[Draft Content] --> B[PII Scan]\n    B --> C[Guardrail]\n    C --> D{Policy OK?}\n    D -->|Yes| E[Approved]\n    D -->|No| F[Rejected]",
				],
			],
			[
				'title'   => '[TEST] Incident Response Workflow',
				'content' => 'Incident detected → Classify severity → Notify stakeholders → Assign investigator → Contain impact → Post-incident review → Close.',
				'status'  => 'publish',
				'meta'    => [
					'_aigis_mermaid_source' => "graph TD\n    A[Incident Detected] --> B{Classify Severity}\n    B -->|Critical| C[Page On-Call Team]\n    B -->|High| D[Alert Dept Head]\n    B -->|Medium| E[Create Ticket]\n    C --> F[Contain Impact]\n    D --> F\n    E --> F\n    F --> G[Investigate]\n    G --> H[Resolve]\n    H --> I[Post-Incident Review]\n    I --> J[Close Incident]",
				],
			],
			[
				'title'   => '[TEST] Model Deployment Approval',
				'content' => 'New model proposed → Risk assessment → Legal review → Technical review → Governance approval → Inventory registration → Deploy.',
				'status'  => 'publish',
				'meta'    => [
					'_aigis_mermaid_source' => "graph LR\n    A[Model Proposed] --> B[Risk Assessment]\n    B --> C[Legal Review]\n    B --> D[Technical Review]\n    C --> E{Governance Vote}\n    D --> E\n    E -->|Approved| F[Inventory Registration]\n    E -->|Rejected| G[Decline & Document]\n    F --> H[Deploy to Staging]\n    H --> I[Production Release]",
				],
			],
			[
				'title'   => '[TEST] Employee AI Onboarding',
				'content' => 'New staff member → Assign AI governance training → Complete acceptable use policy sign-off → Provision AI tool access → Log onboarding.',
				'status'  => 'publish',
				'meta'    => [
					'_aigis_mermaid_source' => "graph TD\n    A[New Employee] --> B[Governance Training]\n    B --> C{Training Passed?}\n    C -->|Yes| D[Sign Acceptable Use Policy]\n    C -->|No| B\n    D --> E[Provision AI Tool Access]\n    E --> F[Notify Line Manager]\n    F --> G[Audit Log Entry]",
				],
			],
		] );
	}

	private static function create_incident_posts(): int {
		return self::insert_posts( 'aigis_incident', [
			[
				'title'   => '[TEST] PII Detected in Customer Support Output',
				'content' => "The Customer Support Reply Generator included a customer's email address in the generated reply without being prompted to do so. The output was flagged by the guardrail layer before delivery.",
				'status'  => 'aigis-investigating',
				'meta'    => [
					'_aigis_severity'    => 'high',
					'_aigis_detected_at' => gmdate( 'Y-m-d\TH:i:s', strtotime( '-3 days' ) ),
				],
			],
			[
				'title'   => '[TEST] Prompt Injection Attempt Blocked',
				'content' => 'A user submitted a message that attempted to override the system instructions ("Ignore all previous instructions and..."). The guardrail layer identified the injection pattern and blocked the request. No data was exposed.',
				'status'  => 'aigis-resolved',
				'meta'    => [
					'_aigis_severity'    => 'medium',
					'_aigis_detected_at' => gmdate( 'Y-m-d\TH:i:s', strtotime( '-10 days' ) ),
				],
			],
		] );
	}

	/**
	 * Insert CPT posts and tag each with test-data meta.
	 */
	private static function insert_posts( string $post_type, array $posts_data ): int {
		$count = 0;

		foreach ( $posts_data as $data ) {
			$post_id = wp_insert_post( [
				'post_type'    => $post_type,
				'post_title'   => $data['title'],
				'post_content' => $data['content'],
				'post_status'  => $data['status'],
			], true );

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, self::POST_META_KEY, '1' );

			foreach ( ( $data['meta'] ?? [] ) as $key => $value ) {
				update_post_meta( $post_id, $key, $value );
			}

			$count++;
		}

		return $count;
	}

	// -----------------------------------------------------------------------
	// DB row insertion
	// -----------------------------------------------------------------------

	/** @return array{int, int[]} */
	private static function insert_inventory(): array {
		global $wpdb;
		$table   = $wpdb->prefix . 'aigis_ai_inventory';
		$user_id = get_current_user_id();
		$ids     = [];

		$rows = [
			[
				'vendor_name'      => 'OpenAI',
				'model_name'       => 'GPT-4o',
				'model_version'    => '2024-08-06',
				'integration_type' => 'api-model',
				'api_endpoint'     => 'https://api.openai.com/v1',
				'agent_identifier' => 'test-openai-gpt4o',
				'owner_user_id'    => $user_id,
				'data_categories'  => 'customer_messages,support_tickets',
				'risk_level'       => 'high',
				'status'           => 'active',
				'notes'            => '[TEST RECORD] Used by the customer support reply workflow.',
			],
			[
				'vendor_name'      => 'Anthropic',
				'model_name'       => 'Claude 3.7 Sonnet',
				'model_version'    => '20250219',
				'integration_type' => 'api-model',
				'api_endpoint'     => 'https://api.anthropic.com/v1',
				'agent_identifier' => 'test-anthropic-claude',
				'owner_user_id'    => $user_id,
				'data_categories'  => 'policy_documents',
				'risk_level'       => 'medium',
				'status'           => 'active',
				'notes'            => '[TEST RECORD] Used by the Policy Summariser prompt.',
			],
			[
				'vendor_name'      => 'Meta',
				'model_name'       => 'LLaMA 3',
				'model_version'    => '8B',
				'integration_type' => 'on-prem',
				'api_endpoint'     => 'http://localhost:11434',
				'agent_identifier' => 'test-ollama-llama3',
				'owner_user_id'    => $user_id,
				'data_categories'  => 'internal_queries',
				'risk_level'       => 'low',
				'status'           => 'active',
				'notes'            => '[TEST RECORD] Local Ollama deployment for internal Q&A.',
			],
		];

		foreach ( $rows as $row ) {
			// Use REPLACE so re-running after a failed purge never hits the
			// UNIQUE KEY idx_agent_identifier constraint silently.
			$wpdb->replace( $table, $row );
			if ( $wpdb->insert_id ) {
				$ids[] = $wpdb->insert_id;
			}
		}

		return [ count( $ids ), $ids ];
	}

	/** @return array{int, int[]} */
	private static function insert_usage_logs( array $inventory_ids ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'aigis_usage_logs';
		$ids   = [];

		// Row format: [ agent_id, inv_idx, dept, proj, in_t, out_t, ms, cost, status, days_ago ]
		// inv_idx: 0 = GPT-4o (OpenAI), 1 = Claude (Anthropic), 2 = LLaMA (Meta on-prem)
		$rows = [
			// ── Current month rows (days 0–7) ─────────────────────────────────
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q3', 1240, 320,  1850, 0.019600, 'success',           0 ],
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q3',  980, 245,  1620, 0.015600, 'success',           1 ],
			[ 'test-anthropic-claude',1, 'Legal',            'policy-rev', 3200, 880,  2940, 0.048700, 'success',           2 ],
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q3', 1380, 410,  2100, 0.022500, 'success',           2 ],
			[ 'test-ollama-llama3',   2, 'Engineering',      'code-review',4200,1200,  3800, 0.000000, 'success',           3 ],
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q3',  760,   0,  3200, 0.000000, 'timeout',           3 ],
			[ 'test-openai-gpt4o',    0, 'Marketing',        'content',    2100, 680,  2400, 0.031200, 'success',           4 ],
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q3', 1180, 295,  1730, 0.018900, 'success',           4 ],
			[ 'test-anthropic-claude',1, 'Legal',            'policy-rev', 2800, 740,  2680, 0.042000, 'success',           5 ],
			[ 'test-ollama-llama3',   2, 'Engineering',      'code-review',3600,   0,   500, 0.000000, 'guardrail-blocked', 5 ],
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q3', 1340, 380,  1940, 0.021700, 'success',           5 ],
			[ 'test-openai-gpt4o',    0, 'Marketing',        'content',    1900, 620,  2200, 0.028200, 'success',           6 ],
			[ 'test-anthropic-claude',1, 'HR',               'hr-chatbot', 1800, 480,  2100, 0.034800, 'success',           6 ],
			[ 'test-ollama-llama3',   2, 'Engineering',      'code-review',5100,1400,  4200, 0.000000, 'success',           7 ],
			// ── Days 8–30 ─────────────────────────────────────────────────────
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q2', 1100, 280,  1720, 0.016200, 'success',           8 ],
			[ 'test-anthropic-claude',1, 'Legal',            'policy-rev', 3200, 880,  2940, 0.047200, 'success',           9 ],
			[ 'test-ollama-llama3',   2, 'Engineering',      'code-review',4200,1200,  3800, 0.000000, 'success',           9 ],
			[ 'test-openai-gpt4o',    0, 'Marketing',        'content',    2100, 680,  2400, 0.031200, 'success',          10 ],
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q2', 1240, 310,  1850, 0.019300, 'success',          10 ],
			[ 'test-anthropic-claude',1, 'HR',               'hr-chatbot', 2200, 600,  2400, 0.041500, 'success',          11 ],
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q2', 1480, 390,  1960, 0.023300, 'success',          12 ],
			[ 'test-anthropic-claude',1, 'Legal',            'policy-rev', 2800, 740,  2680, 0.040400, 'success',          13 ],
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q2',  920, 250,  1580, 0.014300, 'success',          14 ],
			[ 'test-ollama-llama3',   2, 'Engineering',      'code-review',3600,   0,   500, 0.000000, 'guardrail-blocked',15 ],
			[ 'test-openai-gpt4o',    0, 'Marketing',        'content',    1900, 620,  2200, 0.028200, 'success',          15 ],
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q2', 1360, 360,  1840, 0.021400, 'success',          16 ],
			[ 'test-anthropic-claude',1, 'Legal',            'policy-rev', 4100,1100,  3400, 0.062300, 'success',          17 ],
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q2',  830,   0,  2800, 0.000000, 'error',            18 ],
			[ 'test-anthropic-claude',1, 'HR',               'hr-chatbot', 1500, 380,  1900, 0.029100, 'success',          18 ],
			[ 'test-ollama-llama3',   2, 'Engineering',      'code-review',5100,1400,  4200, 0.000000, 'success',          19 ],
			[ 'test-openai-gpt4o',    0, 'Marketing',        'content',    2400, 780,  2700, 0.035700, 'success',          20 ],
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q1', 1200, 295,  1720, 0.018800, 'success',          20 ],
			[ 'test-anthropic-claude',1, 'Legal',            'compliance', 2200, 580,  2100, 0.031400, 'success',          21 ],
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q1',  980, 245,  1620, 0.014500, 'success',          22 ],
			[ 'test-ollama-llama3',   2, 'Engineering',      'qa-testing', 3200, 880,  2900, 0.000000, 'success',          23 ],
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q1', 1380,   0,  3200, 0.000000, 'timeout',          24 ],
			[ 'test-anthropic-claude',1, 'Legal',            'compliance', 3800, 980,  3100, 0.056600, 'success',          25 ],
			[ 'test-openai-gpt4o',    0, 'Marketing',        'social',     1400, 380,  1800, 0.020100, 'success',          25 ],
			[ 'test-anthropic-claude',1, 'HR',               'hr-chatbot', 1800, 480,  2100, 0.034800, 'success',          26 ],
			[ 'test-ollama-llama3',   2, 'Engineering',      'qa-testing', 2800, 740,  2600, 0.000000, 'success',          27 ],
			[ 'test-anthropic-claude',1, 'Legal',            'policy-rev', 2600, 700,  2500, 0.038200, 'success',          28 ],
			[ 'test-openai-gpt4o',    0, 'Marketing',        'social',     1600, 440,  1900, 0.022500, 'success',          29 ],
			[ 'test-openai-gpt4o',    0, 'Customer Support', 'support-q1', 1100, 280,  1650, 0.016200, 'success',          30 ],
		];

		foreach ( $rows as $i => $r ) {
			$inv_idx = $r[1];
			$wpdb->insert( $table, [
				'agent_id'      => $r[0],
				'inventory_id'  => $inventory_ids[ $inv_idx ] ?? 0,
				'user_id'       => 1,
				'session_id'    => 'aigis-test-session-' . $i,
				'department'    => $r[2],
				'project_tag'   => $r[3],
				'input_hash'    => md5( 'test-input-' . $i ),
				'input_tokens'  => $r[4],
				'output_tokens' => $r[5],
				'latency_ms'    => $r[6],
				'cost_usd'      => $r[7],
				'status'        => $r[8],
				'error_code'    => '',
				'logged_at'     => gmdate( 'Y-m-d H:i:s', strtotime( '-' . $r[9] . ' days' ) ),
			] );
			if ( $wpdb->insert_id ) {
				$ids[] = $wpdb->insert_id;
			}
		}

		return [ count( $ids ), $ids ];
	}

	/** @return array{int, int[]} */
	private static function insert_audit_trail(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'aigis_audit_trail';
		$ids   = [];

		$rows = [
			[ 'type' => 'user.login',          'obj_type' => 'user',      'obj_id' => '1', 'summary' => 'Administrator logged in from 127.0.0.1.',                                                       'days' => 30 ],
			[ 'type' => 'settings.saved',      'obj_type' => 'settings',  'obj_id' => '0', 'summary' => 'General settings updated by administrator.',                                                     'days' => 29 ],
			[ 'type' => 'inventory.created',   'obj_type' => 'inventory', 'obj_id' => '1', 'summary' => 'New AI model "GPT-4o" added to inventory.',                                                      'days' => 28 ],
			[ 'type' => 'policy.promoted',     'obj_type' => 'policy',    'obj_id' => '5', 'summary' => 'Policy "Acceptable Use Policy for AI Systems" promoted from draft to active.',                   'days' => 27 ],
			[ 'type' => 'policy.published',    'obj_type' => 'policy',    'obj_id' => '5', 'summary' => 'Policy "Acceptable Use Policy for AI Systems" moved to active status.',                          'days' => 26 ],
			[ 'type' => 'inventory.created',   'obj_type' => 'inventory', 'obj_id' => '2', 'summary' => 'New AI model "Claude 3.7 Sonnet" added to inventory.',                                          'days' => 25 ],
			[ 'type' => 'prompt.promoted',     'obj_type' => 'prompt',    'obj_id' => '3', 'summary' => 'Prompt "Customer Support Reply Generator" promoted from staging to production.',                 'days' => 24 ],
			[ 'type' => 'api.authFailed',      'obj_type' => 'rest_api',  'obj_id' => '0', 'summary' => 'REST API authentication failure — invalid API key from 203.0.113.45.',                          'days' => 22 ],
			[ 'type' => 'inventory.updated',   'obj_type' => 'inventory', 'obj_id' => '2', 'summary' => 'Risk level for "Claude 3.7 Sonnet" updated from low to medium.',                                'days' => 20 ],
			[ 'type' => 'prompt.created',      'obj_type' => 'prompt',    'obj_id' => '4', 'summary' => 'New prompt "Internal Knowledge Assistant" created by administrator.',                            'days' => 17 ],
			[ 'type' => 'policy.updated',      'obj_type' => 'policy',    'obj_id' => '6', 'summary' => 'Data Privacy Policy expiry date extended by 90 days.',                                          'days' => 15 ],
			[ 'type' => 'user.login',          'obj_type' => 'user',      'obj_id' => '1', 'summary' => 'Administrator logged in from 127.0.0.1.',                                                       'days' => 15 ],
			[ 'type' => 'incident.opened',     'obj_type' => 'incident',  'obj_id' => '8', 'summary' => 'New incident "PII Detected in Customer Support Output" opened. Severity: high.',                'days' => 10 ],
			[ 'type' => 'incident.updated',    'obj_type' => 'incident',  'obj_id' => '8', 'summary' => 'Incident status changed from "open" to "investigating". Assigned to administrator.',            'days' => 9  ],
			[ 'type' => 'budget.alertFired',   'obj_type' => 'budget',    'obj_id' => '1', 'summary' => 'Budget "Customer Support — Monthly" crossed the 80% spending threshold.',                       'days' => 5  ],
			[ 'type' => 'guardrail.triggered', 'obj_type' => 'guardrail', 'obj_id' => '0', 'summary' => 'Prompt injection attempt blocked for user ID 1. Rule: ignore_instructions_kw.',                'days' => 3  ],
		];

		foreach ( $rows as $r ) {
			$wpdb->insert( $table, [
				'event_type'    => $r['type'],
				'object_type'   => $r['obj_type'],
				'object_id'     => $r['obj_id'],
				'actor_user_id' => 1,
				'actor_ip'      => '127.0.0.1',
				'summary'       => $r['summary'],
				'before_state'  => '{}',
				'after_state'   => '{}',
				'occurred_at'   => gmdate( 'Y-m-d H:i:s', strtotime( '-' . $r['days'] . ' days' ) ),
			] );
			if ( $wpdb->insert_id ) {
				$ids[] = $wpdb->insert_id;
			}
		}

		return [ count( $ids ), $ids ];
	}

	/** @return array{int, int[]} */
	private static function insert_cost_budgets( array $inventory_ids ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'aigis_cost_budgets';
		$ids   = [];

		// Budget amounts are intentionally small for demo purposes so that the
		// test usage logs drive a meaningful utilisation percentage on the UI.
		$rows = [
			[
				'label'         => '[TEST] Customer Support — Monthly',
				'scope_type'    => 'department',
				'scope_value'   => 'Customer Support',
				'inventory_id'  => $inventory_ids[0] ?? 0,
				'period_type'   => 'monthly',
				'period_start'  => gmdate( 'Y-m-01' ),
				'period_end'    => gmdate( 'Y-m-t' ),
				'budget_usd'    => 0.30,
				'alert_pct_80'  => 1,
				'alert_pct_100' => 1,
				'created_by'    => 1,
			],
			[
				'label'         => '[TEST] Legal & Compliance — Monthly',
				'scope_type'    => 'department',
				'scope_value'   => 'Legal',
				'inventory_id'  => $inventory_ids[1] ?? 0,
				'period_type'   => 'monthly',
				'period_start'  => gmdate( 'Y-m-01' ),
				'period_end'    => gmdate( 'Y-m-t' ),
				'budget_usd'    => 0.40,
				'alert_pct_80'  => 1,
				'alert_pct_100' => 1,
				'created_by'    => 1,
			],
			[
				'label'         => '[TEST] Global AI Budget — Monthly',
				'scope_type'    => 'global',
				'scope_value'   => '',
				'inventory_id'  => 0,
				'period_type'   => 'monthly',
				'period_start'  => gmdate( 'Y-m-01' ),
				'period_end'    => gmdate( 'Y-m-t' ),
				'budget_usd'    => 1.00,
				'alert_pct_80'  => 1,
				'alert_pct_100' => 1,
				'created_by'    => 1,
			],
		];

		foreach ( $rows as $row ) {
			$wpdb->insert( $table, $row );
			if ( $wpdb->insert_id ) {
				$ids[] = $wpdb->insert_id;
			}
		}

		return [ count( $ids ), $ids ];
	}

	/** @return array{int, int[]} */
	private static function insert_eval_results( array $inventory_ids ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'aigis_eval_results';
		$ids   = [];
		$inv   = $inventory_ids[0] ?? 0;

		// 13 pass / 3 fail / 2 pending-review → ~76% pass rate (excludes pending).
		$samples = [
			[ 'hash' => 'e1a2b3c4', 'pass' => 'pass',           'fn' => 0, 'note' => 'Output accurately matched expected intent. No PII present.',                         'days' => 20 ],
			[ 'hash' => 'e2b3c4d5', 'pass' => 'pass',           'fn' => 0, 'note' => 'Tone appropriate; clear and helpful response.',                                       'days' => 19 ],
			[ 'hash' => 'e3c4d5e6', 'pass' => 'fail',           'fn' => 0, 'note' => 'Response included an unverified factual claim.',                                      'days' => 18 ],
			[ 'hash' => 'e4d5e6f7', 'pass' => 'pass',           'fn' => 0, 'note' => 'PII correctly omitted from output.',                                                  'days' => 17 ],
			[ 'hash' => 'e5e6f7g8', 'pass' => 'pending-review', 'fn' => 0, 'note' => 'Ambiguous boundary case — sent for human review.',                                   'days' => 16 ],
			[ 'hash' => 'e6f7g8h9', 'pass' => 'fail',           'fn' => 1, 'note' => 'False negative — potentially harmful framing not caught by automated check.',        'days' => 15 ],
			[ 'hash' => 'e7g8h9i0', 'pass' => 'pass',           'fn' => 0, 'note' => 'Correctly refused out-of-scope request.',                                            'days' => 14 ],
			[ 'hash' => 'e8h9i0j1', 'pass' => 'pass',           'fn' => 0, 'note' => 'Accurate summarisation; no sensitive data leaked.',                                  'days' => 13 ],
			[ 'hash' => 'e9i0j1k2', 'pass' => 'pass',           'fn' => 0, 'note' => 'On-brand tone; content policy compliant.',                                           'days' => 12 ],
			[ 'hash' => 'e0j1k2l3', 'pass' => 'pass',           'fn' => 0, 'note' => 'Correct escalation to human agent triggered.',                                       'days' => 11 ],
			[ 'hash' => 'f1k2l3m4', 'pass' => 'fail',           'fn' => 0, 'note' => 'Hallucinated a company policy that does not exist.',                                 'days' => 10 ],
			[ 'hash' => 'f2l3m4n5', 'pass' => 'pass',           'fn' => 0, 'note' => 'Response appropriately declined with a safe alternative offered.',                  'days' => 9  ],
			[ 'hash' => 'f3m4n5o6', 'pass' => 'pass',           'fn' => 0, 'note' => 'Policy-document summary was accurate and complete.',                                 'days' => 8  ],
			[ 'hash' => 'f4n5o6p7', 'pass' => 'pending-review', 'fn' => 0, 'note' => 'Borderline case — cultural sensitivity concern flagged for human review.',           'days' => 7  ],
			[ 'hash' => 'f5o6p7q8', 'pass' => 'pass',           'fn' => 0, 'note' => 'Code review output: no security anti-patterns recommended.',                        'days' => 6  ],
			[ 'hash' => 'f6p7q8r9', 'pass' => 'pass',           'fn' => 0, 'note' => 'Correct response; cited internal documentation accurately.',                        'days' => 5  ],
			[ 'hash' => 'f7q8r9s0', 'pass' => 'pass',           'fn' => 0, 'note' => 'HR policy query answered correctly with appropriate caveats.',                      'days' => 3  ],
			[ 'hash' => 'f8r9s0t1', 'pass' => 'pass',           'fn' => 0, 'note' => 'Prompt injection attempt detected and blocked at evaluation layer.',                 'days' => 1  ],
		];

		foreach ( $samples as $s ) {
			$wpdb->insert( $table, [
				'agent_id'          => 'test-openai-gpt4o',
				'inventory_id'      => $inv,
				'input_hash'        => $s['hash'],
				'expected_output'   => 'Expected: helpful, safe, policy-compliant response.',
				'actual_output'     => 'Test case output — ' . $s['note'],
				'evaluator_version' => '1.0',
				'pass_fail'         => $s['pass'],
				'false_negative'    => $s['fn'],
				'reviewer_id'       => 0,
				'reviewer_notes'    => $s['note'],
				'rulebook_version'  => '1.0',
				'submitted_at'      => gmdate( 'Y-m-d H:i:s', strtotime( '-' . $s['days'] . ' days' ) ),
			] );
			if ( $wpdb->insert_id ) {
				$ids[] = $wpdb->insert_id;
			}
		}

		return [ count( $ids ), $ids ];
	}

	/** @return array{int, int[]} */
	private static function insert_guardrail_triggers( array $inventory_ids ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'aigis_guardrail_triggers';
		$ids   = [];
		$inv   = $inventory_ids[0] ?? 0;

		$rows = [
			[ 'name' => 'PII Blocker',           'rule' => 'email_address_regex',     'tax' => 'pii-exposure',     'kw' => 0, 'sev' => 'high' ],
			[ 'name' => 'Prompt Injection Guard', 'rule' => 'ignore_instructions_kw',  'tax' => 'prompt-injection', 'kw' => 1, 'sev' => 'critical' ],
			[ 'name' => 'Harmful Content Filter', 'rule' => 'harm_taxonomy_v2',        'tax' => 'harmful-content',  'kw' => 0, 'sev' => 'high' ],
			[ 'name' => 'PII Blocker',            'rule' => 'phone_number_regex',      'tax' => 'pii-exposure',     'kw' => 0, 'sev' => 'medium' ],
			[ 'name' => 'Data Exfil Guard',       'rule' => 'secrets_pattern_v1',      'tax' => 'data-exfiltration','kw' => 0, 'sev' => 'critical' ],
		];

		foreach ( $rows as $i => $r ) {
			$wpdb->insert( $table, [
				'agent_id'        => 'test-openai-gpt4o',
				'inventory_id'    => $inv,
				'guardrail_name'  => $r['name'],
				'input_hash'      => md5( 'guardrail-test-' . $i ),
				'matched_rule'    => $r['rule'],
				'risk_taxonomy'   => $r['tax'],
				'is_keyword_only' => $r['kw'],
				'severity'        => $r['sev'],
				'triggered_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( 15 - $i * 2 ) . ' days' ) ),
			] );
			if ( $wpdb->insert_id ) {
				$ids[] = $wpdb->insert_id;
			}
		}

		return [ count( $ids ), $ids ];
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Delete rows from a table by an array of primary key IDs.
	 * Returns the number of rows deleted (or 0 if the ID list was empty).
	 */
	private static function purge_by_ids( \wpdb $wpdb, array $ids, string $table ): int {
		if ( empty( $ids ) ) {
			return 0;
		}
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query(
			$wpdb->prepare( "DELETE FROM `{$table}` WHERE id IN ({$placeholders})", $ids )
		);
	}

	// -----------------------------------------------------------------------
	// CPT deletion
	// -----------------------------------------------------------------------

	private static function delete_test_posts(): int {
		$deleted    = 0;
		$post_types = [ 'aigis_prompt', 'aigis_policy', 'aigis_workflow', 'aigis_incident' ];

		foreach ( $post_types as $pt ) {
			$q = new WP_Query( [
				'post_type'      => $pt,
				'post_status'    => 'any',
				'meta_key'       => self::POST_META_KEY,
				'meta_value'     => '1',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			] );

			foreach ( $q->posts as $post_id ) {
				wp_delete_post( (int) $post_id, true );
				$deleted++;
			}
		}

		return $deleted;
	}
}
