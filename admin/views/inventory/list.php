<?php
/**
 * AI Inventory list view.
 *
 * Variables: $items, $total, $page, $per_page, $filters, $pages
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$search    = sanitize_text_field( $_GET['s'] ?? '' );
$type_f    = sanitize_text_field( $_GET['access_type'] ?? '' );
$status_f  = sanitize_text_field( $_GET['status'] ?? '' );
$base_url  = admin_url( 'admin.php?page=aigis-inventory' );
?>
<div class="wrap aigis-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'AI Model Inventory', 'ai-governance-suite' ); ?></h1>
	<a href="<?php echo esc_url( add_query_arg( 'action', 'add', $base_url ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'ai-governance-suite' ); ?>
	</a>
	<hr class="wp-header-end">

	<!-- Filters -->
	<form method="get" action="<?php echo esc_url( $base_url ); ?>">
		<input type="hidden" name="page" value="aigis-inventory">
		<div class="aigis-filter-bar">
			<label>
				<?php esc_html_e( 'Search', 'ai-governance-suite' ); ?>
				<input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Model name or vendor…', 'ai-governance-suite' ); ?>">
			</label>
			<label>
				<?php esc_html_e( 'Access Type', 'ai-governance-suite' ); ?>
				<select name="access_type">
					<option value=""><?php esc_html_e( 'All', 'ai-governance-suite' ); ?></option>
					<option value="api" <?php selected( $type_f, 'api' ); ?>><?php esc_html_e( 'API', 'ai-governance-suite' ); ?></option>
					<option value="local" <?php selected( $type_f, 'local' ); ?>><?php esc_html_e( 'Local / On-prem', 'ai-governance-suite' ); ?></option>
					<option value="custom_agent" <?php selected( $type_f, 'custom_agent' ); ?>><?php esc_html_e( 'Custom Agent', 'ai-governance-suite' ); ?></option>
				</select>
			</label>
			<label>
				<?php esc_html_e( 'Status', 'ai-governance-suite' ); ?>
				<select name="status">
					<option value=""><?php esc_html_e( 'All', 'ai-governance-suite' ); ?></option>
					<option value="active" <?php selected( $status_f, 'active' ); ?>><?php esc_html_e( 'Active', 'ai-governance-suite' ); ?></option>
					<option value="inactive" <?php selected( $status_f, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'ai-governance-suite' ); ?></option>
				</select>
			</label>
			<?php submit_button( __( 'Filter', 'ai-governance-suite' ), 'secondary', '', false ); ?>
			<?php if ( $search || $type_f || $status_f ) : ?>
				<a href="<?php echo esc_url( $base_url ); ?>" class="button"><?php esc_html_e( 'Reset', 'ai-governance-suite' ); ?></a>
			<?php endif; ?>
		</div>
	</form>

	<div class="aigis-table-wrap">
		<table class="aigis-table widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Model Name', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Vendor', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Slug', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Access', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Env', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Status', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Max Tokens', 'ai-governance-suite' ); ?></th>
					<th class="column-actions"><?php esc_html_e( 'Actions', 'ai-governance-suite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $items ) ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No models found.', 'ai-governance-suite' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $items as $item ) :
						$edit_url   = add_query_arg( [ 'action' => 'edit', 'id' => $item['id'] ], $base_url );
						$delete_url = wp_nonce_url( add_query_arg( [ 'action' => 'delete', 'id' => $item['id'] ], $base_url ), 'aigis_delete_model_' . $item['id'] );
					?>
					<tr>
						<td>
							<strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $item['model_name'] ); ?></a></strong>
						</td>
						<td><?php echo esc_html( $item['vendor_name'] ); ?></td>
						<td><code><?php echo esc_html( $item['model_slug'] ); ?></code></td>
						<td><?php echo esc_html( ucfirst( $item['access_type'] ) ); ?></td>
						<td><?php echo esc_html( ucfirst( $item['environment'] ?? '—' ) ); ?></td>
						<td>
							<span class="aigis-badge aigis-badge-<?php echo esc_attr( $item['status'] ); ?>">
								<?php echo esc_html( ucfirst( $item['status'] ) ); ?>
							</span>
						</td>
						<td><?php echo $item['max_tokens'] ? esc_html( number_format( (int) $item['max_tokens'] ) ) : '—'; ?></td>
						<td class="column-actions">
							<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'ai-governance-suite' ); ?></a>
							<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small aigis-confirm-delete" data-confirm="<?php esc_attr_e( 'Delete this model?', 'ai-governance-suite' ); ?>">
								<?php esc_html_e( 'Delete', 'ai-governance-suite' ); ?>
							</a>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- Pagination -->
	<?php if ( $total_pages > 1 ) : ?>
	<div class="tablenav bottom">
		<div class="tablenav-pages">
			<span class="displaying-num">
				<?php printf( esc_html( _n( '%s item', '%s items', $total_items, 'ai-governance-suite' ) ), number_format( $total_items ) ); ?>
			</span>
			<?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
				<?php if ( $p === $page ) : ?>
					<span class="current"><?php echo esc_html( $p ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( add_query_arg( 'paged', $p, $base_url ) ); ?>"><?php echo esc_html( $p ); ?></a>
				<?php endif; ?>
			<?php endfor; ?>
		</div>
	</div>
	<?php endif; ?>
</div>
