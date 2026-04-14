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
$item        = is_array( $item ) ? $item : [];

$f = fn( string $key ) => ( $item[ $key ] ?? '' );
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
		<?php wp_nonce_field( 'aigis_inventory_save', 'aigis_inventory_nonce' ); ?>
		<input type="hidden" name="aigis_inventory_action" value="<?php echo esc_attr( $is_edit ? 'update' : 'create' ); ?>">
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
				<th scope="row"><label for="agent_identifier"><?php esc_html_e( 'Agent Identifier *', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="text" id="agent_identifier" name="agent_identifier" class="regular-text" required
						value="<?php echo esc_attr( $f('agent_identifier') ); ?>">
					<p class="description"><?php esc_html_e( 'Stable internal identifier used by logs and API routing.', 'ai-governance-suite' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="integration_type"><?php esc_html_e( 'Integration Type *', 'ai-governance-suite' ); ?></label></th>
				<td>
					<select id="integration_type" name="integration_type" required>
						<option value="api-model"    <?php selected( $f('integration_type'), 'api-model' ); ?>><?php esc_html_e( 'API Model', 'ai-governance-suite' ); ?></option>
						<option value="on-prem"      <?php selected( $f('integration_type'), 'on-prem' ); ?>><?php esc_html_e( 'On-prem', 'ai-governance-suite' ); ?></option>
						<option value="custom-agent" <?php selected( $f('integration_type'), 'custom-agent' ); ?>><?php esc_html_e( 'Custom Agent', 'ai-governance-suite' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="model_version"><?php esc_html_e( 'Model Version', 'ai-governance-suite' ); ?></label></th>
				<td>
					<input type="text" id="model_version" name="model_version" class="regular-text"
						value="<?php echo esc_attr( $f('model_version') ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="status"><?php esc_html_e( 'Status', 'ai-governance-suite' ); ?></label></th>
				<td>
					<select id="status" name="status">
						<option value="active"   <?php selected( $f('status'), 'active' ); ?>><?php esc_html_e( 'Active', 'ai-governance-suite' ); ?></option>
						<option value="deprecated" <?php selected( $f('status'), 'deprecated' ); ?>><?php esc_html_e( 'Deprecated', 'ai-governance-suite' ); ?></option>
						<option value="under-review" <?php selected( $f('status'), 'under-review' ); ?>><?php esc_html_e( 'Under Review', 'ai-governance-suite' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="api_endpoint"><?php esc_html_e( 'API Endpoint', 'ai-governance-suite' ); ?></label></th>
				<td><input type="url" id="api_endpoint" name="api_endpoint" class="regular-text"
					value="<?php echo esc_attr( $f('api_endpoint') ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="data_categories"><?php esc_html_e( 'Data Categories', 'ai-governance-suite' ); ?></label></th>
				<td><input type="text" id="data_categories" name="data_categories" class="regular-text"
					value="<?php echo esc_attr( $f('data_categories') ); ?>">
				<p class="description"><?php esc_html_e( 'Comma-separated categories such as customer_messages,policy_documents.', 'ai-governance-suite' ); ?></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="risk_level"><?php esc_html_e( 'Risk Level', 'ai-governance-suite' ); ?></label></th>
				<td>
					<select id="risk_level" name="risk_level">
						<option value="low" <?php selected( $f('risk_level'), 'low' ); ?>><?php esc_html_e( 'Low', 'ai-governance-suite' ); ?></option>
						<option value="medium" <?php selected( $f('risk_level'), 'medium' ); ?>><?php esc_html_e( 'Medium', 'ai-governance-suite' ); ?></option>
						<option value="high" <?php selected( $f('risk_level'), 'high' ); ?>><?php esc_html_e( 'High', 'ai-governance-suite' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="owner_user_id"><?php esc_html_e( 'Owner User ID', 'ai-governance-suite' ); ?></label></th>
				<td><input type="number" id="owner_user_id" name="owner_user_id" class="small-text" min="0"
					value="<?php echo esc_attr( $f('owner_user_id') ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="notes"><?php esc_html_e( 'Notes', 'ai-governance-suite' ); ?></label></th>
				<td><textarea id="notes" name="notes" rows="4" class="large-text"><?php echo esc_textarea( $f('notes') ); ?></textarea></td>
			</tr>
		</table>

		<?php submit_button( $is_edit ? __( 'Update Model', 'ai-governance-suite' ) : __( 'Add Model', 'ai-governance-suite' ) ); ?>
	</form>
</div>
