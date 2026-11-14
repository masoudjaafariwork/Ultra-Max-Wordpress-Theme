<?php
/**
 * Empty cart widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;

/**
 * Shows only when the cart is empty, with links to the shop.
 */
final class CartEmpty extends CartPartWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'cart-empty';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Empty cart', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-cart';
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
		return 'empty';
	}
}
