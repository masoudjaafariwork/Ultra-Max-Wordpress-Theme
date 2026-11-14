<?php
/**
 * Minimum, maximum and step of the quantity of a product (PDP-25).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Store\Format;

/**
 * A product can be sold from a minimum, up to a maximum, in steps (a box
 * of six, at most three per order). The store can also set a maximum for
 * every product. The limits hold everywhere WooCommerce counts a quantity:
 * the product page, the classic cart, the block cart and checkout (Store
 * API), and when items are added by any route.
 *
 * @phpstan-type Limits array{min: int, max: int, step: int}
 */
final class Quantity {

	/**
	 * Hooks the limits into WooCommerce.
	 */
	public static function register(): void {
		add_filter( 'woocommerce_quantity_input_args', array( self::class, 'input_args' ), 20, 2 );
		add_filter( 'woocommerce_available_variation', array( self::class, 'variation_args' ), 20, 3 );
		add_filter( 'woocommerce_add_to_cart_validation', array( self::class, 'validate_add' ), 20, 4 );
		add_filter( 'woocommerce_update_cart_validation', array( self::class, 'validate_update' ), 20, 4 );
		add_action( 'woocommerce_check_cart_items', array( self::class, 'check_cart' ) );
		foreach ( array( 'minimum', 'maximum', 'multiple_of' ) as $kind ) {
			add_filter( 'woocommerce_store_api_product_quantity_' . $kind, array( self::class, 'store_api_' . $kind ), 20, 2 );
		}
	}

	/**
	 * The limits of a product or variation. A variation follows its parent's
	 * fields; stock and "sold individually" lower the maximum.
	 *
	 * @param \WC_Product $product Product.
	 * @return Limits
	 */
	public static function limits( \WC_Product $product ): array {
		$owner = Meta::owner( $product );
		$step  = max( 1, Meta::int( $owner, Meta::QTY_STEP ) );
		// Quantities are multiples of the step, as the block cart counts them.
		$min   = (int) ( ceil( max( 1, Meta::int( $owner, Meta::QTY_MIN ) ) / $step ) * $step );
		$max   = max( 0, Meta::int( $owner, Meta::QTY_MAX ) );
		$max   = $max > 0 ? (int) ( floor( $max / $step ) * $step ) : 0;
		$store = max( 0, Store::int( 'product_qty_max' ) );
		if ( $store > 0 ) {
			$max = 0 === $max ? $store : min( $max, $store );
		}
		if ( $product->is_sold_individually() ) {
			return array(
				'min'  => 1,
				'max'  => 1,
				'step' => 1,
			);
		}
		$stock = $product->get_stock_quantity();
		if ( $product->managing_stock() && ! $product->backorders_allowed() && null !== $stock ) {
			$stock = max( 0, (int) $stock );
			$max   = 0 === $max ? $stock : min( $max, $stock );
		}
		if ( $max > 0 && $max < $min ) {
			$max = $min;
		}
		return array(
			'min'  => $min,
			'max'  => $max,
			'step' => $step,
		);
	}

	/**
	 * Why a quantity breaks the limits, or an empty text when it does not.
	 *
	 * @param \WC_Product $product  Product.
	 * @param int         $quantity Quantity.
	 */
	public static function problem( \WC_Product $product, int $quantity ): string {
		$limits = self::limits( $product );
		$name   = wp_strip_all_tags( $product->get_name() );
		if ( $quantity < $limits['min'] ) {
			/* translators: 1: product name, 2: number. */
			return sprintf( __( 'You can buy "%1$s" from %2$s items.', 'ultra-max-core' ), $name, Format::number( $limits['min'] ) );
		}
		if ( $limits['max'] > 0 && $quantity > $limits['max'] ) {
			/* translators: 1: product name, 2: number. */
			return sprintf( __( 'You can buy at most %2$s of "%1$s" in one order.', 'ultra-max-core' ), $name, Format::number( $limits['max'] ) );
		}
		if ( 0 !== $quantity % $limits['step'] ) {
			/* translators: 1: product name, 2: number. */
			return sprintf( __( '"%1$s" is sold in steps of %2$s.', 'ultra-max-core' ), $name, Format::number( $limits['step'] ) );
		}
		return '';
	}

	/**
	 * How many of a product the cart already holds, counting every variation
	 * of the same variation, or of the same simple product.
	 *
	 * @param int    $product_id   Product id.
	 * @param int    $variation_id Variation id.
	 * @param string $skip      Cart item key to leave out.
	 */
	private static function in_cart( int $product_id, int $variation_id, string $skip = '' ): int {
		if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
			return 0;
		}
		$count = 0;
		foreach ( WC()->cart->get_cart() as $key => $item ) {
			if ( $key === $skip || ! is_array( $item ) ) {
				continue;
			}
			$same = $variation_id > 0
				? self::number( $item['variation_id'] ?? 0 ) === $variation_id
				: self::number( $item['product_id'] ?? 0 ) === $product_id && 0 === self::number( $item['variation_id'] ?? 0 );
			if ( $same ) {
				$count += self::number( $item['quantity'] ?? 0 );
			}
		}
		return $count;
	}

	/**
	 * A whole number from a cart item value.
	 *
	 * @param mixed $value Value.
	 */
	private static function number( mixed $value ): int {
		return is_numeric( $value ) ? (int) $value : 0;
	}

	/**
	 * Limits of the classic quantity field.
	 *
	 * @param mixed $args    Field arguments.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function input_args( mixed $args, mixed $product ): mixed {
		if ( ! is_array( $args ) || ! $product instanceof \WC_Product ) {
			return $args;
		}
		$limits = self::limits( $product );
		// On the cart page the field shows what is in the cart; keep it.
		$in_cart = function_exists( 'is_cart' ) && is_cart();
		if ( ! $in_cart ) {
			$args['min_value']   = $limits['min'];
			$args['input_value'] = max( $limits['min'], is_numeric( $args['input_value'] ?? null ) ? (int) $args['input_value'] : 1 );
		} else {
			$args['min_value'] = min( $limits['min'], is_numeric( $args['min_value'] ?? null ) ? (int) $args['min_value'] : 0 );
		}
		$args['step'] = $limits['step'];
		if ( $limits['max'] > 0 ) {
			$args['max_value'] = $limits['max'];
		}
		return $args;
	}

	/**
	 * Limits in WooCommerce's variation data.
	 *
	 * @param mixed $data      Variation data.
	 * @param mixed $product   Variable product.
	 * @param mixed $variation Variation.
	 * @return mixed
	 */
	public static function variation_args( mixed $data, mixed $product, mixed $variation ): mixed {
		if ( is_array( $data ) && $variation instanceof \WC_Product ) {
			$limits              = self::limits( $variation );
			$data['min_qty']     = $limits['min'];
			$data['max_qty']     = $limits['max'] > 0 ? $limits['max'] : '';
			$data['um_qty_step'] = $limits['step'];
		}
		return $data;
	}

	/**
	 * Checks an addition to the cart, with what the cart already holds.
	 *
	 * @param mixed $passed       Whether earlier checks passed.
	 * @param mixed $product_id   Product id.
	 * @param mixed $quantity     Quantity added.
	 * @param mixed $variation_id Variation id.
	 */
	public static function validate_add( mixed $passed, mixed $product_id, mixed $quantity, mixed $variation_id = 0 ): bool {
		if ( true !== (bool) $passed ) {
			return false;
		}
		$product_id   = is_numeric( $product_id ) ? (int) $product_id : 0;
		$variation_id = is_numeric( $variation_id ) ? (int) $variation_id : 0;
		$product      = wc_get_product( $variation_id > 0 ? $variation_id : $product_id );
		if ( ! $product instanceof \WC_Product ) {
			return true;
		}
		$total   = self::in_cart( $product_id, $variation_id ) + ( is_numeric( $quantity ) ? (int) $quantity : 0 );
		$problem = self::problem( $product, $total );
		if ( '' !== $problem ) {
			wc_add_notice( $problem, 'error' );
			return false;
		}
		return true;
	}

	/**
	 * Checks a new quantity typed in the classic cart.
	 *
	 * @param mixed $passed   Whether earlier checks passed.
	 * @param mixed $key      Cart item key.
	 * @param mixed $values   Cart item.
	 * @param mixed $quantity New quantity.
	 */
	public static function validate_update( mixed $passed, mixed $key, mixed $values, mixed $quantity ): bool {
		if ( true !== (bool) $passed ) {
			return false;
		}
		$product = is_array( $values ) ? ( $values['data'] ?? null ) : null;
		if ( ! $product instanceof \WC_Product || ! is_numeric( $quantity ) || (int) $quantity <= 0 ) {
			return true;
		}
		$problem = self::problem( $product, (int) $quantity );
		if ( '' !== $problem ) {
			wc_add_notice( $problem, 'error' );
			return false;
		}
		return true;
	}

	/**
	 * Checks the whole cart before checkout.
	 */
	public static function check_cart(): void {
		if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
			return;
		}
		foreach ( WC()->cart->get_cart() as $item ) {
			$product = is_array( $item ) ? ( $item['data'] ?? null ) : null;
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}
			$total   = self::in_cart( self::number( $item['product_id'] ?? 0 ), self::number( $item['variation_id'] ?? 0 ) );
			$problem = self::problem( $product, $total );
			if ( '' !== $problem ) {
				wc_add_notice( $problem, 'error' );
			}
		}
	}

	/**
	 * Minimum in the block cart and checkout.
	 *
	 * @param mixed $value   Value.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function store_api_minimum( mixed $value, mixed $product ): mixed {
		return $product instanceof \WC_Product ? max( is_numeric( $value ) ? (int) $value : 1, self::limits( $product )['min'] ) : $value;
	}

	/**
	 * Maximum in the block cart and checkout.
	 *
	 * @param mixed $value   Value.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function store_api_maximum( mixed $value, mixed $product ): mixed {
		if ( ! $product instanceof \WC_Product ) {
			return $value;
		}
		$max = self::limits( $product )['max'];
		return $max > 0 ? min( is_numeric( $value ) ? (int) $value : $max, $max ) : $value;
	}

	/**
	 * Step in the block cart and checkout.
	 *
	 * @param mixed $value   Value.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function store_api_multiple_of( mixed $value, mixed $product ): mixed {
		return $product instanceof \WC_Product ? self::limits( $product )['step'] : $value;
	}
}
