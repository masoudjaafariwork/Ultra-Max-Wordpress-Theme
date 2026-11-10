<?php
/**
 * Turns WooCommerce products into product card props.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Store;

use UltraMax\Core\Lib\UI\Countdown;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\ProductCard;

/**
 * One place that reads a product for every card design, so a fix to a
 * price or a badge reaches every widget at once.
 *
 * @phpstan-import-type ProductCardProps from ProductCard
 */
final class Cards {

	/**
	 * Props of a card.
	 *
	 * @param \WC_Product                            $product  Product.
	 * @param 'classic'|'hover'|'deal'|'mini'        $variant  Design.
	 * @param array{priority?: bool, deal_end?: int} $options  Options: first image loads first; end of a deal.
	 * @return ProductCardProps
	 */
	public static function props( \WC_Product $product, string $variant = 'classic', array $options = array() ): array {
		static $shown = 0;
		// The first cards of a page are usually in view: they load at once, the very first with high priority (PERF-03).
		$priority = $options['priority'] ?? ( $shown < 2 );
		$first    = 0 === $shown;
		++$shown;
		$image = $product->get_image(
			'woocommerce_thumbnail',
			array(
				'loading'       => $priority ? 'eager' : 'lazy',
				'fetchpriority' => $priority && $first ? 'high' : 'auto',
				'decoding'      => 'async',
			)
		);
		$props = array(
			'title'    => wp_strip_all_tags( $product->get_name() ),
			'href'     => (string) $product->get_permalink(),
			'image'    => Html::wp( is_string( $image ) ? $image : '' ),
			'price'    => '',
			'currency' => Format::currency(),
			'variant'  => $variant,
		);

		$price   = $product->get_price();
		$regular = $product->get_regular_price();
		if ( $product->is_type( 'variable' ) && $product instanceof \WC_Product_Variable ) {
			$price   = $product->get_variation_price( 'min', true );
			$regular = $product->get_variation_regular_price( 'min', true );
		}
		if ( ! is_numeric( $price ) || (float) $price <= 0 ) {
			$props['call'] = __( 'Call for price', 'ultra-max-core' );
		} else {
			$props['price'] = Format::money( (float) $price );
			if ( $product->is_on_sale() && is_numeric( $regular ) && (float) $regular > (float) $price ) {
				$props['regular']  = Format::money( (float) $regular );
				$props['discount'] = Format::percent( (int) round( ( (float) $regular - (float) $price ) / (float) $regular * 100 ) );
			}
		}

		$rating = (float) $product->get_average_rating();
		if ( $rating > 0 ) {
			$props['rating'] = Format::digits( number_format( $rating, 1, Format::persian() ? '٫' : '.', '' ) );
			/* translators: %s: rating such as 4.5. */
			$props['rating_label'] = sprintf( __( 'Rated %s out of 5', 'ultra-max-core' ), $props['rating'] );
		}

		if ( 'hover' === $variant ) {
			$gallery = $product->get_gallery_image_ids();
			if ( array() !== $gallery ) {
				$alt                = wp_get_attachment_image(
					(int) $gallery[0],
					'woocommerce_thumbnail',
					false,
					array(
						'loading' => 'lazy',
						'alt'     => '',
					)
				);
				$props['image_alt'] = Html::wp( $alt );
			}
			$brands = taxonomy_exists( 'product_brand' ) ? get_the_terms( $product->get_id(), 'product_brand' ) : false;
			if ( is_array( $brands ) && isset( $brands[0] ) && $brands[0] instanceof \WP_Term ) {
				$props['brand'] = $brands[0]->name;
			}
		}

		$props['badges'] = self::badges( $product );
		$stock           = $product->get_stock_quantity();
		if ( ! $product->is_in_stock() ) {
			$props['note']      = __( 'Out of stock', 'ultra-max-core' );
			$props['note_tone'] = 'danger';
		} elseif ( null !== $stock && $stock > 0 && $stock <= 3 ) {
			/* translators: %s: number of items left. */
			$props['note']      = sprintf( __( 'Only %s left in stock', 'ultra-max-core' ), Format::number( $stock ) );
			$props['note_tone'] = 'danger';
		}

		$unit = $product->get_meta( 'um_unit' );
		if ( is_string( $unit ) && '' !== $unit ) {
			$props['unit'] = $unit;
		}

		if ( $product->is_purchasable() && $product->is_in_stock() ) {
			$props['add'] = array(
				'href'  => $product->add_to_cart_url(),
				'label' => __( 'Add to cart', 'ultra-max-core' ),
				'id'    => $product->get_id(),
				'ajax'  => $product->is_type( 'simple' ) && $product->supports( 'ajax_add_to_cart' ),
			);
		}

		if ( 'deal' === $variant ) {
			$end           = $options['deal_end'] ?? self::sale_end( $product );
			$sold          = (int) $product->get_total_sales();
			$left          = null === $stock ? 0 : max( 0, $stock );
			$share         = $sold + $left > 0 && null !== $stock ? (int) round( $sold / ( $sold + $left ) * 100 ) : min( 95, 40 + $sold );
			$props['deal'] = array(
				'countdown'  => Countdown::render(
					array(
						'end'    => $end,
						'label'  => __( 'Time left for this offer', 'ultra-max-core' ),
						'tone'   => 'deal',
						'size'   => 'sm',
						'digits' => Format::persian() ? 'fa' : 'en',
					)
				),
				'sold'       => $share,
				/* translators: %s: percent such as 60%. */
				'sold_label' => sprintf( __( '%s sold', 'ultra-max-core' ), Format::percent( $share ) ),
				/* translators: %s: percent such as 40%. */
				'left_label' => sprintf( __( '%s left', 'ultra-max-core' ), Format::percent( 100 - $share ) ),
			);
		}
		return $props;
	}

	/**
	 * Badges of a product: new, best seller, free shipping.
	 *
	 * @param \WC_Product $product Product.
	 * @return list<array{label: string, tone: 'hot'|'new'|'brand'|'neutral', icon?: string}>
	 */
	public static function badges( \WC_Product $product ): array {
		$badges  = array();
		$created = $product->get_date_created();
		if ( $created instanceof \WC_DateTime && $created->getTimestamp() > time() - 14 * DAY_IN_SECONDS ) {
			$badges[] = array(
				'label' => __( 'New', 'ultra-max-core' ),
				'tone'  => 'new',
			);
		}
		if ( $product->is_featured() ) {
			$badges[] = array(
				'label' => __( 'Special', 'ultra-max-core' ),
				'tone'  => 'hot',
				'icon'  => 'fire',
			);
		}
		$label = $product->get_meta( 'um_badge' );
		if ( is_string( $label ) && '' !== $label ) {
			$badges[] = array(
				'label' => $label,
				'tone'  => 'brand',
			);
		}
		return $badges;
	}

	/**
	 * When a product's sale ends: its scheduled end, or the end of today.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function sale_end( \WC_Product $product ): int {
		$to = $product->get_date_on_sale_to();
		if ( $to instanceof \WC_DateTime && $to->getTimestamp() > time() ) {
			return $to->getTimestamp();
		}
		return self::end_of_today();
	}

	/**
	 * Midnight at the end of today in the site's time zone.
	 */
	public static function end_of_today(): int {
		$now = new \DateTimeImmutable( 'now', wp_timezone() );
		return $now->setTime( 23, 59, 59 )->getTimestamp();
	}

	/**
	 * A card.
	 *
	 * @param \WC_Product                            $product Product.
	 * @param 'classic'|'hover'|'deal'|'mini'        $variant Design.
	 * @param array{priority?: bool, deal_end?: int} $options Options.
	 */
	public static function card( \WC_Product $product, string $variant = 'classic', array $options = array() ): Html {
		return ProductCard::render( self::props( $product, $variant, $options ) );
	}
}
