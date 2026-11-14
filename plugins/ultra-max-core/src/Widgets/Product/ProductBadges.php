<?php
/**
 * Product badges widget (PLP-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Product;

/**
 * Badges such as new, special and your own label.
 */
final class ProductBadges extends ProductWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-badges';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product badges', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-tags';
	}

	/**
	 * The card piece.
	 */
	protected function part(): string {
		return 'badges';
	}
}
