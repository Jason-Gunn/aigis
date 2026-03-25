<?php
/**
 * User Manual admin page.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Page_Manual {

	public function render(): void {
		if ( ! current_user_can( AIGIS_Capabilities::VIEW_AI_INVENTORY ) ) {
			wp_die( esc_html__( 'Access denied.', 'ai-governance-suite' ) );
		}

		include AIGIS_PLUGIN_DIR . 'admin/views/manual/manual.php';
	}
}
