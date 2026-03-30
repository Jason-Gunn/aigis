<?php
/**
 * Skill CPT — Relationships metabox view.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><label for="aigis_linked_inventory_id"><?php esc_html_e( 'Linked AI System', 'ai-governance-suite' ); ?></label></th>
		<td>
			<select id="aigis_linked_inventory_id" name="aigis_linked_inventory_id">
				<option value=""><?php esc_html_e( '— None —', 'ai-governance-suite' ); ?></option>
				<?php foreach ( $models as $model ) : ?>
					<option value="<?php echo esc_attr( $model['id'] ); ?>" <?php selected( $inventory_id, (int) $model['id'] ); ?>>
						<?php echo esc_html( $model['model_name'] . ' (' . $model['vendor_name'] . ')' ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_skill_team"><?php esc_html_e( 'Owning Team', 'ai-governance-suite' ); ?></label></th>
		<td><input type="text" id="aigis_skill_team" name="aigis_skill_team" class="regular-text" value="<?php echo esc_attr( $team ); ?>"></td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_linked_prompt_ids"><?php esc_html_e( 'Linked Prompts', 'ai-governance-suite' ); ?></label></th>
		<td>
			<select id="aigis_linked_prompt_ids" name="aigis_linked_prompt_ids[]" class="widefat" multiple size="6">
				<?php foreach ( $prompts as $prompt ) : ?>
					<option value="<?php echo esc_attr( $prompt->ID ); ?>" <?php selected( in_array( (int) $prompt->ID, $linked_prompts, true ) ); ?>>
						<?php echo esc_html( get_the_title( $prompt ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_linked_workflow_ids"><?php esc_html_e( 'Linked Workflows', 'ai-governance-suite' ); ?></label></th>
		<td>
			<select id="aigis_linked_workflow_ids" name="aigis_linked_workflow_ids[]" class="widefat" multiple size="6">
				<?php foreach ( $workflows as $workflow ) : ?>
					<option value="<?php echo esc_attr( $workflow->ID ); ?>" <?php selected( in_array( (int) $workflow->ID, $linked_flows, true ) ); ?>>
						<?php echo esc_html( get_the_title( $workflow ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description"><?php esc_html_e( 'Hold Ctrl or Cmd to select multiple related assets.', 'ai-governance-suite' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_linked_policy_ids"><?php esc_html_e( 'Linked Policies', 'ai-governance-suite' ); ?></label></th>
		<td>
			<select id="aigis_linked_policy_ids" name="aigis_linked_policy_ids[]" class="widefat" multiple size="5">
				<?php foreach ( $policies as $policy ) : ?>
					<option value="<?php echo esc_attr( $policy->ID ); ?>" <?php selected( in_array( (int) $policy->ID, $linked_policies, true ) ); ?>>
						<?php echo esc_html( get_the_title( $policy ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_linked_incident_ids"><?php esc_html_e( 'Linked Incidents', 'ai-governance-suite' ); ?></label></th>
		<td>
			<select id="aigis_linked_incident_ids" name="aigis_linked_incident_ids[]" class="widefat" multiple size="5">
				<?php foreach ( $incidents as $incident ) : ?>
					<option value="<?php echo esc_attr( $incident->ID ); ?>" <?php selected( in_array( (int) $incident->ID, $linked_incidents, true ) ); ?>>
						<?php echo esc_html( get_the_title( $incident ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description"><?php esc_html_e( 'Use these links to tie a skill back to its governing policies and any incidents that shaped or validated it.', 'ai-governance-suite' ); ?></p>
		</td>
	</tr>
</table>