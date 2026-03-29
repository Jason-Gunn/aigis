<?php
/**
 * AI Inventory database class.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_DB_Inventory extends AIGIS_DB {

	protected function get_table_slug(): string {
		return 'aigis_ai_inventory';
	}

	/**
	 * Search inventory by vendor or model name.
	 *
	 * @param string $term     Search term.
	 * @param int    $limit    Max results.
	 * @param int    $offset   Offset.
	 * @return array
	 */
	public function search( string $term, int $limit = 20, int $offset = 0 ): array {
		global $wpdb;
		$like = '%' . $wpdb->esc_like( $term ) . '%';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$this->table}` WHERE vendor_name LIKE %s OR model_name LIKE %s OR agent_identifier LIKE %s ORDER BY vendor_name, model_name LIMIT %d OFFSET %d",
				$like, $like, $like, $limit, $offset
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Get filtered inventory list for the WP_List_Table.
	 *
	 * @param array $args {
	 *   @type string $search      Search term against vendor_name, model_name, agent_identifier.
	 *   @type string $status      'active'|'deprecated'|'under-review'|''
	 *   @type string $vendor      Vendor name filter.
	 *   @type string $access_type 'api-model'|'on-prem'|'custom-agent'|''
	 *   @type int    $limit       Default 20.
	 *   @type int    $offset      Default 0.
	 *   @type string $orderby     Column name. Default 'vendor_name'.
	 *   @type string $order       'ASC'|'DESC'.
	 * }
	 * @return array
	 */
	public function get_filtered( array $args = [] ): array {
		global $wpdb;

		$conditions = [];
		$values     = [];
		$limit      = isset( $args['limit'] )   ? (int) $args['limit']   : 20;
		$offset     = isset( $args['offset'] )  ? (int) $args['offset']  : 0;
		$order      = isset( $args['order'] ) && strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
		$allowed_cols = [ 'vendor_name', 'model_name', 'status', 'integration_type', 'created_at' ];
		$orderby      = in_array( $args['orderby'] ?? '', $allowed_cols, true ) ? $args['orderby'] : 'vendor_name';

		if ( ! empty( $args['search'] ) ) {
			$like         = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$conditions[] = '(vendor_name LIKE %s OR model_name LIKE %s OR agent_identifier LIKE %s)';
			$values[]     = $like;
			$values[]     = $like;
			$values[]     = $like;
		}

		if ( ! empty( $args['status'] ) ) {
			$conditions[] = 'status = %s';
			$values[]     = $args['status'];
		}
		if ( ! empty( $args['vendor'] ) ) {
			$conditions[] = 'vendor_name = %s';
			$values[]     = $args['vendor'];
		}
		if ( ! empty( $args['access_type'] ) ) {
			$conditions[] = 'integration_type = %s';
			$values[]     = $args['access_type'];
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT * FROM `{$this->table}`";
		if ( $conditions ) {
			$sql .= ' WHERE ' . implode( ' AND ', $conditions );
		}
		$sql .= " ORDER BY `{$orderby}` {$order}";
		$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset );

		if ( $values ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $wpdb->get_results( $wpdb->prepare( $sql, ...$values ), ARRAY_A ) ?: []; // phpcs:ignore
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $sql, ARRAY_A ) ?: [];
	}

	/**
	 * Count filtered inventory entries (same args as get_filtered minus pagination).
	 */
	public function count_filtered( array $args = [] ): int {
		global $wpdb;

		$conditions = [];
		$values     = [];

		if ( ! empty( $args['search'] ) ) {
			$like         = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$conditions[] = '(vendor_name LIKE %s OR model_name LIKE %s OR agent_identifier LIKE %s)';
			$values[]     = $like;
			$values[]     = $like;
			$values[]     = $like;
		}

		if ( ! empty( $args['status'] ) ) {
			$conditions[] = 'status = %s';
			$values[]     = $args['status'];
		}
		if ( ! empty( $args['vendor'] ) ) {
			$conditions[] = 'vendor_name = %s';
			$values[]     = $args['vendor'];
		}
		if ( ! empty( $args['access_type'] ) ) {
			$conditions[] = 'integration_type = %s';
			$values[]     = $args['access_type'];
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

	/**
	 * Return lightweight list of active models for use in select dropdowns.
	 *
	 * @return array List of objects with id, vendor_name, model_name.
	 */
	public function get_active_for_select(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, vendor_name, model_name FROM `{$this->table}` WHERE status = %s ORDER BY vendor_name, model_name",
				'active'
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Get all distinct vendor names (for filter dropdowns).
	 *
	 * @return string[]
	 */
	public function get_vendor_list(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_col( "SELECT DISTINCT vendor_name FROM `{$this->table}` ORDER BY vendor_name ASC" );
		return $rows ?: [];
	}
}
