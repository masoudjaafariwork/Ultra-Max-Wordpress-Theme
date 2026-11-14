<?php
/**
 * Product rating widget (PLP-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Product;

/**
 * The average rating of buyers.
 */
final class ProductRating extends ProductWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-rating';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product rating', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-rating';
	}

	/**
	 * The card piece.
	 */
	protected function part(): string {
		return 'rating';
	}
}
