<?php
/**
 * Skill CPT — Export metabox view.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<p class="description"><?php esc_html_e( 'Portable markdown generated from the current structured fields and editor body.', 'ai-governance-suite' ); ?></p>
<p class="description"><?php esc_html_e( 'This export can be pasted back into the Markdown Import metabox to round-trip a skill into another record.', 'ai-governance-suite' ); ?></p>
<p><a href="<?php echo esc_url( $download_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Download Markdown', 'ai-governance-suite' ); ?></a></p>
<textarea readonly class="large-text code" rows="16"><?php echo esc_textarea( $export_markdown ); ?></textarea>