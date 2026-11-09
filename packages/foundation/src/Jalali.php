<?php
/**
 * Jalali (Solar Hijri) calendar: conversion and date formatting.
 *
 * @package UltraMax\Foundation
 */

declare(strict_types=1);

namespace UltraMax\Foundation;

/**
 * Converts between the Gregorian and Jalali calendars and formats
 * timestamps with PHP date format characters in the Jalali calendar.
 *
 * The conversion uses the arithmetic 33-year cycle, which matches the
 * official Iranian calendar for every year from 1178 to 1633 (1799 to 2254).
 */
final class Jalali {

	/**
	 * Formats that machines read (feeds, schema, `datetime` attributes, the
	 * database). They always stay Gregorian with Latin digits.
	 */
	private const MACHINE_FORMATS = array(
		'Y-m-d H:i:s',
		'Y-m-d\TH:i:sP',
		'Y-m-d\TH:i:sO',
		'Y-m-d\TH:i:s',
		'Y-m-d\TH:i:s\Z',
		'D, d M Y H:i:s O',
		'D, d M Y H:i:s +0000',
	);

	/**
	 * Converts a Gregorian date to Jalali.
	 *
	 * @param int $gy Gregorian year.
	 * @param int $gm Gregorian month, 1 to 12.
	 * @param int $gd Gregorian day.
	 * @return array{0: int, 1: int, 2: int} Jalali year, month and day.
	 */
	public static function from_gregorian( int $gy, int $gm, int $gd ): array {
		$month_starts = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
		$gy2          = $gm > 2 ? $gy + 1 : $gy;
		$days         = 355666 + ( 365 * $gy ) + intdiv( $gy2 + 3, 4 ) - intdiv( $gy2 + 99, 100 ) + intdiv( $gy2 + 399, 400 ) + $gd + $month_starts[ $gm - 1 ];

		$jy    = -1595 + ( 33 * intdiv( $days, 12053 ) );
		$days %= 12053;
		$jy   += 4 * intdiv( $days, 1461 );
		$days %= 1461;
		if ( $days > 365 ) {
			--$days;
			$jy   += intdiv( $days, 365 );
			$days %= 365;
		}
		if ( $days < 186 ) {
			return array( $jy, 1 + intdiv( $days, 31 ), 1 + ( $days % 31 ) );
		}
		return array( $jy, 7 + intdiv( $days - 186, 30 ), 1 + ( ( $days - 186 ) % 30 ) );
	}

	/**
	 * Converts a Jalali date to Gregorian.
	 *
	 * @param int $jy Jalali year.
	 * @param int $jm Jalali month, 1 to 12.
	 * @param int $jd Jalali day.
	 * @return array{0: int, 1: int, 2: int} Gregorian year, month and day.
	 */
	public static function to_gregorian( int $jy, int $jm, int $jd ): array {
		$jy  += 1595;
		$days = -355668 + ( 365 * $jy ) + ( intdiv( $jy, 33 ) * 8 ) + intdiv( ( $jy % 33 ) + 3, 4 ) + $jd + ( $jm < 7 ? ( $jm - 1 ) * 31 : ( ( $jm - 7 ) * 30 ) + 186 );

		$gy    = 400 * intdiv( $days, 146097 );
		$days %= 146097;
		if ( $days > 36524 ) {
			--$days;
			$gy   += 100 * intdiv( $days, 36524 );
			$days %= 36524;
			if ( $days >= 365 ) {
				++$days;
			}
		}
		$gy   += 4 * intdiv( $days, 1461 );
		$days %= 1461;
		if ( $days > 365 ) {
			--$days;
			$gy   += intdiv( $days, 365 );
			$days %= 365;
		}
		$gd     = $days + 1;
		$leap   = ( 0 === $gy % 4 && 0 !== $gy % 100 ) || 0 === $gy % 400;
		$months = array( 31, $leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
		$gm     = 1;
		foreach ( $months as $length ) {
			if ( $gd <= $length ) {
				break;
			}
			$gd -= $length;
			++$gm;
		}
		return array( $gy, $gm, $gd );
	}

	/**
	 * Whether a Jalali year has 366 days (Esfand has 30 days).
	 *
	 * @param int $jy Jalali year.
	 */
	public static function is_leap( int $jy ): bool {
		[ $gy, $gm, $gd ] = self::to_gregorian( $jy, 12, 30 );
		return array( $jy, 12, 30 ) === self::from_gregorian( $gy, $gm, $gd );
	}

	/**
	 * Number of days in a Jalali month.
	 *
	 * @param int $jy Jalali year.
	 * @param int $jm Jalali month.
	 */
	public static function days_in_month( int $jy, int $jm ): int {
		if ( $jm <= 6 ) {
			return 31;
		}
		if ( $jm <= 11 ) {
			return 30;
		}
		return self::is_leap( $jy ) ? 30 : 29;
	}

	/**
	 * Whether a format is meant for machines and must stay Gregorian.
	 *
	 * @param string $format PHP date format.
	 */
	public static function is_machine_format( string $format ): bool {
		if ( in_array( $format, self::MACHINE_FORMATS, true ) ) {
			return true;
		}
		// Unescaped c (ISO 8601), r (RFC 2822) or U (Unix time).
		return 1 === preg_match( '/(?<!\\\\)[crU]/', $format );
	}

	/**
	 * Month names, Farvardin first.
	 *
	 * @return list<string>
	 */
	public static function month_names(): array {
		return array(
			__( 'Farvardin', 'ultra-max-foundation' ),
			__( 'Ordibehesht', 'ultra-max-foundation' ),
			__( 'Khordad', 'ultra-max-foundation' ),
			__( 'Tir', 'ultra-max-foundation' ),
			__( 'Mordad', 'ultra-max-foundation' ),
			__( 'Shahrivar', 'ultra-max-foundation' ),
			__( 'Mehr', 'ultra-max-foundation' ),
			__( 'Aban', 'ultra-max-foundation' ),
			__( 'Azar', 'ultra-max-foundation' ),
			__( 'Dey', 'ultra-max-foundation' ),
			__( 'Bahman', 'ultra-max-foundation' ),
			__( 'Esfand', 'ultra-max-foundation' ),
		);
	}

	/**
	 * Weekday names indexed like PHP's `w`: Sunday is 0.
	 *
	 * @return list<string>
	 */
	public static function weekday_names(): array {
		return array(
			__( 'Yekshanbeh', 'ultra-max-foundation' ),
			__( 'Doshanbeh', 'ultra-max-foundation' ),
			__( 'Seshanbeh', 'ultra-max-foundation' ),
			__( 'Chaharshanbeh', 'ultra-max-foundation' ),
			__( 'Panjshanbeh', 'ultra-max-foundation' ),
			__( 'Jomeh', 'ultra-max-foundation' ),
			__( 'Shanbeh', 'ultra-max-foundation' ),
		);
	}

	/**
	 * Formats a timestamp in the Jalali calendar.
	 *
	 * Accepts every PHP date format character. Date characters (d, j, D, l,
	 * z, F, M, m, n, t, L, o, Y, y, a, A) use the Jalali calendar; time
	 * characters come from PHP. Machine formats stay Gregorian.
	 *
	 * @param string             $format    PHP date format.
	 * @param int                $timestamp Unix timestamp.
	 * @param \DateTimeZone|null $timezone  Time zone; the site's when null.
	 */
	public static function format( string $format, int $timestamp, ?\DateTimeZone $timezone = null ): string {
		$datetime = new \DateTimeImmutable( '@' . $timestamp );
		$datetime = $datetime->setTimezone( $timezone ?? wp_timezone() );
		if ( self::is_machine_format( $format ) ) {
			return $datetime->format( $format );
		}

		[ $jy, $jm, $jd ] = self::from_gregorian( (int) $datetime->format( 'Y' ), (int) $datetime->format( 'n' ), (int) $datetime->format( 'j' ) );
		$day_of_year      = ( $jm <= 6 ? ( $jm - 1 ) * 31 : 186 + ( ( $jm - 7 ) * 30 ) ) + $jd - 1;
		$weekday          = (int) $datetime->format( 'w' );
		$months           = self::month_names();
		$weekdays         = self::weekday_names();
		$pm               = (int) $datetime->format( 'G' ) >= 12;

		$out    = '';
		$length = strlen( $format );
		for ( $i = 0; $i < $length; $i++ ) {
			$char = $format[ $i ];
			if ( '\\' === $char ) {
				++$i;
				$out .= $format[ $i ] ?? '';
				continue;
			}
			$out .= match ( $char ) {
				'd'      => sprintf( '%02d', $jd ),
				'j'      => (string) $jd,
				'D', 'l' => $weekdays[ $weekday ] ?? '',
				'S'      => '',
				'z'      => (string) $day_of_year,
				'W'      => (string) ( intdiv( $day_of_year + ( ( $weekday + 1 ) % 7 ), 7 ) + 1 ),
				'F', 'M' => $months[ $jm - 1 ] ?? '',
				'm'      => sprintf( '%02d', $jm ),
				'n'      => (string) $jm,
				't'      => (string) self::days_in_month( $jy, $jm ),
				'L'      => self::is_leap( $jy ) ? '1' : '0',
				'o', 'Y' => (string) $jy,
				'y'      => sprintf( '%02d', $jy % 100 ),
				'a'      => $pm ? __( 'pm', 'ultra-max-foundation' ) : __( 'am', 'ultra-max-foundation' ),
				'A'      => $pm ? __( 'PM', 'ultra-max-foundation' ) : __( 'AM', 'ultra-max-foundation' ),
				'N', 'w', 'B', 'g', 'G', 'h', 'H', 'i', 's', 'u', 'v', 'e', 'I', 'O', 'P', 'p', 'T', 'Z' => $datetime->format( $char ),
				default  => $char,
			};
		}
		return $out;
	}

	/**
	 * Parses a Jalali date written as 1405/08/18 or 1405-8-18, in Latin or
	 * Persian digits.
	 *
	 * @param string $text Date text.
	 * @return array{0: int, 1: int, 2: int}|null Jalali year, month and day, or null when invalid.
	 */
	public static function parse( string $text ): ?array {
		$text = Digits::to_latin( trim( $text ) );
		if ( 1 !== preg_match( '/^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})$/', $text, $parts ) ) {
			return null;
		}
		$jy = (int) $parts[1];
		$jm = (int) $parts[2];
		$jd = (int) $parts[3];
		if ( $jy < 1300 || $jy > 1600 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > self::days_in_month( $jy, $jm ) ) {
			return null;
		}
		return array( $jy, $jm, $jd );
	}

	/**
	 * Unix timestamp of a Jalali date at a time of day in the site's time zone.
	 *
	 * @param int    $jy   Jalali year.
	 * @param int    $jm   Jalali month.
	 * @param int    $jd   Jalali day.
	 * @param string $time Time of day, such as "00:00:00".
	 */
	public static function timestamp( int $jy, int $jm, int $jd, string $time = '00:00:00' ): int {
		[ $gy, $gm, $gd ] = self::to_gregorian( $jy, $jm, $jd );
		$datetime         = new \DateTimeImmutable( sprintf( '%04d-%02d-%02d %s', $gy, $gm, $gd, $time ), wp_timezone() );
		return $datetime->getTimestamp();
	}
}
