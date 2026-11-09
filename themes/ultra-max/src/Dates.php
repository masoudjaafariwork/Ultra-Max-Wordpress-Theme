<?php
/**
 * Jalali dates and Persian digits on the storefront (CORE-03, CORE-04).
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

namespace UltraMax\Theme;

use UltraMax\Theme\Lib\Foundation\Digits;
use UltraMax\Theme\Lib\Foundation\Jalali;
use UltraMax\Theme\Lib\Settings\Store;

/**
 * Every date WordPress formats for the front end goes through `wp_date()`,
 * which this class turns into a Jalali date. Machine formats (ISO 8601,
 * RFC 2822, the database format) stay Gregorian, so `datetime`
 * attributes, feeds and structured data remain valid.
 *
 * The admin, REST responses and feeds are left alone: Rtl-theme requires
 * Jalali dates where visitors read them, not in data that other software
 * parses.
 */
final class Dates {

	/**
	 * Registers the filters.
	 */
	public static function register(): void {
		add_filter( 'wp_date', array( self::class, 'date' ), 10, 4 );
		add_filter( 'number_format_i18n', array( self::class, 'number' ), 10, 1 );
		add_filter( 'wc_price', array( self::class, 'html' ), 20, 1 );
	}

	/**
	 * Whether this request renders the storefront for visitors.
	 */
	public static function storefront(): bool {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}
		if ( wp_is_serving_rest_request() || wp_is_json_request() || is_feed() ) {
			return false;
		}
		return ! ( defined( 'WP_CLI' ) && WP_CLI );
	}

	/**
	 * Whether digits are shown in Persian.
	 */
	public static function persian_digits(): bool {
		return 'fa' === Store::string( 'digits' );
	}

	/**
	 * Formats a date in the Jalali calendar.
	 *
	 * @param mixed $date      Date WordPress formatted.
	 * @param mixed $format    PHP date format.
	 * @param mixed $timestamp Unix timestamp.
	 * @param mixed $timezone  Time zone.
	 * @return mixed
	 */
	public static function date( mixed $date, mixed $format, mixed $timestamp, mixed $timezone ): mixed {
		if ( ! is_string( $format ) || ! is_int( $timestamp ) || ! self::storefront() || Jalali::is_machine_format( $format ) ) {
			return $date;
		}
		// WordPress's English default reads month-first; Persian dates read day, month, year.
		if ( 'F j, Y' === $format && str_starts_with( get_locale(), 'fa' ) ) {
			$format = 'j F Y';
		}
		$out = Jalali::format( $format, $timestamp, $timezone instanceof \DateTimeZone ? $timezone : null );
		return self::persian_digits() ? Digits::to_persian( $out ) : $out;
	}

	/**
	 * Persian digits in a formatted number.
	 *
	 * @param mixed $formatted Formatted number.
	 * @return mixed
	 */
	public static function number( mixed $formatted ): mixed {
		return is_string( $formatted ) && self::storefront() && self::persian_digits() ? Digits::to_persian( $formatted ) : $formatted;
	}

	/**
	 * Persian digits in the text of an HTML fragment, such as a price.
	 *
	 * @param mixed $html HTML fragment.
	 * @return mixed
	 */
	public static function html( mixed $html ): mixed {
		return is_string( $html ) && self::storefront() && self::persian_digits() ? Digits::to_persian_html( $html ) : $html;
	}

	/**
	 * Text with digits in the storefront's style.
	 *
	 * @param string $text Text.
	 */
	public static function digits( string $text ): string {
		return self::persian_digits() ? Digits::to_persian( $text ) : $text;
	}

	/**
	 * Unix timestamp of a stored Jalali date setting (such as 1405-08-18).
	 *
	 * @param string $key Setting id.
	 * @param bool   $end Whether to return the end of that day.
	 */
	public static function setting_timestamp( string $key, bool $end = false ): ?int {
		$date = Jalali::parse( Store::string( $key ) );
		return null === $date ? null : Jalali::timestamp( $date[0], $date[1], $date[2], $end ? '23:59:59' : '00:00:00' );
	}
}
