<?php
/**
 * Specifications widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Widgets\Values;

/**
 * Every specification in groups.
 */
final class ProductSpecs extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-specs';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Specifications', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-table';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-06' );
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		return Page::specs();
	}
}
