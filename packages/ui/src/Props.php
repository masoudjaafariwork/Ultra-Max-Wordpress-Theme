<?php
/**
 * Runtime checks for component props.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Reads props and reports invalid ones.
 *
 * PHPStan checks prop shapes statically. These checks catch what reaches
 * runtime anyway (for example values from settings): an invalid value is
 * reported with `_doing_it_wrong()`, which fails the PHPUnit suite, and the
 * component falls back to its default so a live store never breaks.
 */
final class Props {

	/**
	 * Reads a prop that must be one of a fixed list.
	 *
	 * @template T of string
	 * @param string             $component Component name for the message.
	 * @param array<string, mixed> $props   Props.
	 * @param string             $key       Prop name.
	 * @param non-empty-list<T>  $allowed   Allowed values; the first is the default.
	 * @return T
	 */
	public static function one_of( string $component, array $props, string $key, array $allowed ): string {
		if ( ! array_key_exists( $key, $props ) ) {
			return $allowed[0];
		}
		$value = $props[ $key ];
		if ( in_array( $value, $allowed, true ) ) {
			/** @var T $value */
			return $value;
		}
		self::invalid( $component, $key, sprintf( 'expected one of %s', implode( ', ', $allowed ) ) );
		return $allowed[0];
	}

	/**
	 * Reads a required, non-empty string prop that is used as an HTML id.
	 *
	 * @param string             $component Component name for the message.
	 * @param array<string, mixed> $props   Props.
	 * @param string             $key       Prop name.
	 */
	public static function id( string $component, array $props, string $key = 'id' ): string {
		$value = $props[ $key ] ?? '';
		if ( is_string( $value ) && 1 === preg_match( '/^[A-Za-z][-A-Za-z0-9_]*$/', $value ) ) {
			return $value;
		}
		self::invalid( $component, $key, 'expected a valid HTML id' );
		return 'um-' . strtolower( $component ) . '-' . substr( md5( (string) wp_json_encode( $props ) ), 0, 8 );
	}

	/**
	 * Reports an invalid prop.
	 *
	 * @param string $component Component name.
	 * @param string $key       Prop name.
	 * @param string $reason    What was expected.
	 */
	private static function invalid( string $component, string $key, string $reason ): void {
		_doing_it_wrong(
			esc_html( 'UI::' . lcfirst( $component ) ),
			esc_html( sprintf( 'Invalid "%s" prop: %s.', $key, $reason ) ),
			'0.1.0'
		);
	}
}