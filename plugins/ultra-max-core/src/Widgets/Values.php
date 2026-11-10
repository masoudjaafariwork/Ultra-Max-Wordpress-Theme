<?php
/**
 * Typed access to normalized widget settings.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets;

/**
 * Reads settings that Settings::normalize() produced, with their types,
 * so widget code never handles `mixed`.
 */
final class Values {

	/**
	 * Wraps normalized settings.
	 *
	 * @param array<string, mixed> $values Settings.
	 */
	public function __construct( private readonly array $values ) {}

	/**
	 * A text value.
	 *
	 * @param string $key Control id.
	 */
	public function string( string $key ): string {
		$value = $this->values[ $key ] ?? '';
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * A switch.
	 *
	 * @param string $key Control id.
	 */
	public function bool( string $key ): bool {
		return true === ( $this->values[ $key ] ?? false );
	}

	/**
	 * A whole number.
	 *
	 * @param string $key Control id.
	 */
	public function int( string $key ): int {
		$value = $this->values[ $key ] ?? 0;
		return is_numeric( $value ) ? (int) $value : 0;
	}

	/**
	 * A number.
	 *
	 * @param string $key Control id.
	 */
	public function float( string $key ): float {
		$value = $this->values[ $key ] ?? 0;
		return is_numeric( $value ) ? (float) $value : 0.0;
	}

	/**
	 * A link.
	 *
	 * @param string $key Control id.
	 * @return array{url: string, external: bool, nofollow: bool}
	 */
	public function url( string $key ): array {
		$value = $this->values[ $key ] ?? null;
		return array(
			'url'      => is_array( $value ) && is_string( $value['url'] ?? null ) ? $value['url'] : '',
			'external' => is_array( $value ) && true === ( $value['external'] ?? false ),
			'nofollow' => is_array( $value ) && true === ( $value['nofollow'] ?? false ),
		);
	}

	/**
	 * The address of a link.
	 *
	 * @param string $key Control id.
	 */
	public function href( string $key ): string {
		return $this->url( $key )['url'];
	}

	/**
	 * An image.
	 *
	 * @param string $key Control id.
	 * @return array{id: int, url: string}
	 */
	public function media( string $key ): array {
		$value = $this->values[ $key ] ?? null;
		return array(
			'id'  => is_array( $value ) && is_int( $value['id'] ?? null ) ? $value['id'] : 0,
			'url' => is_array( $value ) && is_string( $value['url'] ?? null ) ? $value['url'] : '',
		);
	}

	/**
	 * A list of post or term ids.
	 *
	 * @param string $key Control id.
	 * @return list<int>
	 */
	public function ids( string $key ): array {
		$value = $this->values[ $key ] ?? array();
		return is_array( $value ) ? array_values( array_filter( $value, 'is_int' ) ) : array();
	}

	/**
	 * Rows of a repeater.
	 *
	 * @param string $key Control id.
	 * @return list<Values>
	 */
	public function rows( string $key ): array {
		$value = $this->values[ $key ] ?? array();
		$rows  = array();
		foreach ( is_array( $value ) ? $value : array() as $row ) {
			if ( is_array( $row ) ) {
				$clean = array();
				foreach ( $row as $name => $item ) {
					if ( is_string( $name ) ) {
						$clean[ $name ] = $item;
					}
				}
				$rows[] = new self( $clean );
			}
		}
		return $rows;
	}

	/**
	 * Every value, for tests and debugging.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		return $this->values;
	}
}
