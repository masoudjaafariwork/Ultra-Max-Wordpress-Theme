<?php
/**
 * Order summary widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Checkout\ThankYou;
use UltraMax\Core\Lib\UI\Html;

/**
 * The thanks with the order number, date, amount and payment method.
 */
final class OrderSummary extends OrderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'order-summary';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Order summary', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-check-circle';
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
		return ThankYou::done( $order );
	}
}
