<?php
/**
 * Amounts of the cart (CART-03, CART-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Cart;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Money;
use UltraMax\Core\Lib\UI\ProgressNote;
use UltraMax\Core\Store\Badges;
use UltraMax\Core\Store\Format;

/**
 * Every amount the cart and the checkout show, in the prices the store
 * displays (with or without tax, as WooCommerce is set):
 *
 * - the items at their regular prices,
 * - the cart total: items at their sale prices, less coupons and less
 *   discounts added as negative fees (such as "bought together"),
 * - the saving: the difference between the two (CART-03),
 * - how far the cart is from free shipping (CART-04).
 *
 * @phpstan-type Amounts array{count: int, regular: float, items: float, discounts: float, fees: float, total: float, saving: float, percent: int}
 */
final class Totals {

	/**
	 * The amounts of a cart.
	 *
	 * @param \WC_Cart $cart Cart.
	 * @return Amounts
	 */
	public static function of( \WC_Cart $cart ): array {
		$tax     = $cart->display_prices_including_tax();
		$regular = 0.0;
		foreach ( $cart->get_cart() as $item ) {
			$product  = is_array( $item ) ? ( $item['data'] ?? null ) : null;
			$quantity = is_array( $item ) && is_numeric( $item['quantity'] ?? null ) ? (float) $item['quantity'] : 0.0;
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}
			$regular += self::regular( $product, $quantity, $tax );
		}
		$items     = (float) $cart->get_displayed_subtotal();
		$discounts = (float) $cart->get_discount_total() + ( $tax ? (float) $cart->get_discount_tax() : 0.0 );
		$fees      = 0.0;
		$negative  = 0.0;
		foreach ( $cart->get_fees() as $fee ) {
			$amount = (float) $fee->total + ( $tax ? (float) $fee->tax : 0.0 );
			$fees  += $amount;
			if ( $amount < 0 ) {
				$negative += -$amount;
			}
		}
		$regular = max( $regular, $items );
		$total   = max( 0.0, $items - $discounts + $fees );
		$saving  = max( 0.0, $regular - $items ) + $discounts + $negative;
		return array(
			'count'     => (int) $cart->get_cart_contents_count(),
			'regular'   => $regular,
			'items'     => $items,
			'discounts' => $discounts,
			'fees'      => $fees,
			'total'     => $total,
			'saving'    => $saving,
			'percent'   => self::percent( $saving, $regular ),
		);
	}

	/**
	 * A saving in whole percent of the regular amount.
	 *
	 * @param float $saving  Saving.
	 * @param float $regular Regular amount.
	 */
	public static function percent( float $saving, float $regular ): int {
		return $regular > 0 ? (int) round( $saving / $regular * 100 ) : 0;
	}

	/**
	 * A line at its regular price, as displayed.
	 *
	 * @param \WC_Product $product  Product.
	 * @param float       $quantity Quantity.
	 * @param bool        $tax      Whether prices show with tax.
	 */
	public static function regular( \WC_Product $product, float $quantity, bool $tax ): float {
		$price = $product->get_regular_price();
		$price = is_numeric( $price ) && (float) $price > 0 ? (float) $price : (float) $product->get_price();
		$args  = array(
			'qty'   => $quantity,
			'price' => $price,
		);
		return (float) ( $tax ? wc_get_price_including_tax( $product, $args ) : wc_get_price_excluding_tax( $product, $args ) );
	}

	/**
	 * A line at its current price, as displayed.
	 *
	 * @param \WC_Product $product  Product.
	 * @param float       $quantity Quantity.
	 * @param bool        $tax      Whether prices show with tax.
	 */
	public static function current( \WC_Product $product, float $quantity, bool $tax ): float {
		$args = array( 'qty' => $quantity );
		return (float) ( $tax ? wc_get_price_including_tax( $product, $args ) : wc_get_price_excluding_tax( $product, $args ) );
	}

	/**
	 * An amount with the currency, such as "27,630,000 Toman".
	 *
	 * @param float $amount Amount.
	 */
	public static function money( float $amount ): string {
		return Format::money( $amount ) . ' ' . Format::currency();
	}

	/**
	 * An amount with a smaller currency name after it.
	 *
	 * @param float $amount Amount.
	 */
	public static function money_html( float $amount ): Html {
		return Money::render(
			array(
				'amount'   => Format::money( $amount ),
				'currency' => Format::currency(),
			)
		);
	}

	/**
	 * The saving as "(19%) 6,570,000 Toman".
	 *
	 * @param array $amounts Amounts.
	 * @phpstan-param Amounts $amounts
	 */
	public static function saving_text( array $amounts ): string {
		return sprintf( '(%1$s) %2$s', Format::percent( $amounts['percent'] ), self::money( $amounts['saving'] ) );
	}

	/**
	 * How much of free shipping the cart has reached, as numbers: the
	 * amount shipping is free from, what is still missing, and the
	 * percentage reached. Null when the store has no free shipping or the
	 * cart ships nothing.
	 *
	 * @param \WC_Cart $cart Cart.
	 * @return array{free: float, missing: float, value: int}|null
	 */
	public static function free_progress( \WC_Cart $cart ): ?array {
		$free = Badges::free_shipping();
		if ( $free <= 0 || ! $cart->needs_shipping() ) {
			return null;
		}
		$amounts = self::of( $cart );
		$missing = max( 0.0, $free - $amounts['total'] );
		return array(
			'free'    => $free,
			'missing' => $missing,
			'value'   => $missing <= 0 ? 100 : (int) floor( $amounts['total'] / $free * 100 ),
		);
	}

	/**
	 * The free shipping bar of the cart (CART-04).
	 *
	 * @param \WC_Cart $cart Cart.
	 */
	public static function free_bar( \WC_Cart $cart ): Html {
		$progress = Store::bool( 'cart_free_bar' ) ? self::free_progress( $cart ) : null;
		if ( null === $progress ) {
			return Html::none();
		}
		if ( $progress['missing'] <= 0 ) {
			return ProgressNote::render(
				array(
					'text'  => Html::trusted( esc_html__( 'This order ships free', 'ultra-max-core' ) ),
					'value' => 100,
					'label' => __( 'Free shipping reached', 'ultra-max-core' ),
					'icon'  => 'truck',
					'tone'  => 'success',
				)
			);
		}
		return ProgressNote::render(
			array(
				'text'  => Html::trusted(
					sprintf(
						/* translators: %s: amount of money. */
						esc_html__( 'Add %s more to ship free', 'ultra-max-core' ),
						'<b>' . esc_html( self::money( $progress['missing'] ) ) . '</b>'
					)
				),
				'value' => $progress['value'],
				'label' => __( 'On the way to free shipping', 'ultra-max-core' ),
				'icon'  => 'truck',
				'tone'  => 'success',
			)
		);
	}
}
