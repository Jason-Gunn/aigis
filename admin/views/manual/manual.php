<?php
/**
 * User Manual view.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tabs = [
	'overview'       => __( 'Overview',               'ai-governance-suite' ),
	'getting-started'=> __( 'Getting Started',        'ai-governance-suite' ),
	'inventory'      => __( 'AI Inventory',           'ai-governance-suite' ),
	'prompts'        => __( 'Prompts',                'ai-governance-suite' ),
	'policies'       => __( 'Policies',               'ai-governance-suite' ),
	'workflows'      => __( 'Workflows',              'ai-governance-suite' ),
	'skills'         => __( 'Skills',                 'ai-governance-suite' ),
	'incidents'      => __( 'Incidents',              'ai-governance-suite' ),
	'analytics'      => __( 'Analytics &amp; Cost',   'ai-governance-suite' ),
	'evaluation'     => __( 'Stress Tests &amp; Eval','ai-governance-suite' ),
	'audit'          => __( 'Audit Log',              'ai-governance-suite' ),
	'rest-api'       => __( 'REST API',               'ai-governance-suite' ),
	'roles'          => __( 'Roles &amp; Permissions','ai-governance-suite' ),
];

$active_tab = isset( $_GET['manual_tab'] ) ? sanitize_key( $_GET['manual_tab'] ) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification
if ( ! array_key_exists( $active_tab, $tabs ) ) {
	$active_tab = 'overview';
}
?>
<div class="wrap aigis-manual">

	<h1 class="wp-heading-inline"><?php esc_html_e( 'AI Governance Suite — User Manual', 'ai-governance-suite' ); ?></h1>
	<hr class="wp-header-end">

	<div style="display:flex;gap:0;margin-top:1.5rem;align-items:flex-start">

	<nav style="flex:0 0 200px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:8px 0;margin-right:24px;margin-top:20px;">
		<?php foreach ( $tabs as $slug => $label ) :
			$url = add_query_arg( [ 'manual_tab' => $slug ] );
			$is_active = $active_tab === $slug;
		?>
			<a href="<?php echo esc_url( $url ); ?>" style="display:block;padding:8px 16px;font-size:.875rem;text-decoration:none;border-left:3px solid <?php echo $is_active ? '#2271b1' : 'transparent'; ?>;color:<?php echo $is_active ? '#2271b1' : '#1d2327'; ?>;font-weight:<?php echo $is_active ? '600' : '400'; ?>;background:<?php echo $is_active ? '#f0f6fc' : 'transparent'; ?>">
				<?php echo $label; /* translators: already escaped */ ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="aigis-manual-body card" style="flex:1;min-width:0;max-width:800px;padding:1rem 2rem 2.5rem;">

	<?php // ----------------------------------------------------------------
	//  OVERVIEW
	// ----------------------------------------------------------------
	if ( 'overview' === $active_tab ) : ?>

		<h2><?php esc_html_e( 'Overview', 'ai-governance-suite' ); ?></h2>
		<p><?php esc_html_e( 'AI Governance and Infrastructure Suite (AIGIS) is a WordPress plugin that provides a centralised framework for managing, auditing, and governing AI models used within your organisation.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'What AIGIS Does', 'ai-governance-suite' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Maintains a register of every AI model integrated into your workflows (AI Inventory).', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Tracks prompt usage, token consumption, latency, and costs across departments and projects.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Manages AI policies, their lifecycle states, and their enforce-by dates.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Documents approved AI workflows using Mermaid diagrams for visual clarity.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Stores reusable agent skills with readiness scoring, lifecycle review, markdown import/export, and links to prompts, workflows, policies, incidents, and inventory records.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Logs incidents — from PII leakage to prompt injection attempts — and tracks their investigation status.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Enforces real-time guardrails that block harmful, injected, or policy-violating inputs and outputs.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Runs stress tests and evaluates AI outputs against expected results, including false-negative detection.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Provides a full immutable audit trail of every consequential action.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Exposes a secured REST API for integrating external systems.', 'ai-governance-suite' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'Architecture', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'AIGIS is structured around four main layers:', 'ai-governance-suite' ); ?></p>
		<ol>
			<li><strong><?php esc_html_e( 'Custom Post Types', 'ai-governance-suite' ); ?></strong> — <?php esc_html_e( 'Prompts, Policies, Workflows, Skills, and Incidents are first-class WordPress content objects, stored in the standard posts table.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'Custom Database Tables', 'ai-governance-suite' ); ?></strong> — <?php esc_html_e( 'High-volume structured data (usage logs, audit trail, inventory, cost budgets, evaluation results, guardrail triggers) lives in dedicated tables for query performance.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'Admin Interface', 'ai-governance-suite' ); ?></strong> — <?php esc_html_e( 'A full WordPress admin menu with dedicated pages for each section, role-based access, and a unified dashboard.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'REST API', 'ai-governance-suite' ); ?></strong> — <?php esc_html_e( 'A secured API layer for external agents, pipelines, and integration scripts to submit logs, check routing, test guardrails, and submit evaluations.', 'ai-governance-suite' ); ?></li>
		</ol>

		<h3><?php esc_html_e( 'Key Concepts', 'ai-governance-suite' ); ?></h3>
		<table class="wp-list-table widefat striped">
			<thead><tr><th><?php esc_html_e( 'Term', 'ai-governance-suite' ); ?></th><th><?php esc_html_e( 'Definition', 'ai-governance-suite' ); ?></th></tr></thead>
			<tbody>
				<tr><td><strong><?php esc_html_e( 'Agent Identifier', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'A unique slug that identifies a specific AI agent or model deployment. Used to correlate logs, evaluations, and guardrail triggers across APIs and admin sections.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Inventory', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'The register of all AI models — their vendor, version, integration type, risk level, and operational status.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Guardrail', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'A real-time input/output check that can block a request. Guardrail triggers are logged for review.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Evaluation', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'A structured comparison of expected vs actual AI output, scored as pass / fail / pending-review. False negatives are flagged separately.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Prompt Stage', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'The lifecycle state of a prompt: development → staging → production. Promotion requires appropriate capability.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Skill', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'A reusable instruction bundle that captures when an agent should use a capability, what output it should return, and what related assets or safeguards it depends on.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Cost Budget', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'A spending limit applied to a department, project, or globally, for a monthly or custom period. Alerts fire at 80% and 100%.', 'ai-governance-suite' ); ?></td></tr>
			</tbody>
		</table>

	<?php // ----------------------------------------------------------------
	//  GETTING STARTED
	// ----------------------------------------------------------------
	elseif ( 'getting-started' === $active_tab ) : ?>

		<h2><?php esc_html_e( 'Getting Started', 'ai-governance-suite' ); ?></h2>

		<h3><?php esc_html_e( '1. Activate the Plugin', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'Install and activate the AI Governance Suite plugin from the WordPress Plugins screen. On activation, AIGIS automatically:', 'ai-governance-suite' ); ?></p>
		<ul>
			<li><?php esc_html_e( 'Creates 6 custom database tables under the wp_ prefix.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Registers 5 Custom Post Types (Prompts, Policies, Workflows, Skills, Incidents).', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Assigns 4 custom roles with granular capabilities.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Schedules the cost-budget alert cron job.', 'ai-governance-suite' ); ?></li>
		</ul>

		<h3><?php esc_html_e( '2. Enter an API Key', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'Navigate to AI Governance → Settings → General and enter your primary AI provider API key. This key is stored as a WordPress option and used for provider connectivity tests from the Settings page.', 'ai-governance-suite' ); ?></p>
		<p class="notice notice-info inline" style="padding:.5em 1em"><?php esc_html_e( 'The API key is used only for sandbox/test calls made from the admin. Your integration code uses its own credentials via the REST API endpoints.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( '3. Register Your First AI Model', 'ai-governance-suite' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Go to AI Governance → AI Inventory → Add New.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Enter the vendor name, model name, version, and a unique Agent Identifier. The identifier is a permanent slug — choose it carefully.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Select the integration type: API Model, Custom Agent, or On-Premises.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Set the risk level (Low, Medium, High) based on what data the model processes.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Save. The model is now available for association with prompts, logs, budgets, and evaluations.', 'ai-governance-suite' ); ?></li>
		</ol>

		<h3><?php esc_html_e( '4. Explore with Test Data', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'You can populate every section of the plugin with realistic sample data to explore the interface without real production records.', 'ai-governance-suite' ); ?></p>
		<p><?php esc_html_e( 'Go to AI Governance → Settings → Developer Tools and click "Generate Test Data". This creates sample inventory records, prompts, policies, workflows, skills, incidents, usage logs, audit entries, cost budgets, evaluation results, and guardrail triggers.', 'ai-governance-suite' ); ?></p>
		<p><?php esc_html_e( 'Use "Remove Test Data" when you are done exploring. Test data is tracked precisely and will not touch any real records.', 'ai-governance-suite' ); ?></p>

	<?php // ----------------------------------------------------------------
	//  AI INVENTORY
	// ----------------------------------------------------------------
	elseif ( 'inventory' === $active_tab ) : ?>

		<h2><?php esc_html_e( 'AI Inventory', 'ai-governance-suite' ); ?></h2>
		<p><?php esc_html_e( 'The AI Inventory is your organisation\'s official register of all AI models and agents. Every usage log, cost budget, evaluation result, and guardrail trigger is linked to an inventory record via the Agent Identifier.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Fields Reference', 'ai-governance-suite' ); ?></h3>
		<table class="wp-list-table widefat striped">
			<thead><tr><th><?php esc_html_e( 'Field', 'ai-governance-suite' ); ?></th><th><?php esc_html_e( 'Description', 'ai-governance-suite' ); ?></th></tr></thead>
			<tbody>
				<tr><td><strong><?php esc_html_e( 'Vendor Name', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'The provider or creator of the model (e.g. OpenAI, Anthropic, Meta).', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Model Name', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'The official name of the model (e.g. GPT-4o, Claude 3 Opus, LLaMA 3).', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Version', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'The specific version or checkpoint. Updates here signal a change that may need re-evaluation.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Agent Identifier', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'Unique, permanent slug. Used in all REST API calls and cross-references. Cannot be reused after deletion.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Integration Type', 'ai-governance-suite' ); ?></strong></td><td><?php echo esc_html__( 'How the model is accessed:', 'ai-governance-suite' ) . ' <em>API Model</em> — ' . esc_html__( 'cloud API, ', 'ai-governance-suite' ) . '<em>Custom Agent</em> — ' . esc_html__( 'bespoke orchestration, ', 'ai-governance-suite' ) . '<em>On-Premises</em> — ' . esc_html__( 'self-hosted (e.g. Ollama).', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'API Endpoint', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'The URL of the API or local service. Used for documentation; AIGIS does not call this directly.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Data Categories', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'What categories of data this model processes (e.g. customer_messages, pii, internal_docs). Used for policy mapping.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Risk Level', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'Low, Medium, or High — based on data sensitivity, blast radius if misused, and regulatory exposure.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Status', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'Active, Deprecated, or Under Review. Deprecated models are retained for historical log linkage.', 'ai-governance-suite' ); ?></td></tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Managing Risk Levels', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'Assign risk levels using these criteria:', 'ai-governance-suite' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Low:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Processes only non-sensitive internal data; no PII; limited downstream impact.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'Medium:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Processes business-sensitive data; limited PII; moderate downstream impact; subject to internal policy.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'High:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Processes personal data, regulated data, or is used in customer-facing outputs. Full audit. Human review of outputs required.', 'ai-governance-suite' ); ?></li>
		</ul>

	<?php // ----------------------------------------------------------------
	//  PROMPTS
	// ----------------------------------------------------------------
	elseif ( 'prompts' === $active_tab ) : ?>

		<h2><?php esc_html_e( 'Prompts', 'ai-governance-suite' ); ?></h2>
		<p><?php esc_html_e( 'Prompts are system instructions or templates for your AI models. AIGIS treats prompts as version-controlled, lifecycle-managed content objects with distinct promotion stages.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Lifecycle Stages', 'ai-governance-suite' ); ?></h3>
		<table class="wp-list-table widefat striped">
			<thead><tr><th><?php esc_html_e( 'Stage', 'ai-governance-suite' ); ?></th><th><?php esc_html_e( 'Meaning', 'ai-governance-suite' ); ?></th><th><?php esc_html_e( 'Who Can Promote', 'ai-governance-suite' ); ?></th></tr></thead>
			<tbody>
				<tr><td><strong><?php esc_html_e( 'Development', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'Being authored or iteration-tested. Not approved for active use.', 'ai-governance-suite' ); ?></td><td><?php esc_html_e( 'Prompt Manager, Administrator', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Staging', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'Under review for quality, policy compliance, and safety. May be tested against real inputs in a sandbox.', 'ai-governance-suite' ); ?></td><td><?php esc_html_e( 'Prompt Manager, Administrator', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Production', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'Approved for live use. Any modification should move it back to staging.', 'ai-governance-suite' ); ?></td><td><?php esc_html_e( 'Administrator only', 'ai-governance-suite' ); ?></td></tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Sandbox Testing', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'From the Prompts list, click "Test in Sandbox" next to any prompt. This sends the prompt with a sample input to your configured provider and displays the response inline. Sandbox calls are logged as usage_logs with the session_id prefix "sandbox-".', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Promotion Workflow', 'ai-governance-suite' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Author writes prompt in Development stage.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Reviewer tests via sandbox, then promotes to Staging.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Administrator reviews staging version, confirms it meets policy, promotes to Production.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Every promotion is recorded in the Audit Log.', 'ai-governance-suite' ); ?></li>
		</ol>

		<h3><?php esc_html_e( 'Prompt Metadata', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'Each prompt stores: associated provider, target model, temperature, max tokens, and a version number. Version numbers are free-form text — use semantic versioning (1.0, 1.1) for clarity.', 'ai-governance-suite' ); ?></p>

	<?php // ----------------------------------------------------------------
	//  POLICIES
	// ----------------------------------------------------------------
	elseif ( 'policies' === $active_tab ) : ?>

		<h2><?php esc_html_e( 'Policies', 'ai-governance-suite' ); ?></h2>
		<p><?php esc_html_e( 'Policies are formal governance documents that define how AI systems must be used, what is prohibited, and what consequences apply to violations. AIGIS tracks their lifecycle from draft through to expiry.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Policy Statuses', 'ai-governance-suite' ); ?></h3>
		<ul>
			<li><strong><?php esc_html_e( 'Draft:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Under authorship. Not in effect.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'Active:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'In force. Effective date has passed; expiry date has not.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'Expired:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Past expiry date. Must be renewed or archived before a replacement takes effect.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'Archived:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Retired. Kept for audit history.', 'ai-governance-suite' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'Expiry Alerts', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'AIGIS sends admin email notifications when a policy is approaching its expiry date. Configure the notification lead time in Settings → Notifications. The Dashboard will also display a prominent warning for policies expiring within the alert window.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Best Practices', 'ai-governance-suite' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Review policies annually at minimum, or whenever an AI model is added, changed, or deprecated.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Assign an owner (staff member) to each policy. Their user ID is stored so responsibility is traceable.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'For high-risk models, have policies reviewed by your Data Protection Officer or legal team before activation.', 'ai-governance-suite' ); ?></li>
		</ul>

	<?php // ----------------------------------------------------------------
	//  WORKFLOWS
	// ----------------------------------------------------------------
	elseif ( 'workflows' === $active_tab ) : ?>

		<h2><?php esc_html_e( 'Workflows', 'ai-governance-suite' ); ?></h2>
		<p><?php esc_html_e( 'Workflows document the approved processes for AI-assisted tasks. They provide a visual map of how data flows through AI systems, where human oversight is required, and what automated decisions are permissible.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Mermaid Diagrams', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'Each workflow can include a Mermaid diagram definition. AIGIS renders these as interactive flow diagrams in the admin. Common node types:', 'ai-governance-suite' ); ?></p>
		<table class="wp-list-table widefat striped">
			<thead><tr><th><?php esc_html_e( 'Mermaid Syntax', 'ai-governance-suite' ); ?></th><th><?php esc_html_e( 'Renders as', 'ai-governance-suite' ); ?></th></tr></thead>
			<tbody>
				<tr><td><code>A[User Input]</code></td><td><?php esc_html_e( 'Rectangle — a process or step.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><code>B{Decision}</code></td><td><?php esc_html_e( 'Diamond — a branch point.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><code>C((API Call))</code></td><td><?php esc_html_e( 'Circle — an external system or API.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><code>A --> B</code></td><td><?php esc_html_e( 'Arrow — flow direction.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><code>A -->|condition| B</code></td><td><?php esc_html_e( 'Labelled arrow — conditional flow.', 'ai-governance-suite' ); ?></td></tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Workflow Statuses', 'ai-governance-suite' ); ?></h3>
		<ul>
			<li><strong><?php esc_html_e( 'Active:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Approved for use by the designated teams.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'Draft:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Being designed or reviewed.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'Deprecated:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Superseded by a newer workflow. Retained for historical reference.', 'ai-governance-suite' ); ?></li>
		</ul>

	<?php // ----------------------------------------------------------------
	//  SKILLS
	// ----------------------------------------------------------------
	elseif ( 'skills' === $active_tab ) : ?>

		<h2><?php esc_html_e( 'Skills', 'ai-governance-suite' ); ?></h2>
		<p><?php esc_html_e( 'The Skills module stores reusable agent capabilities as governed instruction assets. Each skill defines when it should be triggered, what output shape it must produce, what edge cases need human judgement, and which prompts, workflows, policies, incidents, and inventory records it depends on.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'What a Skill Contains', 'ai-governance-suite' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'A short description that explains the trigger and intent of the capability.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'The main instruction body in the editor, which captures the methodology or procedure the agent should follow.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Trigger phrases, output contract, edge cases, examples, format, and owning team metadata.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Links to prompts, workflows, policies, incidents, and an optional inventory record for operational context.', 'ai-governance-suite' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'Lifecycle and Review', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'Skills move through Draft, Pending Review, Staging, and Approved. Readiness scoring highlights whether the skill has enough structure to be safely reused in real environments. Missing descriptions, empty instruction bodies, or weak output contracts will lower the score and block production readiness.', 'ai-governance-suite' ); ?></p>
		<ol>
			<li><?php esc_html_e( 'Draft the skill with a concrete description, clear trigger phrases, and an explicit output contract.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Use Pending Review when another operator needs to verify the methodology, linked assets, and exception handling.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Move the skill to Staging while you test it with realistic prompts, workflows, and inventory context.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Approve the skill only after the readiness checks are green and the reviewer is satisfied that the instructions are production-safe.', 'ai-governance-suite' ); ?></li>
		</ol>

		<h3><?php esc_html_e( 'Markdown Import and Export', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'Each skill can be exported as markdown for external review or versioning. The same markdown can be pasted back into the import box on the skill editor to round-trip changes into WordPress. This is useful when a governance team prefers reviewing structured markdown outside the admin UI before applying edits.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Relationship Mapping', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'Link related prompts, workflows, policies, and incidents whenever the skill depends on them. These references make review easier, improve traceability during incidents, and expose the surrounding governance context when the skill is fetched through the REST API.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Best Practices', 'ai-governance-suite' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Keep the description single-line and specific so routing remains reliable.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Treat the output contract as mandatory. Name the exact sections, fields, or format the agent must return.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Capture edge cases explicitly instead of assuming reviewers will infer them later.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Use markdown export for peer review, but only approve a skill after re-imported content has been validated in the editor.', 'ai-governance-suite' ); ?></li>
		</ul>

	<?php // ----------------------------------------------------------------
	//  INCIDENTS
	// ----------------------------------------------------------------
	elseif ( 'incidents' === $active_tab ) : ?>

		<h2><?php esc_html_e( 'Incidents', 'ai-governance-suite' ); ?></h2>
		<p><?php esc_html_e( 'The Incidents module is a structured log for recording and investigating events where an AI system behaved incorrectly, dangerously, or unexpectedly.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'When to Open an Incident', 'ai-governance-suite' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'PII or sensitive data appeared in an AI output.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'A guardrail was bypassed by a crafted input.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'An AI model generated harmful, inaccurate, or misleading content that reached a user.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'A cost spike or unexpected usage pattern was detected.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'A model version change introduced a regression.', 'ai-governance-suite' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'Severity Levels', 'ai-governance-suite' ); ?></h3>
		<ul>
			<li><strong><?php esc_html_e( 'Low:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Quality degradation with no data or safety risk.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'Medium:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Potential policy violation or near-miss. Investigation required.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'High:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Confirmed policy violation, data exposure, or safety risk. Escalate to DPO/legal.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'Critical:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Material breach. Immediate action, potential regulatory notification.', 'ai-governance-suite' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'Investigation Workflow', 'ai-governance-suite' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Open the incident: set severity, detection timestamp, and risk type.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Link to the relevant AI inventory record if known.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Cross-reference the Audit Log for the event timeline.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Document findings in the incident body text.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'If a guardrail gap is identified — update the guardrail rules and run a stress test.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Mark as Resolved once remediation is confirmed.', 'ai-governance-suite' ); ?></li>
		</ol>

	<?php // ----------------------------------------------------------------
	//  ANALYTICS & COST
	// ----------------------------------------------------------------
	elseif ( 'analytics' === $active_tab ) : ?>

		<h2><?php esc_html_e( 'Analytics &amp; Cost', 'ai-governance-suite' ); ?></h2>
		<p><?php esc_html_e( 'The Analytics section provides KPI cards, usage trends, and cost breakdowns derived from the usage_logs table. Every API call your integration logs via the REST /log endpoint is reflected here.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Dashboard KPIs', 'ai-governance-suite' ); ?></h3>
		<table class="wp-list-table widefat striped">
			<thead><tr><th><?php esc_html_e( 'KPI', 'ai-governance-suite' ); ?></th><th><?php esc_html_e( 'Source', 'ai-governance-suite' ); ?></th></tr></thead>
			<tbody>
				<tr><td><?php esc_html_e( 'Total API Calls (30d)', 'ai-governance-suite' ); ?></td><td><?php esc_html_e( 'COUNT of usage_logs rows in the last 30 days.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Total Cost (30d)', 'ai-governance-suite' ); ?></td><td><?php esc_html_e( 'SUM of cost_usd in usage_logs for the last 30 days.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Average Latency', 'ai-governance-suite' ); ?></td><td><?php esc_html_e( 'AVG of latency_ms for successful calls in the last 30 days.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Guardrail Triggers (30d)', 'ai-governance-suite' ); ?></td><td><?php esc_html_e( 'COUNT of aigis_guardrail_triggers rows in the last 30 days.', 'ai-governance-suite' ); ?></td></tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Cost Budgets', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'Set spending limits for specific departments, projects, or globally. When cumulative cost_usd for the budget\'s scope and period crosses 80% or 100% of the budget amount, AIGIS fires an alert email to the admin and logs the event in the audit trail.', 'ai-governance-suite' ); ?></p>
		<p><?php esc_html_e( 'Budgets are matched to usage logs by:', 'ai-governance-suite' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Department scope:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Matches usage_logs WHERE department = scope_value.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'Project scope:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'Matches usage_logs WHERE project_tag = scope_value.', 'ai-governance-suite' ); ?></li>
			<li><strong><?php esc_html_e( 'Global scope:', 'ai-governance-suite' ); ?></strong> <?php esc_html_e( 'All usage logs within the period.', 'ai-governance-suite' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'Budget checks run on a scheduled cron job (every 6 hours by default).', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Logging Usage via REST API', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'Your integration code logs a call with:', 'ai-governance-suite' ); ?></p>
		<pre style="background:#f0f0f1;padding:1em;overflow:auto"><code>POST /wp-json/aigis/v1/log
X-AIGIS-API-Key: &lt;your-key&gt;

{
  "agent_id":       "my-agent-slug",
  "user_id":         1,
  "department":      "Engineering",
  "project_tag":     "my-project",
  "input_tokens":    1200,
  "output_tokens":   320,
  "latency_ms":      1840,
  "cost_usd":        0.0188,
  "status":          "success"
}</code></pre>

	<?php // ----------------------------------------------------------------
	//  STRESS TESTS & EVALUATION
	// ----------------------------------------------------------------
	elseif ( 'evaluation' === $active_tab ) : ?>

		<h2><?php esc_html_e( 'Stress Tests &amp; Evaluation', 'ai-governance-suite' ); ?></h2>
		<p><?php esc_html_e( 'The Evaluation module lets you verify AI model quality through structured tests: submit an input, compare the actual output against an expected output, and record a pass/fail score.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Evaluation Results Fields', 'ai-governance-suite' ); ?></h3>
		<table class="wp-list-table widefat striped">
			<thead><tr><th><?php esc_html_e( 'Field', 'ai-governance-suite' ); ?></th><th><?php esc_html_e( 'Description', 'ai-governance-suite' ); ?></th></tr></thead>
			<tbody>
				<tr><td><strong><?php esc_html_e( 'Input Hash', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'MD5 hash of the test input. Used to correlate re-runs of the same test.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Expected Output', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'What a correct, policy-compliant response should contain or avoid.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Actual Output', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'The verbatim model output from the test run.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Pass / Fail', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'pass, fail, or pending-review (routed to a human reviewer).', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'False Negative', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'Flagged when the model failed to catch something it should have — e.g., produced harmful content without the guardrail triggering.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Evaluator Version', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'The version of the scoring rulebook used. Update this when you change evaluation criteria.', 'ai-governance-suite' ); ?></td></tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'False Negative Detection', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'A false negative is when a model produces output that should have been blocked or flagged but was not. Mark results as false_negative = 1 when this occurs. These are surfaced separately in the Evaluation list view for targeted remediation.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Submitting Results via REST API', 'ai-governance-suite' ); ?></h3>
		<pre style="background:#f0f0f1;padding:1em;overflow:auto"><code>POST /wp-json/aigis/v1/eval
X-AIGIS-API-Key: &lt;your-key&gt;

{
  "agent_id":          "my-agent-slug",
  "input_hash":        "abc123",
  "expected_output":   "Safe, accurate, helpful response.",
  "actual_output":     "The actual model response text.",
  "pass_fail":         "pass",
  "false_negative":    false,
  "evaluator_version": "1.0"
}</code></pre>

	<?php // ----------------------------------------------------------------
	//  AUDIT LOG
	// ----------------------------------------------------------------
	elseif ( 'audit' === $active_tab ) : ?>

		<h2><?php esc_html_e( 'Audit Log', 'ai-governance-suite' ); ?></h2>
		<p><?php esc_html_e( 'The Audit Log is an append-only record of every consequential action within AIGIS. It cannot be edited or deleted via the admin interface.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'What Is Logged', 'ai-governance-suite' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'User actions: login, settings saves, prompt promotions, policy status changes.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Inventory changes: model creation, deprecation.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Incident lifecycle events: opened, status changed, resolved.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Budget alerts: 80% and 100% threshold crossings.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'REST API authentication failures.', 'ai-governance-suite' ); ?></li>
			<li><?php esc_html_e( 'Test data generated and purged (Developer Tools).', 'ai-governance-suite' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'Reading the Audit Trail', 'ai-governance-suite' ); ?></h3>
		<table class="wp-list-table widefat striped">
			<thead><tr><th><?php esc_html_e( 'Column', 'ai-governance-suite' ); ?></th><th><?php esc_html_e( 'Meaning', 'ai-governance-suite' ); ?></th></tr></thead>
			<tbody>
				<tr><td><strong><?php esc_html_e( 'Event Type', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'A dot-separated slug: object.action (e.g. prompt.promoted, budget.alertFired).', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Object Type / ID', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'What was acted upon and its primary key.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Actor', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'The WordPress user ID who performed the action.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Actor IP', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'IP address at the time of the action.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Before / After State', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'JSON snapshots of the record before and after a mutation. Empty ({}) for creation events.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td><strong><?php esc_html_e( 'Occurred At', 'ai-governance-suite' ); ?></strong></td><td><?php esc_html_e( 'UTC timestamp of the event.', 'ai-governance-suite' ); ?></td></tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Retention', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'AIGIS does not automatically prune the audit trail. For long-running installations, export and archive old rows periodically using a database administration tool. Regulatory requirements (e.g. GDPR, ISO 27001) typically mandate a minimum 12-month retention.', 'ai-governance-suite' ); ?></p>

	<?php // ----------------------------------------------------------------
	//  REST API
	// ----------------------------------------------------------------
	elseif ( 'rest-api' === $active_tab ) : ?>

		<h2><?php esc_html_e( 'REST API', 'ai-governance-suite' ); ?></h2>
		<p><?php esc_html_e( 'AIGIS exposes a versioned REST API at /wp-json/aigis/v1/ for integration with your AI pipelines, agents, and external tooling.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Authentication', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'All API requests require the custom header:', 'ai-governance-suite' ); ?></p>
		<pre style="background:#f0f0f1;padding:1em"><code>X-AIGIS-API-Key: &lt;your-api-key&gt;</code></pre>
		<p><?php esc_html_e( 'Set the API key in AI Governance → Settings → General. Authentication failures are logged in the Audit Trail.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Endpoints', 'ai-governance-suite' ); ?></h3>
		<table class="wp-list-table widefat striped">
			<thead><tr><th><?php esc_html_e( 'Method', 'ai-governance-suite' ); ?></th><th><?php esc_html_e( 'Endpoint', 'ai-governance-suite' ); ?></th><th><?php esc_html_e( 'Purpose', 'ai-governance-suite' ); ?></th></tr></thead>
			<tbody>
				<tr><td>POST</td><td><code>/aigis/v1/log</code></td><td><?php esc_html_e( 'Submit a usage log entry (token counts, cost, latency, status).', 'ai-governance-suite' ); ?></td></tr>
				<tr><td>GET</td><td><code>/aigis/v1/routing</code></td><td><?php esc_html_e( 'Retrieve routing metadata for an agent (active prompt stage, model, risk level).', 'ai-governance-suite' ); ?></td></tr>
				<tr><td>POST</td><td><code>/aigis/v1/guardrail</code></td><td><?php esc_html_e( 'Submit an input/output for real-time guardrail evaluation. Returns allow/block decision and matched rules.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td>POST</td><td><code>/aigis/v1/eval</code></td><td><?php esc_html_e( 'Submit an evaluation result (expected vs actual output, pass/fail, false negative flag).', 'ai-governance-suite' ); ?></td></tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'POST /aigis/v1/log', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'Required fields: agent_id. Optional: user_id, department, project_tag, session_id, input_tokens, output_tokens, latency_ms, cost_usd, status (success|error|timeout|guardrail-blocked), error_code.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'GET /aigis/v1/routing', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'Required query param: agent_id. Returns the inventory record and the most recent production-stage prompt for the agent.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'POST /aigis/v1/guardrail', 'ai-governance-suite' ); ?></h3>
		<p><?php esc_html_e( 'Required fields: agent_id, input_hash. Optional: input_text, output_text. Returns {allowed: true/false, triggered_rules: [...]}. Blocked requests are recorded in aigis_guardrail_triggers.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Error Responses', 'ai-governance-suite' ); ?></h3>
		<table class="wp-list-table widefat striped">
			<thead><tr><th><?php esc_html_e( 'HTTP Status', 'ai-governance-suite' ); ?></th><th><?php esc_html_e( 'Meaning', 'ai-governance-suite' ); ?></th></tr></thead>
			<tbody>
				<tr><td>401</td><td><?php esc_html_e( 'Missing or invalid API key.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td>403</td><td><?php esc_html_e( 'API key valid but lacks permission for the requested action.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td>400</td><td><?php esc_html_e( 'Malformed request — missing required field or invalid enum value.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td>404</td><td><?php esc_html_e( 'Agent identifier not found in inventory.', 'ai-governance-suite' ); ?></td></tr>
				<tr><td>200</td><td><?php esc_html_e( 'Success.', 'ai-governance-suite' ); ?></td></tr>
			</tbody>
		</table>

	<?php // ----------------------------------------------------------------
	//  ROLES & PERMISSIONS
	// ----------------------------------------------------------------
	elseif ( 'roles' === $active_tab ) : ?>

		<h2><?php esc_html_e( 'Roles &amp; Permissions', 'ai-governance-suite' ); ?></h2>
		<p><?php esc_html_e( 'AIGIS registers four custom WordPress roles on activation. Administrators retain all capabilities automatically.', 'ai-governance-suite' ); ?></p>

		<h3><?php esc_html_e( 'Custom Roles', 'ai-governance-suite' ); ?></h3>
		<table class="wp-list-table widefat striped">
			<thead><tr><th><?php esc_html_e( 'Role', 'ai-governance-suite' ); ?></th><th><?php esc_html_e( 'Intended For', 'ai-governance-suite' ); ?></th><th><?php esc_html_e( 'Key Capabilities', 'ai-governance-suite' ); ?></th></tr></thead>
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'AIGIS Manager', 'ai-governance-suite' ); ?></strong></td>
					<td><?php esc_html_e( 'Senior staff responsible for governance strategy.', 'ai-governance-suite' ); ?></td>
					<td><?php esc_html_e( 'Full read access; manage prompts, policies, incidents, inventory; no settings access.', 'ai-governance-suite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'AIGIS Analyst', 'ai-governance-suite' ); ?></strong></td>
					<td><?php esc_html_e( 'Data analysts, compliance officers.', 'ai-governance-suite' ); ?></td>
					<td><?php esc_html_e( 'Read all sections; view analytics; cannot create or modify resources.', 'ai-governance-suite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'AIGIS Prompt Manager', 'ai-governance-suite' ); ?></strong></td>
					<td><?php esc_html_e( 'Prompt engineers and AI developers.', 'ai-governance-suite' ); ?></td>
					<td><?php esc_html_e( 'Create and manage prompts; promote up to staging; cannot promote to production.', 'ai-governance-suite' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'AIGIS Auditor', 'ai-governance-suite' ); ?></strong></td>
					<td><?php esc_html_e( 'Internal auditors, legal team.', 'ai-governance-suite' ); ?></td>
					<td><?php esc_html_e( 'Read-only access across all sections including audit trail. Cannot modify any records.', 'ai-governance-suite' ); ?></td>
				</tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Capability Matrix', 'ai-governance-suite' ); ?></h3>
		<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Capability', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Administrator', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Manager', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Analyst', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Prompt Manager', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Auditor', 'ai-governance-suite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr><td><?php esc_html_e( 'View AI Inventory', 'ai-governance-suite' ); ?></td><td>&#10003;</td><td>&#10003;</td><td>&#10003;</td><td>&#10003;</td><td>&#10003;</td></tr>
				<tr><td><?php esc_html_e( 'Manage AI Inventory', 'ai-governance-suite' ); ?></td><td>&#10003;</td><td>&#10003;</td><td>&ndash;</td><td>&ndash;</td><td>&ndash;</td></tr>
				<tr><td><?php esc_html_e( 'View Analytics', 'ai-governance-suite' ); ?></td><td>&#10003;</td><td>&#10003;</td><td>&#10003;</td><td>&ndash;</td><td>&#10003;</td></tr>
				<tr><td><?php esc_html_e( 'Manage Prompts', 'ai-governance-suite' ); ?></td><td>&#10003;</td><td>&#10003;</td><td>&ndash;</td><td>&#10003;</td><td>&ndash;</td></tr>
				<tr><td><?php esc_html_e( 'Promote Prompt to Production', 'ai-governance-suite' ); ?></td><td>&#10003;</td><td>&ndash;</td><td>&ndash;</td><td>&ndash;</td><td>&ndash;</td></tr>
				<tr><td><?php esc_html_e( 'Manage Policies', 'ai-governance-suite' ); ?></td><td>&#10003;</td><td>&#10003;</td><td>&ndash;</td><td>&ndash;</td><td>&ndash;</td></tr>
				<tr><td><?php esc_html_e( 'Manage Workflows', 'ai-governance-suite' ); ?></td><td>&#10003;</td><td>&#10003;</td><td>&ndash;</td><td>&ndash;</td><td>&ndash;</td></tr>
				<tr><td><?php esc_html_e( 'Manage Incidents', 'ai-governance-suite' ); ?></td><td>&#10003;</td><td>&#10003;</td><td>&ndash;</td><td>&ndash;</td><td>&ndash;</td></tr>
				<tr><td><?php esc_html_e( 'View Audit Log', 'ai-governance-suite' ); ?></td><td>&#10003;</td><td>&#10003;</td><td>&#10003;</td><td>&ndash;</td><td>&#10003;</td></tr>
				<tr><td><?php esc_html_e( 'Manage Settings', 'ai-governance-suite' ); ?></td><td>&#10003;</td><td>&ndash;</td><td>&ndash;</td><td>&ndash;</td><td>&ndash;</td></tr>
			</tbody>
		</table>

		<p style="margin-top:1rem"><?php esc_html_e( 'Roles are assigned from Settings → Roles & Permissions. Removing the AIGIS plugin does not automatically strip custom roles from users — remove role assignments before deactivating if a clean removal is required.', 'ai-governance-suite' ); ?></p>

	<?php endif; ?>

	</div><!-- .aigis-manual-body -->

	</div><!-- .aigis-manual-layout -->
</div><!-- .aigis-manual -->
