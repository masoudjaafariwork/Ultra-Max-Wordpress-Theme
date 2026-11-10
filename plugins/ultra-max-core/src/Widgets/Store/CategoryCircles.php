<?php
/**
 * Category circles widget (HOME-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\CategoryCircles as Component;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Store\Format;
use UltraMax\Core\Widgets\Values;

/**
 * Round tiles for product categories, with the category's image. Shows
 * chosen categories in their order, or the top-level ones.
 */
final class CategoryCircles extends StoreWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'category-circles';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Category circles', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-categories';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-04' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'category-circles' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'head_section',
				'label'  => __( 'Section', 'ultra-max-core' ),
				'fields' => $this->head_fields( __( 'Shop by category', 'ultra-max-core' ) ),
			),
			array(
				'id'     => 'items_section',
				'label'  => __( 'Categories', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'       => 'categories',
						'type'     => 'terms',
						'taxonomy' => 'product_cat',
						'label'    => __( 'Categories', 'ultra-max-core' ),
						'help'     => __( 'Leave empty to show the main categories.', 'ultra-max-core' ),
					),
					array(
						'id'      => 'count',
						'type'    => 'number',
						'label'   => __( 'How many', 'ultra-max-core' ),
						'default' => 8,
						'min'     => 1,
						'max'     => 24,
					),
					array(
						'id'      => 'layout',
						'type'    => 'select',
						'label'   => __( 'Layout', 'ultra-max-core' ),
						'default' => 'grid',
						'options' => array(
							'grid' => __( 'Grid', 'ultra-max-core' ),
							'row'  => __( 'One scrolling row', 'ultra-max-core' ),
						),
					),
					array(
						'id'      => 'shape',
						'type'    => 'select',
						'label'   => __( 'Shape', 'ultra-max-core' ),
						'default' => 'circle',
						'options' => array(
							'circle'  => __( 'Circle', 'ultra-max-core' ),
							'rounded' => __( 'Rounded square', 'ultra-max-core' ),
						),
					),
					array(
						'id'      => 'show_count',
						'type'    => 'switch',
						'label'   => __( 'Show the number of products', 'ultra-max-core' ),
						'default' => false,
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
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return Html::none();
		}
		$chosen = $settings->ids( 'categories' );
		$args   = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'number'     => max( 1, $settings->int( 'count' ) ),
		);
		if ( array() !== $chosen ) {
			$args['include'] = $chosen;
			$args['orderby'] = 'include';
		} else {
			$args['parent']  = 0;
			$args['orderby'] = 'menu_order';
			$uncategorized   = get_option( 'default_product_cat', 0 );
			$args['exclude'] = array( is_numeric( $uncategorized ) ? (int) $uncategorized : 0 );
		}
		$terms = get_terms( $args );
		$items = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}
			$link  = get_term_link( $term );
			$thumb = get_term_meta( $term->term_id, 'thumbnail_id', true );
			$item  = array(
				'label' => $term->name,
				'href'  => is_string( $link ) ? $link : '#',
				'image' => $this->image(
					array(
						'id'  => is_numeric( $thumb ) ? (int) $thumb : 0,
						'url' => '',
					),
					'thumbnail'
				),
			);
			if ( $settings->bool( 'show_count' ) ) {
				/* translators: %s: number of products. */
				$item['count'] = sprintf( _n( '%s product', '%s products', $term->count, 'ultra-max-core' ), Format::number( $term->count ) );
			}
			$items[] = $item;
		}
		$shape = 'rounded' === $settings->string( 'shape' ) ? 'rounded' : 'circle';
		return $this->section(
			$settings,
			Component::render(
				array(
					'label'  => '' === $settings->string( 'title' ) ? __( 'Categories', 'ultra-max-core' ) : $settings->string( 'title' ),
					'items'  => $items,
					'layout' => 'row' === $settings->string( 'layout' ) ? 'row' : 'grid',
					'shape'  => $shape,
				)
			)
		);
	}
}
