<?php
/**
 * Checkout discount code widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;

/**
 * The discount code, applied in place.
 */
final class CheckoutCoupon extends CheckoutWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'checkout-coupon';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Checkout discount code', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-form-horizontal';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-06' );
	}

	/**
	 * The part.
	 */
	protected function part(): string {
		return 'coupon';
	}
}
