<?php
/**
 * Admin page: Audit Log (with list view + CSV export).
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Page_Audit_Log {

	private AIGIS_DB_Audit $db;

	public function __construct() {
		$this->db = new AIGIS_DB_Audit();
	}

	public function render(): void {
		if ( ! current_user_can( AIGIS_Capabilities::VIEW_AUDIT_LOG ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ai-governance-suite' ) );
		}

		// CSV export request (send before any HTML).
		if ( isset( $_GET['export'] ) && $_GET['export'] === 'csv' ) {
			$this->export_csv();
			return;
		}

		$per_page = 50;
		$page     = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$offset   = ( $page - 1 ) * $per_page;

		$args = [
			'limit'         => $per_page,
			'offset'        => $offset,
			'event_type'    => sanitize_text_field( $_GET['event_type'] ?? '' ),
			'actor_user_id' => absint( $_GET['actor_user_id'] ?? 0 ) ?: null,
			'object_type'   => sanitize_text_field( $_GET['object_type'] ?? '' ),
			'date_from'     => sanitize_text_field( $_GET['date_from'] ?? '' ),
			'date_to'       => sanitize_text_field( $_GET['date_to'] ?? '' ),
		];

		// Remove empty args.
		$args = array_filter( $args, static fn( $v ) => $v !== '' && $v !== null );

		$items   = $this->db->query( $args );
		$total   = $this->db->count_query( $args );
		$pages   = (int) ceil( $total / $per_page );
		$filters = [
			'event_type'    => sanitize_text_field( $_GET['event_type'] ?? '' ),
			'actor_user_id' => absint( $_GET['actor_user_id'] ?? 0 ) ?: '',
			'object_type'   => sanitize_text_field( $_GET['object_type'] ?? '' ),
			'date_from'     => sanitize_text_field( $_GET['date_from'] ?? '' ),
			'date_to'       => sanitize_text_field( $_GET['date_to'] ?? '' ),
		];

		include AIGIS_PLUGIN_DIR . 'admin/views/audit-log/list.php';
	}

	private function export_csv(): void {
		if ( ! current_user_can( AIGIS_Capabilities::EXPORT_DATA ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ai-governance-suite' ) );
		}

		$args = [
			'limit'         => 10000,
			'offset'        => 0,
			'event_type'    => sanitize_text_field( $_GET['event_type'] ?? '' ),
			'actor_user_id' => absint( $_GET['actor_user_id'] ?? 0 ) ?: null,
			'object_type'   => sanitize_text_field( $_GET['object_type'] ?? '' ),
			'date_from'     => sanitize_text_field( $_GET['date_from'] ?? '' ),
			'date_to'       => sanitize_text_field( $_GET['date_to'] ?? '' ),
		];
		$args = array_filter( $args, static fn( $v ) => $v !== '' && $v !== null );

		$entries = $this->db->query( $args );

		$filename = 'aigis-audit-log-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$out = fopen( 'php://output', 'w' );

		// BOM for Excel UTF-8 compatibility.
		fwrite( $out, "\xEF\xBB\xBF" );

		fputcsv( $out, [ 'ID', 'Event Type', 'Object Type', 'Object ID', 'Actor User ID', 'Actor IP', 'Summary', 'Occurred At' ] );

		foreach ( $entries as $row ) {
			fputcsv( $out, [
				$row->id,
				$row->event_type,
				$row->object_type,
				$row->object_id,
				$row->actor_user_id,
				$row->actor_ip,
				$row->summary,
				$row->occurred_at,
			] );
		}

		fclose( $out );

		// Log the export itself.
		$this->db->log( 'auditLog.exported', 'audit_log', '0', 'Audit log exported to CSV.' );

		exit;
	}
}
