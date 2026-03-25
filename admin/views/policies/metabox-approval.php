<?php
/**
 * Policy CPT — Approval Workflow metabox view.
 *
 * Variables: $post, $current_status
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$statuses = [
	'draft'           => __( 'Draft', 'ai-governance-suite' ),
	'aigis-in-review' => __( 'In Review', 'ai-governance-suite' ),
	'aigis-approved'  => __( 'Approved', 'ai-governance-suite' ),
	'aigis-retired'   => __( 'Retired', 'ai-governance-suite' ),
];
$label = $statuses[ $current_status ] ?? $current_status;
?>
<p>
	<strong><?php esc_html_e( 'Current Status:', 'ai-governance-suite' ); ?></strong>
	<span class="aigis-badge aigis-badge-<?php echo esc_attr( str_replace( 'aigis-', '', $current_status ) ); ?>">
		<?php echo esc_html( $label ); ?>
	</span>
</p>

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
	<?php if ( $current_status === 'draft' ) : ?>
		<button type="button" class="button aigis-status-btn"
			data-post="<?php echo esc_attr( $post->ID ); ?>"
			data-action="aigis_set_policy_status"
			data-status="aigis-in-review"
			data-label="<?php esc_attr_e( 'In Review', 'ai-governance-suite' ); ?>">
			<?php esc_html_e( 'Submit for Review', 'ai-governance-suite' ); ?>
		</button>
	<?php endif; ?>

	<?php if ( $current_status === 'aigis-in-review' && current_user_can( 'aigis_approve_policy' ) ) : ?>
		<button type="button" class="button button-primary aigis-status-btn"
			data-post="<?php echo esc_attr( $post->ID ); ?>"
			data-action="aigis_set_policy_status"
			data-status="aigis-approved"
			data-label="<?php esc_attr_e( 'Approved', 'ai-governance-suite' ); ?>">
			<?php esc_html_e( 'Approve', 'ai-governance-suite' ); ?>
		</button>
		<button type="button" class="button aigis-status-btn"
			data-post="<?php echo esc_attr( $post->ID ); ?>"
			data-action="aigis_set_policy_status"
			data-status="draft"
			data-label="<?php esc_attr_e( 'Draft', 'ai-governance-suite' ); ?>">
			<?php esc_html_e( 'Return to Draft', 'ai-governance-suite' ); ?>
		</button>
	<?php endif; ?>

	<?php if ( in_array( $current_status, [ 'aigis-approved', 'aigis-in-review' ], true ) && current_user_can( 'aigis_manage_policies' ) ) : ?>
		<button type="button" class="button aigis-status-btn"
			data-post="<?php echo esc_attr( $post->ID ); ?>"
			data-action="aigis_set_policy_status"
			data-status="aigis-retired"
			data-label="<?php esc_attr_e( 'Retired', 'ai-governance-suite' ); ?>">
			<?php esc_html_e( 'Retire Policy', 'ai-governance-suite' ); ?>
		</button>
	<?php endif; ?>
</div>
