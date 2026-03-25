<?php
/**
 * Evaluation view (tabbed: results / pending review / trends).
 *
 * Variables: $active_tab, $results, $pending, $stats, $trend_data
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$base_url = admin_url( 'admin.php?page=aigis-eval' );
$tabs = [
	'results' => __( 'All Results', 'ai-governance-suite' ),
	'pending' => __( 'Pending Review', 'ai-governance-suite' ),
	'trends'  => __( 'Trends', 'ai-governance-suite' ),
];
?>
<div class="wrap aigis-wrap">
	<h1><?php esc_html_e( 'Evaluation', 'ai-governance-suite' ); ?></h1>

	<!-- KPI bar -->
	<div class="aigis-kpi-grid" style="margin-bottom:12px;">
		<div class="aigis-kpi-card">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Total Runs', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value"><?php echo esc_html( number_format( (int) ( $stats['total'] ?? 0 ) ) ); ?></div>
		</div>
		<div class="aigis-kpi-card aigis-kpi-success">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Pass Rate', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value"><?php echo esc_html( number_format( (float) ( $stats['pass_rate'] ?? 0 ), 1 ) ); ?>%</div>
		</div>
		<div class="aigis-kpi-card aigis-kpi-error">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Fail Rate', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value"><?php echo esc_html( number_format( (float) ( $stats['fail_rate'] ?? 0 ), 1 ) ); ?>%</div>
		</div>
		<div class="aigis-kpi-card aigis-kpi-warn">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Pending Review', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value"><?php echo esc_html( (int) ( $stats['pending'] ?? 0 ) ); ?></div>
		</div>
		<div class="aigis-kpi-card">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Avg Score', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value"><?php echo esc_html( number_format( (float) ( $stats['avg_score'] ?? 0 ), 3 ) ); ?></div>
		</div>
	</div>

	<!-- Tabs -->
	<nav class="aigis-tabs">
		<?php foreach ( $tabs as $slug => $label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base_url ) ); ?>"
			   class="aigis-tab <?php echo $active_tab === $slug ? 'aigis-tab-active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
				<?php if ( $slug === 'pending' && ! empty( $stats['pending'] ) ) : ?>
					<span style="background:#d63638;color:#fff;border-radius:10px;padding:1px 6px;font-size:.75rem;margin-left:4px;">
						<?php echo esc_html( $stats['pending'] ); ?>
					</span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php if ( $active_tab === 'results' ) : ?>
	<!-- All results table -->
	<div class="aigis-table-wrap">
		<table class="aigis-table widefat">
			<thead>
				<tr>
					<th>#</th>
					<th><?php esc_html_e( 'Prompt', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Model', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Result', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Score', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Human Verdict', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Evaluator', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Date', 'ai-governance-suite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $results ) ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'No evaluation results yet.', 'ai-governance-suite' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $results as $r ) : ?>
					<tr>
						<td><?php echo esc_html( $r->id ); ?></td>
						<td>
							<?php $p = $r->prompt_id ? get_post( (int) $r->prompt_id ) : null;
							echo $p ? '<a href="' . esc_url( get_edit_post_link( $p ) ) . '">' . esc_html( $p->post_title ) . '</a>' : ( $r->prompt_id ? '#' . esc_html( $r->prompt_id ) : '—' ); ?>
						</td>
						<td><?php echo esc_html( '#' . $r->model_id ); ?></td>
						<td><span class="aigis-badge aigis-badge-<?php echo esc_attr( $r->result ); ?>"><?php echo esc_html( ucfirst( $r->result ) ); ?></span></td>
						<td><?php echo is_numeric( $r->score ) ? esc_html( number_format( (float) $r->score, 3 ) ) : '—'; ?></td>
						<td>
							<?php if ( $r->human_verdict ) : ?>
								<span class="aigis-badge aigis-badge-<?php echo esc_attr( $r->human_verdict ); ?>"><?php echo esc_html( ucfirst( $r->human_verdict ) ); ?></span>
							<?php else : ?>—<?php endif; ?>
						</td>
						<td><?php echo $r->evaluated_by ? esc_html( $r->evaluated_by ) : '—'; ?></td>
						<td><?php echo esc_html( $r->created_at ); ?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php elseif ( $active_tab === 'pending' ) : ?>
	<!-- Pending review -->
	<?php if ( empty( $pending ) ) : ?>
		<p class="aigis-notice aigis-notice-success"><?php esc_html_e( 'No results pending human review.', 'ai-governance-suite' ); ?></p>
	<?php else : ?>
	<div class="aigis-table-wrap">
		<table class="aigis-table widefat">
			<thead>
				<tr>
					<th>#</th>
					<th><?php esc_html_e( 'Prompt', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Auto Result', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Score', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Notes', 'ai-governance-suite' ); ?></th>
					<th><?php esc_html_e( 'Verdict', 'ai-governance-suite' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $pending as $r ) :
					$verdict_url = wp_nonce_url( add_query_arg( [ 'eval_verdict' => 1, 'eval_id' => $r->id ], $base_url ), 'aigis_eval_verdict_' . $r->id );
				?>
				<tr>
					<td><?php echo esc_html( $r->id ); ?></td>
					<td>
						<?php $p = $r->prompt_id ? get_post( (int) $r->prompt_id ) : null;
						echo $p ? '<a href="' . esc_url( get_edit_post_link( $p ) ) . '">' . esc_html( $p->post_title ) . '</a>' : '—'; ?>
					</td>
					<td><span class="aigis-badge aigis-badge-<?php echo esc_attr( $r->result ); ?>"><?php echo esc_html( ucfirst( $r->result ) ); ?></span></td>
					<td><?php echo is_numeric( $r->score ) ? esc_html( number_format( (float) $r->score, 3 ) ) : '—'; ?></td>
					<td><?php echo esc_html( $r->evaluator_notes ?? '' ); ?></td>
					<td>
						<form method="post" action="<?php echo esc_url( add_query_arg( 'tab', 'pending', $base_url ) ); ?>" style="display:flex;gap:4px;align-items:center;">
							<?php wp_nonce_field( 'aigis_eval_verdict', 'aigis_eval_verdict_nonce' ); ?>
							<input type="hidden" name="eval_id" value="<?php echo esc_attr( $r->id ); ?>">
							<select name="human_verdict" required>
								<option value=""><?php esc_html_e( '— Set verdict —', 'ai-governance-suite' ); ?></option>
								<option value="pass"><?php esc_html_e( 'Pass', 'ai-governance-suite' ); ?></option>
								<option value="fail"><?php esc_html_e( 'Fail', 'ai-governance-suite' ); ?></option>
								<option value="flagged"><?php esc_html_e( 'Flagged', 'ai-governance-suite' ); ?></option>
							</select>
							<button type="submit" class="button button-small"><?php esc_html_e( 'Save', 'ai-governance-suite' ); ?></button>
						</form>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

	<?php elseif ( $active_tab === 'trends' ) : ?>
	<!-- Trend chart -->
	<div class="aigis-card">
		<h3><?php esc_html_e( 'Eval Pass / Fail Rate (30 days)', 'ai-governance-suite' ); ?></h3>
		<div class="aigis-chart-wrap">
			<canvas id="aigis-chart-eval-trend"></canvas>
		</div>
	</div>
	<?php endif; ?>

</div>
