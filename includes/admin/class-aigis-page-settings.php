<?php
/**
 * Admin page: Settings (tabbed).
 *
 * Tabs: General | Providers | Notifications | Evaluation | Roles & Permissions
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Page_Settings {

	private const TABS = [
		'general'      => 'General',
		'providers'    => 'Providers',
		'notifications' => 'Notifications',
		'evaluation'   => 'Evaluation',
		'roles'        => 'Roles & Permissions',
		'developer'    => 'Developer Tools',
	];

	public function render(): void {
		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ai-governance-suite' ) );
		}

		if ( ! empty( $_POST['aigis_settings_nonce'] ) ) {
			$this->handle_save();
		}

		$active_tab = sanitize_key( $_GET['tab'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! array_key_exists( $active_tab, self::TABS ) ) {
			$active_tab = 'general';
		}

		include AIGIS_PLUGIN_DIR . 'admin/views/settings/settings.php';
	}

	private function handle_save(): void {
		if ( ! wp_verify_nonce( sanitize_key( $_POST['aigis_settings_nonce'] ), 'aigis_save_settings' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'ai-governance-suite' ) );
		}
		if ( ! current_user_can( AIGIS_Capabilities::MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ai-governance-suite' ) );
		}

		$tab = sanitize_key( $_POST['aigis_active_tab'] ?? 'general' );

		switch ( $tab ) {
			case 'general':
				$this->save_general();
				break;
			case 'providers':
				$this->save_providers();
				break;
			case 'notifications':
				$this->save_notifications();
				break;
			case 'evaluation':
				$this->save_evaluation();
				break;
		}

		$audit = new AIGIS_DB_Audit();
		$audit->log( 'settings.saved', 'settings', '0', sprintf( 'Settings tab "%s" saved.', $tab ) );

		wp_safe_redirect( add_query_arg( [ 'page' => 'aigis-settings', 'tab' => $tab, 'saved' => '1' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	private function save_general(): void {
		$opts = [
			'aigis_audit_retention_days'         => absint( $_POST['aigis_audit_retention_days'] ?? 365 ),
			'aigis_usage_retention_days'         => absint( $_POST['aigis_usage_retention_days'] ?? 365 ),
			'aigis_policy_expiry_warning_days'   => absint( $_POST['aigis_policy_expiry_warning_days'] ?? 14 ),
			'aigis_trust_proxy_headers'          => ! empty( $_POST['aigis_trust_proxy_headers'] ) ? '1' : '0',
			'aigis_pii_blocking_default'         => ! empty( $_POST['aigis_pii_blocking_default'] ) ? '1' : '0',
		];
		foreach ( $opts as $key => $val ) {
			update_option( $key, $val );
		}
	}

	private function save_providers(): void {
		$providers = [
			'openai'    => [ 'api_key', 'org_id', 'default_model' ],
			'anthropic' => [ 'api_key', 'default_model' ],
			'ollama'    => [ 'base_url', 'default_model' ],
		];

		foreach ( $providers as $provider => $fields ) {
			foreach ( $fields as $field ) {
				$key = "aigis_provider_{$provider}_{$field}";
				if ( isset( $_POST[ $key ] ) ) {
					$raw = wp_unslash( $_POST[ $key ] );
					if ( 'api_key' === $field ) {
						$raw = sanitize_text_field( $raw );
						if ( '' === $raw ) {
							continue;
						}

						$encrypted = $this->encrypt_api_key( $raw );
						if ( '' !== $encrypted ) {
							update_option( $key, $encrypted );
						}
						continue;
					}

					update_option( $key, sanitize_text_field( $raw ) );
				}
			}
		}
	}

	private function save_notifications(): void {
		$webhook_url = esc_url_raw( wp_unslash( $_POST['aigis_global_webhook_url'] ?? '' ) );
		$alert_email = sanitize_email( wp_unslash( $_POST['aigis_alert_email'] ?? '' ) );

		update_option( 'aigis_global_webhook_url', $webhook_url );
		update_option( 'aigis_alert_email', $alert_email );

		// Alert rules: stored as JSON array of [ event_type, email, webhook_url ].
		if ( isset( $_POST['aigis_alert_rules'] ) ) {
			$raw_rules = wp_unslash( $_POST['aigis_alert_rules'] );
			$decoded   = json_decode( $raw_rules, true );
			if ( is_array( $decoded ) ) {
				$clean = array_map( static fn( $r ) => [
					'event_type'  => sanitize_key( $r['event_type'] ?? '' ),
					'email'       => sanitize_email( $r['email'] ?? '' ),
					'webhook_url' => esc_url_raw( $r['webhook_url'] ?? '' ),
				], $decoded );
				update_option( 'aigis_alert_rules', $clean );
			}
		}
	}

	private function save_evaluation(): void {
		update_option( 'aigis_auto_eval_enabled', ! empty( $_POST['aigis_auto_eval_enabled'] ) ? '1' : '0' );
		update_option( 'aigis_auto_eval_sample_rate', absint( $_POST['aigis_auto_eval_sample_rate'] ?? 10 ) );
	}

	/**
	 * AES-256-CBC-encrypt an API key for at-rest protection.
	 * Not a substitute for full secrets management, but prevents plaintext storage.
	 *
	 * Stored format: 'aigis_aes:<base64(iv . ciphertext)>'
	 */
	private function encrypt_api_key( string $raw ): string {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}
		$auth_key   = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		$secure_key = defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '';
		$key        = substr( hash( 'sha256', $auth_key . $secure_key, true ), 0, 32 );
		$iv         = openssl_random_pseudo_bytes( 16 );
		$enc        = openssl_encrypt( $raw, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
		if ( $enc === false ) {
			return '';
		}
		return 'aigis_aes:' . base64_encode( $iv . $enc ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Get the tab labels for rendering.
	 *
	 * @return array<string, string>
	 */
	public static function get_tabs(): array {
		return self::TABS;
	}
}
