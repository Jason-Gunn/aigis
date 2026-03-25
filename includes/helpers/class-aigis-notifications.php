<?php
/**
 * Notification dispatcher.
 *
 * Supports email, outbound webhook, and plugin in-app inbox delivery.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Notifications {

	/**
	 * Dispatch an alert based on its configured delivery channels.
	 *
	 * @param string $event_type Human-readable trigger type, e.g. 'budget_overage'.
	 * @param string $subject    Short subject / title.
	 * @param string $message    Full message body (plain text).
	 * @param array  $context    Extra key/value pairs passed to webhooks as JSON.
	 */
	public function dispatch( string $event_type, string $subject, string $message, array $context = [] ): void {
		$rules = $this->get_rules_for_event( $event_type );

		if ( empty( $rules ) ) {
			// Always write to the in-app inbox regardless of rules.
			$this->add_to_inbox( $event_type, $subject, $message );
			return;
		}

		foreach ( $rules as $rule ) {
			if ( ! empty( $rule['email'] ) ) {
				$this->send_email( $rule['email'], $subject, $message );
			}

			if ( ! empty( $rule['webhook_url'] ) ) {
				$this->send_webhook( $rule['webhook_url'], $event_type, $subject, $message, $context );
			}
		}

		$this->add_to_inbox( $event_type, $subject, $message );
	}

	/**
	 * Send a plain-text email notification.
	 *
	 * @param string $to      Recipient email address.
	 * @param string $subject Email subject.
	 * @param string $body    Email body (plain text).
	 * @return bool Whether wp_mail() returned true.
	 */
	public function send_email( string $to, string $subject, string $body ): bool {
		if ( ! is_email( $to ) ) {
			return false;
		}

		$site_name = get_bloginfo( 'name' );
		$headers   = [ 'Content-Type: text/plain; charset=UTF-8' ];
		$full_subject = sprintf( '[%s] %s', $site_name, $subject );

		return wp_mail( $to, $full_subject, $body, $headers );
	}

	/**
	 * POST a JSON payload to a webhook URL.
	 *
	 * @param string $url        Webhook endpoint URL.
	 * @param string $event_type Event type slug.
	 * @param string $subject    Short title.
	 * @param string $message    Full message body.
	 * @param array  $context    Additional context data.
	 * @return bool True on HTTP 2xx response.
	 */
	public function send_webhook( string $url, string $event_type, string $subject, string $message, array $context = [] ): bool {
		// Validate that the URL is a legitimate HTTP/HTTPS endpoint.
		$url = esc_url_raw( $url );
		if ( ! $url || ! preg_match( '/^https?:\/\//i', $url ) ) {
			return false;
		}

		$payload = wp_json_encode( array_merge( [
			'source'     => 'ai-governance-suite',
			'event_type' => $event_type,
			'subject'    => $subject,
			'message'    => $message,
			'site_url'   => home_url(),
			'timestamp'  => current_time( 'c' ),
		], $context ) );

		$response = wp_remote_post( $url, [
			'headers'    => [ 'Content-Type' => 'application/json; charset=utf-8' ],
			'body'       => $payload,
			'timeout'    => 10,
			'blocking'   => false, // fire-and-forget
			'sslverify'  => true,
		] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		return $code >= 200 && $code < 300;
	}

	/**
	 * Write a notification to the plugin's in-app inbox (stored as a WP option list).
	 *
	 * @param string $event_type Event slug.
	 * @param string $subject    Short title.
	 * @param string $message    Full message.
	 */
	public function add_to_inbox( string $event_type, string $subject, string $message ): void {
		$inbox = get_option( 'aigis_inbox', [] );

		array_unshift( $inbox, [
			'id'         => wp_generate_uuid4(),
			'event_type' => $event_type,
			'subject'    => $subject,
			'message'    => $message,
			'read'       => false,
			'created_at' => current_time( 'c' ),
		] );

		// Keep inbox at most 200 items.
		if ( count( $inbox ) > 200 ) {
			$inbox = array_slice( $inbox, 0, 200 );
		}

		update_option( 'aigis_inbox', $inbox, false );
	}

	/**
	 * Mark an inbox message as read.
	 *
	 * @param string $message_id UUID of the message.
	 */
	public function mark_read( string $message_id ): void {
		$inbox = get_option( 'aigis_inbox', [] );
		foreach ( $inbox as &$item ) {
			if ( $item['id'] === $message_id ) {
				$item['read'] = true;
				break;
			}
		}
		unset( $item );
		update_option( 'aigis_inbox', $inbox, false );
	}

	/**
	 * Return the count of unread inbox messages.
	 *
	 * @return int
	 */
	public function unread_count(): int {
		$inbox = get_option( 'aigis_inbox', [] );
		return count( array_filter( $inbox, static fn( $i ) => ! $i['read'] ) );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Load alert rules for a specific event type.
	 *
	 * Rules are stored as a serialized option: aigis_alert_rules.
	 * Each rule: [ event_type, email, webhook_url ]
	 *
	 * @param string $event_type Event slug to filter.
	 * @return array
	 */
	private function get_rules_for_event( string $event_type ): array {
		$all_rules = get_option( 'aigis_alert_rules', [] );
		if ( ! is_array( $all_rules ) ) {
			return [];
		}
		return array_filter( $all_rules, static fn( $r ) => isset( $r['event_type'] ) && $r['event_type'] === $event_type );
	}
}
