<?php
/**
 * Payment methods widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;

/**
 * The payment methods with each gateway's own fields, and the terms.
 */
final class CheckoutPayment extends CheckoutWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'checkout-payment';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Payment methods', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-credit-card';
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
		return 'payment';
	}
}
