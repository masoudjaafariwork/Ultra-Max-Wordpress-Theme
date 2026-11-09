<?php
/**
 * Colour helpers for design tokens.
 *
 * @package UltraMax\Foundation
 */

declare(strict_types=1);

namespace UltraMax\Foundation;

/**
 * Builds a brand colour ramp from one colour and checks contrast.
 *
 * The same rules live in packages/ui/admin/lib/color.ts for the live
 * preview; tests keep both in step.
 */
final class Color {

	/**
	 * How each ramp step is mixed: [mix colour, share of the brand colour in %].
	 */
	private const RAMP = array(
		50  => array( '#ffffff', 8 ),
		100 => array( '#ffffff', 16 ),
		200 => array( '#ffffff', 32 ),
		400 => array( '#ffffff', 70 ),
		500 => array( '#ffffff', 85 ),
		600 => array( '#ffffff', 100 ),
		700 => array( '#000000', 80 ),
		800 => array( '#000000', 62 ),
	);

	/**
	 * Normalises a hex colour to lowercase #rrggbb.
	 *
	 * @param string $hex Colour such as #abc or #AABBCC.
	 * @return string|null Null when the value is not a hex colour.
	 */
	public static function normalize( string $hex ): ?string {
		$hex = strtolower( trim( $hex ) );
		if ( 1 === preg_match( '/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $hex, $m ) ) {
			return '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
		}
		return 1 === preg_match( '/^#[0-9a-f]{6}$/', $hex ) ? $hex : null;
	}

	/**
	 * Red, green and blue channels of a colour.
	 *
	 * @param string $hex Normalised colour.
	 * @return array{0: int, 1: int, 2: int}
	 */
	private static function channels( string $hex ): array {
		return array( (int) hexdec( substr( $hex, 1, 2 ) ), (int) hexdec( substr( $hex, 3, 2 ) ), (int) hexdec( substr( $hex, 5, 2 ) ) );
	}

	/**
	 * Mixes two colours in sRGB, like CSS `color-mix(in srgb, a p%, b)`.
	 *
	 * @param string $a       First colour.
	 * @param int    $percent Share of the first colour, 0 to 100.
	 * @param string $b       Second colour.
	 */
	public static function mix( string $a, int $percent, string $b ): string {
		$ca  = self::channels( self::normalize( $a ) ?? '#000000' );
		$cb  = self::channels( self::normalize( $b ) ?? '#000000' );
		$out = '#';
		for ( $i = 0; $i < 3; $i++ ) {
			$out .= sprintf( '%02x', (int) round( ( $ca[ $i ] * $percent + $cb[ $i ] * ( 100 - $percent ) ) / 100 ) );
		}
		return $out;
	}

	/**
	 * The brand ramp built from one colour.
	 *
	 * @param string $hex Brand colour.
	 * @return array<int, string> Step (50 to 800) to colour.
	 */
	public static function ramp( string $hex ): array {
		$base = self::normalize( $hex ) ?? '#4c42d3';
		$ramp = array();
		foreach ( self::RAMP as $step => [ $with, $share ] ) {
			$ramp[ $step ] = self::mix( $base, $share, $with );
		}
		return $ramp;
	}

	/**
	 * Relative luminance (WCAG 2).
	 *
	 * @param string $hex Colour.
	 */
	public static function luminance( string $hex ): float {
		$sum     = 0.0;
		$weights = array( 0.2126, 0.7152, 0.0722 );
		foreach ( self::channels( self::normalize( $hex ) ?? '#000000' ) as $i => $channel ) {
			$c    = $channel / 255;
			$c    = $c <= 0.03928 ? $c / 12.92 : ( ( $c + 0.055 ) / 1.055 ) ** 2.4;
			$sum += $weights[ $i ] * $c;
		}
		return $sum;
	}

	/**
	 * Contrast ratio between two colours (WCAG 2), from 1 to 21.
	 *
	 * @param string $a First colour.
	 * @param string $b Second colour.
	 */
	public static function contrast( string $a, string $b ): float {
		$la = self::luminance( $a );
		$lb = self::luminance( $b );
		return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
	}

	/**
	 * Text colour to use on a background: white when it reaches 4.5:1, else near black.
	 *
	 * @param string $background Background colour.
	 */
	public static function on( string $background ): string {
		return self::contrast( $background, '#ffffff' ) >= 4.5 ? '#ffffff' : '#15161c';
	}
}
