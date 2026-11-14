<?php
/**
 * Price tables of the Iranian shipping methods (SHIP-01, SHIP-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Shipping;

use UltraMax\Core\Checkout\Iran;

/**
 * Pure functions that read the tables the store types in a method's
 * settings and work out a price.
 *
 * A post table has one line per weight: "up to kilograms = price in the
 * province, price to other provinces", such as `1 = 75000 | 100000`.
 * Heavier parcels pay the last line plus a price for every extra
 * kilogram started. Digits may be typed in Persian.
 *
 * A courier table has one line per city, "city = price"; a city without a
 * price pays the method's own price.
 *
 * @phpstan-type Bracket array{max: float, inside: float, outside: float}
 */
final class Rates {

	/**
	 * Reads a post table.
	 *
	 * @param string $text Table text.
	 * @return list<Bracket>
	 */
	public static function brackets( string $text ): array {
		$out   = array();
		$split = preg_split( '/\R/', $text );
		foreach ( false === $split ? array() : $split as $line ) {
			$line = strtr(
				$line,
				array(
					'٫' => '.',
					'،' => ',',
				)
			);
			if ( 1 !== preg_match( '/^\s*([\d۰-۹٠-٩.]+)\s*[=:]\s*([\d۰-۹٠-٩,\s]+?)\s*(?:[\/|]\s*([\d۰-۹٠-٩,\s]+?))?\s*$/u', str_replace( '،', ',', $line ), $parts ) ) {
				continue;
			}
			$max     = (float) self::number( $parts[1], true );
			$inside  = (float) self::number( $parts[2] );
			$outside = isset( $parts[3] ) && '' !== trim( $parts[3] ) ? (float) self::number( $parts[3] ) : $inside;
			if ( $max > 0 ) {
				$out[] = array(
					'max'     => $max,
					'inside'  => $inside,
					'outside' => $outside,
				);
			}
		}
		usort( $out, static fn ( array $a, array $b ): int => $a['max'] <=> $b['max'] );
		return $out;
	}

	/**
	 * A number typed with Persian digits or thousands marks.
	 *
	 * @param string $text   Text.
	 * @param bool   $points Whether a decimal point may appear.
	 */
	private static function number( string $text, bool $points = false ): string {
		$text  = strtr(
			$text,
			array(
				'۰' => '0',
				'۱' => '1',
				'۲' => '2',
				'۳' => '3',
				'۴' => '4',
				'۵' => '5',
				'۶' => '6',
				'۷' => '7',
				'۸' => '8',
				'۹' => '9',
				'٠' => '0',
				'١' => '1',
				'٢' => '2',
				'٣' => '3',
				'٤' => '4',
				'٥' => '5',
				'٦' => '6',
				'٧' => '7',
				'٨' => '8',
				'٩' => '9',
			)
		);
		$clean = (string) preg_replace( $points ? '/[^0-9.]/' : '/[^0-9]/', '', $text );
		return '' === $clean ? '0' : $clean;
	}

	/**
	 * The post price of a parcel.
	 *
	 * @param array $brackets Table.
	 * @phpstan-param list<Bracket> $brackets
	 * @param float $weight   Weight in kilograms.
	 * @param bool  $inside   Whether it stays in the store's province.
	 * @param float $extra    Price of every kilogram started beyond the table.
	 * @param float $handling Fixed price added to every parcel.
	 * @return float|null Null when the table is empty.
	 */
	public static function post( array $brackets, float $weight, bool $inside, float $extra = 0.0, float $handling = 0.0 ): ?float {
		if ( array() === $brackets ) {
			return null;
		}
		$weight = max( 0.0, $weight );
		foreach ( $brackets as $bracket ) {
			if ( $weight <= $bracket['max'] + 1e-9 ) {
				return ( $inside ? $bracket['inside'] : $bracket['outside'] ) + $handling;
			}
		}
		$last  = $brackets[ count( $brackets ) - 1 ];
		$price = $inside ? $last['inside'] : $last['outside'];
		return $price + (float) ceil( $weight - $last['max'] - 1e-9 ) * $extra + $handling;
	}

	/**
	 * Reads a courier table.
	 *
	 * @param string $text Table text, one "city = price" or "city" per line.
	 * @return array<string, float|null> Price by city as written; null for the method's own price.
	 */
	public static function cities( string $text ): array {
		$out   = array();
		$split = preg_split( '/\R/', $text );
		foreach ( false === $split ? array() : $split as $line ) {
			$parts = array_map( 'trim', explode( '=', str_replace( ':', '=', $line ), 2 ) );
			if ( '' === $parts[0] ) {
				continue;
			}
			$out[ Iran::letters( $parts[0] ) ] = isset( $parts[1] ) && '' !== $parts[1] ? (float) self::number( $parts[1] ) : null;
		}
		return $out;
	}

	/**
	 * The courier price for a city, or null when the courier does not go there.
	 *
	 * @param array<string, float|null> $cities Table; empty means every city of the province.
	 * @param string                    $city   City as the shopper wrote it.
	 * @param float                     $price  The method's own price.
	 */
	public static function courier( array $cities, string $city, float $price ): ?float {
		if ( array() === $cities ) {
			return $price;
		}
		$city = Iran::letters( $city );
		if ( '' === $city || ! array_key_exists( $city, $cities ) ) {
			return null;
		}
		return $cities[ $city ] ?? $price;
	}
}
