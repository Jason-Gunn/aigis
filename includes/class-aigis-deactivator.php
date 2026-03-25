<?php
/**
 * Fired during plugin deactivation.
 *
 * Flushes rewrite rules and clears scheduled cron jobs.
 * Does NOT remove capabilities or roles (preserved for reactivation).
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Deactivator {

	public static function deactivate(): void {
		// Clear scheduled cron jobs.
		$cron_hooks = [
			'aigis_prune_audit_log',
			'aigis_prune_usage_logs',
			'aigis_check_policy_expiry',
			'aigis_check_budget_alerts',
			'aigis_sample_eval_runs',
		];
		foreach ( $cron_hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}

		flush_rewrite_rules();
	}
}
