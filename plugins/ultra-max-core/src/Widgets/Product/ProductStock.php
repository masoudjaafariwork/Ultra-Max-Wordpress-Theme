<?php
/**
 * Product stock widget (PLP-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Product;

/**
 * "Out of stock" or "only 2 left".
 */
final class ProductStock extends ProductWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-stock';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product stock', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-stock';
	}

	/**
	 * The card piece.
	 */
	protected function part(): string {
		return 'stock';
	}
}
