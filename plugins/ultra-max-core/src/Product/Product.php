<?php
/**
 * The product page module (PDP-01 to PDP-26, BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Elementor\Data;
use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Builder\Resolver;
use UltraMax\Core\Shop\Terms;
use UltraMax\Core\Widgets\Widgets;

/**
 * Takes over the single product page. Off, it registers nothing:
 * WooCommerce's own product page shows, and no file or query of this module
 * runs.
 *
 * The page keeps WooCommerce's hooks around the product and inside the add
 * to cart form, so the theme frame, notices, view counting and other
 * plugins keep working; only the inside of the page is ours.
 */
final class Product {

	public const MODULE   = 'single-product';
	public const LOCATION = 'single-product';

	/**
	 * Whether the module is on and WooCommerce is active.
	 */
	public static function enabled(): bool {
		return Modules::enabled( self::MODULE ) && function_exists( 'wc_get_product' );
	}

	/**
	 * Registers the module.
	 */
	public static function register(): void {
		if ( ! Modules::enabled( self::MODULE ) ) {
			return;
		}
		add_filter( 'ultra_max_layout_types', array( self::class, 'types' ) );
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		Terms::register();
		Fields::register();
		Quantity::register();
		Cart::register();
		Related::register();
		Reviews::register();
		Questions::register();
		Delivery::register();
		Sales::register();
		SizeGuide::register();
		Tabs::register();
		Bundle::register();
		add_filter( 'template_include', array( self::class, 'template' ), 50 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'assets' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'drop_woocommerce_assets' ), 100 );
		add_action( 'template_redirect', array( self::class, 'hooks' ) );
		add_filter( 'body_class', array( self::class, 'body_class' ) );
	}

	/**
	 * Makes the single product layout type available (BLD-01, BLD-04).
	 *
	 * @param mixed $types Layout types.
	 * @return mixed
	 */
	public static function types( mixed $types ): mixed {
		if ( is_array( $types ) && isset( $types['single-product'] ) && is_array( $types['single-product'] ) ) {
			$types['single-product']['ready'] = true;
		}
		return $types;
	}

	/**
	 * Whether the request is a product page we draw.
	 */
	public static function is_page(): bool {
		return function_exists( 'is_product' ) && is_product() && ! is_feed();
	}

	/**
	 * Our product template.
	 *
	 * @param mixed $template Template file.
	 * @return mixed
	 */
	public static function template( mixed $template ): mixed {
		if ( ! self::is_page() ) {
			return $template;
		}
		return plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'templates/single-product.php';
	}

	/**
	 * Our page prints its own breadcrumbs and viewer, so WooCommerce's
	 * breadcrumbs and its PhotoSwipe markup in the footer go.
	 */
	public static function hooks(): void {
		if ( self::is_page() ) {
			remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		}
	}

	/**
	 * Queues the page's files in the head, and those of a designed layout.
	 */
	public static function assets(): void {
		if ( ! self::is_page() || post_password_required() ) {
			return;
		}
		foreach ( array( 'product', 'reviews', 'product-card', 'carousel', 'countdown' ) as $asset ) {
			Assets::enqueue_widget( $asset );
		}
		foreach ( Resolver::location( self::LOCATION ) as $pick ) {
			Data::enqueue( $pick['id'] );
			Widgets::enqueue_for( $pick['id'] );
		}
	}

	/**
	 * WooCommerce's own gallery, lightbox and variation scripts need jQuery
	 * and draw nothing on our page, so they do not load (PERF-04).
	 */
	public static function drop_woocommerce_assets(): void {
		if ( ! self::is_page() ) {
			return;
		}
		foreach ( array( 'wc-single-product', 'wc-add-to-cart-variation', 'zoom', 'flexslider', 'photoswipe', 'photoswipe-ui-default', 'wc-photoswipe', 'wc-photoswipe-ui-default', 'wc-zoom', 'wc-flexslider' ) as $handle ) {
			wp_dequeue_script( $handle );
		}
		foreach ( array( 'photoswipe', 'photoswipe-default-skin', 'woocommerce_prettyPhoto_css' ) as $handle ) {
			wp_dequeue_style( $handle );
		}
		// WooCommerce adds its viewer's markup to the footer while it loads its scripts.
		remove_action( 'wp_footer', 'woocommerce_photoswipe' );
	}

	/**
	 * A body class, so a theme or the store's own CSS can tell our page.
	 *
	 * @param mixed $classes Classes.
	 * @return mixed
	 */
	public static function body_class( mixed $classes ): mixed {
		if ( is_array( $classes ) && self::is_page() ) {
			$classes[] = 'um-product-page-body';
		}
		return $classes;
	}
}
