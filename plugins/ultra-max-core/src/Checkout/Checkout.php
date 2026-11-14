<?php
/**
 * The checkout module (CHK-01 to CHK-06, CHK-08 to CHK-10, BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Checkout;

use UltraMax\Core\Builder\Resolver;
use UltraMax\Core\Cart\Cart;
use UltraMax\Core\Elementor\Data;
use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Widgets\Widgets;

/**
 * Off, it registers nothing: WooCommerce's checkout shows as the page has
 * it, and no file or query of this module runs.
 *
 * On, the Iranian address (provinces, cities, postal code and mobile
 * rules), the buyer type, the field editor, the address book and the
 * marketing consent work in both WooCommerce checkouts. With the store's
 * "Ultra Max design" (the default, set with the cart), the classic checkout
 * and the thank you page are ours (design/checkout.html): WooCommerce still
 * handles the page, the payment and every check, and only its form and
 * thank you templates are replaced.
 */
final class Checkout {

	public const MODULE   = 'checkout';
	public const LOCATION = 'checkout';
	public const THANKS   = 'thank-you';

	/**
	 * Whether the module is on and WooCommerce is active.
	 */
	public static function enabled(): bool {
		return Modules::enabled( self::MODULE ) && function_exists( 'WC' );
	}

	/**
	 * Whether the checkout uses our design. It follows the cart's design
	 * setting, as one page leads to the other.
	 */
	public static function ours(): bool {
		return self::enabled() && Cart::ours();
	}

	/**
	 * Registers the module.
	 */
	public static function register(): void {
		if ( ! Modules::enabled( self::MODULE ) ) {
			return;
		}
		add_filter( 'ultra_max_layout_types', array( self::class, 'types' ) );
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		Fields::register();
		AddressBook::register();
		Blocks::register();
		add_filter( 'woocommerce_shipping_methods', array( \UltraMax\Core\Product\Delivery::class, 'method_fields' ), 999 );
		add_filter( 'wc_get_template', array( self::class, 'template_part' ), 20, 3 );
		add_filter( 'woocommerce_update_order_review_fragments', array( Page::class, 'fragments' ) );
		add_action( 'template_redirect', array( self::class, 'hooks' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'assets' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'drop_woocommerce_assets' ), 100 );
	}

	/**
	 * Makes the checkout and thank you layout types available (BLD-06).
	 *
	 * @param mixed $types Layout types.
	 * @return mixed
	 */
	public static function types( mixed $types ): mixed {
		foreach ( array( 'checkout-form', 'thank-you' ) as $type ) {
			if ( is_array( $types ) && isset( $types[ $type ] ) && is_array( $types[ $type ] ) ) {
				$types[ $type ]['ready'] = true;
			}
		}
		return $types;
	}

	/**
	 * Whether the request is the checkout form in our design.
	 */
	public static function is_form(): bool {
		return self::ours() && is_checkout() && ! is_wc_endpoint_url() && ! is_feed();
	}

	/**
	 * Whether the request is the thank you page in our design.
	 */
	public static function is_thanks(): bool {
		return self::ours() && is_checkout() && is_wc_endpoint_url( 'order-received' );
	}

	/**
	 * Swaps WooCommerce's checkout form and thank you templates for ours.
	 *
	 * @param mixed $template Template file.
	 * @param mixed $name     Template name.
	 * @param mixed $args     Template arguments.
	 * @return mixed
	 */
	public static function template_part( mixed $template, mixed $name, mixed $args = array() ): mixed {
		if ( ! self::ours() ) {
			return $template;
		}
		if ( 'checkout/cart-errors.php' === $name ) {
			return plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'templates/woocommerce/cart-errors.php';
		}
		if ( 'checkout/form-checkout.php' === $name ) {
			return plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'templates/woocommerce/form-checkout.php';
		}
		// Only a known order gets our page; WooCommerce's own handles "order not found".
		if ( 'checkout/thankyou.php' === $name && is_array( $args ) && ( $args['order'] ?? null ) instanceof \WC_Order ) {
			return plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'templates/woocommerce/thankyou.php';
		}
		return $template;
	}

	/**
	 * Our form has its own discount code box.
	 */
	public static function hooks(): void {
		if ( self::is_form() ) {
			remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		}
	}

	/**
	 * Queues the checkout's files, and those of a designed checkout.
	 */
	public static function assets(): void {
		if ( ! self::is_form() && ! self::is_thanks() ) {
			return;
		}
		Assets::enqueue_widget( 'checkout' );
		Assets::enqueue_widget( 'cart' );
		foreach ( Resolver::location( self::is_thanks() ? self::THANKS : self::LOCATION ) as $pick ) {
			Data::enqueue( $pick['id'] );
			Widgets::enqueue_for( $pick['id'] );
		}
	}

	/**
	 * WooCommerce's select boxes enhancement would hide our province and
	 * city lists behind its own (PERF-04); the native lists stay.
	 */
	public static function drop_woocommerce_assets(): void {
		if ( self::is_form() ) {
			wp_dequeue_script( 'selectWoo' );
			wp_dequeue_style( 'select2' );
		}
	}
}
