<?php
/**
 * Related products widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Store\Cards;
use UltraMax\Core\Widgets\Values;

/**
 * Similar or complementary products in a slider, or the shoppers recent views.
 */
final class ProductRelated extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-related';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Related products', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-posts-carousel';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-23' );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			$this->choice(
				'source',
				__( 'Products', 'ultra-max-core' ),
				array(
					'similar'       => __( 'Similar products', 'ultra-max-core' ),
					'complementary' => __( 'Goes well with', 'ultra-max-core' ),
					'recent'        => __( 'Recent views', 'ultra-max-core' ),
				),
				'similar'
			),
			array(
				'id'      => 'count',
				'type'    => 'number',
				'label'   => __( 'Number of products', 'ultra-max-core' ),
				'default' => 10,
				'min'     => 2,
				'max'     => 24,
			),
			$this->choice( 'design', __( 'Card design', 'ultra-max-core' ), Cards::designs(), 'hover' ),
		);
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		$design = $settings->string( 'design' );
		$count  = max( 2, $settings->int( 'count' ) );
		return match ( $settings->string( 'source' ) ) {
			'complementary' => Page::complementary( $count, $design ),
			'recent'        => Page::recent(),
			default         => Page::similar( $count, $design ),
		};
	}
}
