<?php
/**
 * Cart totals widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * The amounts, the saving, the free shipping bar, the order limit and the checkout button.
 */
final class CartTotals extends CartPartWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'cart-totals';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Cart totals', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-price-table';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-06', 'CART-03', 'CART-04', 'CART-10' );
	}

	/**
	 * The part.
	 */
	protected function part(): string {
		return 'summary';
	}

	/**
	 * The part's options.
	 *
	 * @param Values $settings Settings.
	 * @return array<string, mixed>
	 */
	protected function args( Values $settings ): array {
		return array( 'sticky' => $settings->bool( 'sticky' ) );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array( $this->toggle( 'sticky', __( 'Stay in view while scrolling on desktop', 'ultra-max-core' ) ) );
	}
}
