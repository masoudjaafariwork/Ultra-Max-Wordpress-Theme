<?php
/**
 * HTML attribute rendering.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Renders an attribute map as escaped HTML.
 */
final class Attributes {

	/**
	 * Renders attributes.
	 *
	 * `true` renders a bare attribute, `false` and `null` are skipped, other
	 * values are escaped. Invalid names are dropped.
	 *
	 * @param array<string, string|int|float|bool|null> $attributes Attribute map.
	 * @return string Attributes with a leading space, or an empty string.
	 */
	public static function render( array $attributes ): string {
		$out = '';
		foreach ( $attributes as $name => $value ) {
			if ( null === $value || false === $value || 1 !== preg_match( '/^[a-zA-Z_:][-a-zA-Z0-9_:.]*$/', $name ) ) {
				continue;
			}
			if ( true === $value ) {
				$out .= ' ' . $name;
				continue;
			}
			$out .= sprintf( ' %s="%s"', $name, esc_attr( (string) $value ) );
		}
		return $out;
	}

	/**
	 * Joins class names, skipping empty ones.
	 *
	 * @param string|null ...$classes Class lists.
	 */
	public static function classes( ?string ...$classes ): string {
		$list = array();
		foreach ( $classes as $class ) {
			$names = preg_split( '/\s+/', (string) $class, -1, PREG_SPLIT_NO_EMPTY );
			foreach ( false === $names ? array() : $names as $name ) {
				$list[ $name ] = true;
			}
		}
		return implode( ' ', array_keys( $list ) );
	}
}
