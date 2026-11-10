<?php
/**
 * Categories menu widget (HDR-07).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

use UltraMax\Core\Lib\UI\CategoryMenu as Component;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Image;
use UltraMax\Core\Store\Categories;
use UltraMax\Core\Widgets\Values;

/**
 * "Product categories" with a large panel: main categories with images on
 * one side and the pointed category's subcategories in columns on the
 * other, built from the store's product categories.
 */
final class CategoryMenu extends HeaderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'category-menu';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Categories menu', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-menu-bar';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HDR-07' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'category-menu' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'menu_section',
				'label'  => __( 'Categories', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'      => 'label',
						'type'    => 'text',
						'label'   => __( 'Button text', 'ultra-max-core' ),
						'default' => __( 'Product categories', 'ultra-max-core' ),
					),
					array(
						'id'       => 'categories',
						'type'     => 'terms',
						'taxonomy' => 'product_cat',
						'label'    => __( 'Main categories', 'ultra-max-core' ),
						'help'     => __( 'Leave empty to show all top categories.', 'ultra-max-core' ),
					),
					array(
						'id'      => 'promo',
						'type'    => 'switch',
						'label'   => __( 'Show the category image as a promotion', 'ultra-max-core' ),
						'default' => true,
					),
					array(
						'id'        => 'promo_title',
						'type'      => 'text',
						'label'     => __( 'Promotion title', 'ultra-max-core' ),
						'default'   => __( 'Up to 30% off', 'ultra-max-core' ),
						'condition' => array( 'promo' => 'yes' ),
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
		$categories = array();
		foreach ( Categories::tree( $settings->ids( 'categories' ) ) as $node ) {
			$columns = array();
			foreach ( $node['children'] as $child ) {
				$child    = Categories::node( $child );
				$children = array();
				foreach ( $child['children'] as $leaf ) {
					$leaf       = Categories::node( $leaf );
					$children[] = array(
						'label' => $leaf['name'],
						'href'  => $leaf['href'],
					);
				}
				$columns[] = array(
					'label'    => $child['name'],
					'href'     => $child['href'],
					'children' => $children,
				);
			}
			$image = Image::render(
				array(
					'id'   => $node['image'],
					'size' => 'thumbnail',
				)
			);
			$entry = array(
				'label'   => $node['name'],
				'href'    => $node['href'],
				'image'   => $image,
				'icon'    => 'folder',
				'columns' => $columns,
			);
			if ( $settings->bool( 'promo' ) && $node['image'] > 0 ) {
				$entry['promo'] = array(
					'image' => Image::render(
						array(
							'id'   => $node['image'],
							'size' => 'medium',
						)
					),
					'title' => $settings->string( 'promo_title' ),
					'text'  => $node['name'],
					'href'  => $node['href'],
				);
			}
			$categories[] = $entry;
		}
		return Component::render(
			array(
				'id'         => 'um-cats-' . substr( md5( (string) wp_json_encode( $settings->all() ) ), 0, 6 ),
				'label'      => $settings->string( 'label' ),
				/* translators: %s: category name. */
				'all_label'  => __( 'All products in %s', 'ultra-max-core' ),
				'categories' => $categories,
			)
		);
	}
}
