<?php
/**
 * PII detection helper.
 *
 * Scans text for common PII patterns before a prompt is dispatched or logged.
 * All matching is done with PHP regex — no external calls.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_PII_Detector {

	/**
	 * Registered patterns: [ label => regex ]
	 * Patterns must not use capturing groups that affect match results.
	 *
	 * @var array<string, string>
	 */
	private array $patterns;

	public function __construct() {
		$this->patterns = $this->default_patterns();
	}

	/**
	 * Default detection patterns.
	 *
	 * @return array<string, string>
	 */
	private function default_patterns(): array {
		return [
			'email'          => '/\b[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}\b/',
			'us_phone'       => '/\b(?:\+1[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}\b/',
			'ssn_like'       => '/\b\d{3}[-\s]?\d{2}[-\s]?\d{4}\b/',
			'credit_card'    => '/\b(?:4\d{3}|5[1-5]\d{2}|6(?:011|5\d{2})|3[47]\d{2})(?:[-\s]?\d{4}){3}\b/',
			'ipv4_address'   => '/\b(?:(?:25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(?:25[0-5]|2[0-4]\d|[01]?\d\d?)\b/',
			'uk_ni_number'   => '/\b[A-CEGHJ-PR-TW-Z]{2}\s?\d{2}\s?\d{2}\s?\d{2}\s?[A-D]\b/i',
			'passport_like'  => '/\b[A-Z]{1,2}\d{7,9}\b/',
		];
	}

	/**
	 * Scan text and return a list of detected PII types.
	 *
	 * @param string $text The text to scan.
	 * @return array {
	 *   @type string $type  PII label, e.g. 'email'.
	 *   @type string $match The matched string.
	 *   @type int    $offset Character offset in the original text.
	 * }[]
	 */
	public function scan( string $text ): array {
		if ( empty( $text ) ) {
			return [];
		}

		$findings = [];

		foreach ( $this->patterns as $label => $regex ) {
			if ( preg_match_all( $regex, $text, $matches, PREG_OFFSET_CAPTURE ) ) {
				foreach ( $matches[0] as [ $match, $offset ] ) {
					$findings[] = [
						'type'   => $label,
						'match'  => $match,
						'offset' => $offset,
					];
				}
			}
		}

		// Sort by offset so findings appear in document order.
		usort( $findings, static fn( $a, $b ) => $a['offset'] <=> $b['offset'] );

		return $findings;
	}

	/**
	 * Quick check — returns true if any PII is detected.
	 *
	 * @param string $text Text to check.
	 * @return bool
	 */
	public function contains_pii( string $text ): bool {
		return ! empty( $this->scan( $text ) );
	}

	/**
	 * Return a redacted copy of the text with each PII match replaced.
	 *
	 * @param string $text         Original text.
	 * @param string $replacement  Replacement token. Default '[REDACTED]'.
	 * @return string
	 */
	public function redact( string $text, string $replacement = '[REDACTED]' ): string {
		foreach ( $this->patterns as $regex ) {
			$text = preg_replace( $regex, $replacement, $text );
		}
		return $text ?? $text;
	}

	/**
	 * Add or overwrite a detection pattern at runtime.
	 *
	 * @param string $label Unique label.
	 * @param string $regex Full PCRE pattern including delimiters.
	 */
	public function add_pattern( string $label, string $regex ): void {
		$this->patterns[ $label ] = $regex;
	}

	/**
	 * Remove a pattern by label.
	 *
	 * @param string $label Pattern label to remove.
	 */
	public function remove_pattern( string $label ): void {
		unset( $this->patterns[ $label ] );
	}

	/**
	 * Return all registered pattern labels.
	 *
	 * @return string[]
	 */
	public function get_pattern_labels(): array {
		return array_keys( $this->patterns );
	}
}
