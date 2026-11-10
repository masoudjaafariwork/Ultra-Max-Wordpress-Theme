<?php
/**
 * Dynamic tags for Elementor Free (BLD-10).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Elementor;

use UltraMax\Core\Lib\Foundation\Brand;
use UltraMax\Core\Lib\Foundation\Digits;
use UltraMax\Core\Lib\Foundation\Jalali;
use UltraMax\Core\Lib\Settings\Store;

/**
 * Elementor Free shows the dynamic tags button on any control once a tag
 * exists for it, so these tags work without Elementor Pro: product price
 * and stock, a custom field, a Jalali date and store details.
 *
 * Each tag's value is computed here, in plain functions that tests call
 * directly; the tag classes in `Tags\` only describe them to Elementor.
 */
final class DynamicTags {

	public const GROUP = 'ultra-max';

	/**
	 * Hooks into Elementor.
	 */
	public static function register(): void {
		add_action( 'elementor/dynamic_tags/register', array( self::class, 'tags' ) );
	}

	/**
	 * Registers the group and the tags.
	 *
	 * @param mixed $manager Dynamic tags manager.
	 */
	public static function tags( mixed $manager ): void {
		if ( ! $manager instanceof \Elementor\Core\DynamicTags\Manager ) {
			return;
		}
		$manager->register_group( self::GROUP, array( 'title' => Brand::name() ) );
		$manager->register( new Tags\ProductPrice() );
		$manager->register( new Tags\ProductStock() );
		$manager->register( new Tags\CustomField() );
		$manager->register( new Tags\JalaliDate() );
		$manager->register( new Tags\StoreInfo() );
	}

	/**
	 * The product a tag talks about: the chosen one, or the current one.
	 *
	 * @param int $id Chosen product id, or 0.
	 */
	public static function product( int $id ): ?\WC_Product {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}
		$product = wc_get_product( $id > 0 ? $id : (int) get_the_ID() );
		return $product instanceof \WC_Product ? $product : null;
	}

	/**
	 * Digits as the store shows them (CORE-04).
	 *
	 * @param string $text Text.
	 */
	public static function digits( string $text ): string {
		return 'en' === Store::string( 'digits' ) ? $text : Digits::to_persian( $text );
	}

	/**
	 * Price of a product as text, with the currency.
	 *
	 * @param int    $id   Product id, or 0 for the current product.
	 * @param string $kind "current", "regular" or "sale".
	 */
	public static function price( int $id, string $kind = 'current' ): string {
		$product = self::product( $id );
		if ( null === $product ) {
			return '';
		}
		$amount = match ( $kind ) {
			'regular' => $product->get_regular_price(),
			'sale'    => $product->get_sale_price(),
			default   => $product->get_price(),
		};
		if ( ! is_numeric( $amount ) ) {
			return '';
		}
		return self::digits( html_entity_decode( wp_strip_all_tags( wc_price( (float) $amount ) ), ENT_QUOTES, 'UTF-8' ) );
	}

	/**
	 * Stock of a product as text.
	 *
	 * @param int    $id     Product id, or 0 for the current product.
	 * @param string $format "label" (In stock / Out of stock) or "quantity".
	 */
	public static function stock( int $id, string $format = 'label' ): string {
		$product = self::product( $id );
		if ( null === $product ) {
			return '';
		}
		if ( 'quantity' === $format ) {
			$quantity = $product->get_stock_quantity();
			return null === $quantity ? '' : self::digits( (string) $quantity );
		}
		return $product->is_in_stock() ? __( 'In stock', 'ultra-max-core' ) : __( 'Out of stock', 'ultra-max-core' );
	}

	/**
	 * A custom field of the current post.
	 *
	 * @param string $key Meta key. Keys starting with an underscore are private and never shown.
	 */
	public static function field( string $key ): string {
		$key = sanitize_key( $key );
		if ( '' === $key || str_starts_with( $key, '_' ) ) {
			return '';
		}
		$value = get_post_meta( (int) get_the_ID(), $key, true );
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * A Jalali date (CORE-03).
	 *
	 * @param string $which  "today", "published" or "modified".
	 * @param string $format PHP date format, for example "j F Y".
	 */
	public static function date( string $which, string $format ): string {
		$timestamp = time();
		if ( 'published' === $which || 'modified' === $which ) {
			$post      = get_post();
			$timestamp = $post instanceof \WP_Post ? (int) get_post_time( 'U', true, $post ) : $timestamp;
			if ( 'modified' === $which && $post instanceof \WP_Post ) {
				$timestamp = (int) get_post_modified_time( 'U', true, $post );
			}
		}
		return self::digits( Jalali::format( '' === $format ? 'j F Y' : $format, $timestamp ) );
	}

	/**
	 * A text option.
	 *
	 * @param string $name Option name.
	 */
	private static function option( string $name ): string {
		$value = get_option( $name, '' );
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * A detail of the store.
	 *
	 * @param string $what name, tagline, email, address or city.
	 */
	public static function store( string $what ): string {
		$value = match ( $what ) {
			'tagline' => get_bloginfo( 'description' ),
			'email'   => self::option( 'woocommerce_email_from_address' ),
			'address' => trim( self::option( 'woocommerce_store_address' ) . ' ' . self::option( 'woocommerce_store_address_2' ) ),
			'city'    => self::option( 'woocommerce_store_city' ),
			default   => get_bloginfo( 'name' ),
		};
		return self::digits( $value );
	}
}
