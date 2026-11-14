<?php
/**
 * Product price widget (PLP-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Product;

/**
 * The price, the old price and the discount.
 */
final class ProductPrice extends ProductWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-price';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product price', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-price';
	}

	/**
	 * The card piece.
	 */
	protected function part(): string {
		return 'price';
	}
}
