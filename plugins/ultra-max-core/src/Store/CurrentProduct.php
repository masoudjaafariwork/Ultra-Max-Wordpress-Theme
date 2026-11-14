<?php
/**
 * The product a product widget shows.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Store;

/**
 * Product widgets (title, price, add to cart, ...) show "the current
 * product": the card being drawn by a card layout (PLP-08), the product
 * of a single product page, or, while a layout is edited in Elementor, a
 * sample product so the design has something to show.
 */
final class CurrentProduct {

	/**
	 * Products being drawn, innermost last.
	 *
	 * @var list<\WC_Product>
	 */
	private static array $stack = array();

	/**
	 * Makes a product current while a card is drawn.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function push( \WC_Product $product ): void {
		self::$stack[] = $product;
	}

	/**
	 * Ends the innermost card.
	 */
	public static function pop(): void {
		array_pop( self::$stack );
	}

	/**
	 * The current product, or null.
	 */
	public static function get(): ?\WC_Product {
		$top = end( self::$stack );
		if ( $top instanceof \WC_Product ) {
			return $top;
		}
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}
		global $product;
		if ( $product instanceof \WC_Product ) {
			return $product;
		}
		if ( is_singular( 'product' ) ) {
			$found = wc_get_product( (int) get_queried_object_id() );
			return $found instanceof \WC_Product ? $found : null;
		}
		return self::designing() ? self::sample() : null;
	}

	/**
	 * Whether a layout is being designed or previewed: its own page, the
	 * Elementor preview, or the editor drawing a widget.
	 */
	public static function designing(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only decides whether to show a sample.
		$preview = isset( $_GET['elementor-preview'] );
		return is_singular( 'um_layout' ) || $preview || ( wp_doing_ajax() && current_user_can( 'edit_posts' ) );
	}

	/**
	 * A sample product for designing: the newest visible product.
	 */
	public static function sample(): ?\WC_Product {
		static $sample = null;
		if ( null === $sample ) {
			$ids    = wc_get_products(
				array(
					'status'     => 'publish',
					'visibility' => 'catalog',
					'limit'      => 1,
					'return'     => 'ids',
				)
			);
			$first  = is_array( $ids ) && isset( $ids[0] ) && is_numeric( $ids[0] ) ? wc_get_product( (int) $ids[0] ) : null;
			$sample = $first instanceof \WC_Product ? $first : false;
		}
		return $sample instanceof \WC_Product ? $sample : null;
	}
}
