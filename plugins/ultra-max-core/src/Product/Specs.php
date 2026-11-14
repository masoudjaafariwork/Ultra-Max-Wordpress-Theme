<?php
/**
 * Key features and the specification table (PDP-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Shop\Terms;

/**
 * Specifications are WooCommerce's product attributes. Each global
 * attribute can name its group ("Display", "Camera") and be marked as a key
 * feature on its own edit screen (Products, Attributes); attributes without
 * a group, the product's own attributes, and the weight and size go into a
 * "General" group first.
 */
final class Specs {

	/**
	 * The values of one attribute of a product as text.
	 *
	 * @param \WC_Product           $product   Product.
	 * @param \WC_Product_Attribute $attribute Attribute.
	 */
	private static function values( \WC_Product $product, \WC_Product_Attribute $attribute ): string {
		$values = $attribute->is_taxonomy()
			? wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) )
			: $attribute->get_options();
		$values = array_values( array_filter( array_map( static fn ( mixed $value ): string => is_scalar( $value ) ? trim( (string) $value ) : '', $values ) ) );
		return implode( is_rtl() ? '، ' : ', ', $values );
	}

	/**
	 * The visible attributes of a product with their names and values.
	 *
	 * @param \WC_Product $product Product.
	 * @return list<array{name: string, label: string, value: string, group: string, key: bool}>
	 */
	public static function rows( \WC_Product $product ): array {
		$specs = Terms::attribute_specs();
		$out   = array();
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_visible() ) {
				continue;
			}
			$value = self::values( $product, $attribute );
			if ( '' === $value ) {
				continue;
			}
			$name  = $attribute->get_name();
			$out[] = array(
				'name'  => $name,
				'label' => wc_attribute_label( $name, $product ),
				'value' => $value,
				'group' => $attribute->is_taxonomy() ? ( $specs[ $name ]['group'] ?? '' ) : '',
				'key'   => $attribute->is_taxonomy() && ( $specs[ $name ]['key'] ?? false ),
			);
		}
		return $out;
	}

	/**
	 * Every specification in groups, "General" first.
	 *
	 * @param \WC_Product $product Product.
	 * @return list<array{title: string, rows: list<array{label: string, value: string}>}>
	 */
	public static function groups( \WC_Product $product ): array {
		$general = __( 'General', 'ultra-max-core' );
		$groups  = array( $general => array() );
		if ( Store::bool( 'product_dimensions' ) ) {
			if ( $product->has_weight() ) {
				$groups[ $general ][] = array(
					'label' => __( 'Weight', 'ultra-max-core' ),
					'value' => wc_format_weight( (float) $product->get_weight() ),
				);
			}
			$dimensions = $product->get_dimensions( false );
			if ( $product->has_dimensions() && is_array( $dimensions ) ) {
				$groups[ $general ][] = array(
					'label' => __( 'Dimensions', 'ultra-max-core' ),
					'value' => wp_strip_all_tags( wc_format_dimensions( $dimensions ) ),
				);
			}
		}
		foreach ( self::rows( $product ) as $row ) {
			$title              = '' === $row['group'] ? $general : $row['group'];
			$groups[ $title ][] = array(
				'label' => $row['label'],
				'value' => $row['value'],
			);
		}
		$out = array();
		foreach ( $groups as $title => $rows ) {
			if ( array() !== $rows ) {
				$out[] = array(
					'title' => (string) $title,
					'rows'  => $rows,
				);
			}
		}
		return $out;
	}

	/**
	 * The key features: attributes marked as key, or the first ones.
	 *
	 * @param \WC_Product $product Product.
	 * @param int         $count   Most to show.
	 * @return list<array{label: string, value: string}>
	 */
	public static function key( \WC_Product $product, int $count ): array {
		if ( $count <= 0 ) {
			return array();
		}
		$rows   = self::rows( $product );
		$marked = array_values( array_filter( $rows, static fn ( array $row ): bool => $row['key'] ) );
		$colors = Terms::swatch_taxonomies();
		$pick   = array() !== $marked ? $marked : array_values( array_filter( $rows, static fn ( array $row ): bool => ! in_array( $row['name'], $colors, true ) ) );
		return array_map(
			static fn ( array $row ): array => array(
				'label' => $row['label'],
				'value' => $row['value'],
			),
			array_slice( $pick, 0, $count )
		);
	}
}
