<?php
/**
 * Stress Tests view.
 *
 * Variables: $variations (array), $runs (array), $prompts (WP_Post[])
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$base_url = admin_url( 'admin.php?page=aigis-stress-tests' );
?>
<div class="wrap aigis-wrap">
	<h1><?php esc_html_e( 'Stress Tests', 'ai-governance-suite' ); ?></h1>

	<?php if ( isset( $_GET['ran'] ) ) : ?>
		<div class="notice notice-success"><p><?php printf( esc_html__( 'Created %d stress test run(s).', 'ai-governance-suite' ), absint( $_GET['ran'] ) ); ?></p></div>
	<?php elseif ( isset( $_GET['error'] ) ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Select a prompt and at least one variation before running a test.', 'ai-governance-suite' ); ?></p></div>
	<?php endif; ?>

	<div class="aigis-row">
		<!-- Launch a test run -->
		<div class="aigis-col">
			<div class="aigis-card">
				<h3><?php esc_html_e( 'Run a Stress Test', 'ai-governance-suite' ); ?></h3>
				<form method="post" action="<?php echo esc_url( $base_url ); ?>">
					<?php wp_nonce_field( 'aigis_run_stress_test', 'aigis_stress_nonce' ); ?>
					<input type="hidden" name="action" value="run_stress_test">

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="run_prompt_id"><?php esc_html_e( 'Prompt *', 'ai-governance-suite' ); ?></label></th>
							<td>
								<select id="run_prompt_id" name="prompt_id" required>
									<option value=""><?php esc_html_e( '— Select a prompt —', 'ai-governance-suite' ); ?></option>
									<?php foreach ( $prompts as $p ) : ?>
										<option value="<?php echo esc_attr( $p->ID ); ?>"><?php echo esc_html( $p->post_title ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Variations', 'ai-governance-suite' ); ?></th>
							<td>
								<?php foreach ( $variations as $v ) : ?>
								<label style="display:block;margin-bottom:4px;">
									<input type="checkbox" name="variation_ids[]" value="<?php echo esc_attr( $v->id ); ?>">
									<?php echo esc_html( $v->name ); ?>
									<span style="color:#646970;font-size:.8125rem"> — <?php echo esc_html( $v->category ); ?></span>
								</label>
								<?php endforeach; ?>
								<?php if ( empty( $variations ) ) : ?>
									<em><?php esc_html_e( 'No variations defined. Add them via the database seed.', 'ai-governance-suite' ); ?></em>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="run_notes"><?php esc_html_e( 'Notes', 'ai-governance-suite' ); ?></label></th>
							<td><textarea id="run_notes" name="notes" rows="3" class="large-text"></textarea></td>
						</tr>
					</table>

					<?php submit_button( __( 'Run Test', 'ai-governance-suite' ) ); ?>
				</form>
			</div>
		</div>

		<!-- Available variation types -->
		<div class="aigis-col">
			<div class="aigis-card">
				<h3><?php esc_html_e( 'Variation Types', 'ai-governance-suite' ); ?></h3>
				<?php if ( empty( $variations ) ) : ?>
					<p><?php esc_html_e( 'No variations configured.', 'ai-governance-suite' ); ?></p>
				<?php else : ?>
				<table class="aigis-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'ai-governance-suite' ); ?></th>
							<th><?php esc_html_e( 'Category', 'ai-governance-suite' ); ?></th>
							<th><?php esc_html_e( 'Slug', 'ai-governance-suite' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $variations as $v ) : ?>
						<tr>
							<td><?php echo esc_html( $v->name ); ?></td>
							<td><code><?php echo esc_html( $v->category ); ?></code></td>
							<td><code><?php echo esc_html( $v->slug ); ?></code></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Recent runs -->
	<div class="aigis-card">
		<h3><?php esc_html_e( 'Recent Test Runs', 'ai-governance-suite' ); ?></h3>
		<?php if ( empty( $runs ) ) : ?>
			<p><?php esc_html_e( 'No test runs yet.', 'ai-governance-suite' ); ?></p>
		<?php else : ?>
		<div class="aigis-table-wrap">
			<table class="aigis-table widefat">
				<thead>
					<tr>
						<th>#</th>
						<th><?php esc_html_e( 'Prompt', 'ai-governance-suite' ); ?></th>
						<th><?php esc_html_e( 'Variation', 'ai-governance-suite' ); ?></th>
						<th><?php esc_html_e( 'Provider / Model', 'ai-governance-suite' ); ?></th>
						<th><?php esc_html_e( 'Score', 'ai-governance-suite' ); ?></th>
						<th><?php esc_html_e( 'Flagged', 'ai-governance-suite' ); ?></th>
						<th><?php esc_html_e( 'Run At', 'ai-governance-suite' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $runs as $r ) :
						$prompt_link = get_edit_post_link( (int) $r->prompt_post_id );
					?>
					<tr>
						<td><?php echo esc_html( $r->id ); ?></td>
						<td>
							<?php if ( $prompt_link ) : ?>
								<a href="<?php echo esc_url( $prompt_link ); ?>"><?php echo esc_html( $r->prompt_title ?? '#' . $r->prompt_post_id ); ?></a>
							<?php else : ?>
								<?php echo esc_html( '#' . $r->prompt_post_id ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $r->variation_name ?? '—' ); ?></td>
						<td><?php echo esc_html( trim( $r->provider . ' / ' . $r->model_used, ' /' ) ?: '—' ); ?></td>
						<td><?php echo esc_html( number_format( (float) $r->score, 2 ) ); ?></td>
						<td><?php echo ! empty( $r->flagged ) ? esc_html__( 'Yes', 'ai-governance-suite' ) : '—'; ?></td>
						<td><?php echo esc_html( $r->executed_at ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
	</div>
</div>
