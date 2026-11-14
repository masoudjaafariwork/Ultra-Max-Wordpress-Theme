<?php
/**
 * Order details widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Checkout\ThankYou;
use UltraMax\Core\Lib\UI\Html;

/**
 * The products with their pictures and the amounts.
 */
final class OrderDetails extends OrderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'order-details';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Order details', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-bullet-list';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-06', 'CHK-09' );
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Order $order Order.
	 */
	protected function order_piece( \WC_Order $order ): Html {
		return ThankYou::items( $order );
	}
}
