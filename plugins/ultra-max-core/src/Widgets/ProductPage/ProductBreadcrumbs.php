<?php
/**
 * Product breadcrumbs widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Shop\Archive;
use UltraMax\Core\Widgets\Values;

/**
 * The path from the home page to the product.
 */
final class ProductBreadcrumbs extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-breadcrumbs';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product breadcrumbs', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-navigation-horizontal';
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
		return Archive::breadcrumbs();
	}
}
