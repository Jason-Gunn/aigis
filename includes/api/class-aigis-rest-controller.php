<?php
/**
 * Base REST API controller.
 *
 * Handles API key authentication for all AIGIS REST endpoints.
 * Extend this class for each endpoint group.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class AIGIS_REST_Controller extends \WP_REST_Controller {

	protected $namespace = 'ai-governance/v1';

	/**
	 * Permission callback: accept requests authenticated via WordPress
	 * Application Passwords OR the AIGIS plugin-managed API key
	 * (sent as X-AIGIS-API-Key header).
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return bool|\WP_Error
	 */
	public function check_api_key( \WP_REST_Request $request ): bool|\WP_Error {
		// 1. Allow authenticated WP users (Application Passwords / cookie).
		if ( is_user_logged_in() ) {
			return true;
		}

		// 2. Plugin-managed API key via header.
		$provided_key = $request->get_header( 'X-AIGIS-API-Key' );
		if ( empty( $provided_key ) ) {
			return new \WP_Error(
				'aigis_rest_unauthorized',
				__( 'Authentication required. Provide a valid X-AIGIS-API-Key header or use WordPress Application Passwords.', 'ai-governance-suite' ),
				[ 'status' => 401 ]
			);
		}

		$stored_hash = get_option( 'aigis_api_key_hash', '' );
		if ( empty( $stored_hash ) ) {
			return new \WP_Error(
				'aigis_rest_no_key',
				__( 'No API key has been configured.', 'ai-governance-suite' ),
				[ 'status' => 403 ]
			);
		}

		if ( ! password_verify( $provided_key, $stored_hash ) ) {
			// Intentional constant-time compare via password_verify; log the failure.
			$audit = new AIGIS_DB_Audit();
			$audit->log(
				'api.authFailed',
				'rest_api',
				'0',
				'REST API authentication failure — invalid API key.'
			);
			return new \WP_Error(
				'aigis_rest_forbidden',
				__( 'Invalid API key.', 'ai-governance-suite' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Helper: return a standardised error response.
	 *
	 * @param string $code    Machine-readable error code.
	 * @param string $message Human-readable message.
	 * @param int    $status  HTTP status code.
	 * @return \WP_Error
	 */
	protected function error( string $code, string $message, int $status = 400 ): \WP_Error {
		return new \WP_Error( $code, $message, [ 'status' => $status ] );
	}
}
