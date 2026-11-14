<?php
/**
 * Product code and categories widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\PostMeta;
use UltraMax\Core\Widgets\Values;

/**
 * Product code, categories and tags.
 */
final class ProductMeta extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-meta';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product code and categories', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-meta';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04' );
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		$items = array();
		if ( '' !== $product->get_sku() ) {
			$items[] = array(
				'icon'         => 'tag',
				'label'        => $product->get_sku(),
				'hidden_label' => __( 'Product code', 'ultra-max-core' ),
			);
		}
		foreach ( array(
			'product_cat' => 'folder',
			'product_tag' => 'tag',
		) as $taxonomy => $icon ) {
			$terms = get_the_terms( $product->get_id(), $taxonomy );
			foreach ( is_array( $terms ) ? $terms : array() as $term ) {
				$link    = get_term_link( $term );
				$items[] = array(
					'icon'  => $icon,
					'label' => $term->name,
					'href'  => is_string( $link ) ? $link : '',
				);
			}
		}
		return array() === $items ? Html::none() : PostMeta::render( array( 'items' => $items ) );
	}
}
