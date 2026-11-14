<?php
/**
 * Turns WooCommerce products into product card props.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Store;

use UltraMax\Core\Builder\Builder;
use UltraMax\Core\Builder\Layouts;
use UltraMax\Core\Builder\Renderer;
use UltraMax\Core\Lib\UI\Countdown;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\ProductCard;
use UltraMax\Core\Shop\Filters;
use UltraMax\Core\Shop\Terms;

/**
 * One place that reads a product for every card design, so a fix to a
 * price or a badge reaches every widget at once.
 *
 * @phpstan-import-type ProductCardProps from ProductCard
 */
final class Cards {

	/**
	 * Card designs a widget or a list can choose (PLP-07).
	 *
	 * @return array<string, string>
	 */
	public static function designs(): array {
		return array(
			'classic' => __( 'Classic with lines', 'ultra-max-core' ),
			'hover'   => __( 'Framed, second image on hover', 'ultra-max-core' ),
			'swatch'  => __( 'Colour swatches, for fashion', 'ultra-max-core' ),
			'list'    => __( 'Row with features', 'ultra-max-core' ),
			'mini'    => __( 'Small, supermarket', 'ultra-max-core' ),
			'deal'    => __( 'Offer with countdown', 'ultra-max-core' ),
		);
	}

	/**
	 * Card layouts designed with Elementor (PLP-08), as design choices.
	 *
	 * @return array<string, string>
	 */
	public static function layouts(): array {
		$out = array();
		foreach ( Layouts::index() as $entry ) {
			if ( 'product-card' === $entry['type'] ) {
				/* translators: %s: name of a card layout. */
				$out[ 'layout:' . $entry['id'] ] = sprintf( __( 'Designed card: %s', 'ultra-max-core' ), $entry['title'] );
			}
		}
		return $out;
	}

	/**
	 * Attribute taxonomies whose values are colours.
	 *
	 * @return list<string>
	 */
	public static function color_taxonomies(): array {
		return array_values( array_filter( Terms::swatch_taxonomies(), 'taxonomy_exists' ) );
	}

	/**
	 * Colours a product comes in, from its colour attribute.
	 *
	 * @param \WC_Product $product Product.
	 * @return list<array{color: string, label: string, slug: string}>
	 */
	public static function colors( \WC_Product $product ): array {
		$out = array();
		foreach ( self::color_taxonomies() as $taxonomy ) {
			$terms = wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'all' ) );
			foreach ( $terms as $term ) {
				if ( ! $term instanceof \WP_Term ) {
					continue;
				}
				$color = get_term_meta( $term->term_id, Filters::COLOR_META, true );
				if ( is_string( $color ) && 1 === preg_match( '/^#[0-9a-f]{3,8}$/i', $color ) ) {
					$out[] = array(
						'color' => $color,
						'label' => $term->name,
						'slug'  => $term->slug,
					);
				}
			}
			if ( array() !== $out ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Colour buttons with their pictures and sizes, for the swatch card.
	 *
	 * @param \WC_Product $product Product.
	 * @return array{swatches: list<array{color: string, label: string, src?: string, srcset?: string}>, sizes: list<array{label: string, out: bool}>}
	 */
	private static function variations( \WC_Product $product ): array {
		$colors = self::colors( $product );
		$out    = array(
			'swatches' => array(),
			'sizes'    => array(),
		);
		$images = array();
		$sizes  = array();
		if ( $product instanceof \WC_Product_Variable ) {
			$color_tax = self::color_taxonomies();
			foreach ( $product->get_children() as $child ) {
				$variation = wc_get_product( $child );
				if ( ! $variation instanceof \WC_Product_Variation || 'publish' !== $variation->get_status() ) {
					continue;
				}
				foreach ( $variation->get_attributes() as $taxonomy => $value ) {
					if ( ! is_string( $taxonomy ) || ! is_string( $value ) || '' === $value ) {
						continue;
					}
					if ( in_array( $taxonomy, $color_tax, true ) ) {
						if ( ! isset( $images[ $value ] ) && $variation->get_image_id() > 0 ) {
							$images[ $value ] = (int) $variation->get_image_id();
						}
						continue;
					}
					$sizes[ $taxonomy ][ $value ] = ( $sizes[ $taxonomy ][ $value ] ?? false ) || $variation->is_in_stock();
				}
			}
		}
		foreach ( $colors as $color ) {
			$swatch = array(
				'color' => $color['color'],
				'label' => $color['label'],
			);
			if ( isset( $images[ $color['slug'] ] ) ) {
				$src = wp_get_attachment_image_url( $images[ $color['slug'] ], 'woocommerce_thumbnail' );
				if ( is_string( $src ) ) {
					$swatch['src'] = $src;
					$set           = wp_get_attachment_image_srcset( $images[ $color['slug'] ], 'woocommerce_thumbnail' );
					if ( is_string( $set ) ) {
						$swatch['srcset'] = $set;
					}
				}
			}
			$out['swatches'][] = $swatch;
		}
		$first = reset( $sizes );
		$tax   = key( $sizes );
		if ( is_array( $first ) && is_string( $tax ) ) {
			foreach ( $first as $slug => $available ) {
				$term           = get_term_by( 'slug', (string) $slug, $tax );
				$out['sizes'][] = array(
					'label' => $term instanceof \WP_Term ? $term->name : (string) $slug,
					'out'   => ! $available,
				);
			}
		}
		return $out;
	}

	/**
	 * What a row shows: brand and reviews, and up to three features.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $brand   Brand name.
	 * @return array{line?: string, features?: list<string>}
	 */
	private static function details( \WC_Product $product, string $brand ): array {
		$line = array();
		if ( '' !== $brand ) {
			/* translators: %s: brand name. */
			$line[] = sprintf( __( 'Brand: %s', 'ultra-max-core' ), $brand );
		}
		$reviews = $product->get_review_count();
		if ( $reviews > 0 ) {
			/* translators: %s: number of reviews. */
			$line[] = sprintf( _n( '%s review', '%s reviews', $reviews, 'ultra-max-core' ), Format::number( $reviews ) );
		}
		$features = array();
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_visible() || in_array( $attribute->get_name(), self::color_taxonomies(), true ) ) {
				continue;
			}
			$values = $attribute->is_taxonomy()
				? wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) )
				: $attribute->get_options();
			$values = array_filter( array_map( 'strval', $values ) );
			if ( array() !== $values ) {
				$features[] = wc_attribute_label( $attribute->get_name(), $product ) . ': ' . implode( '، ', array_slice( $values, 0, 3 ) );
			}
			if ( count( $features ) >= 3 ) {
				break;
			}
		}
		$out = array();
		if ( array() !== $line ) {
			$out['line'] = implode( ' · ', $line );
		}
		if ( array() !== $features ) {
			$out['features'] = $features;
		}
		return $out;
	}

	/**
	 * The brand name of a product.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function brand( \WC_Product $product ): string {
		$brands = taxonomy_exists( 'product_brand' ) ? get_the_terms( $product->get_id(), 'product_brand' ) : false;
		return is_array( $brands ) && isset( $brands[0] ) && $brands[0] instanceof \WP_Term ? $brands[0]->name : '';
	}

	/**
	 * Props of a card.
	 *
	 * @param \WC_Product                                         $product  Product.
	 * @param 'classic'|'hover'|'deal'|'swatch'|'list'|'mini'     $variant  Design.
	 * @param array{priority?: bool, deal_end?: int, rows?: bool} $options  Options: first image loads first; end of a deal; whether the list can show rows (PLP-05).
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

		$brand = in_array( $variant, array( 'hover', 'list' ), true ) || ( $options['rows'] ?? false ) ? self::brand( $product ) : '';
		if ( in_array( $variant, array( 'classic', 'hover', 'list' ), true ) ) {
			$colors = self::colors( $product );
			if ( count( $colors ) > 1 ) {
				$props['colors'] = array_map(
					static fn ( array $color ): array => array(
						'color' => $color['color'],
						'label' => $color['label'],
					),
					$colors
				);
				/* translators: %s: number of colours. */
				$props['colors_label'] = sprintf( _n( 'Available in %s colour', 'Available in %s colours', count( $colors ), 'ultra-max-core' ), Format::number( count( $colors ) ) );
			}
		}
		if ( 'swatch' === $variant ) {
			$found = self::variations( $product );
			if ( array() !== $found['swatches'] ) {
				$props['swatches'] = $found['swatches'];
				/* translators: %s: number of colours. */
				$props['swatches_label'] = sprintf( _n( '%s colour', '%s colours', count( $found['swatches'] ), 'ultra-max-core' ), Format::number( count( $found['swatches'] ) ) );
			}
			if ( array() !== $found['sizes'] ) {
				$props['sizes'] = $found['sizes'];
			}
		}
		if ( 'list' === $variant || ( $options['rows'] ?? false ) ) {
			$props['details'] = self::details( $product, $brand );
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
			if ( '' !== $brand ) {
				$props['brand'] = $brand;
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
			$simple       = $product->is_type( 'simple' );
			$props['add'] = array(
				'href'  => $simple ? $product->add_to_cart_url() : (string) $product->get_permalink(),
				'label' => $simple ? __( 'Add to cart', 'ultra-max-core' ) : __( 'Choose options', 'ultra-max-core' ),
				'id'    => $product->get_id(),
				'ajax'  => $simple && $product->supports( 'ajax_add_to_cart' ),
			);
			if ( ! $simple ) {
				/**
				 * Filters whether "Choose options" of a card opens the quick view
				 * window instead of the product page (SAVE-05). The link still
				 * goes to the product page without JavaScript.
				 *
				 * @param bool        $quick   Whether to open the quick view.
				 * @param \WC_Product $product Product.
				 */
				$props['add']['quick'] = true === apply_filters( 'ultra_max_card_quick_buy', false, $product );
			}
		}

		/**
		 * Filters the round action buttons of a card, such as wishlist,
		 * compare and quick view. Each is markup made by a component.
		 *
		 * @param list<Html>  $actions Buttons.
		 * @param \WC_Product $product Product.
		 * @param string      $variant Card design.
		 */
		$actions = apply_filters( 'ultra_max_card_actions', array(), $product, $variant );
		if ( is_array( $actions ) ) {
			$actions = array_values( array_filter( $actions, static fn ( mixed $action ): bool => $action instanceof Html ) );
			if ( array() !== $actions ) {
				$props['actions'] = $actions;
			}
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
	 * Badges of a product: new, special sale, featured, free shipping, its
	 * own label and the store's rules (PDP-15).
	 *
	 * @param \WC_Product $product Product.
	 * @return list<array{label: string, tone: 'hot'|'new'|'brand'|'neutral', icon?: string}>
	 */
	public static function badges( \WC_Product $product ): array {
		return Badges::for( $product );
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
	 * A card in a design, or in a card layout designed with Elementor
	 * (`layout:<id>`, PLP-08).
	 *
	 * @param \WC_Product                                         $product Product.
	 * @param string                                              $design  Design, one of designs() or layouts().
	 * @param array{priority?: bool, deal_end?: int, rows?: bool} $options Options.
	 */
	public static function card( \WC_Product $product, string $design = 'classic', array $options = array() ): Html {
		if ( str_starts_with( $design, 'layout:' ) ) {
			$card = self::designed( $product, (int) substr( $design, 7 ) );
			if ( ! $card->is_empty() ) {
				return $card;
			}
			$design = 'classic';
		}
		$variant = in_array( $design, ProductCard::VARIANTS, true ) ? $design : 'classic';
		return ProductCard::render( self::props( $product, $variant, $options ) );
	}

	/**
	 * A card drawn by a card layout: the layout's widgets show this product.
	 *
	 * @param \WC_Product $product Product.
	 * @param int         $layout  Layout id.
	 */
	public static function designed( \WC_Product $product, int $layout ): Html {
		if ( 'product-card' !== Layouts::type( $layout ) || ! Builder::enabled() ) {
			return Html::none();
		}
		global $post;
		$saved = $post;
		$post  = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored right after the card is drawn.
		if ( $post instanceof \WP_Post ) {
			setup_postdata( $post );
		}
		CurrentProduct::push( $product );
		$content = Renderer::content( $layout );
		CurrentProduct::pop();
		$post = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring the loop's post.
		if ( $saved instanceof \WP_Post ) {
			setup_postdata( $saved );
		}
		return $content->is_empty() ? $content : ProductCard::shell( $content );
	}
}
