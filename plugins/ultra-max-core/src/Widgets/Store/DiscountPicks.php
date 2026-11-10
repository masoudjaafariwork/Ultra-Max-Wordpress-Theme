<?php
/**
 * Discount picks with a category filter (HOME-09).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\FilterChips;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\ProductGrid;
use UltraMax\Core\Store\Cards;
use UltraMax\Core\Store\Catalog;
use UltraMax\Core\Widgets\Values;

/**
 * Products on sale in a lined grid, with category chips above that filter
 * the grid in place, without reloading the page.
 */
final class DiscountPicks extends StoreWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'discount-picks';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Discount picks', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-filter';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-09' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'countdown', 'product-card', 'discount-picks' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'head_section',
				'label'  => __( 'Section', 'ultra-max-core' ),
				'fields' => $this->head_fields( __( 'Discount picks', 'ultra-max-core' ) ),
			),
			array(
				'id'     => 'picks_section',
				'label'  => __( 'Products', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'       => 'categories',
						'type'     => 'terms',
						'taxonomy' => 'product_cat',
						'label'    => __( 'Filter categories', 'ultra-max-core' ),
						'help'     => __( 'Each category becomes a chip above the products.', 'ultra-max-core' ),
					),
					array(
						'id'      => 'all_label',
						'type'    => 'text',
						'label'   => __( '"All" chip text', 'ultra-max-core' ),
						'default' => __( 'All', 'ultra-max-core' ),
					),
					array(
						'id'      => 'count',
						'type'    => 'number',
						'label'   => __( 'Number of products', 'ultra-max-core' ),
						'default' => 12,
						'min'     => 2,
						'max'     => 48,
					),
					array(
						'id'      => 'columns',
						'type'    => 'number',
						'label'   => __( 'Columns on desktop', 'ultra-max-core' ),
						'default' => 4,
						'min'     => 3,
						'max'     => 6,
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
		$categories = $settings->ids( 'categories' );
		$products   = Catalog::products(
			array(
				'source'     => 'sale',
				'count'      => max( 2, $settings->int( 'count' ) ),
				'categories' => $categories,
				'in_stock'   => true,
			)
		);
		$items      = array();
		foreach ( $products as $product ) {
			$keys = array();
			foreach ( $product->get_category_ids() as $term_id ) {
				$keys[] = 'c' . (int) $term_id;
				foreach ( get_ancestors( (int) $term_id, 'product_cat', 'taxonomy' ) as $ancestor ) {
					$keys[] = 'c' . (int) $ancestor;
				}
			}
			$items[] = array(
				'card' => Cards::card( $product, 'classic' ),
				'keys' => array_values( array_unique( $keys ) ),
			);
		}
		$id    = 'um-picks-' . substr( md5( (string) wp_json_encode( $settings->all() ) ), 0, 8 );
		$chips = array(
			array(
				'key'   => '',
				'label' => $settings->string( 'all_label' ),
			),
		);
		foreach ( $categories as $term_id ) {
			$term = get_term( $term_id, 'product_cat' );
			if ( $term instanceof \WP_Term ) {
				$chips[] = array(
					'key'   => 'c' . $term->term_id,
					'label' => $term->name,
				);
			}
		}
		$label = '' === $settings->string( 'title' ) ? __( 'Discount picks', 'ultra-max-core' ) : $settings->string( 'title' );
		return $this->section(
			$settings,
			ProductGrid::render(
				array(
					'id'      => $id,
					'label'   => $label,
					'items'   => $items,
					'lined'   => true,
					'columns' => array(
						'mobile'  => 2,
						'tablet'  => 3,
						'desktop' => $settings->int( 'columns' ),
					),
				)
			),
			FilterChips::render(
				array(
					'target' => $id,
					'label'  => __( 'Filter by category', 'ultra-max-core' ),
					'chips'  => $chips,
				)
			)
		);
	}
}
