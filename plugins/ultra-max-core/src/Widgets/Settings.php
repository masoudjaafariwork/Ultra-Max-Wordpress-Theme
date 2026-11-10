<?php
/**
 * Normalizes widget settings from Elementor or a shortcode.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets;

/**
 * Elementor stores a switch as "yes" or "", a link as an array with
 * "is_external" set to "on", a list of posts as strings. A shortcode gives
 * only strings. This class turns both into one shape per control type, with
 * the default filled in, so a widget renders the same from either.
 *
 * - text, textarea, code, color, icon, date, select: string
 * - switch: bool
 * - number: int or float
 * - url: array{url: string, external: bool, nofollow: bool}
 * - media: array{id: int, url: string}
 * - posts, terms: list<int>
 * - repeater: list<array<string, mixed>>, each row normalized
 *
 * @phpstan-import-type Section from Widget
 * @phpstan-import-type Field from Widget
 */
final class Settings {

	/**
	 * Normalizes every control of a widget.
	 *
	 * @param array                $sections Sections of controls.
	 * @param array<string, mixed> $raw      Raw settings.
	 * @phpstan-param list<Section> $sections
	 * @return array<string, mixed>
	 */
	public static function normalize( array $sections, array $raw ): array {
		$out = array();
		foreach ( $sections as $section ) {
			foreach ( $section['fields'] as $field ) {
				$id = $field['id'] ?? null;
				if ( ! is_string( $id ) || 'heading' === ( $field['type'] ?? '' ) ) {
					continue;
				}
				$out[ $id ] = self::value( $field, $raw[ $id ] ?? null );
			}
		}
		return $out;
	}

	/**
	 * Normalizes one value.
	 *
	 * @param array $field Field.
	 * @param mixed $value Raw value; null for the default.
	 * @phpstan-param Field|array<string, mixed> $field
	 */
	public static function value( array $field, mixed $value ): mixed {
		$type    = is_string( $field['type'] ?? null ) ? $field['type'] : 'text';
		$default = $field['default'] ?? null;
		if ( null === $value || ( '' === $value && ! in_array( $type, array( 'text', 'textarea', 'wysiwyg', 'code', 'switch' ), true ) ) ) {
			$value = $default;
		}
		switch ( $type ) {
			case 'switch':
				return true === $value || 'yes' === $value || 'true' === $value || '1' === $value || 1 === $value || 'on' === $value;
			case 'number':
				$number = is_numeric( $value ) ? $value + 0 : ( is_numeric( $default ) ? $default + 0 : 0 );
				if ( isset( $field['min'] ) && is_numeric( $field['min'] ) ) {
					$number = max( $field['min'], $number );
				}
				if ( isset( $field['max'] ) && is_numeric( $field['max'] ) ) {
					$number = min( $field['max'], $number );
				}
				return $number;
			case 'select':
				$options = is_array( $field['options'] ?? null ) ? array_map( 'strval', array_keys( $field['options'] ) ) : array();
				$options = array_flip( $options );
				$value   = is_scalar( $value ) ? (string) $value : '';
				if ( isset( $options[ $value ] ) ) {
					return $value;
				}
				return is_scalar( $default ) ? (string) $default : (string) array_key_first( $options );
			case 'url':
				return self::url( $value );
			case 'media':
				return self::media( $value );
			case 'posts':
			case 'terms':
				return self::ids( $value );
			case 'repeater':
				return self::rows( $field, $value );
			default:
				return is_scalar( $value ) ? (string) $value : '';
		}
	}

	/**
	 * A link.
	 *
	 * @param mixed $value Elementor URL value or a plain address.
	 * @return array{url: string, external: bool, nofollow: bool}
	 */
	private static function url( mixed $value ): array {
		if ( is_string( $value ) ) {
			$value = array( 'url' => $value );
		}
		$value = is_array( $value ) ? $value : array();
		$url   = is_string( $value['url'] ?? null ) ? trim( $value['url'] ) : '';
		return array(
			'url'      => $url,
			'external' => in_array( $value['is_external'] ?? ( $value['external'] ?? false ), array( 'on', true, 'yes', '1' ), true ),
			'nofollow' => in_array( $value['nofollow'] ?? false, array( 'on', true, 'yes', '1' ), true ),
		);
	}

	/**
	 * An image or file from the media library.
	 *
	 * @param mixed $value Elementor media value or an attachment id.
	 * @return array{id: int, url: string}
	 */
	private static function media( mixed $value ): array {
		if ( is_numeric( $value ) ) {
			$value = array( 'id' => (int) $value );
		}
		$value = is_array( $value ) ? $value : array();
		$id    = is_numeric( $value['id'] ?? null ) ? (int) $value['id'] : 0;
		$url   = is_string( $value['url'] ?? null ) ? $value['url'] : '';
		if ( $id > 0 && '' === $url ) {
			$found = wp_get_attachment_url( $id );
			$url   = is_string( $found ) ? $found : '';
		}
		return array(
			'id'  => max( 0, $id ),
			'url' => $url,
		);
	}

	/**
	 * A list of ids.
	 *
	 * @param mixed $value Array of ids or a comma separated list.
	 * @return list<int>
	 */
	private static function ids( mixed $value ): array {
		if ( is_string( $value ) || is_int( $value ) ) {
			$value = explode( ',', (string) $value );
		}
		$out = array();
		foreach ( is_array( $value ) ? $value : array() as $id ) {
			if ( is_numeric( $id ) && (int) $id > 0 ) {
				$out[] = (int) $id;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Rows of a repeater.
	 *
	 * @param array $field Field.
	 * @param mixed $value Rows, or a JSON list from a shortcode.
	 * @phpstan-param Field|array<string, mixed> $field
	 * @return list<array<string, mixed>>
	 */
	private static function rows( array $field, mixed $value ): array {
		if ( is_string( $value ) ) {
			$value = json_decode( $value, true );
		}
		$fields = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
		$rows   = array();
		foreach ( is_array( $value ) ? $value : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$clean = array();
			foreach ( $fields as $sub ) {
				if ( is_array( $sub ) && is_string( $sub['id'] ?? null ) ) {
					$clean[ $sub['id'] ] = self::value( $sub, $row[ $sub['id'] ] ?? null );
				}
			}
			$clean['_id'] = is_string( $row['_id'] ?? null ) ? $row['_id'] : '';
			$rows[]       = $clean;
		}
		return $rows;
	}
}
