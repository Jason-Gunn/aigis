<?php
/**
 * Policy CPT — Linked Incidents metabox view.
 *
 * Variables: $post, $linked_incidents (WP_Post[])
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<?php if ( empty( $linked_incidents ) ) : ?>
	<p style="color:#646970"><?php esc_html_e( 'No incidents linked to this policy.', 'ai-governance-suite' ); ?></p>
<?php else : ?>
	<ul style="margin:0;padding-left:16px;">
		<?php foreach ( $linked_incidents as $inc ) :
			$severity = get_post_meta( $inc->ID, '_aigis_severity', true ) ?: 'medium';
		?>
		<li style="margin-bottom:4px;">
			<a href="<?php echo esc_url( get_edit_post_link( $inc ) ); ?>"><?php echo esc_html( $inc->post_title ); ?></a>
			<span class="aigis-badge aigis-badge-<?php echo esc_attr( $severity ); ?>" style="margin-left:4px;"><?php echo esc_html( ucfirst( $severity ) ); ?></span>
		</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
