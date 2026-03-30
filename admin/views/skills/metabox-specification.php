<?php
/**
 * Skill CPT — Specification metabox view.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) exit;

wp_nonce_field( 'aigis_save_skill', 'aigis_skill_spec_nonce' );
?>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><label for="aigis_skill_description"><?php esc_html_e( 'Description', 'ai-governance-suite' ); ?></label></th>
		<td>
			<input type="text" id="aigis_skill_description" name="aigis_skill_description" class="regular-text" maxlength="255" value="<?php echo esc_attr( $description ); ?>">
			<p class="description"><?php esc_html_e( 'Single-line routing description for agents. Be specific about trigger phrases and the artifact this skill produces.', 'ai-governance-suite' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_skill_tier"><?php esc_html_e( 'Tier', 'ai-governance-suite' ); ?></label></th>
		<td>
			<select id="aigis_skill_tier" name="aigis_skill_tier">
				<?php foreach ( [ 'standard' => __( 'Standard', 'ai-governance-suite' ), 'methodology' => __( 'Methodology', 'ai-governance-suite' ), 'personal' => __( 'Personal Workflow', 'ai-governance-suite' ) ] as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $tier ?: 'methodology', $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_skill_version"><?php esc_html_e( 'Version', 'ai-governance-suite' ); ?></label></th>
		<td><input type="text" id="aigis_skill_version" name="aigis_skill_version" class="regular-text" value="<?php echo esc_attr( $version ?: '0.1.0' ); ?>"></td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_skill_format"><?php esc_html_e( 'Output Format', 'ai-governance-suite' ); ?></label></th>
		<td>
			<select id="aigis_skill_format" name="aigis_skill_format">
				<?php foreach ( [ 'markdown', 'json', 'table', 'csv', 'excel', 'pdf', 'html' ] as $candidate ) : ?>
					<option value="<?php echo esc_attr( $candidate ); ?>" <?php selected( $format ?: 'markdown', $candidate ); ?>><?php echo esc_html( strtoupper( $candidate ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_skill_lifecycle_status"><?php esc_html_e( 'Lifecycle Status', 'ai-governance-suite' ); ?></label></th>
		<td>
			<select id="aigis_skill_lifecycle_status" name="aigis_skill_lifecycle_status">
				<?php foreach ( [ 'draft' => __( 'Draft', 'ai-governance-suite' ), 'aigis-pending-review' => __( 'Pending Review', 'ai-governance-suite' ), 'aigis-staging' => __( 'Staging', 'ai-governance-suite' ), 'publish' => __( 'Production', 'ai-governance-suite' ) ] as $value => $label ) : ?>
					<?php if ( 'publish' === $value && ! $can_approve ) continue; ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $lifecycle_status ?: 'draft', $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php if ( ! $can_approve ) : ?>
				<p class="description"><?php esc_html_e( 'Production status is only available to skill approvers.', 'ai-governance-suite' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_skill_status_note"><?php esc_html_e( 'Transition Note', 'ai-governance-suite' ); ?></label></th>
		<td>
			<textarea id="aigis_skill_status_note" name="aigis_skill_status_note" class="large-text" rows="3"></textarea>
			<p class="description"><?php esc_html_e( 'Optional note recorded when the lifecycle status changes.', 'ai-governance-suite' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_skill_trigger_phrases"><?php esc_html_e( 'Trigger Phrases', 'ai-governance-suite' ); ?></label></th>
		<td>
			<textarea id="aigis_skill_trigger_phrases" name="aigis_skill_trigger_phrases" class="large-text" rows="4"><?php echo esc_textarea( $trigger_phrases ); ?></textarea>
			<p class="description"><?php esc_html_e( 'List the requests or intents that should cause this skill to fire. One per line or as bullets.', 'ai-governance-suite' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_skill_output_contract"><?php esc_html_e( 'Output Contract', 'ai-governance-suite' ); ?></label></th>
		<td><textarea id="aigis_skill_output_contract" name="aigis_skill_output_contract" class="large-text" rows="5"><?php echo esc_textarea( $output_contract ); ?></textarea></td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_skill_edge_cases"><?php esc_html_e( 'Edge Cases', 'ai-governance-suite' ); ?></label></th>
		<td><textarea id="aigis_skill_edge_cases" name="aigis_skill_edge_cases" class="large-text" rows="5"><?php echo esc_textarea( $edge_cases ); ?></textarea></td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_skill_examples"><?php esc_html_e( 'Examples', 'ai-governance-suite' ); ?></label></th>
		<td><textarea id="aigis_skill_examples" name="aigis_skill_examples" class="large-text" rows="5"><?php echo esc_textarea( $examples ); ?></textarea></td>
	</tr>
</table>
<p class="description"><?php esc_html_e( 'Use the main WordPress editor for the full methodology and instructions body. Keep the core lean and focused on reusable execution guidance.', 'ai-governance-suite' ); ?></p>