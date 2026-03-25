<?php
/**
 * Audit trail database class.
 *
 * APPEND-ONLY: update() and delete() throw RuntimeException.
 * The only exception is the retention pruning run by AIGIS_Cron.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_DB_Audit extends AIGIS_DB {

	protected function get_table_slug(): string {
		return 'aigis_audit_trail';
	}

	/**
	 * Audit updates are not allowed.
	 *
	 * @throws RuntimeException Always.
	 */
	public function update( array $data, array $where ): never {
		throw new RuntimeException( 'The audit trail is append-only and cannot be modified.' );
	}

	/**
	 * Audit deletes are not allowed via this method.
	 * Pruning is handled separately in AIGIS_Cron::prune_audit_log().
	 *
	 * @throws RuntimeException Always.
	 */
	public function delete( array $where ): never {
		throw new RuntimeException( 'The audit trail is append-only. Use AIGIS_Cron::prune_audit_log() for retention management.' );
	}

	/**
	 * Prune audit records older than the configured retention period.
	 * Called by WP-Cron only.
	 *
	 * @return int Number of rows deleted.
	 */
	public function prune_old_records(): int {
		global $wpdb;
		$days = (int) get_option( 'aigis_audit_retention_days', 365 );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$this->table}` WHERE occurred_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);
		return (int) $result;
	}

	/**
	 * Write an audit event.
	 *
	 * @param string $event_type  e.g. 'prompt.published'
	 * @param string $object_type e.g. 'prompt'
	 * @param string $object_id   Post ID or table row ID as string.
	 * @param string $summary     Short human-readable description.
	 * @param array  $before      Snapshot of record before change (will be JSON-encoded).
	 * @param array  $after       Snapshot of record after change (will be JSON-encoded).
	 * @return int|false New record ID or false on failure.
	 */
	public function log(
		string $event_type,
		string $object_type,
		string $object_id,
		string $summary = '',
		array $before = [],
		array $after = []
	): int|false {
		$actor_ip = $this->resolve_actor_ip();

		return $this->insert( [
			'event_type'   => $event_type,
			'object_type'  => $object_type,
			'object_id'    => $object_id,
			'actor_user_id' => get_current_user_id(),
			'actor_ip'     => $actor_ip,
			'summary'      => substr( $summary, 0, 500 ),
			'before_state' => ! empty( $before ) ? wp_json_encode( $before ) : '',
			'after_state'  => ! empty( $after )  ? wp_json_encode( $after )  : '',
			'occurred_at'  => current_time( 'mysql', true ),
		] );
	}

	/**
	 * Resolve the actor IP address, respecting proxy header settings.
	 */
	private function resolve_actor_ip(): string {
		$trust_proxy = (bool) get_option( 'aigis_trust_proxy_headers', false );

		if ( $trust_proxy && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// Take the first non-private IP from the chain.
			$ips = array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) );
			foreach ( $ips as $ip ) {
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	}

	/**
	 * Hook: log user logins.
	 *
	 * @param string   $user_login Username.
	 * @param \WP_User $user       User object.
	 */
	public function log_user_login( string $user_login, \WP_User $user ): void {
		$this->log(
			'user.login',
			'user',
			(string) $user->ID,
			sprintf( 'User "%s" logged in.', $user_login )
		);
	}

	/**
	 * Get audit log entries with optional filters.
	 *
	 * @param array $args {
	 *   @type string $event_type   Filter by event type.
	 *   @type int    $actor_user_id Filter by user ID.
	 *   @type string $date_from    MySQL datetime string.
	 *   @type string $date_to      MySQL datetime string.
	 *   @type string $object_type  Filter by object type.
	 *   @type int    $limit        Max rows; default 50.
	 *   @type int    $offset       Offset; default 0.
	 * }
	 * @return array
	 */
	public function query( array $args = [] ): array {
		global $wpdb;

		$conditions = [];
		$values     = [];
		$limit  = isset( $args['limit'] )  ? (int) $args['limit']  : 50;
		$offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;

		if ( ! empty( $args['event_type'] ) ) {
			$conditions[] = 'event_type = %s';
			$values[]     = $args['event_type'];
		}
		if ( ! empty( $args['actor_user_id'] ) ) {
			$conditions[] = 'actor_user_id = %d';
			$values[]     = (int) $args['actor_user_id'];
		}
		if ( ! empty( $args['object_type'] ) ) {
			$conditions[] = 'object_type = %s';
			$values[]     = $args['object_type'];
		}
		if ( ! empty( $args['date_from'] ) ) {
			$conditions[] = 'occurred_at >= %s';
			$values[]     = $args['date_from'];
		}
		if ( ! empty( $args['date_to'] ) ) {
			$conditions[] = 'occurred_at <= %s';
			$values[]     = $args['date_to'];
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT * FROM `{$this->table}`";
		if ( $conditions ) {
			$sql .= ' WHERE ' . implode( ' AND ', $conditions );
		}
		$sql .= ' ORDER BY occurred_at DESC';
		$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset );

		if ( $values ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $wpdb->get_results( $wpdb->prepare( $sql, ...$values ) ) ?: []; // phpcs:ignore
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $sql ) ?: [];
	}

	/**
	 * Count audit entries matching the same filter args as query().
	 */
	public function count_query( array $args = [] ): int {
		global $wpdb;

		$conditions = [];
		$values     = [];

		if ( ! empty( $args['event_type'] ) ) {
			$conditions[] = 'event_type = %s';
			$values[]     = $args['event_type'];
		}
		if ( ! empty( $args['actor_user_id'] ) ) {
			$conditions[] = 'actor_user_id = %d';
			$values[]     = (int) $args['actor_user_id'];
		}
		if ( ! empty( $args['object_type'] ) ) {
			$conditions[] = 'object_type = %s';
			$values[]     = $args['object_type'];
		}
		if ( ! empty( $args['date_from'] ) ) {
			$conditions[] = 'occurred_at >= %s';
			$values[]     = $args['date_from'];
		}
		if ( ! empty( $args['date_to'] ) ) {
			$conditions[] = 'occurred_at <= %s';
			$values[]     = $args['date_to'];
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT COUNT(*) FROM `{$this->table}`";
		if ( $conditions ) {
			$sql .= ' WHERE ' . implode( ' AND ', $conditions );
		}

		if ( $values ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$values ) ); // phpcs:ignore
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $sql );
	}
}
