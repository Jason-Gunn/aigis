<?php
/**
 * Workflow CPT — Mermaid Diagram metabox view.
 *
 * Variables: $post, $diagram_source (string)
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;
wp_nonce_field( 'aigis_save_workflow', 'aigis_workflow_nonce' );
?>
<p class="description" style="margin-bottom:8px;">
	<?php esc_html_e( 'Enter a Mermaid diagram definition. The preview updates automatically.', 'ai-governance-suite' ); ?>
	<a href="https://mermaid.js.org/syntax/flowchart.html" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Syntax reference ↗', 'ai-governance-suite' ); ?></a>
</p>

<div class="aigis-mermaid-wrap">
	<div class="aigis-mermaid-source">
		<label for="aigis-diagram-source" class="screen-reader-text"><?php esc_html_e( 'Diagram source', 'ai-governance-suite' ); ?></label>
		<textarea id="aigis-diagram-source" name="aigis_diagram_source" rows="16"
			style="width:100%;font-family:monospace;font-size:.875rem;resize:vertical;color:#1d2327;background:#fff;"><?php echo esc_textarea( $diagram_source ); ?></textarea>
	</div>
	<div id="aigis-diagram-preview" class="aigis-mermaid-preview">
		<p style="color:#646970;font-size:.875rem;"><?php esc_html_e( 'Preview will appear here…', 'ai-governance-suite' ); ?></p>
	</div>
</div>

<div id="aigis-diagram-error" class="aigis-notice aigis-notice-error" style="margin-top:8px;display:none;"></div>
