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
		$action  = sanitize_key( $_GET['action'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$edit_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : absint( $_GET['edit'] ?? 0 );
		if ( $edit_id ) {
			$item = (array) $this->db->get( $edit_id );
			if ( ! $item ) {
				wp_die( esc_html__( 'Item not found.', 'ai-governance-suite' ) );
			}
			$is_edit = true;
			include AIGIS_PLUGIN_DIR . 'admin/views/inventory/edit.php';
			return;
		}

		// Adding new item?
		$adding = in_array( $action, [ 'new', 'add' ], true );
		if ( $adding && current_user_can( AIGIS_Capabilities::MANAGE_AI_INVENTORY ) ) {
			$item = null;
			$is_edit = false;
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
			'search'      => sanitize_text_field( $_GET['s'] ?? '' ),
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
		$action = sanitize_key( $_GET['action'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'delete' === $action && isset( $_GET['id'] ) ) {
			$id = absint( $_GET['id'] );
			if ( ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ?? '' ), 'aigis_delete_model_' . $id ) ) {
				wp_die( esc_html__( 'Nonce verification failed.', 'ai-governance-suite' ) );
			}
			if ( ! current_user_can( AIGIS_Capabilities::MANAGE_AI_INVENTORY ) ) {
				wp_die( esc_html__( 'Permission denied.', 'ai-governance-suite' ) );
			}
			$this->db->delete( [ 'id' => $id ] );
			( new AIGIS_DB_Audit() )->log( 'inventory.deleted', 'inventory', (string) $id, 'Model deleted.' );
			$this->finish_request( admin_url( 'admin.php?page=aigis-inventory&deleted=1' ) );
		}

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

		$this->finish_request( admin_url( 'admin.php?page=aigis-inventory&saved=1' ) );
	}

	private function sanitize_inventory_post( array $post ): array {
		$allowed_access   = [ 'api-model', 'on-prem', 'custom-agent' ];
		$allowed_statuses = [ 'active', 'deprecated', 'under-review' ];
		$allowed_risk     = [ 'low', 'medium', 'high' ];

		return [
			'vendor_name'    => sanitize_text_field( wp_unslash( $post['vendor_name'] ?? '' ) ),
			'model_name'     => sanitize_text_field( wp_unslash( $post['model_name'] ?? '' ) ),
			'model_version'  => sanitize_text_field( wp_unslash( $post['model_version'] ?? '' ) ),
			'integration_type' => in_array( $post['integration_type'] ?? '', $allowed_access, true ) ? sanitize_text_field( wp_unslash( $post['integration_type'] ) ) : 'api-model',
			'status'         => in_array( $post['status'] ?? '', $allowed_statuses, true ) ? sanitize_text_field( wp_unslash( $post['status'] ) ) : 'active',
			'api_endpoint'   => esc_url_raw( wp_unslash( $post['api_endpoint'] ?? '' ) ),
			'agent_identifier' => sanitize_text_field( wp_unslash( $post['agent_identifier'] ?? '' ) ),
			'data_categories' => sanitize_text_field( wp_unslash( $post['data_categories'] ?? '' ) ),
			'risk_level'     => in_array( $post['risk_level'] ?? '', $allowed_risk, true ) ? sanitize_text_field( wp_unslash( $post['risk_level'] ) ) : 'medium',
			'owner_user_id'  => absint( $post['owner_user_id'] ?? 0 ),
			'notes'          => sanitize_textarea_field( wp_unslash( $post['notes'] ?? '' ) ),
		];
	}

	private function finish_request( string $url ): void {
		if ( ! headers_sent() ) {
			wp_safe_redirect( $url );
			exit;
		}

		echo '<script>window.location = ' . wp_json_encode( $url ) . ';</script>';
		echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $url ) . '"></noscript>';
		exit;
	}
}
