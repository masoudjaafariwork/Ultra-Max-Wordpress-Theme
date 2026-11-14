<?php
/**
 * Buy later list widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;

/**
 * The products the shopper set aside, with "move to cart".
 */
final class CartLater extends CartPartWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'cart-later';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Buy later list', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-history';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-06', 'CART-02', 'CART-07' );
	}

	/**
	 * The part.
	 */
	protected function part(): string {
		return 'later';
	}
}
