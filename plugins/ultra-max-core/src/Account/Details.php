<?php
/**
 * Extra account details: mobile, national code and birthday (ACC-07).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Account;

use UltraMax\Core\Lib\Foundation\Jalali;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Store\Format;
use UltraMax\Core\Checkout\Iran;

/**
 * The fields go into WooCommerce's own "Account details" form, so other
 * plugins' fields and WooCommerce's password change keep working. The
 * mobile number is WooCommerce's billing phone, so checkout fills it in.
 *
 * Changing a mobile number with a code sent to the new number arrives with
 * the login plugin (phase 5): it switches the field off through the
 * `ultra_max_account_mobile_editable` filter and offers its own form.
 */
final class Details {

	public const NATIONAL = 'um_national_code';
	public const BIRTHDAY = 'um_birthday';
	public const NONCE    = 'save_account_details';

	/**
	 * Hooks the fields.
	 */
	public static function register(): void {
		add_action( 'woocommerce_edit_account_form', array( self::class, 'fields' ) );
		add_action( 'woocommerce_save_account_details_errors', array( self::class, 'check' ) );
		add_action( 'woocommerce_save_account_details', array( self::class, 'save' ) );
	}

	/**
	 * Whether the shopper may change the mobile number here.
	 */
	public static function mobile_editable(): bool {
		/**
		 * Filters whether the mobile number is changed in the account details
		 * form. The login plugin turns it off and asks for a code instead.
		 *
		 * @param bool $editable Whether it is editable.
		 */
		return Store::bool( 'account_mobile' ) && true === apply_filters( 'ultra_max_account_mobile_editable', true );
	}

	/**
	 * A posted text field.
	 *
	 * @param string $key Field name.
	 */
	private static function posted( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce checked its nonce before its save hooks run.
		$value = isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		return $value;
	}

	/**
	 * The shopper's birthday as Jalali year, month and day, or nulls.
	 *
	 * @param int $user User id.
	 * @return array{0: int, 1: int, 2: int}|null
	 */
	public static function birthday( int $user ): ?array {
		$stored = get_user_meta( $user, self::BIRTHDAY, true );
		if ( ! is_string( $stored ) || 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $stored, $parts ) ) {
			return null;
		}
		return Jalali::from_gregorian( (int) $parts[1], (int) $parts[2], (int) $parts[3] );
	}

	/**
	 * The shopper's birthday as text.
	 *
	 * @param int $user User id.
	 */
	public static function birthday_text( int $user ): string {
		$stored = get_user_meta( $user, self::BIRTHDAY, true );
		if ( ! is_string( $stored ) || '' === $stored ) {
			return '';
		}
		$time = strtotime( $stored . ' 12:00:00' );
		return false === $time ? '' : Format::date( $time, 'j F Y' );
	}

	/**
	 * A select of numbers.
	 *
	 * @param string             $name    Field name.
	 * @param string             $label   Accessible name.
	 * @param array<int, string> $options Values and labels.
	 * @param int                $chosen  Chosen value.
	 */
	private static function select( string $name, string $label, array $options, int $chosen ): string {
		$out = sprintf( '<select name="%1$s" aria-label="%2$s"><option value="">%2$s</option>', esc_attr( $name ), esc_attr( $label ) );
		foreach ( $options as $value => $text ) {
			$out .= sprintf( '<option value="%1$d"%2$s>%3$s</option>', $value, selected( $chosen, $value, false ), esc_html( $text ) );
		}
		return $out . '</select>';
	}

	/**
	 * Prints the fields inside WooCommerce's form.
	 */
	public static function fields(): void {
		$user = get_current_user_id();
		echo '<div class="um-account-fields">';
		if ( Store::bool( 'account_mobile' ) ) {
			$mobile = get_user_meta( $user, 'billing_phone', true );
			printf(
				'<p class="woocommerce-form-row form-row"><label for="um_mobile">%1$s</label><input type="tel" class="woocommerce-Input input-text" name="um_mobile" id="um_mobile" dir="ltr" autocomplete="tel" inputmode="tel" value="%2$s"%3$s>%4$s</p>',
				esc_html__( 'Mobile number', 'ultra-max-core' ),
				esc_attr( is_string( $mobile ) ? $mobile : '' ),
				self::mobile_editable() ? '' : ' readonly',
				self::mobile_editable() ? '' : '<span class="description">' . esc_html__( 'Change it with a code sent to the new number.', 'ultra-max-core' ) . '</span>'
			);
		}
		if ( Store::bool( 'account_national_code' ) ) {
			$code = get_user_meta( $user, self::NATIONAL, true );
			printf(
				'<p class="woocommerce-form-row form-row"><label for="um_national_code">%1$s</label><input type="text" class="woocommerce-Input input-text" name="um_national_code" id="um_national_code" dir="ltr" inputmode="numeric" maxlength="10" value="%2$s"></p>',
				esc_html__( 'National code', 'ultra-max-core' ),
				esc_attr( is_string( $code ) ? $code : '' )
			);
		}
		if ( Store::bool( 'account_birthday' ) ) {
			$date   = self::birthday( $user );
			$now    = Jalali::from_gregorian( (int) gmdate( 'Y' ), (int) gmdate( 'n' ), (int) gmdate( 'j' ) );
			$days   = array();
			$years  = array();
			$months = array();
			for ( $day = 1; $day <= 31; $day++ ) {
				$days[ $day ] = Format::number( $day );
			}
			foreach ( Jalali::month_names() as $index => $name ) {
				$months[ (int) $index + 1 ] = $name;
			}
			for ( $year = $now[0]; $year >= $now[0] - 100; $year-- ) {
				$years[ $year ] = Format::digits( (string) $year );
			}
			printf(
				'<fieldset class="form-row-wide"><legend>%1$s</legend><div class="um-birthday-fields">%2$s%3$s%4$s</div></fieldset>',
				esc_html__( 'Birthday', 'ultra-max-core' ),
				self::select( 'um_birth_day', __( 'Day', 'ultra-max-core' ), $days, null === $date ? 0 : $date[2] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in select().
				self::select( 'um_birth_month', __( 'Month', 'ultra-max-core' ), $months, null === $date ? 0 : $date[1] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in select().
				self::select( 'um_birth_year', __( 'Year', 'ultra-max-core' ), $years, null === $date ? 0 : $date[0] ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in select().
			);
		}
		echo '</div>';
	}

	/**
	 * The birthday the shopper chose, as a Gregorian date, null when left
	 * empty, false when not a date.
	 *
	 * @return string|null|false
	 */
	private static function posted_birthday(): string|null|false {
		$day   = (int) self::posted( 'um_birth_day' );
		$month = (int) self::posted( 'um_birth_month' );
		$year  = (int) self::posted( 'um_birth_year' );
		if ( 0 === $day && 0 === $month && 0 === $year ) {
			return null;
		}
		if ( $day < 1 || $month < 1 || $month > 12 || $year < 1200 || $day > Jalali::days_in_month( $year, $month ) ) {
			return false;
		}
		$gregorian = Jalali::to_gregorian( $year, $month, $day );
		return sprintf( '%04d-%02d-%02d', $gregorian[0], $gregorian[1], $gregorian[2] );
	}

	/**
	 * Checks the fields before WooCommerce saves.
	 *
	 * @param mixed $errors Errors.
	 */
	public static function check( mixed $errors ): void {
		if ( ! $errors instanceof \WP_Error ) {
			return;
		}
		if ( self::mobile_editable() ) {
			$mobile = self::posted( 'um_mobile' );
			if ( '' !== $mobile && '' === ( Iran::mobile( $mobile ) ?? '' ) ) {
				$errors->add( 'um_mobile', __( 'Enter a mobile number such as 09121234567.', 'ultra-max-core' ) );
			}
		}
		if ( Store::bool( 'account_national_code' ) ) {
			$code = self::posted( 'um_national_code' );
			if ( '' !== $code && ! Iran::national_code( $code ) ) {
				$errors->add( 'um_national_code', __( 'This national code is not valid.', 'ultra-max-core' ) );
			}
		}
		if ( Store::bool( 'account_birthday' ) && false === self::posted_birthday() ) {
			$errors->add( 'um_birthday', __( 'Choose the day, month and year of your birthday.', 'ultra-max-core' ) );
		}
	}

	/**
	 * Saves the fields after WooCommerce saved its own.
	 *
	 * @param mixed $user_id User id.
	 */
	public static function save( mixed $user_id ): void {
		$user = is_numeric( $user_id ) ? (int) $user_id : 0;
		if ( $user <= 0 ) {
			return;
		}
		if ( self::mobile_editable() ) {
			update_user_meta( $user, 'billing_phone', ( Iran::mobile( self::posted( 'um_mobile' ) ) ?? '' ) );
		}
		if ( Store::bool( 'account_national_code' ) ) {
			$code = Iran::digits( self::posted( 'um_national_code' ) );
			if ( '' === $code ) {
				delete_user_meta( $user, self::NATIONAL );
			} else {
				update_user_meta( $user, self::NATIONAL, $code );
			}
		}
		if ( Store::bool( 'account_birthday' ) ) {
			$birthday = self::posted_birthday();
			if ( null === $birthday ) {
				delete_user_meta( $user, self::BIRTHDAY );
			} elseif ( false !== $birthday ) {
				update_user_meta( $user, self::BIRTHDAY, $birthday );
			}
		}
	}
}
