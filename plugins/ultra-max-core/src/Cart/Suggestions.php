<?php
/**
 * Products that go with the cart (CART-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Cart;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Box;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\SectionHead;
use UltraMax\Core\Product\Related;

/**
 * The complementary products (WooCommerce's cross-sells) of everything in
 * the cart, without what is already in it. When the cart's products name
 * none, products of the same categories fill the row, best sellers first.
 * Only public products show (Related::visible).
 */
final class Suggestions {

	/**
	 * Products to suggest for the cart.
	 *
	 * @param int $count Most to return.
	 * @return list<\WC_Product>
	 */
	public static function products( int $count ): array {
		$cart     = WC()->cart;
		$in_cart  = array();
		$cross    = array();
		$category = array();
		foreach ( $cart->get_cart() as $item ) {
			$id = is_array( $item ) && is_numeric( $item['product_id'] ?? null ) ? (int) $item['product_id'] : 0;
			if ( $id <= 0 ) {
				continue;
			}
			$in_cart[] = $id;
			$product   = wc_get_product( $id );
			if ( $product instanceof \WC_Product ) {
				$cross    = array_merge( $cross, array_map( 'intval', $product->get_cross_sell_ids() ) );
				$category = array_merge( $category, array_map( 'intval', $product->get_category_ids() ) );
			}
		}
		$ids = array_values( array_diff( array_unique( $cross ), $in_cart ) );
		if ( count( $ids ) < $count && array() !== $category ) {
			$more = wc_get_products(
				array(
					'status'       => 'publish',
					'visibility'   => 'catalog',
					'category'     => array_values(
						array_filter(
							array_map(
								static function ( int $term ): string {
									$found = get_term( $term, 'product_cat' );
									return $found instanceof \WP_Term ? $found->slug : '';
								},
								array_unique( $category )
							)
						)
					),
					'exclude'      => array_merge( $in_cart, $ids ),
					'limit'        => $count,
					'orderby'      => 'popularity',
					'return'       => 'ids',
					'stock_status' => 'instock',
				)
			);
			$ids  = array_merge( $ids, array_map( 'intval', is_array( $more ) ? $more : array() ) );
		}
		return array_slice( Related::visible( array_values( $ids ) ), 0, $count );
	}

	/**
	 * The section of suggestions.
	 *
	 * @param int $count Most to show; the setting when 0.
	 */
	public static function section( int $count = 0 ): Html {
		if ( 0 === $count && ! Store::bool( 'cart_suggestions' ) ) {
			return Html::none();
		}
		if ( WC()->cart->is_empty() ) {
			return Html::none();
		}
		$title  = __( 'Goes with your cart', 'ultra-max-core' );
		$slider = Related::slider( self::products( $count > 0 ? $count : max( 4, Store::int( 'cart_suggestions_count' ) ) ), $title );
		if ( $slider->is_empty() ) {
			return Html::none();
		}
		return Box::render(
			array(
				'tag'      => 'section',
				'padding'  => 'md',
				'children' => array( SectionHead::render( array( 'title' => $title ) ), $slider ),
			)
		);
	}
}
