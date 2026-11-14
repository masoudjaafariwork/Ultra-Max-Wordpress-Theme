<?php
/**
 * Shipping form widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;

/**
 * The shipping methods with their delivery dates and the pictures of the parcel.
 */
final class CheckoutShipping extends CheckoutWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'checkout-shipping';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Shipping form', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-shipping';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-06', 'SHIP-01', 'SHIP-02', 'CHK-09' );
	}

	/**
	 * The part.
	 */
	protected function part(): string {
		return 'shipping';
	}
}
