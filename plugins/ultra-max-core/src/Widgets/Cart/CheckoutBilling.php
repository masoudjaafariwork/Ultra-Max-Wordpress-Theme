<?php
/**
 * Address form widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;

/**
 * Saved addresses and the address fields, with a different shipping address and account creation when the store allows them.
 */
final class CheckoutBilling extends CheckoutWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'checkout-billing';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Address form', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-map-pin';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-06', 'CHK-02', 'CHK-03', 'CHK-04' );
	}

	/**
	 * The part.
	 */
	protected function part(): string {
		return 'address';
	}
}
