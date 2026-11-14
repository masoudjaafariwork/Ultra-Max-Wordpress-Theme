<?php
/**
 * Buyer and invoice widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;

/**
 * Buyer type, national code or company details, email and the store's own buyer fields.
 */
final class CheckoutBuyer extends CheckoutWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'checkout-buyer';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Buyer and invoice', 'ultra-max-core' );
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
		return array( 'BLD-06', 'CHK-05' );
	}

	/**
	 * The part.
	 */
	protected function part(): string {
		return 'buyer';
	}
}
