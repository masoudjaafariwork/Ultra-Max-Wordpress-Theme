<?php
/**
 * Product gallery widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Widgets\Values;

/**
 * Pictures and videos with zoom, a full screen viewer and pictures per variation.
 */
final class ProductGallery extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-gallery';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product gallery', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-images';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-01', 'PDP-02', 'PDP-03' );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			$this->choice(
				'layout',
				__( 'Layout', 'ultra-max-core' ),
				array(
					''       => __( 'As in the settings', 'ultra-max-core' ),
					'side'   => __( 'Thumbnails beside', 'ultra-max-core' ),
					'bottom' => __( 'Thumbnails under', 'ultra-max-core' ),
					'grid'   => __( 'All in a grid', 'ultra-max-core' ),
					'slider' => __( 'Slider', 'ultra-max-core' ),
				),
				''
			),
			$this->toggle( 'tools', __( 'Share and other round buttons', 'ultra-max-core' ) ),
		);
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		$tools = $settings->bool( 'tools' );
		return Html::from( array( Page::gallery( $settings->string( 'layout' ), $tools ), $tools ? Page::share_dialog() : Html::none() ) );
	}
}
