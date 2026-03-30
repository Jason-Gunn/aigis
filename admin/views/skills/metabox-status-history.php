<?php
/**
 * Skill CPT — Status history metabox view.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<?php if ( empty( $status_log ) ) : ?>
	<p class="description"><?php esc_html_e( 'No status transitions recorded yet.', 'ai-governance-suite' ); ?></p>
<?php else : ?>
	<ul>
		<?php foreach ( array_reverse( $status_log ) as $entry ) : ?>
			<?php
			$user = ! empty( $entry['user_id'] ) ? get_userdata( (int) $entry['user_id'] ) : false;
			$from = (string) ( $entry['from'] ?? '' );
			$to   = (string) ( $entry['to'] ?? '' );
			$when = (string) ( $entry['timestamp'] ?? '' );
			$note = (string) ( $entry['note'] ?? '' );
			?>
			<li>
				<strong><?php echo esc_html( $from !== '' ? $from : 'none' ); ?></strong>
				&rarr;
				<strong><?php echo esc_html( $to !== '' ? $to : 'none' ); ?></strong><br>
				<small>
					<?php echo esc_html( $when ); ?>
					<?php if ( $user ) : ?>
						<?php echo esc_html( ' • ' . $user->display_name ); ?>
					<?php endif; ?>
				</small>
				<?php if ( $note !== '' ) : ?>
					<br><em><?php echo esc_html( $note ); ?></em>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>