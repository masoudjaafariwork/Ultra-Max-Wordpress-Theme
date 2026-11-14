<?php
/**
 * Offer stock bar widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Sales;
use UltraMax\Core\Widgets\Values;

/**
 * How much of a product on sale is sold and how many are left.
 */
final class ProductStockBar extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-stock-bar';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Offer stock bar', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-skill-bar';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-16' );
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		return Sales::stock_bar( $product );
	}
}
