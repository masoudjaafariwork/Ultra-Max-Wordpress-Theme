<?php
/**
 * The login, register and password pages (ACC-01, BLD-07).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Account;

use UltraMax\Core\Lib\UI\AuthCard;
use UltraMax\Core\Lib\UI\Button;
use UltraMax\Core\Lib\UI\Checkbox;
use UltraMax\Core\Lib\UI\Field;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Logo;

/**
 * Draws WooCommerce's login, register, lost password and new password
 * forms in our card. The field names, nonces and every WooCommerce hook
 * stay as they are, so WooCommerce still checks and signs the shopper in
 * and other plugins' fields keep showing. The login plugin (phase 5) adds
 * login by a code.
 */
final class Login {

	/**
	 * What a hook prints.
	 *
	 * @param string $name Hook name.
	 * @phpstan-param non-empty-string $name
	 */
	private static function hook( string $name ): Html {
		ob_start();
		do_action( $name ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- WooCommerce's hooks.
		return Html::wp( (string) ob_get_clean() );
	}

	/**
	 * A posted text value, to fill the form again after an error.
	 *
	 * @param string $key Field name.
	 */
	private static function posted( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Only shown back in the form; WooCommerce checks the nonce.
		return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	/**
	 * The card's title is the page's main heading on the account page,
	 * whose own title is hidden for shoppers who are not logged in.
	 *
	 * @return 'h1'|'h2'
	 */
	private static function heading(): string {
		return Account::bare_page() ? 'h1' : 'h2';
	}

	/**
	 * The store logo.
	 */
	private static function logo(): Html {
		$image = get_theme_mod( 'custom_logo', 0 );
		return Logo::render(
			array(
				'name'     => get_bloginfo( 'name' ),
				'href'     => home_url( '/' ),
				'image_id' => is_numeric( $image ) ? (int) $image : 0,
			)
		);
	}

	/**
	 * The account page's address.
	 */
	private static function account_url(): string {
		$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';
		return '' === $url ? home_url( '/' ) : $url;
	}

	/**
	 * The link back to the store.
	 *
	 * @return array{label: string, href: string}
	 */
	private static function back(): array {
		return array(
			'label' => __( 'Back to the store', 'ultra-max-core' ),
			'href'  => home_url( '/' ),
		);
	}

	/**
	 * A wide submit button.
	 *
	 * @param string $label Label.
	 * @param string $name  Button name WooCommerce looks for, or empty.
	 */
	private static function submit( string $label, string $name = '' ): Html {
		return Button::render(
			array(
				'children' => $label,
				'type'     => 'submit',
				'size'     => 'lg',
				'block'    => true,
				'attrs'    => '' === $name ? array() : array(
					'name'  => $name,
					'value' => $label,
				),
			)
		);
	}

	/**
	 * The login form.
	 */
	private static function login_form(): Html {
		return Html::trusted(
			sprintf(
				'<form class="woocommerce-form woocommerce-form-login grid gap-4" method="post" novalidate>%1$s%2$s%3$s%4$s%5$s%6$s%7$s%8$s</form>',
				self::hook( 'woocommerce_login_form_start' ),
				Field::render(
					array(
						'id'           => 'username',
						'name'         => 'username',
						'label'        => __( 'Username or email', 'ultra-max-core' ),
						'value'        => isset( $_POST['login'] ) ? self::posted( 'username' ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Which form was sent.
						'autocomplete' => 'username',
						'icon'         => 'user',
						'dir'          => 'auto',
						'required'     => true,
					)
				),
				Field::render(
					array(
						'id'           => 'password',
						'name'         => 'password',
						'type'         => 'password',
						'label'        => __( 'Password', 'ultra-max-core' ),
						'autocomplete' => 'current-password',
						'icon'         => 'lock',
						'dir'          => 'ltr',
						'reveal'       => true,
						'required'     => true,
						'link'         => array(
							'label' => __( 'Forgot your password?', 'ultra-max-core' ),
							'href'  => wp_lostpassword_url(),
						),
					)
				),
				self::hook( 'woocommerce_login_form' ),
				Checkbox::render(
					array(
						'id'    => 'rememberme',
						'name'  => 'rememberme',
						'value' => 'forever',
						'label' => __( 'Remember me', 'ultra-max-core' ),
					)
				),
				wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce', true, false ),
				self::submit( __( 'Log in', 'ultra-max-core' ), 'login' ),
				self::hook( 'woocommerce_login_form_end' )
			)
		);
	}

	/**
	 * The register form.
	 */
	private static function register_form(): Html {
		$fields = array();
		if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) {
			$fields[] = Field::render(
				array(
					'id'           => 'reg_username',
					'name'         => 'username',
					'label'        => __( 'Username', 'ultra-max-core' ),
					'value'        => isset( $_POST['register'] ) ? self::posted( 'username' ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Which form was sent.
					'autocomplete' => 'username',
					'icon'         => 'user',
					'dir'          => 'auto',
					'required'     => true,
				)
			);
		}
		$fields[] = Field::render(
			array(
				'id'           => 'reg_email',
				'name'         => 'email',
				'type'         => 'email',
				'label'        => __( 'Email address', 'ultra-max-core' ),
				'value'        => self::posted( 'email' ),
				'autocomplete' => 'email',
				'icon'         => 'mail',
				'dir'          => 'ltr',
				'required'     => true,
			)
		);
		if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) {
			$fields[] = Field::render(
				array(
					'id'           => 'reg_password',
					'name'         => 'password',
					'type'         => 'password',
					'label'        => __( 'Password', 'ultra-max-core' ),
					'autocomplete' => 'new-password',
					'icon'         => 'lock',
					'dir'          => 'ltr',
					'reveal'       => true,
					'required'     => true,
				)
			);
		} else {
			$fields[] = Html::trusted( '<p class="m-0">' . esc_html__( 'We will email you a link to set your password.', 'ultra-max-core' ) . '</p>' );
		}
		return Html::trusted(
			sprintf(
				'<form method="post" class="woocommerce-form woocommerce-form-register grid gap-4" %1$s>%2$s%3$s%4$s%5$s%6$s%7$s</form>',
				self::hook( 'woocommerce_register_form_tag' ),
				self::hook( 'woocommerce_register_form_start' ),
				Html::from( $fields ),
				self::hook( 'woocommerce_register_form' ),
				wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce', true, false ),
				self::submit( __( 'Create account', 'ultra-max-core' ), 'register' ),
				self::hook( 'woocommerce_register_form_end' )
			)
		);
	}

	/**
	 * The login page: login, and register in a second tab when the store
	 * lets shoppers register.
	 */
	public static function login(): Html {
		$register = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
		$props    = array(
			'title'   => $register ? __( 'Log in or create an account', 'ultra-max-core' ) : __( 'Log in to your account', 'ultra-max-core' ),
			'lead'    => $register ? __( 'Log in to your account, or create one in a minute.', 'ultra-max-core' ) : __( 'Welcome back! Enter your username or email and your password.', 'ultra-max-core' ),
			'heading' => self::heading(),
			'logo'    => self::logo(),
			'notices' => self::hook( 'woocommerce_before_customer_login_form' ),
			'back'    => self::back(),
		);
		if ( $register ) {
			$props['tabs'] = array(
				'id'       => 'um-auth-tabs',
				'label'    => __( 'Log in or create an account', 'ultra-max-core' ),
				'selected' => isset( $_POST['register'] ) ? 1 : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Which form was sent.
				'items'    => array(
					array(
						'label'   => __( 'Log in', 'ultra-max-core' ),
						'content' => self::login_form(),
					),
					array(
						'label'   => __( 'Create account', 'ultra-max-core' ),
						'content' => self::register_form(),
					),
				),
			);
		} else {
			$props['children'] = self::login_form();
		}
		$props['after'] = self::hook( 'woocommerce_after_customer_login_form' );
		return AuthCard::render( $props );
	}

	/**
	 * The lost password page.
	 */
	public static function lost_password(): Html {
		$notices = self::hook( 'woocommerce_before_lost_password_form' );
		$form    = sprintf(
			'<form method="post" class="woocommerce-ResetPassword grid gap-4">%1$s%2$s<input type="hidden" name="wc_reset_password" value="true">%3$s%4$s</form>',
			Field::render(
				array(
					'id'           => 'user_login',
					'name'         => 'user_login',
					'label'        => __( 'Username or email', 'ultra-max-core' ),
					'autocomplete' => 'username',
					'icon'         => 'user',
					'dir'          => 'auto',
					'required'     => true,
				)
			),
			self::hook( 'woocommerce_lostpassword_form' ),
			self::submit( __( 'Send the reset link', 'ultra-max-core' ) ),
			wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce', true, false )
		);
		return AuthCard::render(
			array(
				'title'    => __( 'Forgot your password?', 'ultra-max-core' ),
				'lead'     => (string) apply_filters( 'woocommerce_lost_password_message', __( 'Enter your username or email. We will email you a link to make a new password.', 'ultra-max-core' ) ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's filter.
				'heading'  => self::heading(),
				'icon'     => 'key',
				'notices'  => $notices,
				'children' => Html::trusted( $form ),
				'links'    => array(
					array(
						'text'  => __( 'Remembered it?', 'ultra-max-core' ),
						'label' => __( 'Back to login', 'ultra-max-core' ),
						'href'  => self::account_url(),
					),
				),
				'back'     => self::back(),
				'after'    => self::hook( 'woocommerce_after_lost_password_form' ),
			)
		);
	}

	/**
	 * The new password page, reached from the emailed link.
	 *
	 * @param mixed $args WooCommerce's template arguments: key and login.
	 */
	public static function reset_password( mixed $args ): Html {
		$args    = is_array( $args ) ? $args : array();
		$key     = isset( $args['key'] ) && is_string( $args['key'] ) ? $args['key'] : '';
		$login   = isset( $args['login'] ) && is_string( $args['login'] ) ? $args['login'] : '';
		$notices = self::hook( 'woocommerce_before_reset_password_form' );
		$form    = sprintf(
			'<form method="post" class="woocommerce-ResetPassword grid gap-4">%1$s%2$s<input type="hidden" name="reset_key" value="%3$s"><input type="hidden" name="reset_login" value="%4$s">%5$s<input type="hidden" name="wc_reset_password" value="true">%6$s%7$s</form>',
			Field::render(
				array(
					'id'           => 'password_1',
					'name'         => 'password_1',
					'type'         => 'password',
					'label'        => __( 'New password', 'ultra-max-core' ),
					'autocomplete' => 'new-password',
					'icon'         => 'lock',
					'dir'          => 'ltr',
					'reveal'       => true,
					'required'     => true,
				)
			),
			Field::render(
				array(
					'id'           => 'password_2',
					'name'         => 'password_2',
					'type'         => 'password',
					'label'        => __( 'Repeat the new password', 'ultra-max-core' ),
					'autocomplete' => 'new-password',
					'icon'         => 'lock',
					'dir'          => 'ltr',
					'reveal'       => true,
					'required'     => true,
				)
			),
			esc_attr( $key ),
			esc_attr( $login ),
			self::hook( 'woocommerce_resetpassword_form' ),
			self::submit( __( 'Save the password', 'ultra-max-core' ) ),
			wp_nonce_field( 'reset_password', 'woocommerce-reset-password-nonce', true, false )
		);
		return AuthCard::render(
			array(
				'title'    => __( 'Choose a new password', 'ultra-max-core' ),
				'lead'     => (string) apply_filters( 'woocommerce_reset_password_message', __( 'Choose a strong password that you have not used before.', 'ultra-max-core' ) ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's filter.
				'heading'  => self::heading(),
				'icon'     => 'lock',
				'notices'  => $notices,
				'children' => Html::trusted( $form ),
				'back'     => self::back(),
				'after'    => self::hook( 'woocommerce_after_reset_password_form' ),
			)
		);
	}

	/**
	 * The page after the reset link was sent.
	 */
	public static function link_sent(): Html {
		return AuthCard::render(
			array(
				'title'    => __( 'Check your email', 'ultra-max-core' ),
				'lead'     => (string) apply_filters( 'woocommerce_lost_password_confirmation_message', __( 'We sent a reset link to the email of your account. It may take a few minutes to arrive.', 'ultra-max-core' ) ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's filter.
				'heading'  => self::heading(),
				'icon'     => 'mail',
				'children' => array(
					self::hook( 'woocommerce_before_lost_password_confirmation_message' ),
					self::hook( 'woocommerce_after_lost_password_confirmation_message' ),
				),
				'links'    => array(
					array(
						'label' => __( 'Back to login', 'ultra-max-core' ),
						'href'  => self::account_url(),
					),
				),
				'back'     => self::back(),
			)
		);
	}
}
