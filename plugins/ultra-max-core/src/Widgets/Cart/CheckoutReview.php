<?php
/**
 * Order review widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;

/**
 * The amounts, the saving, the buttons to go on and to place the order.
 */
final class CheckoutReview extends CheckoutWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'checkout-review';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Order review', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-checkout';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-06', 'CHK-01', 'CART-03' );
	}

	/**
	 * The part.
	 */
	protected function part(): string {
		return 'summary';
	}
}
