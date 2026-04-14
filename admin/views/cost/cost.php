<?php
/**
 * Cost & Budgets view.
 *
 * Variables: $budgets (array with actual_spend, pct_used), $errors (array)
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$base_url = admin_url( 'admin.php?page=aigis-cost' );
?>
<div class="wrap aigis-wrap">
	<h1><?php esc_html_e( 'Cost & Budgets', 'ai-governance-suite' ); ?></h1>

	<?php if ( ! empty( $errors ) ) : ?>
	<div class="notice notice-error is-dismissible">
		<ul><?php foreach ( $errors as $e ) echo '<li>' . esc_html( $e ) . '</li>'; ?></ul>
	</div>
	<?php endif; ?>

	<div class="aigis-row">
		<!-- Budget list -->
		<div class="aigis-col-2">
			<div class="aigis-card">
				<div class="aigis-card-header">
					<h3><?php esc_html_e( 'Active Budgets', 'ai-governance-suite' ); ?></h3>
				</div>

				<?php if ( empty( $budgets ) ) : ?>
					<p><?php esc_html_e( 'No budgets configured yet.', 'ai-governance-suite' ); ?></p>
				<?php else : ?>
				<div class="aigis-table-wrap">
					<table class="aigis-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Label', 'ai-governance-suite' ); ?></th>
								<th><?php esc_html_e( 'Scope', 'ai-governance-suite' ); ?></th>
								<th><?php esc_html_e( 'Period', 'ai-governance-suite' ); ?></th>
								<th><?php esc_html_e( 'Limit', 'ai-governance-suite' ); ?></th>
								<th><?php esc_html_e( 'Spent', 'ai-governance-suite' ); ?></th>
								<th><?php esc_html_e( 'Utilisation', 'ai-governance-suite' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'ai-governance-suite' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $budgets as $b ) :
								$pct        = min( 100, (float) ( $b['pct_used'] ?? 0 ) );
								$bar_class  = $pct >= 100 ? 'aigis-danger' : ( $pct >= 80 ? 'aigis-warn' : '' );
								$edit_url   = add_query_arg( [ 'budget_action' => 'edit', 'budget_id' => $b['id'] ], $base_url );
								$delete_url = wp_nonce_url( add_query_arg( [ 'budget_action' => 'delete', 'budget_id' => $b['id'] ], $base_url ), 'aigis_delete_budget_' . $b['id'] );
							?>
							<tr>
								<td><?php echo esc_html( $b['label'] ); ?></td>
								<td><?php echo esc_html( ucfirst( $b['scope_type'] ) ); ?>
									<?php if ( $b['scope_value'] ) echo ' — ' . esc_html( $b['scope_value'] ); ?>
								</td>
								<td><?php echo esc_html( ucfirst( $b['period_type'] ) ); ?></td>
								<td>$<?php echo esc_html( number_format( (float) $b['budget_usd'], 2 ) ); ?></td>
								<td>$<?php echo esc_html( number_format( (float) ( $b['actual_spend'] ?? 0 ), 2 ) ); ?></td>
								<td style="min-width:100px;">
									<div class="aigis-progress">
										<div class="aigis-progress-bar <?php echo esc_attr( $bar_class ); ?>"
											style="width:<?php echo esc_attr( $pct ); ?>%"></div>
									</div>
									<span style="font-size:.8125rem"><?php echo esc_html( number_format( $pct, 1 ) ); ?>%</span>
								</td>
								<td class="column-actions">
									<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'ai-governance-suite' ); ?></a>
									<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small aigis-confirm-delete"><?php esc_html_e( 'Delete', 'ai-governance-suite' ); ?></a>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Add / edit budget form -->
		<div class="aigis-col">
			<div class="aigis-card">
				<h3>
					<?php echo 'edit' === ( $budget_action ?? '' )
						? esc_html__( 'Edit Budget', 'ai-governance-suite' )
						: esc_html__( 'Add Budget', 'ai-governance-suite' ); ?>
				</h3>
				<?php
				$edit_id   = absint( $_GET['budget_id'] ?? 0 );
				$edit_data = [];
				if ( $edit_id ) {
					$db        = new AIGIS_DB_Cost();
					$edit_data = (array) ( $db->get( $edit_id ) ?: [] );
				}
				$f = fn( $k ) => $edit_data[ $k ] ?? '';
				?>
				<form method="post" action="<?php echo esc_url( $base_url ); ?>">
					<?php wp_nonce_field( 'aigis_save_budget', 'aigis_budget_nonce' ); ?>
					<input type="hidden" name="budget_action" value="<?php echo esc_attr( $edit_id ? 'update' : 'create' ); ?>">
					<?php if ( $edit_id ) : ?>
						<input type="hidden" name="budget_id" value="<?php echo esc_attr( $edit_id ); ?>">
					<?php endif; ?>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="budget_label"><?php esc_html_e( 'Label *', 'ai-governance-suite' ); ?></label></th>
							<td><input type="text" id="budget_label" name="label" class="regular-text" required value="<?php echo esc_attr( $f('label') ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="budget_scope"><?php esc_html_e( 'Scope', 'ai-governance-suite' ); ?></label></th>
							<td>
								<select id="budget_scope" name="scope">
							<option value="global"     <?php selected( $f('scope_type'), 'global' ); ?>><?php esc_html_e( 'Global', 'ai-governance-suite' ); ?></option>
							<option value="department" <?php selected( $f('scope_type'), 'department' ); ?>><?php esc_html_e( 'Department', 'ai-governance-suite' ); ?></option>
							<option value="project"    <?php selected( $f('scope_type'), 'project' ); ?>><?php esc_html_e( 'Project', 'ai-governance-suite' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="budget_scope_value"><?php esc_html_e( 'Scope Value', 'ai-governance-suite' ); ?></label></th>
							<td>
								<input type="text" id="budget_scope_value" name="scope_value" class="regular-text"
									value="<?php echo esc_attr( $f('scope_value') ); ?>"
									placeholder="<?php esc_attr_e( 'dept name or project tag', 'ai-governance-suite' ); ?>">
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="budget_period"><?php esc_html_e( 'Period', 'ai-governance-suite' ); ?></label></th>
							<td>
								<select id="budget_period" name="period">
							<option value="monthly" <?php selected( $f('period_type'), 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'ai-governance-suite' ); ?></option>
							<option value="custom"  <?php selected( $f('period_type'), 'custom' ); ?>><?php esc_html_e( 'Custom', 'ai-governance-suite' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="budget_limit"><?php esc_html_e( 'Limit (USD) *', 'ai-governance-suite' ); ?></label></th>
							<td><input type="number" id="budget_limit" name="budget_usd" step="0.01" min="0" class="small-text" required value="<?php echo esc_attr( $f('budget_usd') ); ?>"></td>
						</tr>
					</table>

					<?php submit_button( $edit_id ? __( 'Update Budget', 'ai-governance-suite' ) : __( 'Add Budget', 'ai-governance-suite' ) ); ?>
					<?php if ( $edit_id ) : ?>
						<a href="<?php echo esc_url( $base_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'ai-governance-suite' ); ?></a>
					<?php endif; ?>
				</form>
			</div>
		</div>
	</div>

	<!-- Cost trend chart -->
	<div class="aigis-card">
		<h3><?php esc_html_e( 'Monthly Spend Trend', 'ai-governance-suite' ); ?></h3>
		<div class="aigis-chart-wrap aigis-chart-sm">
			<canvas id="aigis-chart-cost-trend"></canvas>
		</div>
	</div>
</div>
