<?php
/**
 * The customer account module (ACC-01 to ACC-09, SHIP-03, BLD-07).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Account;

use UltraMax\Core\Builder\Resolver;
use UltraMax\Core\Elementor\Data;
use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Widgets\Widgets;

/**
 * Draws WooCommerce's account. WooCommerce keeps its addresses, login,
 * forms, notices and hooks; the frame (`myaccount/my-account.php`), the
 * dashboard and each page are ours. Off, the module registers nothing and
 * WooCommerce's own account shows.
 *
 * Order tracking for guests (ACC-09) is a module of its own.
 */
final class Account {

	public const MODULE   = 'account';
	public const TRACKING = 'order-tracking';
	public const LOCATION = 'account';

	public const ADDRESS_NONCE = 'ultra_max_address_remove';

	/**
	 * Whether the module is on and WooCommerce is active.
	 */
	public static function enabled(): bool {
		return Modules::enabled( self::MODULE ) && function_exists( 'wc_get_order' );
	}

	/**
	 * Registers the module and order tracking.
	 */
	public static function register(): void {
		if ( Modules::enabled( self::TRACKING ) && function_exists( 'wc_get_order' ) ) {
			Lookup::register();
		}
		if ( ! Modules::enabled( self::MODULE ) ) {
			return;
		}
		add_filter( 'ultra_max_layout_types', array( self::class, 'types' ) );
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}
		Endpoints::register();
		Details::register();
		Tracking::register();
		add_filter( 'wc_get_template', array( self::class, 'template' ), 20, 2 );
		add_action( 'wp', array( self::class, 'swap' ) );
		add_action( 'template_redirect', array( self::class, 'remove_address' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'assets' ), 20 );
		add_filter( 'body_class', array( self::class, 'body_class' ) );
		// WooCommerce's login, register and lost password forms on the account page take our form look.
		foreach ( array( 'customer_login_form', 'lost_password_form', 'reset_password_form' ) as $form ) {
			add_action( 'woocommerce_before_' . $form, array( self::class, 'open_form' ), 1 );
			add_action( 'woocommerce_after_' . $form, array( self::class, 'close_form' ), 99 );
		}
	}

	/**
	 * Opens our form look around a WooCommerce form.
	 */
	public static function open_form(): void {
		echo '<div class="um-scope um-account-form um-account-login">';
	}

	/**
	 * Closes it.
	 */
	public static function close_form(): void {
		echo '</div>';
	}

	/**
	 * Makes the account layout type available (BLD-01, BLD-07).
	 *
	 * @param mixed $types Layout types.
	 * @return mixed
	 */
	public static function types( mixed $types ): mixed {
		if ( is_array( $types ) && isset( $types['account'] ) && is_array( $types['account'] ) ) {
			$types['account']['ready'] = true;
		}
		return $types;
	}

	/**
	 * Our frame and dashboard instead of WooCommerce's templates.
	 *
	 * @param mixed $template Template file.
	 * @param mixed $name     Template name.
	 * @return mixed
	 */
	public static function template( mixed $template, mixed $name ): mixed {
		$ours = array(
			'myaccount/my-account.php' => 'account.php',
			'myaccount/dashboard.php'  => 'account-dashboard.php',
		);
		if ( is_string( $name ) && isset( $ours[ $name ] ) ) {
			return plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'templates/' . $ours[ $name ];
		}
		return $template;
	}

	/**
	 * Routes WooCommerce's endpoints to our pages.
	 */
	public static function swap(): void {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}
		remove_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation' );
		$pages = array(
			'orders'       => array( 'woocommerce_account_orders', array( self::class, 'orders' ) ),
			'view-order'   => array( 'woocommerce_account_view_order', array( self::class, 'view_order' ) ),
			'edit-address' => array( 'woocommerce_account_edit_address', array( self::class, 'edit_address' ) ),
			'edit-account' => array( 'woocommerce_account_edit_account', array( self::class, 'edit_account' ) ),
		);
		foreach ( $pages as $endpoint => list( $theirs, $ours ) ) {
			remove_action( 'woocommerce_account_' . $endpoint . '_endpoint', $theirs );
			add_action( 'woocommerce_account_' . $endpoint . '_endpoint', $ours );
		}
		add_action( 'woocommerce_account_' . Endpoints::LISTS . '_endpoint', array( self::class, 'lists' ) );
		add_action( 'woocommerce_account_' . Endpoints::ACTIVITY . '_endpoint', array( self::class, 'activity' ) );
		add_action( 'woocommerce_account_' . Endpoints::RECENT . '_endpoint', array( self::class, 'recent' ) );
	}

	/**
	 * Removes an address of the address book (ACC-06, CHK-02).
	 */
	public static function remove_address(): void {
		$nonce = isset( $_POST['um_address_nonce'] ) && is_string( $_POST['um_address_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['um_address_nonce'] ) ) : '';
		if ( '' === $nonce || ! is_user_logged_in() || ! wp_verify_nonce( $nonce, self::ADDRESS_NONCE ) || ! class_exists( \UltraMax\Core\Checkout\AddressBook::class ) ) {
			return;
		}
		$id = isset( $_POST['um_address_remove'] ) && is_string( $_POST['um_address_remove'] ) ? sanitize_text_field( wp_unslash( $_POST['um_address_remove'] ) ) : '';
		if ( '' !== $id ) {
			\UltraMax\Core\Checkout\AddressBook::remove( get_current_user_id(), $id );
			wc_add_notice( __( 'The address was removed.', 'ultra-max-core' ) );
		}
		wp_safe_redirect( wc_get_account_endpoint_url( 'edit-address' ) );
		exit;
	}

	/**
	 * The orders page.
	 *
	 * @param mixed $page Page number.
	 */
	public static function orders( mixed $page = 1 ): void {
		Page::orders( is_numeric( $page ) ? max( 1, (int) $page ) : 1 )->print();
	}

	/**
	 * One order.
	 *
	 * @param mixed $order_id Order id.
	 */
	public static function view_order( mixed $order_id ): void {
		Page::order( is_numeric( $order_id ) ? (int) $order_id : 0 )->print();
	}

	/**
	 * Addresses.
	 *
	 * @param mixed $type billing, shipping or empty.
	 */
	public static function edit_address( mixed $type = '' ): void {
		$type = is_string( $type ) ? sanitize_key( $type ) : '';
		Page::addresses( in_array( $type, array( 'billing', 'shipping' ), true ) ? $type : '' )->print();
	}

	/**
	 * Account details.
	 */
	public static function edit_account(): void {
		Page::details()->print();
	}

	/**
	 * My lists.
	 */
	public static function lists(): void {
		Page::lists()->print();
	}

	/**
	 * Reviews and questions.
	 *
	 * @param mixed $page Page number.
	 */
	public static function activity( mixed $page = 1 ): void {
		Page::activity( is_numeric( $page ) ? max( 1, (int) $page ) : 1 )->print();
	}

	/**
	 * Recent views.
	 */
	public static function recent(): void {
		Page::recent_page()->print();
	}

	/**
	 * Queues the account's files in the head, and those of a designed layout.
	 */
	public static function assets(): void {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}
		foreach ( array( 'account', 'product-card', 'carousel', 'product', 'reviews' ) as $asset ) {
			Assets::enqueue_widget( $asset );
		}
		foreach ( Resolver::location( self::LOCATION ) as $pick ) {
			Data::enqueue( $pick['id'] );
			Widgets::enqueue_for( $pick['id'] );
		}
	}

	/**
	 * A body class for the account.
	 *
	 * @param mixed $classes Classes.
	 * @return mixed
	 */
	public static function body_class( mixed $classes ): mixed {
		if ( is_array( $classes ) && function_exists( 'is_account_page' ) && is_account_page() && is_user_logged_in() ) {
			$classes[] = 'um-account-body';
		}
		return $classes;
	}
}
