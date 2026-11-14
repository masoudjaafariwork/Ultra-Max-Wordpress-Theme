<?php
/**
 * Phone checkout bar widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;

/**
 * The bar at the bottom of phones with the total and the checkout button.
 */
final class CartBar extends CartPartWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'cart-bar';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Phone checkout bar', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-button';
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
		return 'bar';
	}
}
