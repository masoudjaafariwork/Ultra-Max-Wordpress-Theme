<?php
/**
 * Iranian checks of checkout values (CHK-04, CHK-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Checkout;

/**
 * Pure functions: digits typed in Persian or Arabic become Latin, spaces
 * and dashes go, and each value is checked by its own rule. The same
 * rules run in the browser (checkout.behavior.ts) for an instant answer.
 */
final class Iran {

	/**
	 * Latin digits only, without spaces, dashes or other marks.
	 *
	 * @param string $value Typed value.
	 */
	public static function digits( string $value ): string {
		$value = strtr(
			$value,
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
		return (string) preg_replace( '/[^0-9+]/', '', $value );
	}

	/**
	 * A mobile number as 09xxxxxxxxx, or null when it is not one.
	 *
	 * @param string $value Typed number, such as +98 912 345 6789.
	 */
	public static function mobile( string $value ): ?string {
		$number = self::digits( $value );
		$number = (string) preg_replace( '/^(\+98|0098|98)(?=9\d{9}$)/', '', $number );
		if ( 1 === preg_match( '/^9\d{9}$/', $number ) ) {
			$number = '0' . $number;
		}
		return 1 === preg_match( '/^09\d{9}$/', $number ) ? $number : null;
	}

	/**
	 * Whether a value is a valid national code (کد ملی): ten digits whose
	 * last one is the check digit of the others.
	 *
	 * @param string $value Typed code.
	 */
	public static function national_code( string $value ): bool {
		$code = self::digits( $value );
		if ( 1 !== preg_match( '/^\d{10}$/', $code ) || 1 === preg_match( '/^(\d)\1{9}$/', $code ) ) {
			return false;
		}
		$sum = 0;
		for ( $i = 0; $i < 9; $i++ ) {
			$sum += (int) $code[ $i ] * ( 10 - $i );
		}
		$rest  = $sum % 11;
		$check = (int) $code[9];
		return ( $rest < 2 ? $rest : 11 - $rest ) === $check;
	}

	/**
	 * Whether a value is a valid national id of a company (شناسه ملی):
	 * eleven digits whose last one is the check digit of the others.
	 *
	 * @param string $value Typed id.
	 */
	public static function company_id( string $value ): bool {
		$id = self::digits( $value );
		if ( 1 !== preg_match( '/^\d{11}$/', $id ) || 1 === preg_match( '/^(\d)\1{10}$/', $id ) ) {
			return false;
		}
		$weights = array( 29, 27, 23, 19, 17 );
		$plus    = (int) $id[9] + 2;
		$sum     = 0;
		for ( $i = 0; $i < 10; $i++ ) {
			$sum += ( (int) $id[ $i ] + $plus ) * $weights[ $i % 5 ];
		}
		$rest = $sum % 11;
		return ( 10 === $rest ? 0 : $rest ) === (int) $id[10];
	}

	/**
	 * Whether a value looks like a postal code: ten digits, without 0 or 2
	 * in the first five and without four equal digits at the start.
	 *
	 * @param string $value Typed code.
	 */
	public static function postcode( string $value ): bool {
		return 1 === preg_match( '/^(?!(\d)\1{3})[13-9]{4}[1346-9][013-9]{5}$/', self::digits( $value ) );
	}

	/**
	 * Whether a value looks like an economic code (کد اقتصادی): ten to
	 * fourteen digits, as old and new codes differ in length.
	 *
	 * @param string $value Typed code.
	 */
	public static function economic_code( string $value ): bool {
		return 1 === preg_match( '/^\d{10,14}$/', self::digits( $value ) );
	}

	/**
	 * Persian letters written one way: Arabic yeh and kaf become Persian,
	 * marks and extra spaces go. For comparing city names.
	 *
	 * @param string $value Text.
	 */
	public static function letters( string $value ): string {
		$value = strtr(
			$value,
			array(
				'ي'        => 'ی',
				'ى'        => 'ی',
				'ك'        => 'ک',
				'ة'        => 'ه',
				"\u{200c}" => ' ',
				'ـ'        => '',
			)
		);
		return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
	}
}
