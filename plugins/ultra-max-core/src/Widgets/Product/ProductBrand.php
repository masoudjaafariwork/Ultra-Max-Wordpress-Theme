<?php
/**
 * Product brand widget (PLP-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Product;

/**
 * The brand name.
 */
final class ProductBrand extends ProductWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-brand';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product brand', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-meta';
	}

	/**
	 * The card piece.
	 */
	protected function part(): string {
		return 'brand';
	}
}
