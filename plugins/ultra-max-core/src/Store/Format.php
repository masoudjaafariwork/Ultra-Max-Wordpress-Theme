<?php
/**
 * Numbers, prices and dates as the store shows them.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Store;

use UltraMax\Core\Lib\Foundation\Digits;
use UltraMax\Core\Lib\Foundation\Jalali;
use UltraMax\Core\Lib\Settings\Store;

/**
 * Widgets hand components finished text: digits in the store's choice
 * (CORE-04), prices with WooCommerce's separators, dates in the Jalali
 * calendar (CORE-03).
 */
final class Format {

	/**
	 * Digits in the store's choice.
	 *
	 * @param string $text Text with Latin digits.
	 */
	public static function digits( string $text ): string {
		return 'en' === Store::string( 'digits' ) ? $text : Digits::to_persian( $text );
	}

	/**
	 * Whether the store uses Persian digits.
	 */
	public static function persian(): bool {
		return 'en' !== Store::string( 'digits' );
	}

	/**
	 * A whole number with thousands separators.
	 *
	 * @param int|float $number Number.
	 */
	public static function number( int|float $number ): string {
		return self::digits( number_format_i18n( $number ) );
	}

	/**
	 * An amount of money without the currency.
	 *
	 * @param float $amount Amount.
	 */
	public static function money( float $amount ): string {
		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 0;
		$thousand = function_exists( 'wc_get_price_thousand_separator' ) ? wc_get_price_thousand_separator() : ',';
		$decimal  = function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : '.';
		return self::digits( number_format( $amount, $decimals, $decimal, $thousand ) );
	}

	/**
	 * The currency name shown after prices.
	 */
	public static function currency(): string {
		if ( ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
			return '';
		}
		return html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * A percent, for example "۲۵٪".
	 *
	 * @param int $percent Percent.
	 */
	public static function percent( int $percent ): string {
		return self::persian() ? self::digits( (string) $percent ) . '٪' : $percent . '%';
	}

	/**
	 * A Jalali date.
	 *
	 * @param int    $timestamp Unix timestamp.
	 * @param string $format    PHP date format.
	 */
	public static function date( int $timestamp, string $format = 'j F Y' ): string {
		return self::digits( Jalali::format( $format, $timestamp ) );
	}
}
