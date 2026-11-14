<?php
/**
 * Add to cart button widget (PLP-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Product;

/**
 * Adds the product without leaving the page, or opens it to choose options.
 */
final class ProductAddToCart extends ProductWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-add-to-cart';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Add to cart button', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-add-to-cart';
	}

	/**
	 * The card piece.
	 */
	protected function part(): string {
		return 'add';
	}
}
