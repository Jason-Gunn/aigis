<?php
/**
 * Ollama provider adapter (local / on-prem inference).
 *
 * Communicates with a locally-running Ollama server via
 * {base_url}/api/chat  (streaming disabled; JSON mode).
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Provider_Ollama extends AIGIS_Provider_Abstract {

	// -----------------------------------------------------------------------
	// Contract
	// -----------------------------------------------------------------------

	public function send_prompt( string $prompt, array $options = [] ): array {
		$response = $this->default_response();

		$base_url  = trailingslashit( get_option( 'aigis_provider_ollama_base_url', 'http://localhost:11434' ) );
		$endpoint  = $base_url . 'api/chat';

		$model_slug   = $this->model['model_slug']
			?? get_option( 'aigis_provider_ollama_default_model', 'llama3' );
		$response['model'] = $model_slug;

		$system_msg  = sanitize_textarea_field( $options['system'] ?? '' );
		$temperature = isset( $options['temperature'] ) ? (float) $options['temperature'] : 0.7;

		$messages = [];
		if ( $system_msg !== '' ) {
			$messages[] = [ 'role' => 'system', 'content' => $system_msg ];
		}
		$messages[] = [ 'role' => 'user', 'content' => $prompt ];

		$body = [
			'model'    => $model_slug,
			'messages' => $messages,
			'stream'   => false,
			'options'  => [
				'temperature' => $temperature,
				'num_predict' => absint( $options['max_tokens'] ?? 2048 ),
			],
		];

		$headers = [
			'Content-Type' => 'application/json',
		];

		$t0   = microtime( true );
		$data = $this->http_post( $endpoint, $headers, $body, 120 );
		$response['latency_ms'] = round( ( microtime( true ) - $t0 ) * 1000, 2 );

		if ( isset( $data['error'] ) ) {
			$response['error'] = $data['error'];
			return $response;
		}

		$response['output']     = $data['message']['content'] ?? '';
		$response['tokens_in']  = (int) ( $data['prompt_eval_count'] ?? 0 );
		$response['tokens_out'] = (int) ( $data['eval_count'] ?? 0 );
		// Local inference: no cost.
		$response['cost_usd'] = 0.0;

		return $response;
	}

	public function list_models(): array {
		$base_url = trailingslashit( get_option( 'aigis_provider_ollama_base_url', 'http://localhost:11434' ) );
		$data     = $this->http_get( $base_url . 'api/tags', [], 10 );

		if ( isset( $data['error'] ) || empty( $data['models'] ) ) {
			return [];
		}

		$models = [];
		foreach ( $data['models'] as $m ) {
			$id       = $m['name'] ?? '';
			$size_gb  = isset( $m['size'] ) ? round( $m['size'] / 1073741824, 1 ) . ' GB' : '';
			$label    = $id . ( $size_gb ? " ($size_gb)" : '' );
			$models[] = [ 'id' => $id, 'name' => $label ];
		}
		return $models;
	}
}
