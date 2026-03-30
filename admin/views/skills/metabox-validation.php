<?php
/**
 * Skill CPT — Validation metabox view.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<p><strong><?php esc_html_e( 'Agent Readiness Score', 'ai-governance-suite' ); ?>:</strong> <?php echo esc_html( (string) $validation['score'] ); ?>/100</p>
<p><strong><?php esc_html_e( 'Production Ready', 'ai-governance-suite' ); ?>:</strong> <?php echo ! empty( $validation['production_ready'] ) ? esc_html__( 'Yes', 'ai-governance-suite' ) : esc_html__( 'Not yet', 'ai-governance-suite' ); ?></p>

<?php if ( ! empty( $validation['errors'] ) ) : ?>
	<p><strong><?php esc_html_e( 'Errors', 'ai-governance-suite' ); ?></strong></p>
	<ul>
		<?php foreach ( $validation['errors'] as $error ) : ?>
			<li><?php echo esc_html( $error ); ?></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>

<?php if ( ! empty( $validation['warnings'] ) ) : ?>
	<p><strong><?php esc_html_e( 'Warnings', 'ai-governance-suite' ); ?></strong></p>
	<ul>
		<?php foreach ( $validation['warnings'] as $warning ) : ?>
			<li><?php echo esc_html( $warning ); ?></li>
		<?php endforeach; ?>
	</ul>
<?php else : ?>
	<p class="description"><?php esc_html_e( 'No validation warnings at the moment.', 'ai-governance-suite' ); ?></p>
<?php endif; ?>