<?php
/**
 * Cities of each province (CHK-03).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Checkout;

use UltraMax\Core\Lib\Settings\Store;

/**
 * The cities of the shipped list (data/cities.php) and the ones the store
 * adds in its settings, one per line as "province: city", where the
 * province is its name or WooCommerce's code.
 */
final class Cities {

	/**
	 * Every list, by province code.
	 *
	 * @var array<string, list<string>>|null
	 */
	private static ?array $all = null;

	/**
	 * Every city by province code.
	 *
	 * @return array<string, list<string>>
	 */
	public static function all(): array {
		if ( null !== self::$all ) {
			return self::$all;
		}
		$data   = require __DIR__ . '/data/cities.php';
		$lists  = array();
		$states = function_exists( 'WC' ) ? WC()->countries->get_states( 'IR' ) : array();
		$codes  = array();
		foreach ( is_array( $states ) ? $states : array() as $code => $name ) {
			$codes[ Iran::letters( wp_strip_all_tags( html_entity_decode( (string) $name, ENT_QUOTES, 'UTF-8' ) ) ) ] = (string) $code;
		}
		foreach ( is_array( $data ) ? $data : array() as $code => $cities ) {
			$lists[ (string) $code ] = array_values( array_map( static fn ( mixed $city ): string => is_scalar( $city ) ? (string) $city : '', is_array( $cities ) ? $cities : array() ) );
		}
		$split = preg_split( '/\R/', Store::string( 'checkout_cities' ) );
		foreach ( false === $split ? array() : $split as $line ) {
			$parts = explode( ':', $line, 2 );
			if ( 2 !== count( $parts ) ) {
				continue;
			}
			$province = Iran::letters( trim( $parts[0] ) );
			$city     = Iran::letters( trim( $parts[1] ) );
			$code     = isset( $lists[ strtoupper( $province ) ] ) ? strtoupper( $province ) : ( $codes[ $province ] ?? '' );
			if ( '' !== $code && '' !== $city && ! in_array( $city, $lists[ $code ] ?? array(), true ) ) {
				$lists[ $code ][] = $city;
			}
		}
		self::$all = $lists;
		return $lists;
	}

	/**
	 * Forgets the lists, after the settings change.
	 */
	public static function reset(): void {
		self::$all = null;
	}

	/**
	 * The cities of a province.
	 *
	 * @param string $state Province code.
	 * @return list<string>
	 */
	public static function of( string $state ): array {
		return self::all()[ strtoupper( $state ) ] ?? array();
	}

	/**
	 * Whether two city names are the same city, however they were typed.
	 *
	 * @param string $a A name.
	 * @param string $b Another name.
	 */
	public static function same( string $a, string $b ): bool {
		return '' !== trim( $a ) && Iran::letters( $a ) === Iran::letters( $b );
	}
}
