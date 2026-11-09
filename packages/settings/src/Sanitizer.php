<?php
/**
 * Sanitises setting values by field type.
 *
 * @package UltraMax\Settings
 */

declare(strict_types=1);

namespace UltraMax\Settings;

use UltraMax\Foundation\Jalali;

/**
 * Turns any submitted value into a valid value of its field, or null when
 * it cannot, in which case the store keeps the previous value.
 *
 * @phpstan-import-type Field from Schema
 */
final class Sanitizer {

	/**
	 * The value a field has when nothing was set and it declares no default.
	 *
	 * @param array $field Field.
	 * @phpstan-param Field|array<string, mixed> $field
	 */
	public static function empty_value( array $field ): mixed {
		return match ( $field['type'] ?? '' ) {
			'toggle'   => false,
			'range', 'number', 'media' => 0,
			'devices'  => array(
				'desktop' => true,
				'tablet'  => true,
				'mobile'  => true,
			),
			'repeater' => array(),
			'select', 'segmented', 'choice', 'preset' => is_array( $field['options'] ?? null ) && is_array( $field['options'][0] ?? null ) && is_scalar( $field['options'][0]['value'] ?? null ) ? (string) $field['options'][0]['value'] : '',
			default    => '',
		};
	}

	/**
	 * Sanitises one value.
	 *
	 * @param array $field Field.
	 * @param mixed $value Submitted value.
	 * @phpstan-param Field|array<string, mixed> $field
	 * @return mixed The clean value, or null when the value is rejected.
	 */
	public static function sanitize( array $field, mixed $value ): mixed {
		$type = is_string( $field['type'] ?? null ) ? $field['type'] : 'text';
		return match ( $type ) {
			'toggle'   => self::to_bool( $value ),
			'select', 'segmented', 'choice', 'preset' => self::option( $field, $value ),
			'color'    => is_string( $value ) ? self::color( $value ) : null,
			'range', 'number' => self::number( $field, $value ),
			'text'     => is_scalar( $value ) ? sanitize_text_field( (string) $value ) : null,
			'url'      => is_string( $value ) ? esc_url_raw( trim( $value ) ) : null,
			'textarea' => is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : null,
			'code'     => self::code( $field, $value ),
			'devices'  => self::devices( $value ),
			'jdate'    => self::jdate( $value ),
			'media'    => is_numeric( $value ) ? absint( $value ) : null,
			'repeater' => self::repeater( $field, $value ),
			default    => null,
		};
	}

	/**
	 * Reads a boolean from form values.
	 *
	 * @param mixed $value Value.
	 */
	private static function to_bool( mixed $value ): bool {
		return true === $value || 1 === $value || '1' === $value || 'true' === $value || 'on' === $value;
	}

	/**
	 * One of the field's options.
	 *
	 * @param array $field Field.
	 * @param mixed $value Value.
	 * @phpstan-param array<string, mixed> $field
	 */
	private static function option( array $field, mixed $value ): ?string {
		if ( ! is_scalar( $value ) ) {
			return null;
		}
		foreach ( is_array( $field['options'] ?? null ) ? $field['options'] : array() as $option ) {
			if ( is_array( $option ) && is_scalar( $option['value'] ?? null ) && (string) $option['value'] === (string) $value ) {
				return (string) $value;
			}
		}
		return null;
	}

	/**
	 * A #rrggbb colour, or an empty string to clear it.
	 *
	 * @param string $value Value.
	 */
	private static function color( string $value ): ?string {
		if ( '' === trim( $value ) ) {
			return '';
		}
		$color = sanitize_hex_color( trim( $value ) );
		return is_string( $color ) && '' !== $color ? strtolower( $color ) : null;
	}

	/**
	 * A number clamped to the field's range.
	 *
	 * @param array $field Field.
	 * @param mixed $value Value.
	 * @phpstan-param array<string, mixed> $field
	 */
	private static function number( array $field, mixed $value ): int|float|null {
		if ( ! is_numeric( $value ) ) {
			return null;
		}
		$step   = $field['step'] ?? 1;
		$number = (float) $value;
		if ( is_numeric( $field['min'] ?? null ) ) {
			$number = max( (float) $field['min'], $number );
		}
		if ( is_numeric( $field['max'] ?? null ) ) {
			$number = min( (float) $field['max'], $number );
		}
		$whole = ! is_float( $step ) && ! ( is_numeric( $step ) && floor( (float) $step ) !== (float) $step );
		return $whole ? (int) round( $number ) : $number;
	}

	/**
	 * Custom code. CSS is kept as written except for anything that could
	 * close the style element; JS and HTML need `unfiltered_html`, the same
	 * rule WordPress applies to its own Custom HTML.
	 *
	 * @param array $field Field.
	 * @param mixed $value Value.
	 * @phpstan-param array<string, mixed> $field
	 */
	private static function code( array $field, mixed $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}
		if ( 'css' === ( $field['language'] ?? 'css' ) ) {
			return trim( str_ireplace( '</style', '', wp_strip_all_tags( $value ) ) );
		}
		return current_user_can( 'unfiltered_html' ) ? trim( $value ) : null;
	}

	/**
	 * Device visibility.
	 *
	 * @param mixed $value Value.
	 * @return array{desktop: bool, tablet: bool, mobile: bool}|null
	 */
	private static function devices( mixed $value ): ?array {
		if ( ! is_array( $value ) ) {
			return null;
		}
		return array(
			'desktop' => self::to_bool( $value['desktop'] ?? false ),
			'tablet'  => self::to_bool( $value['tablet'] ?? false ),
			'mobile'  => self::to_bool( $value['mobile'] ?? false ),
		);
	}

	/**
	 * A Jalali date normalised to 1405-08-18, or an empty string.
	 *
	 * @param mixed $value Value.
	 */
	private static function jdate( mixed $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}
		if ( '' === trim( $value ) ) {
			return '';
		}
		$date = Jalali::parse( $value );
		return null === $date ? null : sprintf( '%04d-%02d-%02d', ...$date );
	}

	/**
	 * A list of rows, each sanitised by the sub-fields.
	 *
	 * @param array $field Field.
	 * @param mixed $value Value.
	 * @phpstan-param array<string, mixed> $field
	 * @return list<array<string, mixed>>|null
	 */
	private static function repeater( array $field, mixed $value ): ?array {
		if ( ! is_array( $value ) ) {
			return null;
		}
		$max  = is_int( $field['max_rows'] ?? null ) ? $field['max_rows'] : 50;
		$subs = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
		$rows = array();
		foreach ( array_slice( array_values( $value ), 0, $max ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$clean = array();
			foreach ( $subs as $sub ) {
				if ( ! is_array( $sub ) || ! is_string( $sub['id'] ?? null ) ) {
					continue;
				}
				$item                = self::sanitize( $sub, $row[ $sub['id'] ] ?? null );
				$clean[ $sub['id'] ] = $item ?? ( $sub['default'] ?? self::empty_value( $sub ) );
			}
			$rows[] = $clean;
		}
		return $rows;
	}
}
