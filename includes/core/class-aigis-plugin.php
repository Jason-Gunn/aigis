<?php
/**
 * Main plugin orchestrator.
 *
 * Instantiates all plugin components, registers their hooks via AIGIS_Loader,
 * and starts the plugin.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Plugin {

	private AIGIS_Loader $loader;

	public function __construct() {
		$this->loader = new AIGIS_Loader();
	}

	/**
	 * Initialize all plugin components and register their hooks.
	 */
	private function init(): void {
		AIGIS_Capabilities::register_roles_and_caps();

		// CPTs and taxonomies — create instances then wire hooks via register().
		( new AIGIS_CPT_Prompt() )->register( $this->loader );
		( new AIGIS_CPT_Policy() )->register( $this->loader );
		( new AIGIS_CPT_Workflow() )->register( $this->loader );
		( new AIGIS_CPT_Incident() )->register( $this->loader );

		// Shared service instances reused below.
		$db_audit      = new AIGIS_DB_Audit();
		$db_usage      = new AIGIS_DB_Usage_Log();
		$db_cost       = new AIGIS_DB_Cost();
		$notifications = new AIGIS_Notifications();

		// Cron jobs — pass all required dependencies.
		$cron = new AIGIS_Cron( $db_audit, $db_usage, $db_cost, $notifications );
		$this->loader->add_action( 'aigis_prune_audit_log',     $cron, 'prune_audit_log' );
		$this->loader->add_action( 'aigis_prune_usage_logs',    $cron, 'prune_usage_logs' );
		$this->loader->add_action( 'aigis_check_policy_expiry', $cron, 'check_policy_expiry' );
		$this->loader->add_action( 'aigis_check_budget_alerts', $cron, 'check_budget_alerts' );
		$this->loader->add_action( 'aigis_sample_eval_runs',    $cron, 'sample_eval_runs' );

		// REST API controllers.
		$this->loader->add_rest_controller( new AIGIS_REST_Log() );
		$this->loader->add_rest_controller( new AIGIS_REST_Routing() );
		$this->loader->add_rest_controller( new AIGIS_REST_Guardrail() );
		$this->loader->add_rest_controller( new AIGIS_REST_Eval() );

		// User login audit hook.
		$this->loader->add_action( 'wp_login', $db_audit, 'log_user_login', 10, 2 );

		if ( is_admin() ) {
			$this->init_admin( $notifications );
		}
	}

	/**
	 * Initialize admin-only components.
	 *
	 * @param AIGIS_Notifications $notifications Shared notifications instance.
	 */
	private function init_admin( AIGIS_Notifications $notifications ): void {
		$admin = new AIGIS_Admin( $notifications );
		$admin->register( $this->loader );
	}

	/**
	 * Run the plugin.
	 */
	public function run(): void {
		$this->init();
		$this->loader->run();
	}
}
