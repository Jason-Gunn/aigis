<?php
/**
 * Audit log list view.
 *
 * Variables: $items, $total, $page, $per_page, $pages, $filters
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$base_url   = admin_url( 'admin.php?page=aigis-audit-log' );
$export_url = wp_nonce_url( add_query_arg( 'export', '1', $base_url ), 'aigis_export_audit' );
?>
<div class="wrap aigis-wrap">
	<h1><?php esc_html_e( 'Audit Log', 'ai-governance-suite' ); ?></h1>

	<!-- Filters -->
	<form method="get" action="<?php echo esc_url( $base_url ); ?>">
		<input type="hidden" name="page" value="aigis-audit-log">
		<div class="aigis-filter-bar">
			<label>
				<?php esc_html_e( 'Event Type', 'ai-governance-suite' ); ?>
				<input type="text" name="event_type" value="<?php echo esc_attr( $filters['event_type'] ?? '' ); ?>" placeholder="e.g. prompt.created">
			</label>
			<label>
				<?php esc_html_e( 'Object Type', 'ai-governance-suite' ); ?>
				<input type="text" name="object_type" value="<?php echo esc_attr( $filters['object_type'] ?? '' ); ?>">
			</label>
			<label>
				<?php esc_html_e( 'User ID', 'ai-governance-suite' ); ?>
				<input type="number" name="actor_user_id" value="<?php echo esc_attr( $filters['actor_user_id'] ?? '' ); ?>" class="small-text">
			</label>
			<label>
				<?php esc_html_e( 'From', 'ai-governance-suite' ); ?>
				<input type="date" name="date_from" value="<?php echo esc_attr( $filters['date_from'] ?? '' ); ?>">
			</label>
			<label>
				<?php esc_html_e( 'To', 'ai-governance-suite' ); ?>
				<input type="date" name="date_to" value="<?php echo esc_attr( $filters['date_to'] ?? '' ); ?>">
			</label>
			<?php submit_button( __( 'Filter', 'ai-governance-suite' ), 'secondary', '', false ); ?>
			<a href="<?php echo esc_url( $base_url ); ?>" class="button"><?php esc_html_e( 'Reset', 'ai-governance-suite' ); ?></a>
		</div>
	</form>

	<div style="margin-bottom:12px;">
		<a href="<?php echo esc_url( $export_url ); ?>" class="button">
			⬇ <?php esc_html_e( 'Export CSV', 'ai-governance-suite' ); ?>
		</a>
		<span style="margin-left:8px;color:#646970;font-size:.875rem;">
			<?php printf( esc_html( _n( '%s record', '%s records', $total, 'ai-governance-suite' ) ), number_format( $total ) ); ?>
		</span>
	</div>

	<div class="aigis-table-wrap">
		<table class="aigis-table widefat">
			<thead>
				<tr>
					<th>#</th>
					<th><?php esc_html_e( 'Event', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Object', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Actor', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'IP', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Summary', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Time (UTC)', 'ai-governance-suite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $items ) ) : ?>
				<tr><td colspan="7"><?php esc_html_e( 'No audit records match your filters.', 'ai-governance-suite' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $items as $row ) :
						$actor = $row->actor_user_id ? get_userdata( (int) $row->actor_user_id ) : false;
					?>
					<tr>
						<td><?php echo esc_html( $row->id ); ?></td>
						<td><code><?php echo esc_html( $row->event_type ); ?></code></td>
						<td>
							<?php echo esc_html( $row->object_type ); ?>
							<?php if ( $row->object_id ) : ?>
								<span style="color:#646970"> #<?php echo esc_html( $row->object_id ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $actor ) :
								echo esc_html( $actor->display_name . ' (' . $actor->user_login . ')' );
							elseif ( $row->actor_user_id ) :
								echo esc_html( '#' . $row->actor_user_id );
							else :
								esc_html_e( 'System / API', 'ai-governance-suite' );
							endif; ?>
						</td>
						<td><?php echo esc_html( $row->actor_ip ?? '—' ); ?></td>
						<td><?php echo esc_html( wp_trim_words( $row->summary, 15 ) ); ?></td>
						<td><span title="<?php echo esc_attr( $row->occurred_at ); ?>"><?php echo esc_html( $row->occurred_at ); ?></span></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- Pagination -->
	<?php if ( $pages > 1 ) : ?>
	<div class="tablenav bottom">
		<div class="tablenav-pages">
			<?php for ( $p = 1; $p <= min( $pages, 20 ); $p++ ) :
				$url = add_query_arg( array_merge( $filters, [ 'paged' => $p ] ), $base_url );
			?>
				<?php if ( $p === $page ) : ?>
					<span class="current"><?php echo esc_html( $p ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $p ); ?></a>
				<?php endif; ?>
			<?php endfor; ?>
			<?php if ( $pages > 20 ) : ?>
				<span>… <?php echo esc_html( $pages ); ?></span>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>
</div>
