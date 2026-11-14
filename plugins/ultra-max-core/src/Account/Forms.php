<?php
/**
 * Login, register and lost password forms as widgets (BLD-07).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Account;

use UltraMax\Core\Lib\UI\Alert;
use UltraMax\Core\Lib\UI\Html;

/**
 * WooCommerce's own forms, so its checks, notices, redirects and other
 * plugins' fields keep working, inside our form look. The login plugin
 * (phase 5) replaces them with login by a code.
 */
final class Forms {

	/**
	 * A note for a shopper who is already logged in.
	 */
	private static function logged_in(): Html {
		$user = wp_get_current_user();
		return Alert::render(
			array(
				'tone'     => 'info',
				'children' => Html::trusted(
					sprintf(
						'<span>%1$s <a href="%2$s">%3$s</a></span>',
						/* translators: %s: name of the shopper. */
						esc_html( sprintf( __( 'You are logged in as %s.', 'ultra-max-core' ), $user->display_name ) ),
						esc_url( wc_get_page_permalink( 'myaccount' ) ),
						esc_html__( 'Go to your account', 'ultra-max-core' )
					)
				),
			)
		);
	}

	/**
	 * The login form, and the register form when the store allows it.
	 */
	public static function login(): Html {
		if ( ! function_exists( 'wc_get_template_html' ) ) {
			return Html::none();
		}
		if ( is_user_logged_in() ) {
			return self::logged_in();
		}
		return Html::wp( sprintf( '<div class="um-account-form">%s</div>', wc_get_template_html( 'myaccount/form-login.php' ) ) );
	}

	/**
	 * The lost password form.
	 */
	public static function lost_password(): Html {
		if ( ! function_exists( 'wc_get_template_html' ) ) {
			return Html::none();
		}
		if ( is_user_logged_in() ) {
			return self::logged_in();
		}
		return Html::wp( sprintf( '<div class="um-account-form">%s</div>', wc_get_template_html( 'myaccount/form-lost-password.php' ) ) );
	}
}
