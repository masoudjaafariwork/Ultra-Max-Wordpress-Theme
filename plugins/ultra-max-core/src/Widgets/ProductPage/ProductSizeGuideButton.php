<?php
/**
 * Size guide widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\SizeGuide;
use UltraMax\Core\Widgets\Values;

/**
 * A button that opens the size guide of the products category.
 */
final class ProductSizeGuideButton extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-size-guide';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Size guide', 'ultra-max-core' );
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
		return array( 'BLD-04', 'PDP-13' );
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		return Html::from( array( SizeGuide::button( $product ), SizeGuide::dialog( $product ) ) );
	}
}
