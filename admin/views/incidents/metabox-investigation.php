<?php
/**
 * Incident CPT — Investigation metabox view.
 *
 * Variables: $post, $investigation_notes, $assigned_to, $current_status
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;
// $notes is set by render_investigation_metabox() — saved to _aigis_investigation_notes.
?>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><label for="aigis_investigation_notes"><?php esc_html_e( 'Investigation Notes', 'ai-governance-suite' ); ?></label></th>
		<td>
			<textarea id="aigis_investigation_notes" name="aigis_investigation_notes"
				rows="12" style="width:100%;"><?php echo esc_textarea( $notes ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Chronological notes. Append entries with timestamps for traceability.', 'ai-governance-suite' ); ?></p>
		</td>
	</tr>
</table>
