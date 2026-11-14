<?php
/**
 * Product page price widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Widgets\Values;

/**
 * The large price; it follows the chosen variation.
 */
final class ProductPrice extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-page-price';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product page price', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-price';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-05' );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			$this->choice(
				'size',
				__( 'Size', 'ultra-max-core' ),
				array(
					'lg' => __( 'Large', 'ultra-max-core' ),
					'md' => __( 'Medium', 'ultra-max-core' ),
					'sm' => __( 'Small', 'ultra-max-core' ),
				),
				'lg'
			),
		);
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		$size = $settings->string( 'size' );
		return Page::price( in_array( $size, array( 'lg', 'md', 'sm' ), true ) ? $size : 'lg' );
	}
}
