<?php
/**
 * Product title widget (PLP-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Product;

use UltraMax\Core\Widgets\Values;

/**
 * The product name, linked to the product.
 */
final class ProductTitle extends ProductWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-title';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product title', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-title';
	}

	/**
	 * The card piece.
	 */
	protected function part(): string {
		return 'title';
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			array(
				'id'      => 'tag',
				'type'    => 'select',
				'label'   => __( 'Heading level', 'ultra-max-core' ),
				'default' => 'h3',
				'options' => array(
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'p'  => __( 'Paragraph', 'ultra-max-core' ),
				),
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
		$tag = $settings->string( 'tag' );
		return array( 'tag' => in_array( $tag, array( 'h2', 'h3', 'h4', 'p' ), true ) ? $tag : 'h3' );
	}
}
