<?php
/**
 * OpenAI provider adapter.
 *
 * Supports chat completions (GPT-4, GPT-3.5, etc.) via
 * https://api.openai.com/v1/chat/completions
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Provider_OpenAI extends AIGIS_Provider_Abstract {

	private const COMPLETIONS_URL = 'https://api.openai.com/v1/chat/completions';
	private const MODELS_URL      = 'https://api.openai.com/v1/models';

	// -----------------------------------------------------------------------
	// Contract
	// -----------------------------------------------------------------------

	public function send_prompt( string $prompt, array $options = [] ): array {
		$response = $this->default_response();
		$api_key  = $this->decrypt_option( 'aigis_provider_openai_api_key' );

		if ( $api_key === '' ) {
			$response['error'] = __( 'OpenAI API key not configured.', 'ai-governance-suite' );
			return $response;
		}

		$model_slug   = $this->model['model_slug'] ?? 'gpt-3.5-turbo';
		$response['model'] = $model_slug;

		$system_msg  = sanitize_textarea_field( $options['system'] ?? '' );
		$temperature = isset( $options['temperature'] ) ? (float) $options['temperature'] : 0.7;
		$max_tokens  = absint( $options['max_tokens'] ?? 2048 );

		$messages = [];
		if ( $system_msg !== '' ) {
			$messages[] = [ 'role' => 'system', 'content' => $system_msg ];
		}
		$messages[] = [ 'role' => 'user', 'content' => $prompt ];

		$body = [
			'model'       => $model_slug,
			'messages'    => $messages,
			'temperature' => $temperature,
			'max_tokens'  => $max_tokens,
		];

		$headers = $this->build_headers( $api_key );

		$t0   = microtime( true );
		$data = $this->http_post( self::COMPLETIONS_URL, $headers, $body );
		$response['latency_ms'] = round( ( microtime( true ) - $t0 ) * 1000, 2 );

		if ( isset( $data['error'] ) ) {
			$response['error'] = $data['error'];
			return $response;
		}

		$response['output']      = $data['choices'][0]['message']['content'] ?? '';
		$response['tokens_in']   = (int) ( $data['usage']['prompt_tokens'] ?? 0 );
		$response['tokens_out']  = (int) ( $data['usage']['completion_tokens'] ?? 0 );
		$response['cost_usd']    = $this->estimate_cost( $model_slug, $response['tokens_in'], $response['tokens_out'] );

		return $response;
	}

	public function list_models(): array {
		$api_key = $this->decrypt_option( 'aigis_provider_openai_api_key' );
		if ( $api_key === '' ) {
			return [];
		}

		$data = $this->http_get( self::MODELS_URL, $this->build_headers( $api_key ) );
		if ( isset( $data['error'] ) || empty( $data['data'] ) ) {
			return [];
		}

		$models = [];
		foreach ( $data['data'] as $m ) {
			if ( strpos( $m['id'], 'gpt' ) !== false ) {
				$models[] = [ 'id' => $m['id'], 'name' => $m['id'] ];
			}
		}
		usort( $models, fn( $a, $b ) => strcmp( $a['id'], $b['id'] ) );
		return $models;
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function build_headers( string $api_key ): array {
		$headers = [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		];

		$org_id = get_option( 'aigis_provider_openai_org_id', '' );
		if ( $org_id !== '' ) {
			$headers['OpenAI-Organization'] = $org_id;
		}

		return $headers;
	}

	/**
	 * Rough cost estimate based on published (mid-2024) pricing.
	 * Kept as a best-effort; admins can override via budgets module.
	 */
	private function estimate_cost( string $model, int $tokens_in, int $tokens_out ): float {
		$pricing = [
			'gpt-4o'             => [ 'in' => 0.005,  'out' => 0.015  ],
			'gpt-4-turbo'        => [ 'in' => 0.01,   'out' => 0.03   ],
			'gpt-4'              => [ 'in' => 0.03,   'out' => 0.06   ],
			'gpt-3.5-turbo'      => [ 'in' => 0.0005, 'out' => 0.0015 ],
			'gpt-3.5-turbo-1106' => [ 'in' => 0.001,  'out' => 0.002  ],
		];

		foreach ( $pricing as $prefix => $rates ) {
			if ( strpos( $model, $prefix ) === 0 ) {
				return round(
					( $tokens_in / 1000 * $rates['in'] ) + ( $tokens_out / 1000 * $rates['out'] ),
					6
				);
			}
		}
		return 0.0;
	}
}
