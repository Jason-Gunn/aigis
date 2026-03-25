<?php
/**
 * Analytics view.
 *
 * Variables: $days, $kpi, $trend, $model_breakdown, $dept_cost, $top_prompts
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$base_url = admin_url( 'admin.php?page=aigis-analytics' );
?>
<div class="wrap aigis-wrap">
	<h1><?php esc_html_e( 'Analytics', 'ai-governance-suite' ); ?></h1>

	<!-- Period picker -->
	<div style="margin-bottom:16px;">
		<?php foreach ( [ 7, 30, 90 ] as $d ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'days', $d, $base_url ) ); ?>"
			   class="button <?php echo (int) $days === $d ? 'button-primary' : ''; ?>">
				<?php printf( esc_html__( 'Last %d days', 'ai-governance-suite' ), $d ); ?>
			</a>
		<?php endforeach; ?>
	</div>

	<!-- KPIs -->
	<div class="aigis-kpi-grid">
		<div class="aigis-kpi-card">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Total Calls', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value"><?php echo esc_html( number_format( (int) ( $kpi->total_calls ?? 0 ) ) ); ?></div>
		</div>
		<div class="aigis-kpi-card">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Total Tokens', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value"><?php echo esc_html( number_format( (int) ( $kpi->total_tokens ?? 0 ) ) ); ?></div>
		</div>
		<div class="aigis-kpi-card">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Avg Latency (ms)', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value"><?php echo esc_html( number_format( (float) ( $kpi->avg_latency_ms ?? 0 ), 0 ) ); ?></div>
		</div>
		<div class="aigis-kpi-card">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Total Cost (USD)', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value">$<?php echo esc_html( number_format( (float) ( $kpi->total_cost_usd ?? 0 ), 2 ) ); ?></div>
		</div>
		<div class="aigis-kpi-card">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Unique Users', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value"><?php echo esc_html( number_format( (int) ( $kpi->unique_users ?? 0 ) ) ); ?></div>
		</div>
	</div>

	<div class="aigis-row">
		<!-- Usage trend -->
		<div class="aigis-col-2">
			<div class="aigis-card">
				<h3><?php esc_html_e( 'Session Volume Trend', 'ai-governance-suite' ); ?></h3>
				<div class="aigis-chart-wrap">
					<canvas id="aigis-chart-usage-trend"></canvas>
				</div>
			</div>
		</div>

		<!-- Model breakdown -->
		<div class="aigis-col">
			<div class="aigis-card">
				<h3><?php esc_html_e( 'By Model', 'ai-governance-suite' ); ?></h3>
				<div class="aigis-chart-wrap">
					<canvas id="aigis-chart-model-breakdown"></canvas>
				</div>
			</div>
		</div>
	</div>

	<div class="aigis-row">
		<!-- Department cost -->
		<div class="aigis-col-2">
			<div class="aigis-card">
				<h3><?php esc_html_e( 'Cost by Department', 'ai-governance-suite' ); ?></h3>
				<div class="aigis-chart-wrap">
					<canvas id="aigis-chart-dept-cost"></canvas>
				</div>
			</div>
		</div>

		<!-- Top prompts table -->
		<div class="aigis-col">
			<div class="aigis-card">
				<h3><?php esc_html_e( 'Top Prompts (by sessions)', 'ai-governance-suite' ); ?></h3>
				<?php if ( ! empty( $top_prompts ) ) : ?>
				<table class="aigis-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Prompt', 'ai-governance-suite' ); ?></th>
							<th><?php esc_html_e( 'Sessions', 'ai-governance-suite' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $top_prompts as $row ) :
								$post = get_post( (int) $row->prompt_post_id );
						?>
						<tr>
							<td>
								<?php if ( $post ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $post ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a>
								<?php else : ?>
										<?php echo esc_html( '#' . $row->prompt_post_id ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( number_format( (int) $row->uses ) ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php else : ?>
					<p><?php esc_html_e( 'No data for this period.', 'ai-governance-suite' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
