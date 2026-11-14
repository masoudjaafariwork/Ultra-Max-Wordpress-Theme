<?php
/**
 * Checkout steps widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;

/**
 * Cart, shipping details, payment; the current one moves as the shopper goes on.
 */
final class CheckoutSteps extends CheckoutWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'checkout-steps';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Checkout steps', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-navigation-horizontal';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-06', 'CHK-01' );
	}

	/**
	 * The part.
	 */
	protected function part(): string {
		return 'steps';
	}
}
