<?php
/**
 * Shared parts of the checkout widgets (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Checkout\Checkout;
use UltraMax\Core\Checkout\Page;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * A checkout widget prints one part of the checkout form. The checkout
 * layout as a whole is the form, so the parts work together and WooCommerce
 * sends them as one order.
 */
abstract class CheckoutWidget extends CartWidget {

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'checkout', 'cart' );
	}

	/**
	 * Where the widget belongs.
	 */
	protected function where(): string {
		return __( 'For a checkout layout. Shows a part of the checkout form.', 'ultra-max-core' );
	}

	/**
	 * The checkout part this widget prints, one of Page::PARTS.
	 */
	abstract protected function part(): string;

	/**
	 * Whether the checkout module is on and a cart exists.
	 */
	protected function ready(): bool {
		return Checkout::enabled() && null !== WC()->cart && ! WC()->cart->is_empty();
	}

	/**
	 * The part.
	 *
	 * @param Values $settings Settings.
	 */
	protected function piece( Values $settings ): Html {
		return Page::part( $this->part() );
	}
}
