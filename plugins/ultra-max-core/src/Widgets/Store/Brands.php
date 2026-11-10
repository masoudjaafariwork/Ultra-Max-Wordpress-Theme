<?php
/**
 * Shop by brand widget (HOME-07).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\BrandTiles;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * Brand tiles from WooCommerce's brands, with their logos, linking to each
 * brand's products. Chosen brands in their order, or the brands with the
 * most products.
 */
final class Brands extends StoreWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'brands';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Shop by brand', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-logo';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-07' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'brand-tiles' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'head_section',
				'label'  => __( 'Section', 'ultra-max-core' ),
				'fields' => $this->head_fields( __( 'Popular brands', 'ultra-max-core' ) ),
			),
			array(
				'id'     => 'brands_section',
				'label'  => __( 'Brands', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'       => 'brands',
						'type'     => 'terms',
						'taxonomy' => 'product_brand',
						'label'    => __( 'Brands', 'ultra-max-core' ),
						'help'     => __( 'Leave empty to show the brands with the most products.', 'ultra-max-core' ),
					),
					array(
						'id'      => 'count',
						'type'    => 'number',
						'label'   => __( 'How many', 'ultra-max-core' ),
						'default' => 8,
						'min'     => 1,
						'max'     => 30,
					),
				),
			),
		);
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		if ( ! taxonomy_exists( 'product_brand' ) ) {
			return Html::none();
		}
		$chosen = $settings->ids( 'brands' );
		$args   = array(
			'taxonomy'   => 'product_brand',
			'hide_empty' => array() === $chosen,
			'number'     => max( 1, $settings->int( 'count' ) ),
			'orderby'    => array() === $chosen ? 'count' : 'include',
			'order'      => 'DESC',
		);
		if ( array() !== $chosen ) {
			$args['include'] = $chosen;
		}
		$terms = get_terms( $args );
		$items = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}
			$link    = get_term_link( $term );
			$thumb   = get_term_meta( $term->term_id, 'thumbnail_id', true );
			$items[] = array(
				'name' => $term->name,
				'href' => is_string( $link ) ? $link : '#',
				'logo' => $this->image(
					array(
						'id'  => is_numeric( $thumb ) ? (int) $thumb : 0,
						'url' => '',
					),
					'thumbnail'
				),
			);
		}
		return $this->section(
			$settings,
			BrandTiles::render(
				array(
					'label' => __( 'Brands', 'ultra-max-core' ),
					'items' => $items,
				)
			)
		);
	}
}
