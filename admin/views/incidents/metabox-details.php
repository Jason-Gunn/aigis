<?php
/**
 * Incident CPT — Details metabox view.
 *
 * Variables: $post, $severity, $category, $detected_at, $resolved_at, $current_status
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;
// Nonce is already output in render_details_metabox() before this include.

$current_status = $post->post_status;
$statuses = [
	'aigis-open'          => __( 'Open', 'ai-governance-suite' ),
	'aigis-investigating' => __( 'Investigating', 'ai-governance-suite' ),
	'aigis-resolved'      => __( 'Resolved', 'ai-governance-suite' ),
];
?>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><label for="aigis_severity"><?php esc_html_e( 'Severity', 'ai-governance-suite' ); ?></label></th>
		<td>
			<select id="aigis_severity" name="aigis_severity">
				<?php foreach ( [ 'low', 'medium', 'high', 'critical' ] as $s ) : ?>
					<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $severity, $s ); ?>>
						<?php echo esc_html( ucfirst( $s ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_incident_category"><?php esc_html_e( 'Category', 'ai-governance-suite' ); ?></label></th>
		<td>
			<select id="aigis_incident_category" name="aigis_incident_category">
				<?php foreach ( [ 'pii_leak', 'policy_violation', 'model_failure', 'cost_anomaly', 'security', 'other' ] as $c ) : ?>
					<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $category, $c ); ?>>
						<?php echo esc_html( ucwords( str_replace( '_', ' ', $c ) ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_detected_at"><?php esc_html_e( 'Detected At', 'ai-governance-suite' ); ?></label></th>
		<td><input type="datetime-local" id="aigis_detected_at" name="aigis_detected_at"
			value="<?php echo esc_attr( $detected ? str_replace( ' ', 'T', $detected ) : '' ); ?>"></td>
	</tr>
	<?php if ( $resolved ) : ?>
	<tr>
		<th scope="row"><?php esc_html_e( 'Resolved At', 'ai-governance-suite' ); ?></th>
		<td><?php echo esc_html( $resolved ); ?></td>
	</tr>
	<?php endif; ?>
</table>

<hr>
<p><strong><?php esc_html_e( 'Change Status', 'ai-governance-suite' ); ?></strong></p>
<div style="display:flex;gap:8px;flex-wrap:wrap;">
	<?php foreach ( $statuses as $slug => $label ) :
		if ( $slug === $current_status ) continue;
	?>
	<button type="button" class="button aigis-status-btn"
		data-post="<?php echo esc_attr( $post->ID ); ?>"
		data-action="aigis_set_incident_status"
		data-status="<?php echo esc_attr( $slug ); ?>"
		data-label="<?php echo esc_attr( $label ); ?>">
		→ <?php echo esc_html( $label ); ?>
	</button>
	<?php endforeach; ?>
</div>
