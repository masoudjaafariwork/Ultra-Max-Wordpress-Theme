<?php
/**
 * Shared parts of the thank you widgets (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Checkout\Checkout;
use UltraMax\Core\Checkout\ThankYou;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * A thank you widget prints one piece of the page after an order, for the
 * order WooCommerce allowed the visitor to see; while a layout is designed,
 * the newest order of the store.
 */
abstract class OrderWidget extends CartWidget {

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'checkout' );
	}

	/**
	 * Where the widget belongs.
	 */
	protected function where(): string {
		return __( 'For a thank you layout. Shows the order just placed.', 'ultra-max-core' );
	}

	/**
	 * Whether the checkout module is on.
	 */
	protected function ready(): bool {
		return Checkout::enabled();
	}

	/**
	 * The piece for the order.
	 *
	 * @param \WC_Order $order Order.
	 */
	abstract protected function order_piece( \WC_Order $order ): Html;

	/**
	 * The piece.
	 *
	 * @param Values $settings Settings.
	 */
	protected function piece( Values $settings ): Html {
		$order = ThankYou::order();
		return null === $order ? Html::none() : $this->order_piece( $order );
	}
}
