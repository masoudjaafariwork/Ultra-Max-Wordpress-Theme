<?php
/**
 * Reviews widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Reviews;
use UltraMax\Core\Widgets\Values;

/**
 * Summary, criteria, photos, the list with filters and votes, and the review form.
 */
final class ProductReviews extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-reviews';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Reviews', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-review';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-07', 'PDP-08', 'PDP-09' );
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		return Reviews::section( $product );
	}
}
