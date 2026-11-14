<?php
/**
 * Buy box widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Widgets\Values;

/**
 * Seller, warranty, delivery, stock, price, add to cart, buy now and the free shipping bar.
 */
final class ProductBuyBox extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-buy-box';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Buy box', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-cart-medium';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-05', 'PDP-16', 'PDP-17', 'PDP-18', 'PDP-19', 'PDP-21', 'PDP-25' );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			$this->toggle( 'sticky', __( 'Stay in view while scrolling on desktop', 'ultra-max-core' ) ),
		);
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		return Page::buy_box( $settings->bool( 'sticky' ) );
	}
}
