<?php
/**
 * Payment instructions widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Checkout\ThankYou;
use UltraMax\Core\Lib\UI\Html;

/**
 * The payment method's own instructions, such as a bank account to pay to.
 */
final class OrderInstructions extends OrderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'order-instructions';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Payment instructions', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-info-circle';
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
	 * The piece.
	 *
	 * @param \WC_Order $order Order.
	 */
	protected function order_piece( \WC_Order $order ): Html {
		return ThankYou::instructions( $order );
	}
}
