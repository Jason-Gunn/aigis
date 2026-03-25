<?php
/**
 * Admin page: AI Inventory (with WP_List_Table).
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Page_Inventory {

	private AIGIS_DB_Inventory $db;

	public function __construct() {
		$this->db = new AIGIS_DB_Inventory();
	}

	public function render(): void {
		if ( ! current_user_can( AIGIS_Capabilities::VIEW_AI_INVENTORY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ai-governance-suite' ) );
		}

		// Handle form submissions before any output.
		$this->handle_actions();

		// Editing single item?
		$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		if ( $edit_id ) {
			$item = $this->db->get( $edit_id );
			if ( ! $item ) {
				wp_die( esc_html__( 'Item not found.', 'ai-governance-suite' ) );
			}
			include AIGIS_PLUGIN_DIR . 'admin/views/inventory/edit.php';
			return;
		}

		// Adding new item?
		$adding = isset( $_GET['action'] ) && $_GET['action'] === 'new';
		if ( $adding && current_user_can( AIGIS_Capabilities::MANAGE_AI_INVENTORY ) ) {
			$item = null;
			include AIGIS_PLUGIN_DIR . 'admin/views/inventory/edit.php';
			return;
		}

		// Default: list view.
		$per_page = 20;
		$page     = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset   = ( $page - 1 ) * $per_page;

		$args = [
			'limit'       => $per_page,
			'offset'      => $offset,
			'status'      => sanitize_text_field( $_GET['status'] ?? '' ),
			'vendor'      => sanitize_text_field( $_GET['vendor'] ?? '' ),
			'access_type' => sanitize_text_field( $_GET['access_type'] ?? '' ),
			'orderby'     => sanitize_key( $_GET['orderby'] ?? 'vendor_name' ),
			'order'       => strtoupper( sanitize_key( $_GET['order'] ?? 'ASC' ) ),
		];

		$items       = $this->db->get_filtered( $args );
		$total_items = $this->db->count_filtered( $args );
		$vendors     = $this->db->get_vendor_list();
		$total_pages = (int) ceil( $total_items / $per_page );

		include AIGIS_PLUGIN_DIR . 'admin/views/inventory/list.php';
	}

	private function handle_actions(): void {
		if ( empty( $_POST['aigis_inventory_action'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_POST['aigis_inventory_nonce'] ?? '' ), 'aigis_inventory_save' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'ai-governance-suite' ) );
		}
		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_AI_INVENTORY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ai-governance-suite' ) );
		}

		$action = sanitize_key( $_POST['aigis_inventory_action'] );
		$data   = $this->sanitize_inventory_post( $_POST );

		if ( $action === 'create' ) {
			$id = $this->db->insert( $data );
			$audit = new AIGIS_DB_Audit();
			$audit->log( 'inventory.created', 'inventory', (string) $id, sprintf( 'Model "%s / %s" added.', $data['vendor_name'], $data['model_name'] ) );
		} elseif ( $action === 'update' ) {
			$id = absint( $_POST['id'] ?? 0 );
			$before = (array) $this->db->get( $id );
			$this->db->update( $data, [ 'id' => $id ] );
			$audit = new AIGIS_DB_Audit();
			$audit->log( 'inventory.updated', 'inventory', (string) $id, sprintf( 'Model "%s / %s" updated.', $data['vendor_name'], $data['model_name'] ), $before, $data );
		} elseif ( $action === 'delete' ) {
			$id = absint( $_POST['id'] ?? 0 );
			$this->db->delete( [ 'id' => $id ] );
			$audit = new AIGIS_DB_Audit();
			$audit->log( 'inventory.deleted', 'inventory', (string) $id, 'Model deleted.' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=aigis-inventory&saved=1' ) );
		exit;
	}

	private function sanitize_inventory_post( array $post ): array {
		$allowed_access  = [ 'api', 'on-prem', 'custom-agent' ];
		$allowed_statuses = [ 'active', 'deprecated', 'retired' ];

		return [
			'vendor_name'    => sanitize_text_field( wp_unslash( $post['vendor_name'] ?? '' ) ),
			'model_name'     => sanitize_text_field( wp_unslash( $post['model_name'] ?? '' ) ),
			'version'        => sanitize_text_field( wp_unslash( $post['version'] ?? '' ) ),
			'access_type'    => in_array( $post['access_type'] ?? '', $allowed_access, true ) ? $post['access_type'] : 'api',
			'status'         => in_array( $post['status'] ?? '', $allowed_statuses, true ) ? $post['status'] : 'active',
			'description'    => sanitize_textarea_field( wp_unslash( $post['description'] ?? '' ) ),
			'endpoint_url'   => esc_url_raw( wp_unslash( $post['endpoint_url'] ?? '' ) ),
			'data_residency' => sanitize_text_field( wp_unslash( $post['data_residency'] ?? '' ) ),
			'risk_level'     => sanitize_text_field( wp_unslash( $post['risk_level'] ?? '' ) ),
			'owner_user_id'  => absint( $post['owner_user_id'] ?? 0 ),
			'notes'          => sanitize_textarea_field( wp_unslash( $post['notes'] ?? '' ) ),
		];
	}
}
