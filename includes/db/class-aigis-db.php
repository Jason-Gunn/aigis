<?php
/**
 * Base database abstraction class.
 *
 * Provides common CRUD helpers that concrete DB classes extend.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class AIGIS_DB {

	/** @var string The full table name (including WP prefix). */
	protected string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . $this->get_table_slug();
	}

	/**
	 * Return the table slug (without WP prefix), e.g. 'aigis_ai_inventory'.
	 */
	abstract protected function get_table_slug(): string;

	/**
	 * Insert a row into the table.
	 *
	 * @param array $data  Column => value pairs.
	 * @return int|false   The new row ID, or false on failure.
	 */
	public function insert( array $data ): int|false {
		global $wpdb;
		$result = $wpdb->insert( $this->table, $data );
		if ( false === $result ) {
			return false;
		}
		return (int) $wpdb->insert_id;
	}

	/**
	 * Update rows in the table.
	 *
	 * @param array $data  Column => value pairs to update.
	 * @param array $where Column => value pairs for WHERE clause.
	 * @return int|false   Number of rows updated, or false on failure.
	 */
	public function update( array $data, array $where ): int|false {
		global $wpdb;
		return $wpdb->update( $this->table, $data, $where );
	}

	/**
	 * Delete rows from the table.
	 *
	 * @param array $where Column => value pairs for WHERE clause.
	 * @return int|false   Number of rows deleted, or false on failure.
	 */
	public function delete( array $where ): int|false {
		global $wpdb;
		return $wpdb->delete( $this->table, $where );
	}

	/**
	 * Get a single row by ID.
	 *
	 * @param int $id Row ID.
	 * @return object|null
	 */
	public function get( int $id ): ?object {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$this->table}` WHERE id = %d", $id )
		);
	}

	/**
	 * Get all rows, with optional ordering.
	 *
	 * @param string $order_by  Column to order by.
	 * @param string $order     ASC or DESC.
	 * @param int    $limit     Max rows; 0 = no limit.
	 * @param int    $offset    Offset.
	 * @return array
	 */
	public function get_all(
		string $order_by = 'id',
		string $order = 'DESC',
		int $limit = 0,
		int $offset = 0
	): array {
		global $wpdb;
		$order    = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';
		$order_by = sanitize_sql_orderby( $order_by ) ?: 'id';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT * FROM `{$this->table}` ORDER BY `{$order_by}` {$order}";

		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $sql ) ?: [];
	}

	/**
	 * Count rows, with optional WHERE conditions.
	 *
	 * @param array $where Column => value pairs (all combined with AND).
	 * @return int
	 */
	public function count( array $where = [] ): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT COUNT(*) FROM `{$this->table}`";

		if ( ! empty( $where ) ) {
			$conditions = [];
			$values     = [];
			foreach ( $where as $col => $val ) {
				$conditions[] = "`{$col}` = %s";
				$values[]     = $val;
			}
			$sql .= ' WHERE ' . implode( ' AND ', $conditions );
			$sql  = $wpdb->prepare( $sql, ...$values ); // phpcs:ignore
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $sql );
	}
}
