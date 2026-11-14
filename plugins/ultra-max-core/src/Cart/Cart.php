<?php
/**
 * The cart module (CART-01 to CART-05, CART-07, CART-10, BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Cart;

use UltraMax\Core\Builder\Resolver;
use UltraMax\Core\Elementor\Data;
use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Widgets\Widgets;

/**
 * Off, it registers nothing: WooCommerce's cart shows as the cart page has
 * it (blocks or shortcode), and no file or query of this module runs.
 *
 * On, the side cart, the "buy later" list, the saving, the free shipping
 * bar, the suggestions and the order limits work with both WooCommerce
 * carts. With the store's "Ultra Max design" (the default), the cart page
 * is ours (design/cart.html): WooCommerce still handles the page, its
 * forms and its notices, and only its cart template is replaced.
 */
final class Cart {

	public const MODULE   = 'cart';
	public const LOCATION = 'cart';

	/**
	 * Whether the module is on and WooCommerce is active.
	 */
	public static function enabled(): bool {
		return Modules::enabled( self::MODULE ) && function_exists( 'WC' );
	}

	/**
	 * Whether the cart and checkout pages use our design rather than
	 * WooCommerce's own (blocks or shortcode).
	 */
	public static function ours(): bool {
		return self::enabled() && 'woocommerce' !== Store::string( 'cart_design' );
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
		Actions::register();
		Later::register();
		Limits::register();
		SideCart::register();
		Blocks::register();
		add_filter( 'ultra_max_free_shipping_amount', array( \UltraMax\Core\Product\Delivery::class, 'free_amount' ) );
		add_filter( 'wc_get_template', array( self::class, 'template_part' ), 20, 2 );
		add_filter( 'template_include', array( self::class, 'template' ), 50 );
		add_action( 'template_redirect', array( self::class, 'hooks' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'assets' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'drop_woocommerce_assets' ), 100 );
		add_filter( 'body_class', array( self::class, 'body_class' ) );
		add_filter( 'ultra_max_account_lists', array( self::class, 'account_list' ) );
	}

	/**
	 * The "buy later" list as a tab of the account's lists page (CART-02, ACC-04).
	 *
	 * @param mixed $tabs Tabs, each with a label and its content.
	 * @return mixed
	 */
	public static function account_list( mixed $tabs ): mixed {
		if ( ! is_array( $tabs ) || ! Later::enabled() || null === WC()->cart ) {
			return $tabs;
		}
		Assets::enqueue_widget( 'cart' );
		Assets::enqueue_widget( 'product-card' );
		$tabs[] = array(
			'label'   => __( 'Buy later', 'ultra-max-core' ),
			'content' => Page::root( Page::part( 'later', array( 'always' => true ) ) ),
		);
		return $tabs;
	}

	/**
	 * Makes the cart layout type available (BLD-06).
	 *
	 * @param mixed $types Layout types.
	 * @return mixed
	 */
	public static function types( mixed $types ): mixed {
		if ( is_array( $types ) && isset( $types['cart'] ) && is_array( $types['cart'] ) ) {
			$types['cart']['ready'] = true;
		}
		return $types;
	}

	/**
	 * Whether the request is the cart page in our design.
	 */
	public static function is_page(): bool {
		return self::ours() && is_cart() && ! is_feed();
	}

	/**
	 * The cart and checkout pages print WooCommerce's own cart and
	 * checkout, whatever blocks the page holds, inside the theme's frame.
	 *
	 * @param mixed $template Template file.
	 * @return mixed
	 */
	public static function template( mixed $template ): mixed {
		if ( ! self::ours() || is_feed() || ! ( is_cart() || is_checkout() ) || post_password_required() ) {
			return $template;
		}
		return plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'templates/store-page.php';
	}

	/**
	 * Swaps WooCommerce's cart templates for ours.
	 *
	 * @param mixed $template Template file.
	 * @param mixed $name     Template name.
	 * @return mixed
	 */
	public static function template_part( mixed $template, mixed $name ): mixed {
		if ( ! self::ours() || ! in_array( $name, array( 'cart/cart.php', 'cart/cart-empty.php' ), true ) ) {
			return $template;
		}
		return plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'templates/woocommerce/cart.php';
	}

	/**
	 * Our page prints its own empty cart, and no breadcrumbs.
	 */
	public static function hooks(): void {
		if ( self::ours() && ( is_cart() || is_checkout() ) ) {
			remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		}
		if ( self::is_page() ) {
			remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
			remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
		}
	}

	/**
	 * Queues the cart page's files, and those of a designed cart.
	 */
	public static function assets(): void {
		if ( ! self::is_page() ) {
			return;
		}
		foreach ( array( 'cart', 'product-card', 'carousel' ) as $asset ) {
			Assets::enqueue_widget( $asset );
		}
		foreach ( Resolver::location( self::LOCATION ) as $pick ) {
			Data::enqueue( $pick['id'] );
			Widgets::enqueue_for( $pick['id'] );
		}
	}

	/**
	 * WooCommerce's jQuery cart script draws nothing on our page, and its
	 * general styles would restyle our cart and checkout (PERF-04).
	 */
	public static function drop_woocommerce_assets(): void {
		if ( self::is_page() ) {
			wp_dequeue_script( 'wc-cart' );
		}
		if ( self::ours() && ( is_cart() || is_checkout() ) ) {
			foreach ( array( 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen' ) as $handle ) {
				wp_dequeue_style( $handle );
			}
		}
	}

	/**
	 * A body class for our cart and checkout pages.
	 *
	 * @param mixed $classes Classes.
	 * @return mixed
	 */
	public static function body_class( mixed $classes ): mixed {
		if ( is_array( $classes ) && self::ours() && ( is_cart() || is_checkout() ) ) {
			$classes[] = is_cart() ? 'um-cart-page-body' : 'um-checkout-page-body';
		}
		return $classes;
	}
}
