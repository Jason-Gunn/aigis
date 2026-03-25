<?php
/**
 * Abstract AI provider adapter.
 *
 * Every concrete provider extends this class and implements
 * send_prompt() and list_models(). Use the static make() factory
 * to get the right implementation for a given inventory model record.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class AIGIS_Provider_Abstract {

	/** Model record loaded from the inventory table */
	protected array $model;

	public function __construct( array $model ) {
		$this->model = $model;
	}

	// -----------------------------------------------------------------------
	// Contract
	// -----------------------------------------------------------------------

	/**
	 * Send a prompt to the provider.
	 *
	 * @param  string $prompt  The rendered prompt text.
	 * @param  array  $options Override defaults: temperature, max_tokens, system, etc.
	 * @return array {
	 *     @type string  $output      Model response text.
	 *     @type int     $tokens_in   Prompt token count (0 if unavailable).
	 *     @type int     $tokens_out  Completion token count (0 if unavailable).
	 *     @type float   $latency_ms  Wall-clock milliseconds.
	 *     @type float   $cost_usd    Estimated cost (0.0 if unavailable).
	 *     @type string  $model       Model identifier actually used.
	 *     @type string  $error       Non-empty on failure.
	 * }
	 */
	abstract public function send_prompt( string $prompt, array $options = [] ): array;

	/**
	 * List available models from the provider.
	 *
	 * @return array[] Each entry: [ 'id' => string, 'name' => string ]
	 */
	abstract public function list_models(): array;

	// -----------------------------------------------------------------------
	// Factory
	// -----------------------------------------------------------------------

	/**
	 * Instantiate the correct provider subclass for a given inventory model ID.
	 *
	 * @param  int $model_id aigis_ai_inventory PK.
	 * @return static|null   Null when model not found or provider unsupported.
	 */
	public static function make( int $model_id ): ?static {
		$db    = new AIGIS_DB_Inventory();
		$model = $db->get( $model_id );
		if ( ! $model ) {
			return null;
		}

		$vendor = strtolower( trim( $model['vendor_name'] ?? '' ) );

		$class = match ( true ) {
			in_array( $vendor, [ 'openai', 'open ai', 'openai api' ], true ) => AIGIS_Provider_OpenAI::class,
			in_array( $vendor, [ 'anthropic' ], true )                        => AIGIS_Provider_Anthropic::class,
			in_array( $vendor, [ 'ollama', 'local', 'on-prem' ], true )       => AIGIS_Provider_Ollama::class,
			default                                                            => null,
		};

		if ( $class === null ) {
			return null;
		}

		return new $class( $model );
	}

	// -----------------------------------------------------------------------
	// Helpers available to all concrete implementations
	// -----------------------------------------------------------------------

	/**
	 * Decrypt an XOR-encrypted provider API key stored in wp_options.
	 *
	 * Keys are encrypted with XOR(AUTH_KEY) in class-aigis-page-settings.php.
	 */
	protected function decrypt_option( string $option_name ): string {
		$stored = get_option( $option_name, '' );
		if ( $stored === '' ) {
			return '';
		}

		$key  = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'aigis-fallback-key';
		$raw  = base64_decode( $stored, true );
		if ( $raw === false ) {
			return '';
		}

		$result = '';
		$klen   = strlen( $key );
		for ( $i = 0; $i < strlen( $raw ); $i++ ) {
			$result .= $raw[ $i ] ^ $key[ $i % $klen ];
		}
		return $result;
	}

	/**
	 * Standard response skeleton — merge concrete data on top.
	 */
	protected function default_response(): array {
		return [
			'output'      => '',
			'tokens_in'   => 0,
			'tokens_out'  => 0,
			'latency_ms'  => 0.0,
			'cost_usd'    => 0.0,
			'model'       => $this->model['model_slug'] ?? '',
			'error'       => '',
		];
	}

	/**
	 * Perform a wp_remote_post() call and return the decoded JSON body.
	 *
	 * On failure, returns an array with an 'error' key set.
	 */
	protected function http_post( string $url, array $headers, array $body, int $timeout = 60 ): array {
		$response = wp_remote_post( $url, [
			'headers' => $headers,
			'body'    => wp_json_encode( $body ),
			'timeout' => $timeout,
		] );

		if ( is_wp_error( $response ) ) {
			return [ 'error' => $response->get_error_message() ];
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 ) {
			$msg = $data['error']['message'] ?? $data['error'] ?? "HTTP $code";
			return [ 'error' => (string) $msg ];
		}

		return $data ?? [];
	}

	/**
	 * GET request, returns decoded JSON or ['error' => ...].
	 */
	protected function http_get( string $url, array $headers, int $timeout = 15 ): array {
		$response = wp_remote_get( $url, [
			'headers' => $headers,
			'timeout' => $timeout,
		] );

		if ( is_wp_error( $response ) ) {
			return [ 'error' => $response->get_error_message() ];
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			return [ 'error' => "HTTP $code" ];
		}

		return $data ?? [];
	}
}
