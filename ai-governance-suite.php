<?php
/**
 * Plugin Name:       AI Governance & Infrastructure Suite
 * Plugin URI:        https://example.com/ai-governance-suite
 * Description:       A comprehensive AI governance platform for managing, auditing, and continuously improving your organization's use of AI systems.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Your Organization
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-governance-suite
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'AIGIS_VERSION',    '1.0.0' );
define( 'AIGIS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIGIS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AIGIS_PLUGIN_FILE', __FILE__ );
define( 'AIGIS_TEXT_DOMAIN', 'ai-governance-suite' );

/**
 * Load required core files.
 */
function aigis_load_dependencies(): void {
	require_once AIGIS_PLUGIN_DIR . 'includes/class-aigis-loader.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/class-aigis-activator.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/class-aigis-deactivator.php';

	// Helpers (loaded early — other classes depend on them)
	require_once AIGIS_PLUGIN_DIR . 'includes/helpers/class-aigis-capabilities.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/helpers/class-aigis-pii-detector.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/helpers/class-aigis-notifications.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/helpers/class-aigis-cron.php';

	// Database layer
	require_once AIGIS_PLUGIN_DIR . 'includes/db/class-aigis-db.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/db/class-aigis-db-audit.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/db/class-aigis-db-inventory.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/db/class-aigis-db-usage-log.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/db/class-aigis-db-cost.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/db/class-aigis-db-eval.php';

	// Custom Post Types
	require_once AIGIS_PLUGIN_DIR . 'includes/cpt/class-aigis-cpt-prompt.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/cpt/class-aigis-cpt-policy.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/cpt/class-aigis-cpt-workflow.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/cpt/class-aigis-cpt-incident.php';

	// Provider adapters
	require_once AIGIS_PLUGIN_DIR . 'includes/providers/class-aigis-provider-abstract.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/providers/class-aigis-provider-openai.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/providers/class-aigis-provider-anthropic.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/providers/class-aigis-provider-ollama.php';

	// REST API controllers
	require_once AIGIS_PLUGIN_DIR . 'includes/api/class-aigis-rest-controller.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/api/class-aigis-rest-log.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/api/class-aigis-rest-routing.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/api/class-aigis-rest-guardrail.php';
	require_once AIGIS_PLUGIN_DIR . 'includes/api/class-aigis-rest-eval.php';

	// Admin pages
	if ( is_admin() ) {
		require_once AIGIS_PLUGIN_DIR . 'includes/admin/class-aigis-admin.php';
		require_once AIGIS_PLUGIN_DIR . 'includes/admin/class-aigis-page-dashboard.php';
		require_once AIGIS_PLUGIN_DIR . 'includes/admin/class-aigis-page-inventory.php';
		require_once AIGIS_PLUGIN_DIR . 'includes/admin/class-aigis-page-audit-log.php';
		require_once AIGIS_PLUGIN_DIR . 'includes/admin/class-aigis-page-settings.php';
		require_once AIGIS_PLUGIN_DIR . 'includes/admin/class-aigis-page-analytics.php';
		require_once AIGIS_PLUGIN_DIR . 'includes/admin/class-aigis-page-cost.php';
		require_once AIGIS_PLUGIN_DIR . 'includes/admin/class-aigis-page-stress-tests.php';
		require_once AIGIS_PLUGIN_DIR . 'includes/admin/class-aigis-page-eval.php';
		require_once AIGIS_PLUGIN_DIR . 'includes/helpers/class-aigis-test-data.php';
		require_once AIGIS_PLUGIN_DIR . 'includes/admin/class-aigis-page-manual.php';
	}

	// Main plugin orchestrator (loaded last)
	require_once AIGIS_PLUGIN_DIR . 'includes/class-aigis-plugin.php';
}

/**
 * Register activation and deactivation hooks.
 */
register_activation_hook( __FILE__, [ 'AIGIS_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'AIGIS_Deactivator', 'deactivate' ] );

/**
 * Bootstrap the plugin.
 */
function run_aigis(): void {
	aigis_load_dependencies();
	$plugin = new AIGIS_Plugin();
	$plugin->run();
}

run_aigis();
