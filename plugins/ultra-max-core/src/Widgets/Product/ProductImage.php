<?php
/**
 * Product image widget (PLP-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Product;

use UltraMax\Core\Widgets\Values;

/**
 * The product picture, linked to the product, with its colours and
 * optionally its second picture on hover.
 */
final class ProductImage extends ProductWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-image';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product image', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-images';
	}

	/**
	 * The card piece.
	 */
	protected function part(): string {
		return 'image';
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			array(
				'id'      => 'hover',
				'type'    => 'switch',
				'label'   => __( 'Show the second picture on hover', 'ultra-max-core' ),
				'default' => true,
			),
		);
	}

	/**
	 * Options.
	 *
	 * @param Values $settings Settings.
	 * @return array{tag?: 'h2'|'h3'|'h4'|'p', hover?: bool}
	 */
	protected function options( Values $settings ): array {
		return array( 'hover' => $settings->bool( 'hover' ) );
	}
}
