<?php
/**
 * Delivery estimate widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Delivery;
use UltraMax\Core\Widgets\Values;

/**
 * Delivery dates by province and shipping method.
 */
final class ProductDeliveryEstimate extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-delivery';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Delivery estimate', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-clock-o';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-18' );
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		return Delivery::estimate( $product );
	}
}
