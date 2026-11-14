<?php
/**
 * Product sections widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Widgets\Values;

/**
 * Introduction, specifications, tabs, reviews and questions with sticky links, as tabs or as folding panels.
 */
final class ProductSections extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-sections';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product sections', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-tabs';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-06', 'PDP-07', 'PDP-08', 'PDP-09', 'PDP-10', 'PDP-14', 'PDP-20' );
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
					''          => __( 'As in the settings', 'ultra-max-core' ),
					'sections'  => __( 'One page with sticky links', 'ultra-max-core' ),
					'tabs'      => __( 'Tabs', 'ultra-max-core' ),
					'accordion' => __( 'Folding panels', 'ultra-max-core' ),
				),
				''
			),
			$this->toggle( 'mini', __( 'Small buy box beside them on desktop', 'ultra-max-core' ) ),
		);
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		return Page::sections( $settings->bool( 'mini' ) ? Page::sticky( 'card' ) : null, $settings->string( 'variant' ) );
	}
}
