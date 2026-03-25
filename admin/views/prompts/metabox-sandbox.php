<?php
/**
 * Prompt CPT — Sandbox Test metabox view.
 *
 * Variables: $post
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div id="aigis-sandbox-wrap" class="aigis-sandbox">
	<p>
		<label for="aigis-sandbox-input"><strong><?php esc_html_e( 'Test Input', 'ai-governance-suite' ); ?></strong></label>
	</p>
	<textarea id="aigis-sandbox-input" rows="5" style="width:100%;font-family:monospace;"
		placeholder="<?php esc_attr_e( 'Enter test input to send to the model…', 'ai-governance-suite' ); ?>"></textarea>
	<p>
		<button type="button" id="aigis-sandbox-run" class="button button-primary"
			data-post="<?php echo esc_attr( $post->ID ); ?>">
			▶ <?php esc_html_e( 'Run Test', 'ai-governance-suite' ); ?>
		</button>
		<span style="margin-left:8px;color:#646970;font-size:.8125rem;">
			<?php esc_html_e( 'The prompt template will be filled with this input before sending.', 'ai-governance-suite' ); ?>
		</span>
	</p>
	<div id="aigis-sandbox-output" class="aigis-sandbox-output"></div>
</div>
