<?php
/**
 * What the purchase form of a product page shows (PDP-04, PDP-05, PDP-21, PDP-25).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Image;
use UltraMax\Core\Lib\UI\ProductPrice;
use UltraMax\Core\Lib\UI\PurchaseForm;
use UltraMax\Core\Lib\UI\VariationPicker;
use UltraMax\Core\Shop\Filters;
use UltraMax\Core\Shop\Terms;
use UltraMax\Core\Store\Format;

/**
 * Reads a product for the picker, the form and the price. Variations are
 * listed with everything the page changes when one is chosen, so choosing
 * needs no request to the server.
 *
 * @phpstan-import-type PickerAttribute from VariationPicker
 * @phpstan-import-type PurchaseVariation from PurchaseForm
 * @phpstan-import-type PurchaseFormProps from PurchaseForm
 * @phpstan-import-type ProductPriceProps from ProductPrice
 */
final class Purchase {

	/**
	 * Id of a product's purchase form.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function form_id( \WC_Product $product ): string {
		return 'um-purchase-' . $product->get_id();
	}

	/**
	 * The address the form posts to, as WooCommerce's own form does.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function action( \WC_Product $product ): string {
		/** This filter is documented in WooCommerce's single-product/add-to-cart templates. */
		$action = apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
		return is_string( $action ) ? $action : (string) $product->get_permalink();
	}

	/**
	 * The choices of a variable product.
	 *
	 * @param \WC_Product_Variable $product Product.
	 * @return list<PickerAttribute>
	 */
	public static function attributes( \WC_Product_Variable $product ): array {
		$colors   = Terms::swatch_taxonomies();
		$pictures = Terms::image_taxonomies();
		$display  = 'select' === Store::string( 'product_picker' ) ? 'select' : 'button';
		$out      = array();
		$images   = self::value_images( $product );
		foreach ( $product->get_variation_attributes() as $name => $values ) {
			$name     = (string) $name;
			$field    = 'attribute_' . sanitize_title( $name );
			$values   = array_map( static fn ( mixed $value ): string => is_scalar( $value ) ? (string) $value : '', is_array( $values ) ? $values : array() );
			$options  = array();
			$taxonomy = taxonomy_exists( $name );
			$kind     = $taxonomy && in_array( $name, $colors, true ) ? 'color' : ( $taxonomy && in_array( $name, $pictures, true ) ? 'image' : $display );
			if ( $taxonomy ) {
				foreach ( wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'all' ) ) as $term ) {
					if ( ! $term instanceof \WP_Term || ! in_array( $term->slug, $values, true ) ) {
						continue;
					}
					$option = array(
						'value' => $term->slug,
						'label' => $term->name,
					);
					if ( 'color' === $kind ) {
						$color = get_term_meta( $term->term_id, Filters::COLOR_META, true );
						if ( is_string( $color ) && 1 === preg_match( '/^#[0-9a-f]{3,8}$/i', $color ) ) {
							$option['color'] = $color;
						}
					} elseif ( 'image' === $kind ) {
						$image = get_term_meta( $term->term_id, Terms::IMAGE_META, true );
						$image = is_numeric( $image ) && (int) $image > 0 ? (int) $image : ( $images[ $field ][ $term->slug ] ?? 0 );
						if ( $image > 0 ) {
							$option['image'] = Image::render(
								array(
									'id'   => $image,
									'size' => 'woocommerce_gallery_thumbnail',
									'alt'  => '',
								)
							);
						}
					}
					$options[] = $option;
				}
			} else {
				foreach ( $values as $value ) {
					$options[] = array(
						'value' => $value,
						'label' => $value,
					);
				}
			}
			if ( array() === $options ) {
				continue;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only preselects a value from a shared link, as WooCommerce does; wc_clean() sanitizes.
			$requested = isset( $_REQUEST[ $field ] ) && is_string( $_REQUEST[ $field ] ) ? wc_clean( wp_unslash( $_REQUEST[ $field ] ) ) : '';
			$selected  = is_string( $requested ) && '' !== $requested ? $requested : $product->get_variation_default_attribute( $name );
			$out[]     = array(
				'name'     => $field,
				'label'    => wc_attribute_label( $name, $product ),
				'display'  => $kind,
				'options'  => self::mark_out( $product, $field, $options ),
				'selected' => is_string( $selected ) ? $selected : '',
				'choose'   => __( 'Choose one', 'ultra-max-core' ),
			);
		}
		return $out;
	}

	/**
	 * The picture of each value from the first variation that has one, for
	 * attributes shown as pictures.
	 *
	 * @param \WC_Product_Variable $product Product.
	 * @return array<string, array<string, int>>
	 */
	private static function value_images( \WC_Product_Variable $product ): array {
		$out = array();
		foreach ( self::variations( $product ) as $variation ) {
			$image = (int) $variation->get_image_id( 'edit' );
			if ( $image <= 0 ) {
				continue;
			}
			foreach ( $variation->get_variation_attributes() as $field => $value ) {
				if ( is_string( $value ) && '' !== $value && ! isset( $out[ (string) $field ][ $value ] ) ) {
					$out[ (string) $field ][ $value ] = $image;
				}
			}
		}
		return $out;
	}

	/**
	 * Marks values that no variation in stock has, when the product has only
	 * this one choice; with more choices the behaviour marks them as the
	 * shopper chooses.
	 *
	 * @param \WC_Product_Variable                                                    $product Product.
	 * @param string                                                                  $field   Field name.
	 * @param list<array{value: string, label: string, color?: string, image?: Html}> $options Values.
	 * @return list<array{value: string, label: string, color?: string, image?: Html, out?: bool}>
	 */
	private static function mark_out( \WC_Product_Variable $product, string $field, array $options ): array {
		if ( count( $product->get_variation_attributes() ) !== 1 ) {
			return $options;
		}
		$open = array();
		foreach ( self::variations( $product ) as $variation ) {
			$value = $variation->get_variation_attributes()[ $field ] ?? '';
			if ( $variation->is_in_stock() ) {
				$open[ '' === $value ? '*' : $value ] = true;
			}
		}
		foreach ( $options as $index => $option ) {
			if ( ! isset( $open['*'] ) && ! isset( $open[ $option['value'] ] ) ) {
				$options[ $index ]['out'] = true;
			}
		}
		return $options;
	}

	/**
	 * The variations a shopper can see, once per request.
	 *
	 * @param \WC_Product_Variable $product Product.
	 * @return list<\WC_Product_Variation>
	 */
	public static function variations( \WC_Product_Variable $product ): array {
		static $cache = array();
		$id           = $product->get_id();
		if ( ! isset( $cache[ $id ] ) ) {
			$cache[ $id ] = array();
			foreach ( $product->get_available_variations( 'objects' ) as $variation ) {
				if ( $variation instanceof \WC_Product_Variation ) {
					$cache[ $id ][] = $variation;
				}
			}
		}
		return $cache[ $id ];
	}

	/**
	 * Price texts of a product or variation.
	 *
	 * @param \WC_Product $product Product.
	 * @return array{price: string, regular: string, discount: string, call: string}
	 */
	public static function price_texts( \WC_Product $product ): array {
		$price   = wc_get_price_to_display( $product );
		$regular = wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
		if ( '' === $product->get_price() || $price <= 0 ) {
			return array(
				'price'    => '',
				'regular'  => '',
				'discount' => '',
				'call'     => __( 'Call for price', 'ultra-max-core' ),
			);
		}
		$sale = $product->is_on_sale() && $regular > $price;
		return array(
			'price'    => Format::money( $price ),
			'regular'  => $sale ? Format::money( $regular ) : '',
			'discount' => $sale ? Format::percent( (int) round( ( $regular - $price ) / $regular * 100 ) ) : '',
			'call'     => '',
		);
	}

	/**
	 * The variation chosen by default, if the defaults make one.
	 *
	 * @param \WC_Product_Variable $product Product.
	 */
	public static function default_variation( \WC_Product_Variable $product ): ?\WC_Product_Variation {
		$chosen = array();
		foreach ( self::attributes( $product ) as $attribute ) {
			if ( '' === ( $attribute['selected'] ?? '' ) ) {
				return null;
			}
			$chosen[ $attribute['name'] ] = $attribute['selected'] ?? '';
		}
		foreach ( self::variations( $product ) as $variation ) {
			$fits = true;
			foreach ( $variation->get_variation_attributes() as $field => $value ) {
				$wanted = $chosen[ (string) $field ] ?? '';
				if ( '' !== $value && $wanted !== $value ) {
					$fits = false;
					break;
				}
			}
			if ( $fits ) {
				return $variation;
			}
		}
		return null;
	}

	/**
	 * Props of the price of a product page.
	 *
	 * @param \WC_Product    $product Product.
	 * @param 'lg'|'md'|'sm' $size Size.
	 * @return ProductPriceProps
	 */
	public static function price( \WC_Product $product, string $size = 'lg' ): array {
		$shown  = $product;
		$prefix = '';
		if ( $product instanceof \WC_Product_Variable ) {
			$default = self::default_variation( $product );
			if ( null !== $default ) {
				$shown = $default;
			} else {
				$prices = $product->get_variation_prices( true );
				$cheap  = array_keys( $prices['price'] ?? array() );
				$first  = isset( $cheap[0] ) ? wc_get_product( (int) $cheap[0] ) : null;
				if ( $first instanceof \WC_Product ) {
					$shown = $first;
				}
				if ( count( array_unique( $prices['price'] ?? array() ) ) > 1 ) {
					$prefix = __( 'From', 'ultra-max-core' );
				}
			}
		}
		$texts = self::price_texts( $shown );
		$props = array(
			'product'  => $product->get_id(),
			'price'    => $texts['price'],
			'currency' => Format::currency(),
			'size'     => $size,
		);
		foreach ( array( 'regular', 'discount', 'call' ) as $key ) {
			if ( '' !== $texts[ $key ] ) {
				$props[ $key ] = $texts[ $key ];
			}
		}
		if ( '' !== $prefix ) {
			$props['prefix'] = $prefix;
		}
		return $props;
	}

	/**
	 * The stock line of a product or variation, and how it looks.
	 *
	 * @param \WC_Product $product Product.
	 * @return array{text: string, tone: 'danger'|'success'|'muted'}
	 */
	public static function stock( \WC_Product $product ): array {
		if ( $product instanceof \WC_Product_Variable ) {
			$product = self::default_variation( $product ) ?? $product;
			if ( $product instanceof \WC_Product_Variable ) {
				return array(
					'text' => $product->is_in_stock() ? '' : __( 'Out of stock', 'ultra-max-core' ),
					'tone' => 'danger',
				);
			}
		}
		if ( ! $product->is_in_stock() ) {
			return array(
				'text' => __( 'Out of stock', 'ultra-max-core' ),
				'tone' => 'danger',
			);
		}
		$stock = $product->get_stock_quantity();
		$low   = max( 0, Store::int( 'product_low_stock' ) );
		if ( $product->managing_stock() && null !== $stock && $stock > 0 && $stock <= $low ) {
			return array(
				/* translators: %s: number of items left. */
				'text' => sprintf( _n( 'Only %s left in stock', 'Only %s left in stock', (int) $stock, 'ultra-max-core' ), Format::number( (int) $stock ) ),
				'tone' => 'danger',
			);
		}
		if ( $product->is_on_backorder() ) {
			return array(
				'text' => __( 'Available on backorder', 'ultra-max-core' ),
				'tone' => 'muted',
			);
		}
		return array(
			'text' => __( 'In stock', 'ultra-max-core' ),
			'tone' => 'success',
		);
	}

	/**
	 * The data of each variation for the purchase behaviour.
	 *
	 * @param \WC_Product_Variable $product Product.
	 * @return list<PurchaseVariation>
	 */
	public static function variation_data( \WC_Product_Variable $product ): array {
		$out    = array();
		$labels = array();
		foreach ( self::attributes( $product ) as $attribute ) {
			foreach ( $attribute['options'] as $option ) {
				$labels[ $attribute['name'] ][ $option['value'] ] = $option['label'];
			}
		}
		foreach ( self::variations( $product ) as $variation ) {
			$texts      = self::price_texts( $variation );
			$stock      = self::stock( $variation );
			$limits     = Quantity::limits( $variation );
			$attributes = array();
			$summary    = array();
			foreach ( $variation->get_variation_attributes() as $field => $value ) {
				$attributes[ (string) $field ] = (string) $value;
				if ( '' !== $value ) {
					$summary[] = $labels[ (string) $field ][ (string) $value ] ?? (string) $value;
				}
			}
			$out[] = array(
				'id'         => $variation->get_id(),
				'attributes' => $attributes,
				'price'      => '' === $texts['call'] ? $texts['price'] : $texts['call'],
				'regular'    => $texts['regular'],
				'discount'   => $texts['discount'],
				'in_stock'   => $variation->is_in_stock() && $variation->is_purchasable(),
				'stock'      => $stock['text'],
				'stock_tone' => $stock['tone'],
				'min'        => $limits['min'],
				'max'        => $limits['max'],
				'step'       => $limits['step'],
				'gallery'    => Gallery::key( Gallery::variation_ids( $product, $variation ) ),
				'sku'        => $variation->get_sku(),
				'summary'    => implode( ' · ', $summary ),
			);
		}
		return $out;
	}

	/**
	 * Output of WooCommerce's hooks inside the form, so plugins that add
	 * fields there keep working.
	 *
	 * @param non-empty-string $hook Hook name.
	 */
	private static function hook( string $hook ): Html {
		ob_start();
		do_action( $hook ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- WooCommerce's own hooks.
		return Html::wp( (string) ob_get_clean() );
	}

	/**
	 * Props of the purchase form, or null when the product cannot be bought
	 * from our form (grouped and external products keep WooCommerce's).
	 *
	 * @param \WC_Product $product Product.
	 * @param bool        $quick   Whether to offer "buy now".
	 * @return PurchaseFormProps|null
	 */
	public static function form( \WC_Product $product, bool $quick = true ): ?array {
		$variable = $product instanceof \WC_Product_Variable;
		if ( ! $variable && ! $product->is_type( 'simple' ) ) {
			return null;
		}
		$limits = Quantity::limits( $product );
		$open   = $product->is_purchasable() && $product->is_in_stock();
		$props  = array(
			'id'       => self::form_id( $product ),
			'product'  => $product->get_id(),
			'action'   => self::action( $product ),
			'add'      => array(
				'label'    => $open ? __( 'Add to cart', 'ultra-max-core' ) : __( 'Out of stock', 'ultra-max-core' ),
				'disabled' => ! $open,
			),
			'messages' => array(
				'choose'      => __( 'Choose the options of this product first.', 'ultra-max-core' ),
				'unavailable' => __( 'This combination is not available. Please choose another.', 'ultra-max-core' ),
				'out'         => __( 'This choice is out of stock.', 'ultra-max-core' ),
				'added'       => __( 'Added to your cart.', 'ultra-max-core' ),
				'cart'        => __( 'View cart', 'ultra-max-core' ),
				'cart_url'    => wc_get_cart_url(),
				'error'       => __( 'Something went wrong. Please try again.', 'ultra-max-core' ),
			),
			'endpoint' => \WC_AJAX::get_endpoint( Cart::ACTION ),
			'extra'    => self::hook( 'woocommerce_before_add_to_cart_button' ),
			'after'    => self::hook( 'woocommerce_after_add_to_cart_button' ),
		);
		if ( ! $product->is_sold_individually() ) {
			$props['quantity'] = array(
				'min'      => $limits['min'],
				'max'      => $limits['max'],
				'step'     => $limits['step'],
				'value'    => $limits['min'],
				'label'    => __( 'Quantity', 'ultra-max-core' ),
				'increase' => __( 'One more', 'ultra-max-core' ),
				'decrease' => __( 'One less', 'ultra-max-core' ),
			);
		}
		if ( $quick && $open && Store::bool( 'product_quick_buy' ) ) {
			$props['quick'] = __( 'Buy now', 'ultra-max-core' );
		}
		if ( $variable ) {
			$props['variations'] = self::variation_data( $product );
		}
		return $props;
	}
}
