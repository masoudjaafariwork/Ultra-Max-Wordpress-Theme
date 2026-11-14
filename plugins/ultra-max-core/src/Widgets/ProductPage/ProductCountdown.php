<?php
/**
 * Offer countdown widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Widgets\Values;

/**
 * Time left until the sale price ends, for products with a sale end date.
 */
final class ProductCountdown extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-countdown';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Offer countdown', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-countdown';
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
		$deal = Page::deal( $product );
		return null === $deal ? Html::none() : Html::from( array( Html::text( $deal['label'] ), $deal['countdown'] ) );
	}
}
