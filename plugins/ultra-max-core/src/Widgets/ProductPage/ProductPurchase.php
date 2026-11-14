<?php
/**
 * Add to cart form widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Widgets\Values;

/**
 * Quantity, add to cart and buy now, without the rest of the buy box.
 */
final class ProductPurchase extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-purchase';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Add to cart form', 'ultra-max-core' );
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
		return array( 'BLD-04', 'PDP-21', 'PDP-25' );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			$this->toggle( 'quick', __( '"Buy now" button', 'ultra-max-core' ) ),
		);
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		return Page::form( $settings->bool( 'quick' ) );
	}
}
