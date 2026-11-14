<?php
/**
 * Customer details widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Checkout\ThankYou;
use UltraMax\Core\Lib\UI\Html;

/**
 * Recipient, mobile, address and buyer details.
 */
final class OrderCustomer extends OrderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'order-customer';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Customer details', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-person';
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
		return ThankYou::customer( $order );
	}
}
