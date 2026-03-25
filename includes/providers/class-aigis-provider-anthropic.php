<?php
/**
 * Anthropic provider adapter.
 *
 * Uses the Messages API:
 * https://api.anthropic.com/v1/messages
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Provider_Anthropic extends AIGIS_Provider_Abstract {

	private const MESSAGES_URL = 'https://api.anthropic.com/v1/messages';
	private const API_VERSION  = '2023-06-01';

	// -----------------------------------------------------------------------
	// Contract
	// -----------------------------------------------------------------------

	public function send_prompt( string $prompt, array $options = [] ): array {
		$response = $this->default_response();
		$api_key  = $this->decrypt_option( 'aigis_provider_anthropic_api_key' );

		if ( $api_key === '' ) {
			$response['error'] = __( 'Anthropic API key not configured.', 'ai-governance-suite' );
			return $response;
		}

		$model_slug   = $this->model['model_slug'] ?? 'claude-3-sonnet-20240229';
		$response['model'] = $model_slug;

		$system_msg  = sanitize_textarea_field( $options['system'] ?? '' );
		$temperature = isset( $options['temperature'] ) ? (float) $options['temperature'] : 0.7;
		$max_tokens  = absint( $options['max_tokens'] ?? 2048 );

		$body = [
			'model'        => $model_slug,
			'messages'     => [ [ 'role' => 'user', 'content' => $prompt ] ],
			'max_tokens'   => $max_tokens,
			'temperature'  => $temperature,
		];

		if ( $system_msg !== '' ) {
			$body['system'] = $system_msg;
		}

		$headers = [
			'Content-Type'      => 'application/json',
			'x-api-key'         => $api_key,
			'anthropic-version' => self::API_VERSION,
		];

		$t0   = microtime( true );
		$data = $this->http_post( self::MESSAGES_URL, $headers, $body );
		$response['latency_ms'] = round( ( microtime( true ) - $t0 ) * 1000, 2 );

		if ( isset( $data['error'] ) ) {
			$response['error'] = is_array( $data['error'] )
				? ( $data['error']['message'] ?? wp_json_encode( $data['error'] ) )
				: (string) $data['error'];
			return $response;
		}

		// Extract text from the first content block.
		$output = '';
		foreach ( $data['content'] ?? [] as $block ) {
			if ( ( $block['type'] ?? '' ) === 'text' ) {
				$output = $block['text'];
				break;
			}
		}
		$response['output']     = $output;
		$response['tokens_in']  = (int) ( $data['usage']['input_tokens'] ?? 0 );
		$response['tokens_out'] = (int) ( $data['usage']['output_tokens'] ?? 0 );
		$response['cost_usd']   = $this->estimate_cost( $model_slug, $response['tokens_in'], $response['tokens_out'] );

		return $response;
	}

	public function list_models(): array {
		// Anthropic does not expose a public /models endpoint as of 2024.
		// Return the known stable model list.
		return [
			[ 'id' => 'claude-3-5-sonnet-20241022', 'name' => 'Claude 3.5 Sonnet (Oct 2024)' ],
			[ 'id' => 'claude-3-5-haiku-20241022',  'name' => 'Claude 3.5 Haiku (Oct 2024)'  ],
			[ 'id' => 'claude-3-opus-20240229',      'name' => 'Claude 3 Opus'                ],
			[ 'id' => 'claude-3-sonnet-20240229',    'name' => 'Claude 3 Sonnet'              ],
			[ 'id' => 'claude-3-haiku-20240307',     'name' => 'Claude 3 Haiku'               ],
		];
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Rough cost estimate based on Anthropic published pricing (mid-2024).
	 * Per-1K-token rates.
	 */
	private function estimate_cost( string $model, int $tokens_in, int $tokens_out ): float {
		$pricing = [
			'claude-3-5-sonnet' => [ 'in' => 0.003,  'out' => 0.015  ],
			'claude-3-5-haiku'  => [ 'in' => 0.001,  'out' => 0.005  ],
			'claude-3-opus'     => [ 'in' => 0.015,  'out' => 0.075  ],
			'claude-3-sonnet'   => [ 'in' => 0.003,  'out' => 0.015  ],
			'claude-3-haiku'    => [ 'in' => 0.00025,'out' => 0.00125 ],
		];

		foreach ( $pricing as $prefix => $rates ) {
			if ( strpos( $model, $prefix ) !== false ) {
				return round(
					( $tokens_in / 1000 * $rates['in'] ) + ( $tokens_out / 1000 * $rates['out'] ),
					6
				);
			}
		}
		return 0.0;
	}
}
