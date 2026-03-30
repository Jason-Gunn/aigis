<?php
/**
 * Skill CPT — Import metabox view.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<p class="description"><?php esc_html_e( 'Paste a markdown export here, check the box, and save the post to replace the current skill specification with the imported content.', 'ai-governance-suite' ); ?></p>
<textarea name="aigis_skill_import_markdown" class="large-text code" rows="10" placeholder="---&#10;name: Example skill&#10;description: Single-line routing description&#10;version: 1.0.0&#10;---"></textarea>
<p>
	<label for="aigis_skill_import_apply">
		<input type="checkbox" id="aigis_skill_import_apply" name="aigis_skill_import_apply" value="1">
		<?php esc_html_e( 'Apply pasted markdown on save', 'ai-governance-suite' ); ?>
	</label>
</p>
<p class="description"><?php esc_html_e( 'Import updates the title, methodology body, structured fields, and any related assets whose titles already exist in AIGIS.', 'ai-governance-suite' ); ?></p>
