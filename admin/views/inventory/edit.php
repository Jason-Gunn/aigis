<?php
/**
 * AI Inventory add / edit view.
 *
 * Variables: $item (array, empty for add), $is_edit (bool), $errors (array)
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$back_url    = admin_url( 'admin.php?page=aigis-inventory' );
$form_action = admin_url( 'admin.php?page=aigis-inventory' );
$title       = $is_edit
	? __( 'Edit AI Model', 'ai-governance-suite' )
	: __( 'Add New AI Model', 'ai-governance-suite' );

$f = fn( string $key ) => $item[ $key ] ?? '';
?>
<div class="wrap aigis-wrap">
	<h1>
		<?php echo esc_html( $title ); ?>
		<a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action"><?php esc_html_e( '← Back to list', 'ai-governance-suite' ); ?></a>
	</h1>

	<?php if ( ! empty( $errors ) ) : ?>
	<div class="notice notice-error">
		<ul>
			<?php foreach ( $errors as $err ) : ?>
				<li><?php echo esc_html( $err ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( $form_action ); ?>">
		<?php wp_nonce_field( 'aigis_save_model', 'aigis_model_nonce' ); ?>
		<input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
		<?php if ( $is_edit ) : ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ); ?>">
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="model_name"><?php esc_html_e( 'Model Name *', 'ai-governance-suite' ); ?></label></th>
				<td><input type="text" id="model_name" name="model_name" class="regular-text" required
					value="<?php echo esc_attr( $f('model_name') ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="vendor_name"><?php esc_html_e( 'Vendor *', 'ai-governance-suite' ); ?></label></th>
				<td><input type="text" id="vendor_name" name="vendor_name" class="regular-text" required
					value="<?php echo esc_attr( $f('vendor_name') ); ?>"
					list="aigis-vendor-list">
				<datalist id="aigis-vendor-list">
					<option value="OpenAI"><option value="Anthropic"><option value="Ollama"><option value="Google"><option value="Mistral"><option value="Cohere">
				</datalist></td>
			</tr>
			<tr>
				<th scope="row"><label for="model_slug"><?php esc_html_e( 'Model Slug *', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="text" id="model_slug" name="model_slug" class="regular-text" required
						value="<?php echo esc_attr( $f('model_slug') ); ?>">
					<p class="description"><?php esc_html_e( 'e.g. gpt-4o, claude-3-opus-20240229, llama3', 'ai-governance-suite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="access_type"><?php esc_html_e( 'Access Type *', 'ai-governance-suite' ); ?></label></th>
				<td>
					<select id="access_type" name="access_type" required>
						<option value="api"          <?php selected( $f('access_type'), 'api' ); ?>><?php esc_html_e( 'API', 'ai-governance-suite' ); ?></option>
						<option value="local"        <?php selected( $f('access_type'), 'local' ); ?>><?php esc_html_e( 'Local / On-prem', 'ai-governance-suite' ); ?></option>
						<option value="custom_agent" <?php selected( $f('access_type'), 'custom_agent' ); ?>><?php esc_html_e( 'Custom Agent', 'ai-governance-suite' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="environment"><?php esc_html_e( 'Environment', 'ai-governance-suite' ); ?></label></th>
				<td>
					<select id="environment" name="environment">
						<option value="development" <?php selected( $f('environment'), 'development' ); ?>><?php esc_html_e( 'Development', 'ai-governance-suite' ); ?></option>
						<option value="staging"     <?php selected( $f('environment'), 'staging' ); ?>><?php esc_html_e( 'Staging', 'ai-governance-suite' ); ?></option>
						<option value="production"  <?php selected( $f('environment'), 'production' ); ?>><?php esc_html_e( 'Production', 'ai-governance-suite' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="status"><?php esc_html_e( 'Status', 'ai-governance-suite' ); ?></label></th>
				<td>
					<select id="status" name="status">
						<option value="active"   <?php selected( $f('status'), 'active' ); ?>><?php esc_html_e( 'Active', 'ai-governance-suite' ); ?></option>
						<option value="inactive" <?php selected( $f('status'), 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'ai-governance-suite' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="max_tokens"><?php esc_html_e( 'Max Tokens', 'ai-governance-suite' ); ?></label></th>
				<td><input type="number" id="max_tokens" name="max_tokens" class="small-text" min="0"
					value="<?php echo esc_attr( $f('max_tokens') ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="context_window"><?php esc_html_e( 'Context Window', 'ai-governance-suite' ); ?></label></th>
				<td><input type="number" id="context_window" name="context_window" class="small-text" min="0"
					value="<?php echo esc_attr( $f('context_window') ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="cost_per_1k_input"><?php esc_html_e( 'Cost / 1K Input Tokens (USD)', 'ai-governance-suite' ); ?></label></th>
				<td><input type="number" id="cost_per_1k_input" name="cost_per_1k_input" step="0.00001" min="0" class="small-text"
					value="<?php echo esc_attr( $f('cost_per_1k_input') ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="cost_per_1k_output"><?php esc_html_e( 'Cost / 1K Output Tokens (USD)', 'ai-governance-suite' ); ?></label></th>
				<td><input type="number" id="cost_per_1k_output" name="cost_per_1k_output" step="0.00001" min="0" class="small-text"
					value="<?php echo esc_attr( $f('cost_per_1k_output') ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="notes"><?php esc_html_e( 'Notes', 'ai-governance-suite' ); ?></label></th>
				<td><textarea id="notes" name="notes" rows="4" class="large-text"><?php echo esc_textarea( $f('notes') ); ?></textarea></td>
			</tr>
		</table>

		<?php submit_button( $is_edit ? __( 'Update Model', 'ai-governance-suite' ) : __( 'Add Model', 'ai-governance-suite' ) ); ?>
	</form>
</div>
