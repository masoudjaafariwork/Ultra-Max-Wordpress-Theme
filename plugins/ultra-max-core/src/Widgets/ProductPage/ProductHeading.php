<?php
/**
 * Product name and rating widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Widgets\Values;

/**
 * Brand and category links, the name, its Latin name, rating, reviews and questions.
 */
final class ProductHeading extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-heading';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product name and rating', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-title';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-07', 'PDP-15' );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			$this->choice(
				'tag',
				__( 'Heading level', 'ultra-max-core' ),
				array(
					'h1' => 'H1',
					'h2' => 'H2',
				),
				'h1'
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
		return Page::heading( 'h2' === $settings->string( 'tag' ) ? 'h2' : 'h1' );
	}
}
