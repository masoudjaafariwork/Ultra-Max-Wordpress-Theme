<?php
/**
 * Structured data of the product page (PDP-26).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

/**
 * WooCommerce writes the Product data itself, with its offer, rating and
 * latest reviews, but only from hooks inside its own template. Our page
 * asks it for the same data, so the markup is exactly WooCommerce's and
 * SEO plugins that adjust or replace WooCommerce's data (Yoast WooCommerce
 * SEO, Rank Math) keep working without printing anything twice (INT-03).
 *
 * We add the brand when WooCommerce left it out, and the breadcrumb trail
 * our page shows, unless an SEO plugin prints its own breadcrumbs.
 */
final class Schema {

	/**
	 * Whether the data of this request is made.
	 *
	 * @var bool
	 */
	private static bool $done = false;

	/**
	 * Hooks the data.
	 */
	public static function register(): void {
		add_action( 'woocommerce_after_single_product', array( self::class, 'generate' ), 5 );
		add_filter( 'woocommerce_structured_data_product', array( self::class, 'brand' ), 10, 2 );
	}

	/**
	 * Whether an SEO plugin prints breadcrumb data of its own.
	 */
	public static function seo_plugin(): bool {
		return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
	}

	/**
	 * Asks WooCommerce for the product and breadcrumb data of our page.
	 */
	public static function generate(): void {
		if ( self::$done || ! Product::is_page() || post_password_required() || ! function_exists( 'WC' ) ) {
			return;
		}
		self::$done = true;
		global $product;
		if ( $product instanceof \WC_Product ) {
			WC()->structured_data->generate_product_data( $product );
		}
		if ( self::seo_plugin() || ! class_exists( '\WC_Breadcrumb' ) ) {
			return;
		}
		$trail = new \WC_Breadcrumb();
		$trail->add_crumb( _x( 'Home', 'breadcrumb', 'ultra-max-core' ), home_url( '/' ) );
		$trail->generate();
		WC()->structured_data->generate_breadcrumblist_data( $trail );
	}

	/**
	 * Adds the product's brand when WooCommerce's data has none.
	 *
	 * @param mixed $markup  Product data.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function brand( mixed $markup, mixed $product ): mixed {
		if ( ! is_array( $markup ) || ! $product instanceof \WC_Product || isset( $markup['brand'] ) || ! taxonomy_exists( 'product_brand' ) ) {
			return $markup;
		}
		$brands = get_the_terms( $product->get_id(), 'product_brand' );
		if ( is_array( $brands ) && isset( $brands[0] ) ) {
			$markup['brand'] = array(
				'@type' => 'Brand',
				'name'  => $brands[0]->name,
			);
		}
		return $markup;
	}
}
