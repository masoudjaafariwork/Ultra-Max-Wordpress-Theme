<?php
/**
 * Questions and answers widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Questions;
use UltraMax\Core\Widgets\Values;

/**
 * Questions with their answers and the form to ask.
 */
final class ProductQuestions extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-questions';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Questions and answers', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-help-o';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-10' );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			array(
				'id'      => 'count',
				'type'    => 'number',
				'label'   => __( 'Questions shown at first', 'ultra-max-core' ),
				'default' => 5,
				'min'     => 1,
				'max'     => 30,
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
		return Questions::section( $product, max( 1, $settings->int( 'count' ) ) );
	}
}
