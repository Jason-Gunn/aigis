<?php
/**
 * WP-Cron job callbacks.
 *
 * Registered by AIGIS_Activator::schedule_cron_jobs() and wired to
 * WP-Cron events in AIGIS_Plugin::init().
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Cron {

	private AIGIS_DB_Audit     $db_audit;
	private AIGIS_DB_Usage_Log $db_usage;
	private AIGIS_DB_Cost      $db_cost;
	private AIGIS_Notifications $notifications;

	public function __construct(
		AIGIS_DB_Audit $db_audit,
		AIGIS_DB_Usage_Log $db_usage,
		AIGIS_DB_Cost $db_cost,
		AIGIS_Notifications $notifications
	) {
		$this->db_audit      = $db_audit;
		$this->db_usage      = $db_usage;
		$this->db_cost       = $db_cost;
		$this->notifications = $notifications;
	}

	/**
	 * Prune audit log records older than the retention setting.
	 * WP-Cron hook: aigis_prune_audit_log
	 */
	public function prune_audit_log(): void {
		$deleted = $this->db_audit->prune_old_records();
		if ( $deleted > 0 ) {
			$this->db_audit->log(
				'system.pruneAuditLog',
				'system',
				'0',
				sprintf( 'Pruned %d audit log records older than retention window.', $deleted )
			);
		}
	}

	/**
	 * Prune usage log records older than the retention setting.
	 * WP-Cron hook: aigis_prune_usage_logs
	 */
	public function prune_usage_logs(): void {
		$deleted = $this->db_usage->prune_old_records();
		if ( $deleted > 0 ) {
			$this->db_audit->log(
				'system.pruneUsageLogs',
				'system',
				'0',
				sprintf( 'Pruned %d usage log records older than retention window.', $deleted )
			);
		}
	}

	/**
	 * Check active budgets and dispatch alerts for any over the threshold.
	 * WP-Cron hook: aigis_check_budget_alerts
	 */
	public function check_budget_alerts(): void {
		$overages = $this->db_cost->get_overage_budgets();

		foreach ( $overages as $budget ) {
			$subject = sprintf(
				__( 'Budget alert: %s has reached %s%% of limit', 'ai-governance-suite' ),
				esc_html( $budget['scope'] === 'global' ? 'Global' : $budget['scope_id'] ),
				$budget['pct_used']
			);
			$message = sprintf(
				__( "Budget: %s\nScope: %s\nPeriod: %s\nBudget: $%s\nActual spend: $%s (%s%%)", 'ai-governance-suite' ),
				esc_html( $budget['scope'] === 'global' ? 'Global' : $budget['scope_id'] ),
				esc_html( $budget['scope'] ),
				esc_html( $budget['period'] ),
				number_format( (float) $budget['budget_usd'], 2 ),
				number_format( (float) $budget['actual_spend'], 2 ),
				$budget['pct_used']
			);

			$this->notifications->dispatch( 'budget_overage', $subject, $message, (array) $budget );
		}
	}

	/**
	 * Check for policies nearing their expiry date.
	 * WP-Cron hook: aigis_check_policy_expiry
	 */
	public function check_policy_expiry(): void {
		$days_warning = (int) get_option( 'aigis_policy_expiry_warning_days', 14 );

		$args = [
			'post_type'      => 'aigis_policy',
			'post_status'    => 'aigis-approved',
			'posts_per_page' => -1,
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'     => '_aigis_policy_expiry_date',
					'value'   => [ date( 'Y-m-d' ), date( 'Y-m-d', strtotime( "+{$days_warning} days" ) ) ],
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				],
			],
			'fields'         => 'ids',
		];

		$expiring = get_posts( $args );

		foreach ( $expiring as $policy_id ) {
			$expiry   = get_post_meta( $policy_id, '_aigis_policy_expiry_date', true );
			$title    = get_the_title( $policy_id );
			$edit_url = get_edit_post_link( $policy_id, 'raw' );

			$subject = sprintf(
				__( 'Policy expiry warning: "%s"', 'ai-governance-suite' ),
				$title
			);
			$message = sprintf(
				__( 'Policy "%s" expires on %s. Please review and renew: %s', 'ai-governance-suite' ),
				$title,
				$expiry,
				$edit_url
			);

			$this->notifications->dispatch( 'policy_expiry', $subject, $message, [
				'policy_id'   => $policy_id,
				'expiry_date' => $expiry,
			] );
		}
	}

	/**
	 * Sample evaluation runs for prompts that are due for re-evaluation.
	 * WP-Cron hook: aigis_sample_eval_runs
	 */
	public function sample_eval_runs(): void {
		if ( ! (bool) get_option( 'aigis_auto_eval_enabled', false ) ) {
			return;
		}

		$sample_rate = (int) get_option( 'aigis_auto_eval_sample_rate', 10 );

		$args = [
			'post_type'      => 'aigis_prompt',
			'post_status'    => 'publish',
			'posts_per_page' => $sample_rate,
			'orderby'        => 'meta_value',
			'meta_key'       => '_aigis_last_eval_at', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'          => 'ASC',
			'fields'         => 'ids',
		];

		$prompts = get_posts( $args );

		foreach ( $prompts as $prompt_id ) {
			/**
			 * Fires when the cron job determines a prompt should be sampled for evaluation.
			 *
			 * @param int $prompt_id The prompt post ID.
			 */
			do_action( 'aigis_eval_sample_prompt', $prompt_id );
			update_post_meta( $prompt_id, '_aigis_last_eval_at', current_time( 'mysql', true ) );
		}
	}
}
