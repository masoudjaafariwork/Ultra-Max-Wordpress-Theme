<?php
/**
 * "Frequently bought together" with a bundle discount (PDP-22).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Lib\UI\BundleBox;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Image;
use UltraMax\Core\Store\Format;

/**
 * A product names up to three products often bought with it and a bundle
 * discount in percent. The box adds the ticked ones in one click. When the
 * cart holds the product and any of its bundle products, the discount
 * applies to one of each as a negative fee, which both the classic and
 * the block cart and checkout show.
 *
 * Only simple products that can be bought join a bundle; the phase 8
 * bundle products (MERCH-03) build on this.
 */
final class Bundle {

	public const MOST = 3;

	/**
	 * Hooks the box, the fields and the discount.
	 */
	public static function register(): void {
		add_filter( 'ultra_max_product_page_extras', array( self::class, 'extra' ), 10, 3 );
		add_action( 'ultra_max_product_fields', array( self::class, 'fields' ), 30 );
		add_action( 'ultra_max_save_product_fields', array( self::class, 'save' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( self::class, 'discount' ) );
	}

	/**
	 * Whether a product can join a bundle.
	 *
	 * @param \WC_Product $product Product.
	 */
	private static function joins( \WC_Product $product ): bool {
		return $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() && $product->is_visible() && 'publish' === $product->get_status() && '' === $product->get_post_password();
	}

	/**
	 * The other products of a product's bundle.
	 *
	 * @param \WC_Product $product Product.
	 * @return list<\WC_Product>
	 */
	public static function items( \WC_Product $product ): array {
		$out = array();
		foreach ( Meta::ids( $product, Meta::BUNDLE ) as $id ) {
			$item = wc_get_product( $id );
			if ( $item instanceof \WC_Product && $id !== $product->get_id() && self::joins( $item ) ) {
				$out[] = $item;
			}
			if ( count( $out ) >= self::MOST ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * The bundle discount of a product in percent.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function percent( \WC_Product $product ): int {
		return max( 0, min( 90, Meta::int( $product, Meta::DISCOUNT ) ) );
	}

	/**
	 * Price of one unit as shown.
	 *
	 * @param \WC_Product $product Product.
	 */
	private static function price( \WC_Product $product ): float {
		return (float) wc_get_price_to_display( $product );
	}

	/**
	 * The box of a product.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function box( \WC_Product $product ): Html {
		$others = self::items( $product );
		if ( array() === $others || ! self::joins( $product ) ) {
			return Html::none();
		}
		$all     = array_merge( array( $product ), $others );
		$percent = self::percent( $product );
		$items   = array();
		foreach ( $all as $index => $item ) {
			$items[] = array(
				'id'    => $item->get_id(),
				'title' => wp_strip_all_tags( $item->get_name() ),
				'href'  => (string) $item->get_permalink(),
				'image' => Image::render(
					array(
						'id'   => (int) $item->get_image_id(),
						'size' => 'woocommerce_thumbnail',
						'alt'  => '',
					)
				),
				'price' => Format::money( self::price( $item ) ),
				'fixed' => 0 === $index,
			);
		}
		// Totals of every choice: the product with any of the others.
		$totals = array();
		$count  = count( $others );
		for ( $mask = 0; $mask < ( 1 << $count ); $mask++ ) {
			$chosen = array( $product );
			foreach ( $others as $bit => $other ) {
				if ( $mask & ( 1 << $bit ) ) {
					$chosen[] = $other;
				}
			}
			$sum            = array_sum( array_map( array( self::class, 'price' ), $chosen ) );
			$off            = count( $chosen ) > 1 ? $sum * $percent / 100 : 0.0;
			$key            = implode( ',', array_map( static fn ( \WC_Product $item ): string => (string) $item->get_id(), $chosen ) );
			$totals[ $key ] = array(
				'old'  => $off > 0 ? Format::money( $sum ) : '',
				'now'  => Format::money( $sum - $off ),
				/* translators: %s: amount of money. */
				'save' => $off > 0 ? sprintf( __( 'You save %s', 'ultra-max-core' ), Format::money( $off ) . ' ' . Format::currency() ) : '',
			);
		}
		return BundleBox::render(
			array(
				'id'       => 'um-bundle-' . $product->get_id(),
				'title'    => __( 'Frequently bought together', 'ultra-max-core' ),
				'items'    => $items,
				'totals'   => $totals,
				'currency' => Format::currency(),
				'labels'   => array(
					'total'   => __( 'Price of the chosen products', 'ultra-max-core' ),
					'add'     => __( 'Add the chosen products to cart', 'ultra-max-core' ),
					/* translators: %s: product name. */
					'include' => __( 'Include %s', 'ultra-max-core' ),
				),
				'endpoint' => \WC_AJAX::get_endpoint( Cart::ACTION ),
				'messages' => array(
					'added'    => __( 'Added to your cart.', 'ultra-max-core' ),
					'cart'     => __( 'View cart', 'ultra-max-core' ),
					'cart_url' => wc_get_cart_url(),
					'error'    => __( 'Something went wrong. Please try again.', 'ultra-max-core' ),
				),
			)
		);
	}

	/**
	 * Adds the box after the store's promises.
	 *
	 * @param mixed $pieces  Pieces.
	 * @param mixed $where   Place.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function extra( mixed $pieces, mixed $where, mixed $product ): mixed {
		if ( is_array( $pieces ) && 'after_perks' === $where && $product instanceof \WC_Product ) {
			$pieces[] = self::box( $product );
		}
		return $pieces;
	}

	/**
	 * Applies bundle discounts to the cart, once per bundle product.
	 *
	 * @param mixed $cart Cart.
	 */
	public static function discount( mixed $cart ): void {
		if ( ! $cart instanceof \WC_Cart ) {
			return;
		}
		$prices = array();
		foreach ( $cart->get_cart() as $item ) {
			$data = is_array( $item ) ? ( $item['data'] ?? null ) : null;
			if ( $data instanceof \WC_Product && 0 === (int) ( is_numeric( $item['variation_id'] ?? null ) ? $item['variation_id'] : 0 ) ) {
				$prices[ $data->get_id() ] = (float) wc_get_price_to_display( $data );
			}
		}
		foreach ( array_keys( $prices ) as $id ) {
			$main    = wc_get_product( $id );
			$percent = $main instanceof \WC_Product ? self::percent( $main ) : 0;
			if ( ! $main instanceof \WC_Product || 0 === $percent ) {
				continue;
			}
			$present = array_values( array_intersect( Meta::ids( $main, Meta::BUNDLE ), array_keys( $prices ) ) );
			if ( array() === $present ) {
				continue;
			}
			$sum = $prices[ $id ];
			foreach ( array_slice( $present, 0, self::MOST ) as $other ) {
				$sum += $prices[ $other ];
			}
			$cart->add_fee(
				/* translators: %s: product name. */
				sprintf( __( 'Bundle discount: %s', 'ultra-max-core' ), wp_strip_all_tags( $main->get_name() ) ),
				-round( $sum * $percent / 100, wc_get_price_decimals() ),
				false
			);
		}
	}

	/**
	 * The bundle fields in the product's tab.
	 *
	 * @param mixed $product Product.
	 */
	public static function fields( mixed $product ): void {
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$options = '';
		foreach ( Meta::ids( $product, Meta::BUNDLE ) as $id ) {
			$item = wc_get_product( $id );
			if ( $item instanceof \WC_Product ) {
				$options .= sprintf( '<option value="%1$d" selected>%2$s</option>', $id, esc_html( wp_strip_all_tags( $item->get_formatted_name() ) ) );
			}
		}
		printf(
			'<div class="options_group"><p class="form-field"><label for="um-bundle-ids">%1$s</label><select class="wc-product-search" multiple="multiple" style="width:50%%" id="um-bundle-ids" name="um_bundle_ids[]" data-placeholder="%2$s" data-action="woocommerce_json_search_products" data-exclude="%3$d" data-limit="3">%4$s</select> %5$s</p>',
			esc_html__( 'Bought together', 'ultra-max-core' ),
			esc_attr__( 'Search for a product…', 'ultra-max-core' ),
			(int) $product->get_id(),
			$options, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
			wc_help_tip( __( 'Up to three simple products shown with this one, with one button to add them all.', 'ultra-max-core' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's own escaped tip.
		);
		woocommerce_wp_text_input(
			array(
				'id'                => Meta::DISCOUNT,
				'label'             => __( 'Bundle discount (%)', 'ultra-max-core' ),
				'value'             => (string) Meta::int( $product, Meta::DISCOUNT ),
				'type'              => 'number',
				'description'       => __( 'Taken off this product and the bundle products when they are in the cart together. 0 for none.', 'ultra-max-core' ),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'min' => '0',
					'max' => '90',
				),
			)
		);
		echo '</div>';
	}

	/**
	 * Saves the bundle fields.
	 *
	 * @param mixed $product Product.
	 */
	public static function save( mixed $product ): void {
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- WooCommerce checked its nonce; ids are checked below.
		$posted = isset( $_POST['um_bundle_ids'] ) && is_array( $_POST['um_bundle_ids'] ) ? wp_unslash( $_POST['um_bundle_ids'] ) : array();
		$ids    = array();
		foreach ( is_array( $posted ) ? $posted : array() as $id ) {
			if ( is_numeric( $id ) && (int) $id > 0 && (int) $id !== $product->get_id() && 'product' === get_post_type( (int) $id ) ) {
				$ids[] = (int) $id;
			}
		}
		$product->update_meta_data( Meta::BUNDLE, implode( ',', array_slice( array_unique( $ids ), 0, self::MOST ) ) );
		$product->update_meta_data( Meta::DISCOUNT, (string) max( 0, min( 90, absint( Fields::posted( Meta::DISCOUNT ) ) ) ) );
	}
}
