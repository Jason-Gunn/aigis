<?php
/**
 * Prompt CPT — Settings metabox view.
 *
 * Variables: $post, $model_id, $department, $max_tokens, $temperature, $models
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;
wp_nonce_field( 'aigis_save_prompt_settings', 'aigis_prompt_settings_nonce' );
?>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><label for="aigis_model_id"><?php esc_html_e( 'AI Model', 'ai-governance-suite' ); ?></label></th>
		<td>
			<select id="aigis_model_id" name="aigis_model_id">
				<option value=""><?php esc_html_e( '— None —', 'ai-governance-suite' ); ?></option>
				<?php foreach ( $models as $m ) : ?>
					<option value="<?php echo esc_attr( $m['id'] ); ?>" <?php selected( $model_id, (string) $m['id'] ); ?>>
						<?php echo esc_html( $m['model_name'] . ' (' . $m['vendor_name'] . ')' ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_department"><?php esc_html_e( 'Department', 'ai-governance-suite' ); ?></label></th>
		<td><input type="text" id="aigis_department" name="aigis_department" class="regular-text" value="<?php echo esc_attr( $department ); ?>"></td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_max_tokens"><?php esc_html_e( 'Max Tokens', 'ai-governance-suite' ); ?></label></th>
		<td><input type="number" id="aigis_max_tokens" name="aigis_max_tokens" class="small-text" min="0"
			value="<?php echo esc_attr( $max_tokens ); ?>"></td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_temperature"><?php esc_html_e( 'Temperature', 'ai-governance-suite' ); ?></label></th>
		<td>
			<input type="number" id="aigis_temperature" name="aigis_temperature" class="small-text"
				min="0" max="2" step="0.05" value="<?php echo esc_attr( $temperature ); ?>">
			<p class="description"><?php esc_html_e( '0 = deterministic, 1 = creative, max 2', 'ai-governance-suite' ); ?></p>
		</td>
	</tr>
</table>
