<?php
/**
 * Bought together widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Bundle;
use UltraMax\Core\Widgets\Values;

/**
 * The products bought with this one, with one add button and the bundle discount.
 */
final class ProductBoughtTogether extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-bundle';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Bought together', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-products';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-22' );
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		return Bundle::box( $product );
	}
}
