<?php
/**
 * Incident CPT — Linked records metabox view.
 *
 * Variables: $post, $linked_policies (WP_Post[]), $linked_prompts (WP_Post[])
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// $linked_policy_ids — int[], $linked_prompt_id — int, $linked_model_id — int (set by CPT render)
$policies = ! empty( $linked_policy_ids ) ? array_filter( array_map( 'get_post', (array) $linked_policy_ids ) ) : [];
$prompt_post = $linked_prompt_id ? get_post( $linked_prompt_id ) : null;
$model_row   = $linked_model_id ? ( new AIGIS_DB_Inventory() )->get( $linked_model_id ) : null;
?>
<p class="description"><?php esc_html_e( 'Records linked to this incident via meta or reference.', 'ai-governance-suite' ); ?></p>

<h4 style="margin:12px 0 6px;"><?php esc_html_e( 'Linked Policies', 'ai-governance-suite' ); ?></h4>
<?php if ( ! empty( $policies ) ) : ?>
	<ul class="aigis-linked-list">
		<?php foreach ( $policies as $policy ) : ?>
			<li>
				<a href="<?php echo esc_url( get_edit_post_link( $policy->ID ) ); ?>">
					<?php echo esc_html( get_the_title( $policy ) ); ?>
				</a>
				<span class="aigis-status-badge status-<?php echo esc_attr( $policy->post_status ); ?>" style="margin-left:6px;">
					<?php echo esc_html( $policy->post_status ); ?>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>
<?php else : ?>
	<p class="aigis-empty-notice"><?php esc_html_e( 'No policies linked.', 'ai-governance-suite' ); ?></p>
<?php endif; ?>

<h4 style="margin:16px 0 6px;"><?php esc_html_e( 'Linked Prompt', 'ai-governance-suite' ); ?></h4>
<?php if ( $prompt_post ) : ?>
	<ul class="aigis-linked-list">
		<li>
			<a href="<?php echo esc_url( get_edit_post_link( $prompt_post->ID ) ); ?>">
				<?php echo esc_html( get_the_title( $prompt_post ) ); ?>
			</a>
			<span class="aigis-status-badge status-<?php echo esc_attr( $prompt_post->post_status ); ?>" style="margin-left:6px;">
				<?php echo esc_html( $prompt_post->post_status ); ?>
			</span>
		</li>
	</ul>
<?php else : ?>
	<p class="aigis-empty-notice"><?php esc_html_e( 'No prompt linked.', 'ai-governance-suite' ); ?></p>
<?php endif; ?>

<?php if ( $model_row ) : ?>
<h4 style="margin:16px 0 6px;"><?php esc_html_e( 'Linked AI Model', 'ai-governance-suite' ); ?></h4>
<p><?php echo esc_html( $model_row['model_name'] . ' (' . $model_row['vendor_name'] . ')' ); ?></p>
<?php endif; ?>
