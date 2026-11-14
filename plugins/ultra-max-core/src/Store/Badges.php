<?php
/**
 * Conditional product badges (PDP-15).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Store;

use UltraMax\Core\Lib\Settings\Store;

/**
 * The badges of a product, the same on cards and on the product page:
 *
 * - "New" for a set number of days after it is published;
 * - "Special sale" from a set discount;
 * - "Free shipping" when its price alone reaches the free shipping amount;
 * - "Special" for featured products;
 * - the product's own label;
 * - the store's own rules: a label for a category, a tag, products on
 *   sale, featured products or products with few left.
 *
 * @phpstan-type Badge array{label: string, tone: 'hot'|'new'|'brand'|'neutral', icon?: string}
 */
final class Badges {

	public const TONES = array( 'hot', 'new', 'brand', 'neutral' );

	/**
	 * The amount from which an order ships free, 0 when there is none. A
	 * filter lets the delivery part of the product page provide it.
	 */
	public static function free_shipping(): float {
		/**
		 * Filters the order amount from which shipping is free.
		 *
		 * @param float $amount Amount; 0 for none.
		 */
		$amount = apply_filters( 'ultra_max_free_shipping_amount', (float) Store::int( 'product_free_shipping' ) );
		return is_numeric( $amount ) ? max( 0.0, (float) $amount ) : 0.0;
	}

	/**
	 * Discount of a product in percent.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function discount( \WC_Product $product ): int {
		if ( ! $product->is_on_sale() ) {
			return 0;
		}
		if ( $product instanceof \WC_Product_Variable ) {
			$price   = (float) $product->get_variation_price( 'min', true );
			$regular = (float) $product->get_variation_regular_price( 'min', true );
		} else {
			$price   = (float) $product->get_price();
			$regular = (float) $product->get_regular_price();
		}
		return $regular > 0 && $price < $regular ? (int) round( ( $regular - $price ) / $regular * 100 ) : 0;
	}

	/**
	 * Whether one of the store's rules matches a product.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $rule    Rule: category, tag, sale, featured, low_stock.
	 * @param string      $value   Category or tag slugs, separated by commas.
	 */
	public static function matches( \WC_Product $product, string $rule, string $value ): bool {
		$slugs = array_values( array_filter( array_map( 'sanitize_title', explode( ',', $value ) ) ) );
		switch ( $rule ) {
			case 'category':
			case 'tag':
				$taxonomy = 'category' === $rule ? 'product_cat' : 'product_tag';
				$terms    = get_the_terms( $product->get_id(), $taxonomy );
				foreach ( is_array( $terms ) ? $terms : array() as $term ) {
					$own = array_merge( array( $term->term_id ), get_ancestors( $term->term_id, $taxonomy, 'taxonomy' ) );
					foreach ( $own as $id ) {
						$found = get_term( (int) $id, $taxonomy );
						if ( $found instanceof \WP_Term && in_array( $found->slug, $slugs, true ) ) {
							return true;
						}
					}
				}
				return false;
			case 'sale':
				return $product->is_on_sale();
			case 'featured':
				return $product->is_featured();
			case 'low_stock':
				$stock = $product->get_stock_quantity();
				return $product->managing_stock() && null !== $stock && $stock > 0 && $stock <= max( 1, Store::int( 'product_low_stock' ) );
			default:
				return false;
		}
	}

	/**
	 * The badges of a product.
	 *
	 * @param \WC_Product $product Product.
	 * @return list<Badge>
	 */
	public static function for( \WC_Product $product ): array {
		$badges  = array();
		$days    = Store::int( 'product_badge_new_days' );
		$created = $product->get_date_created();
		if ( $days > 0 && $created instanceof \WC_DateTime && $created->getTimestamp() > time() - $days * DAY_IN_SECONDS ) {
			$badges[] = array(
				'label' => __( 'New', 'ultra-max-core' ),
				'tone'  => 'new',
			);
		}
		$sale = Store::int( 'product_badge_sale' );
		if ( $sale > 0 && self::discount( $product ) >= $sale ) {
			$badges[] = array(
				'label' => __( 'Special sale', 'ultra-max-core' ),
				'tone'  => 'hot',
				'icon'  => 'percent',
			);
		}
		if ( $product->is_featured() ) {
			$badges[] = array(
				'label' => __( 'Special', 'ultra-max-core' ),
				'tone'  => 'hot',
				'icon'  => 'fire',
			);
		}
		$free = self::free_shipping();
		if ( Store::bool( 'product_badge_free' ) && $free > 0 && $product->needs_shipping() && (float) wc_get_price_to_display( $product ) >= $free ) {
			$badges[] = array(
				'label' => __( 'Free shipping', 'ultra-max-core' ),
				'tone'  => 'brand',
				'icon'  => 'truck',
			);
		}
		$label = $product->get_meta( 'um_badge' );
		if ( is_string( $label ) && '' !== trim( $label ) ) {
			$badges[] = array(
				'label' => trim( $label ),
				'tone'  => 'brand',
			);
		}
		foreach ( Store::rows( 'product_badge_rules' ) as $row ) {
			$text = is_string( $row['label'] ?? null ) ? trim( $row['label'] ) : '';
			$tone = is_string( $row['tone'] ?? null ) && in_array( $row['tone'], self::TONES, true ) ? $row['tone'] : 'neutral';
			$rule = is_string( $row['rule'] ?? null ) ? $row['rule'] : '';
			if ( '' !== $text && self::matches( $product, $rule, is_string( $row['value'] ?? null ) ? $row['value'] : '' ) ) {
				$badges[] = array(
					'label' => $text,
					'tone'  => $tone,
				);
			}
		}
		/**
		 * Filters the badges of a product on cards and on its page.
		 *
		 * @param list<Badge> $badges  Badges.
		 * @param \WC_Product $product Product.
		 */
		$filtered = apply_filters( 'ultra_max_product_badges', $badges, $product );
		$out      = array();
		foreach ( is_array( $filtered ) ? $filtered : array() as $badge ) {
			if ( is_array( $badge ) && is_string( $badge['label'] ?? null ) && in_array( $badge['tone'] ?? '', self::TONES, true ) ) {
				$clean = array(
					'label' => $badge['label'],
					'tone'  => $badge['tone'],
				);
				if ( is_string( $badge['icon'] ?? null ) ) {
					$clean['icon'] = $badge['icon'];
				}
				$out[] = $clean;
			}
		}
		return array_slice( $out, 0, 3 );
	}
}
