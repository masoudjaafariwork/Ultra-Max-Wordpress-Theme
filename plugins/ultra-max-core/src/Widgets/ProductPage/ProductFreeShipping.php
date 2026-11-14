<?php
/**
 * Free shipping bar widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Delivery;
use UltraMax\Core\Widgets\Values;

/**
 * How much more the cart needs to ship free.
 */
final class ProductFreeShipping extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-free-shipping';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Free shipping bar', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-progress-tracker';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-19' );
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		return Delivery::free_bar( $product );
	}
}
