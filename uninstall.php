<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes all AIGIS capabilities from all WP roles,
 * removes the four custom AIGIS roles, drops all custom tables,
 * and deletes all plugin options.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// --- Remove AIGIS capabilities from all roles ---
$aigis_capabilities = [
	'aigis_manage_ai_inventory',
	'aigis_view_ai_inventory',
	'aigis_manage_prompts',
	'aigis_approve_prompts',
	'aigis_use_prompts',
	'aigis_manage_skills',
	'aigis_approve_skills',
	'aigis_use_skills',
	'aigis_view_skills',
	'aigis_manage_policies',
	'aigis_approve_policies',
	'aigis_view_policies',
	'aigis_manage_workflows',
	'aigis_view_workflows',
	'aigis_view_analytics',
	'aigis_export_data',
	'aigis_manage_settings',
	'aigis_manage_api_keys',
	'aigis_view_audit_log',
	'aigis_run_stress_tests',
	'aigis_manage_eval',
	'aigis_view_eval',
	'aigis_manage_budgets',
	'aigis_view_costs',
	'aigis_manage_incidents',
	'aigis_view_incidents',
];

$wp_roles = wp_roles();
foreach ( $wp_roles->roles as $role_slug => $role_data ) {
	$role = get_role( $role_slug );
	if ( $role ) {
		foreach ( $aigis_capabilities as $cap ) {
			$role->remove_cap( $cap );
		}
	}
}

// --- Remove the four custom AIGIS roles ---
$custom_roles = [ 'aigis_super_admin', 'aigis_ai_manager', 'aigis_ai_developer', 'aigis_ai_reviewer' ];
foreach ( $custom_roles as $role_slug ) {
	remove_role( $role_slug );
}

// --- Drop all custom tables ---
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
	$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}

// --- Delete all plugin options ---
$options = [
	'aigis_version',
	'aigis_db_version',
	'aigis_audit_retention_days',
	'aigis_pii_detection_enabled',
	'aigis_trust_proxy_headers',
	'aigis_api_key_hash',
	'aigis_dev_mode',
	'aigis_openai_api_key',
	'aigis_openai_default_model',
	'aigis_anthropic_api_key',
	'aigis_anthropic_default_model',
	'aigis_ollama_endpoint',
	'aigis_ollama_default_model',
	'aigis_alert_rules',
	'aigis_policy_expiry_alert_days',
	'aigis_notification_inbox_cap',
	'aigis_notification_inbox',
	'aigis_eval_sample_rate_pct',
	'aigis_eval_rulebook',
	'aigis_tracev_rules',
	'aigis_risk_taxonomy',
	'aigis_stake_levels',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

// --- Delete all plugin transients ---
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_aigis_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_aigis_' ) . '%'
	)
);
