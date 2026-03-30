<?php
/**
 * Skill CPT — Review actions metabox view.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$status_labels = [
	'draft'                => __( 'Draft', 'ai-governance-suite' ),
	'aigis-staging'        => __( 'Staging', 'ai-governance-suite' ),
	'aigis-pending-review' => __( 'Pending Review', 'ai-governance-suite' ),
	'publish'              => __( 'Production', 'ai-governance-suite' ),
];
?>
<p>
	<strong><?php esc_html_e( 'Current Status:', 'ai-governance-suite' ); ?></strong>
	<span class="aigis-badge aigis-badge-<?php echo esc_attr( $current_status ); ?>">
		<?php echo esc_html( $status_labels[ $current_status ] ?? $current_status ); ?>
	</span>
</p>

<div style="display:flex;gap:8px;flex-wrap:wrap;">
	<?php if ( $can_manage && $current_status === 'draft' ) : ?>
		<button type="button" class="button aigis-skill-transition-btn" data-post="<?php echo esc_attr( $post->ID ); ?>" data-status="aigis-staging" data-label="<?php esc_attr_e( 'Staging', 'ai-governance-suite' ); ?>">
			<?php esc_html_e( 'Move to Staging', 'ai-governance-suite' ); ?>
		</button>
	<?php endif; ?>

	<?php if ( $can_manage && in_array( $current_status, [ 'draft', 'aigis-staging' ], true ) ) : ?>
		<button type="button" class="button aigis-skill-transition-btn" data-post="<?php echo esc_attr( $post->ID ); ?>" data-status="aigis-pending-review" data-label="<?php esc_attr_e( 'Pending Review', 'ai-governance-suite' ); ?>">
			<?php esc_html_e( 'Submit for Review', 'ai-governance-suite' ); ?>
		</button>
	<?php endif; ?>

	<?php if ( $can_approve && $current_status === 'aigis-pending-review' ) : ?>
		<button type="button" class="button aigis-skill-transition-btn" data-post="<?php echo esc_attr( $post->ID ); ?>" data-status="aigis-staging" data-label="<?php esc_attr_e( 'Staging', 'ai-governance-suite' ); ?>">
			<?php esc_html_e( 'Reject to Staging', 'ai-governance-suite' ); ?>
		</button>
		<button type="button" class="button button-primary aigis-skill-transition-btn" data-post="<?php echo esc_attr( $post->ID ); ?>" data-status="publish" data-label="<?php esc_attr_e( 'Production', 'ai-governance-suite' ); ?>">
			<?php esc_html_e( 'Approve to Production', 'ai-governance-suite' ); ?>
		</button>
	<?php endif; ?>
</div>

<?php if ( ! $can_manage && ! $can_approve ) : ?>
	<p class="description"><?php esc_html_e( 'You do not have permission to change this skill status.', 'ai-governance-suite' ); ?></p>
<?php endif; ?>