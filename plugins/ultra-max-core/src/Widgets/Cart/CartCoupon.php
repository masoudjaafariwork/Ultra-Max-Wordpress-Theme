<?php
/**
 * Discount code widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;

/**
 * The discount code field and the codes already applied.
 */
final class CartCoupon extends CartPartWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'cart-coupon';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Discount code', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-form-horizontal';
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
		return 'coupon';
	}
}
