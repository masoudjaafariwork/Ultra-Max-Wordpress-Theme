<?php
/**
 * The product page's own product fields.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

/**
 * Names and typed readers of the fields the product page adds to products
 * and variations. Products keep them as WooCommerce meta data, so they
 * travel with WooCommerce's own export and import.
 */
final class Meta {

	public const LATIN    = 'um_latin';
	public const WARRANTY = 'um_warranty';
	public const SHIP     = 'um_ship_days';
	public const VIDEOS   = 'um_videos';
	public const QTY_MIN  = 'um_qty_min';
	public const QTY_MAX  = 'um_qty_max';
	public const QTY_STEP = 'um_qty_step';
	public const BADGE    = 'um_badge';
	public const UNIT     = 'um_unit';
	public const TABS     = 'um_tabs';
	public const GUIDE    = 'um_size_guide';
	public const BUNDLE   = 'um_bundle_ids';
	public const DISCOUNT = 'um_bundle_discount';
	public const GALLERY  = 'um_gallery';

	/**
	 * A text field.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $key     Field.
	 */
	public static function text( \WC_Product $product, string $key ): string {
		$value = $product->get_meta( $key );
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * A whole number field, 0 when empty.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $key     Field.
	 */
	public static function int( \WC_Product $product, string $key ): int {
		$value = $product->get_meta( $key );
		return is_numeric( $value ) ? (int) $value : 0;
	}

	/**
	 * A list of ids, stored as an array or a comma separated text.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $key     Field.
	 * @return list<int>
	 */
	public static function ids( \WC_Product $product, string $key ): array {
		$value = $product->get_meta( $key );
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}
		$out = array();
		foreach ( is_array( $value ) ? $value : array() as $id ) {
			if ( is_numeric( $id ) && (int) $id > 0 ) {
				$out[] = (int) $id;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Rows of title and content, such as the product's own tabs.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $key     Field.
	 * @return list<array{title: string, content: string}>
	 */
	public static function rows( \WC_Product $product, string $key ): array {
		$value = $product->get_meta( $key );
		$out   = array();
		foreach ( is_array( $value ) ? $value : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$title   = is_string( $row['title'] ?? null ) ? trim( $row['title'] ) : '';
			$content = is_string( $row['content'] ?? null ) ? trim( $row['content'] ) : '';
			if ( '' !== $title && '' !== $content ) {
				$out[] = array(
					'title'   => $title,
					'content' => $content,
				);
			}
		}
		return $out;
	}

	/**
	 * The product whose fields a variation follows: its parent.
	 *
	 * @param \WC_Product $product Product or variation.
	 */
	public static function owner( \WC_Product $product ): \WC_Product {
		if ( $product instanceof \WC_Product_Variation ) {
			$parent = wc_get_product( $product->get_parent_id() );
			if ( $parent instanceof \WC_Product ) {
				return $parent;
			}
		}
		return $product;
	}
}
