<?php
/**
 * Dashboard view.
 *
 * Variables available from AIGIS_Page_Dashboard::render():
 *   $kpi        array   30-day KPI stats
 *   $trend      array   sessions-over-time rows
 *   $breakdown  array   model breakdown rows
 *   $eval_stats array   evaluation summary
 *   $incidents  array   open incidents (max 5)
 *   $policies   array   expiring policies (max 5)
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap aigis-wrap">
	<h1><?php esc_html_e( 'AI Governance Dashboard', 'ai-governance-suite' ); ?></h1>

	<!-- KPI cards -->
	<div class="aigis-kpi-grid">
		<div class="aigis-kpi-card">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Sessions (30d)', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value"><?php echo esc_html( number_format( (int) ( $kpi['total_sessions'] ?? 0 ) ) ); ?></div>
		</div>
		<div class="aigis-kpi-card">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Tokens Used (30d)', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value"><?php echo esc_html( number_format( (int) ( $kpi['total_tokens'] ?? 0 ) ) ); ?></div>
		</div>
		<div class="aigis-kpi-card <?php echo ( $kpi['mtd_cost'] ?? 0 ) > 0 ? '' : 'aigis-kpi-success'; ?>">
			<div class="aigis-kpi-label"><?php esc_html_e( 'MTD Cost', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value">$<?php echo esc_html( number_format( (float) ( $kpi['mtd_cost'] ?? 0 ), 2 ) ); ?></div>
		</div>
		<div class="aigis-kpi-card <?php echo ( $kpi['open_incidents'] ?? 0 ) > 0 ? 'aigis-kpi-error' : 'aigis-kpi-success'; ?>">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Open Incidents', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value"><?php echo esc_html( (int) ( $kpi['open_incidents'] ?? 0 ) ); ?></div>
		</div>
		<div class="aigis-kpi-card">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Active AI Models', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value"><?php echo esc_html( (int) ( $kpi['active_models'] ?? 0 ) ); ?></div>
		</div>
		<?php
		$_eval_pass_rate = ( isset( $eval_stats->total ) && $eval_stats->total > 0 )
			? round( $eval_stats->passed / $eval_stats->total * 100, 1 )
			: 0.0;
		?>
		<div class="aigis-kpi-card <?php echo $_eval_pass_rate < 80 ? 'aigis-kpi-warn' : 'aigis-kpi-success'; ?>">
			<div class="aigis-kpi-label"><?php esc_html_e( 'Eval Pass Rate', 'ai-governance-suite' ); ?></div>
			<div class="aigis-kpi-value"><?php echo esc_html( number_format( $_eval_pass_rate, 1 ) ); ?>%</div>
		</div>
	</div>

	<div class="aigis-row">
		<!-- Usage trend chart -->
		<div class="aigis-col-2">
			<div class="aigis-card">
				<h3><?php esc_html_e( 'Session Volume (30 days)', 'ai-governance-suite' ); ?></h3>
				<div class="aigis-chart-wrap">
					<canvas id="aigis-chart-usage-trend"></canvas>
				</div>
			</div>
		</div>

		<!-- Model breakdown -->
		<div class="aigis-col">
			<div class="aigis-card">
				<h3><?php esc_html_e( 'Model Usage', 'ai-governance-suite' ); ?></h3>
				<div class="aigis-chart-wrap">
					<canvas id="aigis-chart-model-breakdown"></canvas>
				</div>
			</div>
		</div>
	</div>

	<div class="aigis-row">
		<!-- Open incidents -->
		<div class="aigis-col">
			<div class="aigis-card">
				<div class="aigis-card-header">
					<h3><?php esc_html_e( 'Open Incidents', 'ai-governance-suite' ); ?></h3>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=aigis_incident&post_status=aigis-open' ) ); ?>" class="button button-small">
						<?php esc_html_e( 'View all', 'ai-governance-suite' ); ?>
					</a>
				</div>
				<?php if ( empty( $incidents ) ) : ?>
					<p class="aigis-notice aigis-notice-success"><?php esc_html_e( 'No open incidents.', 'ai-governance-suite' ); ?></p>
				<?php else : ?>
					<table class="aigis-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Title', 'ai-governance-suite' ); ?></th>
								<th><?php esc_html_e( 'Severity', 'ai-governance-suite' ); ?></th>
								<th><?php esc_html_e( 'Date', 'ai-governance-suite' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $incidents as $inc ) :
								$inc_severity = get_post_meta( $inc->ID, '_aigis_severity', true ) ?: 'medium';
							?>
							<tr>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $inc->ID ) ); ?>">
										<?php echo esc_html( $inc->post_title ); ?>
									</a>
								</td>
								<td>
									<span class="aigis-badge aigis-badge-<?php echo esc_attr( $inc_severity ); ?>">
										<?php echo esc_html( ucfirst( $inc_severity ) ); ?>
									</span>
								</td>
								<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $inc->post_date ) ) ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

		<!-- Expiring policies -->
		<div class="aigis-col">
			<div class="aigis-card">
				<div class="aigis-card-header">
					<h3><?php esc_html_e( 'Policies Expiring Soon', 'ai-governance-suite' ); ?></h3>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=aigis_policy' ) ); ?>" class="button button-small">
						<?php esc_html_e( 'View all', 'ai-governance-suite' ); ?>
					</a>
				</div>
				<?php if ( empty( $policies ) ) : ?>
					<p class="aigis-notice aigis-notice-success"><?php esc_html_e( 'No policies expiring within 30 days.', 'ai-governance-suite' ); ?></p>
				<?php else : ?>
					<table class="aigis-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Policy', 'ai-governance-suite' ); ?></th>
								<th><?php esc_html_e( 'Expires', 'ai-governance-suite' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $policies as $pol ) : ?>
							<tr>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $pol->ID ) ); ?>">
										<?php echo esc_html( $pol->post_title ); ?>
									</a>
								</td>
								<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( get_post_meta( $pol->ID, '_aigis_policy_expiry_date', true ) ) ) ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
