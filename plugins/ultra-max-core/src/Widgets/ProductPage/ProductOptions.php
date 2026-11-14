<?php
/**
 * Product options widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Product\SizeGuide;
use UltraMax\Core\Widgets\Values;

/**
 * Colour, picture and size choices of a variable product.
 */
final class ProductOptions extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-options';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product options', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-add-to-cart';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-04' );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			$this->toggle( 'guide', __( 'Size guide button', 'ultra-max-core' ) ),
		);
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		return Html::from( array( Page::picker(), $settings->bool( 'guide' ) ? Page::size_guide() : Html::none(), $settings->bool( 'guide' ) ? SizeGuide::dialog( $product ) : Html::none() ) );
	}
}
