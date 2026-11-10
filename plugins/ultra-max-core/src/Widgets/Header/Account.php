<?php
/**
 * Account widget (HDR-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

use UltraMax\Core\Lib\UI\HeaderAction;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * "Log in | Sign up" for visitors and "My account" for customers, linking
 * to WooCommerce's account page (or WordPress's login without WooCommerce).
 */
final class Account extends HeaderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'account';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Account', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-person';
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'account_section',
				'label'  => __( 'Account', 'ultra-max-core' ),
				'fields' => array(
					$this->look_field( 'button' ),
					array(
						'id'      => 'guest_text',
						'type'    => 'text',
						'label'   => __( 'Text for visitors', 'ultra-max-core' ),
						'default' => __( 'Log in | Sign up', 'ultra-max-core' ),
					),
					array(
						'id'      => 'user_text',
						'type'    => 'text',
						'label'   => __( 'Text for customers', 'ultra-max-core' ),
						'default' => __( 'My account', 'ultra-max-core' ),
					),
				),
			),
		);
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$logged = is_user_logged_in();
		$href   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : ( $logged ? admin_url( 'profile.php' ) : wp_login_url() );
		$text   = $logged ? $settings->string( 'user_text' ) : $settings->string( 'guest_text' );
		return HeaderAction::render(
			array(
				'label'   => $logged ? __( 'My account', 'ultra-max-core' ) : __( 'Log in or sign up', 'ultra-max-core' ),
				'text'    => $text,
				'icon'    => 'user',
				'href'    => $href,
				'variant' => self::look( $settings->string( 'look' ) ),
			)
		);
	}
}
