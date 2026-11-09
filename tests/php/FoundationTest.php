<?php
/**
 * Tests of the shared foundation: Jalali calendar, digits and colours.
 *
 * @package UltraMax\Tests
 */

use UltraMax\Theme\Lib\Foundation\Color;
use UltraMax\Theme\Lib\Foundation\Digits;
use UltraMax\Theme\Lib\Foundation\Jalali;

/**
 * Foundation helpers (CORE-03, CORE-04, CORE-06).
 */
final class FoundationTest extends WP_UnitTestCase {

	/**
	 * Known pairs of Gregorian and Jalali dates.
	 *
	 * @return array<string, array{0: array{int, int, int}, 1: array{int, int, int}}>
	 */
	public static function dates(): array {
		return array(
			'project start'      => array( array( 2026, 11, 9 ), array( 1405, 8, 18 ) ),
			'nowruz 1405'        => array( array( 2026, 3, 21 ), array( 1405, 1, 1 ) ),
			'nowruz 1404'        => array( array( 2025, 3, 21 ), array( 1404, 1, 1 ) ),
			'last day of 1403'   => array( array( 2025, 3, 20 ), array( 1403, 12, 30 ) ),
			'leap gregorian day' => array( array( 2024, 2, 29 ), array( 1402, 12, 10 ) ),
			'yalda 1402'         => array( array( 2023, 12, 21 ), array( 1402, 9, 30 ) ),
			'unix epoch'         => array( array( 1970, 1, 1 ), array( 1348, 10, 11 ) ),
		);
	}

	/**
	 * @dataProvider dates
	 *
	 * @param array{int, int, int} $gregorian Gregorian date.
	 * @param array{int, int, int} $jalali    Jalali date.
	 */
	public function test_conversion_both_ways( array $gregorian, array $jalali ): void {
		$this->assertSame( $jalali, Jalali::from_gregorian( ...$gregorian ) );
		$this->assertSame( $gregorian, Jalali::to_gregorian( ...$jalali ) );
	}

	public function test_every_day_of_four_centuries_round_trips(): void {
		$day = new DateTimeImmutable( '1900-01-01' );
		$end = new DateTimeImmutable( '2300-01-01' );
		while ( $day < $end ) {
			$g = array( (int) $day->format( 'Y' ), (int) $day->format( 'n' ), (int) $day->format( 'j' ) );
			$this->assertSame( $g, Jalali::to_gregorian( ...Jalali::from_gregorian( ...$g ) ) );
			$day = $day->modify( '+1 day' );
		}
	}

	public function test_leap_years(): void {
		$this->assertTrue( Jalali::is_leap( 1403 ) );
		$this->assertFalse( Jalali::is_leap( 1404 ) );
		$this->assertSame( 30, Jalali::days_in_month( 1403, 12 ) );
		$this->assertSame( 29, Jalali::days_in_month( 1404, 12 ) );
		$this->assertSame( 31, Jalali::days_in_month( 1405, 6 ) );
	}

	public function test_format_uses_jalali_parts_and_keeps_time(): void {
		$timestamp = ( new DateTimeImmutable( '2026-11-09 14:05:09', new DateTimeZone( 'Asia/Tehran' ) ) )->getTimestamp();
		$tehran    = new DateTimeZone( 'Asia/Tehran' );
		$this->assertSame( '1405/08/18 14:05', Jalali::format( 'Y/m/d H:i', $timestamp, $tehran ) );
		$this->assertSame( '18 Aban 1405', Jalali::format( 'j F Y', $timestamp, $tehran ) );
		$this->assertSame( 'Y 1405', Jalali::format( '\Y Y', $timestamp, $tehran ) );
		$this->assertSame( '30', Jalali::format( 't', $timestamp, $tehran ) );
	}

	public function test_machine_formats_stay_gregorian(): void {
		$timestamp = ( new DateTimeImmutable( '2026-11-09 10:00:00', new DateTimeZone( 'UTC' ) ) )->getTimestamp();
		$utc       = new DateTimeZone( 'UTC' );
		$this->assertSame( '2026-11-09T10:00:00+00:00', Jalali::format( 'c', $timestamp, $utc ) );
		$this->assertSame( '2026-11-09 10:00:00', Jalali::format( 'Y-m-d H:i:s', $timestamp, $utc ) );
		$this->assertTrue( Jalali::is_machine_format( DATE_RFC2822 ) );
		$this->assertFalse( Jalali::is_machine_format( 'j F Y' ) );
		$this->assertFalse( Jalali::is_machine_format( '\c j' ) );
	}

	public function test_parse_accepts_persian_digits_and_rejects_invalid_days(): void {
		$this->assertSame( array( 1405, 8, 18 ), Jalali::parse( '۱۴۰۵/۰۸/۱۸' ) );
		$this->assertSame( array( 1405, 8, 18 ), Jalali::parse( '1405-8-18' ) );
		$this->assertNull( Jalali::parse( '1404/12/30' ) );
		$this->assertNull( Jalali::parse( '2026-11-09' ) );
		$this->assertNull( Jalali::parse( 'tomorrow' ) );
	}

	public function test_digits_convert_text_but_not_markup(): void {
		$this->assertSame( '۱۲۳ و ۴۵۶', Digits::to_persian( '123 و ٤٥٦' ) );
		$this->assertSame( '123', Digits::to_latin( '۱۲۳' ) );
		$html = '<span class="amount-2" data-x="9">1,200&nbsp;&#x062A;&#1578;</span>';
		$this->assertSame( '<span class="amount-2" data-x="9">۱,۲۰۰&nbsp;&#x062A;&#1578;</span>', Digits::to_persian_html( $html ) );
	}

	public function test_color_ramp_and_contrast(): void {
		$ramp = Color::ramp( '#4C42D3' );
		$this->assertSame( '#4c42d3', $ramp[600] );
		$this->assertSame( array( 50, 100, 200, 400, 500, 600, 700, 800 ), array_keys( $ramp ) );
		$this->assertSame( '#3d35a9', $ramp[700] );
		$this->assertSame( '#ffffff', Color::on( '#4c42d3' ) );
		$this->assertSame( '#15161c', Color::on( '#ffd400' ) );
		$this->assertEqualsWithDelta( 21.0, Color::contrast( '#000', '#fff' ), 0.01 );
		$this->assertNull( Color::normalize( 'red' ) );
	}
}
