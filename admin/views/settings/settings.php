<?php
/**
 * Settings page view (tabbed).
 *
 * Variables: $active_tab (string), $settings (array)
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$base_url = admin_url( 'admin.php?page=aigis-settings' );
$tabs = [
	'general'       => __( 'General', 'ai-governance-suite' ),
	'providers'     => __( 'AI Providers', 'ai-governance-suite' ),
	'notifications' => __( 'Notifications', 'ai-governance-suite' ),
	'evaluation'    => __( 'Evaluation', 'ai-governance-suite' ),
	'roles'         => __( 'Roles & Permissions', 'ai-governance-suite' ),
	'developer'     => __( 'Developer Tools', 'ai-governance-suite' ),
];
?>
<div class="wrap aigis-wrap">
	<h1><?php esc_html_e( 'AIGIS Settings', 'ai-governance-suite' ); ?></h1>

	<nav class="aigis-tabs">
		<?php foreach ( $tabs as $slug => $label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base_url ) ); ?>"
			   class="<?php echo esc_attr( 'aigis-tab' . ( $active_tab === $slug ? ' aigis-tab-active' : '' ) ); ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<form method="post" action="<?php echo esc_url( add_query_arg( 'tab', $active_tab, $base_url ) ); ?>">
		<?php wp_nonce_field( 'aigis_save_settings', 'aigis_settings_nonce' ); ?>
		<input type="hidden" name="aigis_active_tab" value="<?php echo esc_attr( $active_tab ); ?>">

		<?php if ( $active_tab === 'general' ) : ?>
		<!-- ===== General ===== -->
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="aigis_audit_retention_days"><?php esc_html_e( 'Audit Log Retention (days)', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="number" id="aigis_audit_retention_days" name="aigis_audit_retention_days" min="30" max="3650" class="small-text"
						value="<?php echo esc_attr( get_option( 'aigis_audit_retention_days', 365 ) ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aigis_usage_log_retention_days"><?php esc_html_e( 'Usage Log Retention (days)', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="number" id="aigis_usage_log_retention_days" name="aigis_usage_log_retention_days" min="30" max="3650" class="small-text"
						value="<?php echo esc_attr( get_option( 'aigis_usage_log_retention_days', 180 ) ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'PII Detection', 'ai-governance-suite' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="aigis_pii_scan_enabled" value="1" <?php checked( get_option( 'aigis_pii_scan_enabled', '1' ) ); ?>>
						<?php esc_html_e( 'Enable PII scanning on prompt inputs and outputs', 'ai-governance-suite' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'API Key', 'ai-governance-suite' ); ?></th>
				<td>
					<div class="aigis-api-key-wrap">
						<input type="text" id="aigis_api_key_display" class="regular-text aigis-api-key-field" readonly
							value="<?php echo get_option( 'aigis_api_key_hash' ) ? '••••••••••••••••••••••••••••••••' : esc_attr__( 'Not set', 'ai-governance-suite' ); ?>">
						<?php if ( get_option( 'aigis_api_key_hash' ) ) : ?>
						<button type="button" class="button aigis-copy-btn" data-target="aigis_api_key_display">
							<?php esc_html_e( 'Copy', 'ai-governance-suite' ); ?>
						</button>
						<?php endif; ?>
					</div>
					<p class="description">
						<?php esc_html_e( 'To regenerate the API key, use WP-CLI or contact your administrator.', 'ai-governance-suite' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php elseif ( $active_tab === 'providers' ) : ?>
		<!-- ===== Providers ===== -->
		<h2><?php esc_html_e( 'OpenAI', 'ai-governance-suite' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="aigis_provider_openai_api_key"><?php esc_html_e( 'API Key', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="password" id="aigis_provider_openai_api_key" name="aigis_provider_openai_api_key"
						class="regular-text" autocomplete="new-password"
						placeholder="<?php esc_attr_e( 'sk-…  (leave blank to keep current)', 'ai-governance-suite' ); ?>">
					<p class="description"><?php esc_html_e( 'Stored encrypted. Leave blank to keep the existing key.', 'ai-governance-suite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aigis_provider_openai_org_id"><?php esc_html_e( 'Organisation ID', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="text" id="aigis_provider_openai_org_id" name="aigis_provider_openai_org_id"
						class="regular-text" value="<?php echo esc_attr( get_option( 'aigis_provider_openai_org_id', '' ) ); ?>">
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Anthropic', 'ai-governance-suite' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="aigis_provider_anthropic_api_key"><?php esc_html_e( 'API Key', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="password" id="aigis_provider_anthropic_api_key" name="aigis_provider_anthropic_api_key"
						class="regular-text" autocomplete="new-password"
						placeholder="<?php esc_attr_e( 'sk-ant-…  (leave blank to keep current)', 'ai-governance-suite' ); ?>">
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Ollama (Local)', 'ai-governance-suite' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="aigis_provider_ollama_base_url"><?php esc_html_e( 'Base URL', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="url" id="aigis_provider_ollama_base_url" name="aigis_provider_ollama_base_url"
						class="regular-text" value="<?php echo esc_attr( get_option( 'aigis_provider_ollama_base_url', 'http://localhost:11434' ) ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aigis_provider_ollama_default_model"><?php esc_html_e( 'Default Model', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="text" id="aigis_provider_ollama_default_model" name="aigis_provider_ollama_default_model"
						class="regular-text" value="<?php echo esc_attr( get_option( 'aigis_provider_ollama_default_model', 'llama3' ) ); ?>">
				</td>
			</tr>
		</table>

		<?php elseif ( $active_tab === 'notifications' ) : ?>
		<!-- ===== Notifications ===== -->
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Email Alerts', 'ai-governance-suite' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="aigis_notify_email" value="1" <?php checked( get_option( 'aigis_notify_email', '1' ) ); ?>>
						<?php esc_html_e( 'Send alert emails', 'ai-governance-suite' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aigis_notify_email_to"><?php esc_html_e( 'Alert Email(s)', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="text" id="aigis_notify_email_to" name="aigis_notify_email_to" class="regular-text"
						value="<?php echo esc_attr( get_option( 'aigis_notify_email_to', get_option( 'admin_email' ) ) ); ?>">
					<p class="description"><?php esc_html_e( 'Comma-separated email addresses.', 'ai-governance-suite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Webhook Alerts', 'ai-governance-suite' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="aigis_notify_webhook" value="1" <?php checked( get_option( 'aigis_notify_webhook' ) ); ?>>
						<?php esc_html_e( 'Send POST webhook notifications', 'ai-governance-suite' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aigis_notify_webhook_url"><?php esc_html_e( 'Webhook URL', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="url" id="aigis_notify_webhook_url" name="aigis_notify_webhook_url" class="regular-text"
						value="<?php echo esc_attr( get_option( 'aigis_notify_webhook_url', '' ) ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aigis_budget_alert_threshold"><?php esc_html_e( 'Budget Alert Threshold (%)', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="number" id="aigis_budget_alert_threshold" name="aigis_budget_alert_threshold"
						min="50" max="100" class="small-text"
						value="<?php echo esc_attr( get_option( 'aigis_budget_alert_threshold', 80 ) ); ?>">
				</td>
			</tr>
		</table>

		<?php elseif ( $active_tab === 'evaluation' ) : ?>
		<!-- ===== Evaluation ===== -->
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="aigis_eval_auto_sample_pct"><?php esc_html_e( 'Auto-sample Rate (%)', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="number" id="aigis_eval_auto_sample_pct" name="aigis_eval_auto_sample_pct"
						min="0" max="100" class="small-text"
						value="<?php echo esc_attr( get_option( 'aigis_eval_auto_sample_pct', 10 ) ); ?>">
					<p class="description"><?php esc_html_e( 'Percentage of usage log entries flagged for automated evaluation.', 'ai-governance-suite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aigis_eval_pass_threshold"><?php esc_html_e( 'Pass Score Threshold', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="number" id="aigis_eval_pass_threshold" name="aigis_eval_pass_threshold"
						min="0" max="1" step="0.05" class="small-text"
						value="<?php echo esc_attr( get_option( 'aigis_eval_pass_threshold', 0.7 ) ); ?>">
					<p class="description"><?php esc_html_e( 'Score ≥ threshold = Pass. Float 0–1.', 'ai-governance-suite' ); ?></p>
				</td>
			</tr>
		</table>

		<?php elseif ( $active_tab === 'developer' ) : ?>
		<!-- ===== Developer Tools ===== -->
		<div class="aigis-card">
			<h3><?php esc_html_e( 'Test Data', 'ai-governance-suite' ); ?></h3>
			<p><?php esc_html_e( 'Populate every section of the plugin with realistic sample data for exploration and demonstration purposes. Test data is tagged and tracked so it can be removed cleanly without affecting any real records.', 'ai-governance-suite' ); ?></p>

			<?php $counts = AIGIS_Test_Data::count_existing(); ?>
			<table class="wp-list-table widefat striped" style="max-width:480px;margin-bottom:1.5rem">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Section', 'ai-governance-suite' ); ?></th>
						<th style="text-align:center"><?php esc_html_e( 'Test Records', 'ai-governance-suite' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$labels = [
						'prompts'      => __( 'Prompts', 'ai-governance-suite' ),
						'policies'     => __( 'Policies', 'ai-governance-suite' ),
						'workflows'    => __( 'Workflows', 'ai-governance-suite' ),
						'incidents'    => __( 'Incidents', 'ai-governance-suite' ),
						'inventory'    => __( 'AI Inventory', 'ai-governance-suite' ),
						'usage_logs'   => __( 'Usage Logs', 'ai-governance-suite' ),
						'audit'        => __( 'Audit Trail', 'ai-governance-suite' ),
						'cost_budgets' => __( 'Cost Budgets', 'ai-governance-suite' ),
						'eval_results' => __( 'Evaluation Results', 'ai-governance-suite' ),
						'guardrail'    => __( 'Guardrail Triggers', 'ai-governance-suite' ),
					];
					foreach ( $labels as $key => $label ) :
						$n = absint( $counts[ $key ] ?? 0 );
					?>
					<tr>
						<td><?php echo esc_html( $label ); ?></td>
						<td style="text-align:center"><?php echo $n > 0 ? wp_kses_post( '<strong>' . $n . '</strong>' ) : esc_html__( '—', 'ai-governance-suite' ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p>
				<button type="button" id="aigis-generate-test-data" class="button button-primary"
					<?php disabled( array_sum( $counts ) > 0 ); ?>>
					<?php esc_html_e( 'Generate Test Data', 'ai-governance-suite' ); ?>
				</button>
				&nbsp;
				<button type="button" id="aigis-purge-test-data" class="button button-secondary"
					<?php disabled( array_sum( $counts ) === 0 ); ?>>
					<?php esc_html_e( 'Remove Test Data', 'ai-governance-suite' ); ?>
				</button>
			</p>
			<div id="aigis-test-data-log"></div>
		</div>

		<div class="aigis-card" style="border-top:3px solid #d63638;margin-top:1.5rem">
			<h3 style="color:#d63638"><?php esc_html_e( 'Factory Reset', 'ai-governance-suite' ); ?></h3>
			<p><?php esc_html_e( 'Permanently deletes every record created by this plugin — all CPT posts (prompts, policies, workflows, incidents), all custom-table rows, and all plugin options — then re-runs the activator so the plugin is in a clean-install state. Use this when you need a completely fresh environment for testing.', 'ai-governance-suite' ); ?></p>
			<p><strong><?php esc_html_e( 'This cannot be undone.', 'ai-governance-suite' ); ?></strong></p>
			<p>
				<button type="button" id="aigis-factory-reset" class="button" style="background:#d63638;border-color:#b32d2e;color:#fff">
					<?php esc_html_e( 'Reset All Plugin Data', 'ai-governance-suite' ); ?>
				</button>
			</p>
			<div id="aigis-factory-reset-log"></div>
		</div>

		<?php elseif ( $active_tab === 'roles' ) : ?>
		<!-- ===== Roles & Permissions ===== -->
		<div class="aigis-card">
			<h3><?php esc_html_e( 'Custom Roles', 'ai-governance-suite' ); ?></h3>
			<?php
			$roles_info = [
				'aigis_super_admin'  => __( 'Full access to all AIGIS features', 'ai-governance-suite' ),
				'aigis_ai_manager'   => __( 'Manage inventory, budgets, policies and view analytics', 'ai-governance-suite' ),
				'aigis_ai_developer' => __( 'Create and test prompts and workflows', 'ai-governance-suite' ),
				'aigis_ai_reviewer'  => __( 'Review evaluation results and audit logs', 'ai-governance-suite' ),
			];
			?>
			<table class="aigis-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Role', 'ai-governance-suite' ); ?></th>
						<th><?php esc_html_e( 'Description', 'ai-governance-suite' ); ?></th>
						<th><?php esc_html_e( 'Users', 'ai-governance-suite' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $roles_info as $slug => $desc ) :
						$role = get_role( $slug );
						$count = $role ? count( get_users( [ 'role' => $slug, 'count_total' => true, 'fields' => 'ID' ] ) ) : 0;
					?>
					<tr>
						<td><code><?php echo esc_html( $slug ); ?></code></td>
						<td><?php echo esc_html( $desc ); ?></td>
						<td>
							<?php if ( $count > 0 ) : ?>
								<a href="<?php echo esc_url( admin_url( 'users.php?role=' . $slug ) ); ?>"><?php echo esc_html( $count ); ?></a>
							<?php else : ?>
								0
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description" style="margin-top:8px;">
				<?php esc_html_e( 'Roles are managed automatically by AIGIS. Use the Users screen to assign roles.', 'ai-governance-suite' ); ?>
			</p>
		</div>
		<?php endif; ?>

		<?php if ( $active_tab !== 'roles' && $active_tab !== 'developer' ) : ?>
			<?php submit_button( __( 'Save Settings', 'ai-governance-suite' ) ); ?>
		<?php endif; ?>
	</form>
</div>
