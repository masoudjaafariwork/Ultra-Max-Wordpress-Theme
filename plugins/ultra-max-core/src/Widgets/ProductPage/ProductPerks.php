<?php
/**
 * Store promises widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Widgets\Values;

/**
 * The promises set in Product page settings, such as express delivery and returns.
 */
final class ProductPerks extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-perks';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Store promises', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-icon-box';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04' );
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		return Page::perks();
	}
}
