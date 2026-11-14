<?php
/**
 * Key features widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Widgets\Values;

/**
 * A few specifications in tiles with a link to all of them.
 */
final class ProductKeyFeatures extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-key-features';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Key features', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-bullet-list';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-06' );
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
				'label'   => __( 'How many', 'ultra-max-core' ),
				'default' => 6,
				'min'     => 1,
				'max'     => 12,
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
		return Page::key_features( max( 1, $settings->int( 'count' ) ) );
	}
}
