<?php
/**
 * Incident CPT — Post-mortem metabox view.
 *
 * Variables: $post, $root_cause, $remediation, $lessons_learned, $postmortem_completed
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;
// $postmortem is set by render_postmortem_metabox() — saved to _aigis_postmortem.
?>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><label for="aigis_postmortem"><?php esc_html_e( 'Post-mortem Notes', 'ai-governance-suite' ); ?></label></th>
		<td>
			<textarea id="aigis_postmortem" name="aigis_postmortem"
				rows="16" style="width:100%;"><?php echo esc_textarea( $postmortem ); ?></textarea>
			<p class="description">
				<?php esc_html_e( 'Document root cause, remediation steps, and lessons learned. Use structured headings for clarity.', 'ai-governance-suite' ); ?>
			</p>
		</td>
	</tr>
</table>
