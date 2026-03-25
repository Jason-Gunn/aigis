<?php
/**
 * Prompt CPT — Promotion Status metabox view.
 *
 * Variables: $post, $current_status, $promotion_log (array of entries)
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$status_labels = [
	'draft'               => __( 'Draft', 'ai-governance-suite' ),
	'aigis-staging'       => __( 'Staging', 'ai-governance-suite' ),
	'aigis-pending-review'=> __( 'Pending Review', 'ai-governance-suite' ),
	'publish'             => __( 'Production', 'ai-governance-suite' ),
];
$label = $status_labels[ $current_status ] ?? $current_status;
?>
<div class="aigis-promotion-row" style="margin-bottom:12px;">
	<strong><?php esc_html_e( 'Current Status:', 'ai-governance-suite' ); ?></strong>
	<span class="aigis-badge aigis-badge-<?php echo esc_attr( $current_status ); ?>">
		<?php echo esc_html( $label ); ?>
	</span>
</div>

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
	<?php if ( $current_status === 'draft' ) : ?>
		<button type="button" class="button aigis-promote-btn"
			data-post="<?php echo esc_attr( $post->ID ); ?>" data-env="staging">
			<?php esc_html_e( '→ Move to Staging', 'ai-governance-suite' ); ?>
		</button>
	<?php endif; ?>
	<?php if ( in_array( $current_status, [ 'draft', 'aigis-staging' ], true ) ) : ?>
		<button type="button" class="button aigis-promote-btn"
			data-post="<?php echo esc_attr( $post->ID ); ?>" data-env="pending_review">
			<?php esc_html_e( '→ Submit for Review', 'ai-governance-suite' ); ?>
		</button>
	<?php endif; ?>
	<?php if ( current_user_can( 'aigis_promote_prompt' ) && $current_status === 'aigis-pending-review' ) : ?>
		<button type="button" class="button button-primary aigis-promote-btn"
			data-post="<?php echo esc_attr( $post->ID ); ?>" data-env="production">
			<?php esc_html_e( '→ Promote to Production', 'ai-governance-suite' ); ?>
		</button>
	<?php endif; ?>
</div>

<?php if ( ! empty( $promotion_log ) ) : ?>
<details>
	<summary style="cursor:pointer;font-weight:600;margin-bottom:6px;"><?php esc_html_e( 'Promotion History', 'ai-governance-suite' ); ?></summary>
	<ul id="aigis-promotion-log" style="font-size:.875rem;margin:4px 0 0 16px;">
		<?php foreach ( $promotion_log as $entry ) : ?>
			<li><?php echo esc_html( $entry ); ?></li>
		<?php endforeach; ?>
	</ul>
</details>
<?php else : ?>
	<ul id="aigis-promotion-log" style="display:none;"></ul>
<?php endif; ?>
