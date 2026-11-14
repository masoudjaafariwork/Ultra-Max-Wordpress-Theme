<?php
/**
 * Order notes widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;

/**
 * Order notes, the store's order fields and the marketing consent.
 */
final class CheckoutNotes extends CheckoutWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'checkout-notes';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Order notes', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-text-area';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-06', 'CHK-08' );
	}

	/**
	 * The part.
	 */
	protected function part(): string {
		return 'notes';
	}
}
