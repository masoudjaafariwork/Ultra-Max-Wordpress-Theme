<?php
/**
 * Sticky buy button widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Widgets\Values;

/**
 * A bar at the bottom of phones, or a small box for a desktop column.
 */
final class ProductSticky extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-sticky';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Sticky buy button', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-button';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-20' );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			$this->choice(
				'variant',
				__( 'Show as', 'ultra-max-core' ),
				array(
					'bar'  => __( 'Bar at the bottom of phones', 'ultra-max-core' ),
					'card' => __( 'Small box', 'ultra-max-core' ),
				),
				'bar'
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
		return Page::sticky( 'card' === $settings->string( 'variant' ) ? 'card' : 'bar' );
	}
}
