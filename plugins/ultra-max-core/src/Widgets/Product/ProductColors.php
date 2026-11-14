<?php
/**
 * Product colours widget (PLP-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Product;

/**
 * The colours the product comes in, from its colour swatch attribute.
 */
final class ProductColors extends ProductWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-colors';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product colours', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-palette';
	}

	/**
	 * The card piece.
	 */
	protected function part(): string {
		return 'colors';
	}
}
