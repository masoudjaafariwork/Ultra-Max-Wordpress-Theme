<?php
/**
 * Product tabs widget (HOME-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\SectionHead;
use UltraMax\Core\Lib\UI\Tabs;
use UltraMax\Core\Store\Catalog;
use UltraMax\Core\Widgets\Values;

/**
 * Several product lists under one title, switched with tabs, such as
 * "newest", "best selling" and "on sale".
 */
final class ProductTabs extends StoreWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-tabs';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product tabs', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-tabs';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-06' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'carousel', 'countdown', 'product-card' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		$tab = static fn ( string $label, string $source ): array => array(
			'label'  => $label,
			'source' => $source,
		);
		return array(
			array(
				'id'     => 'head_section',
				'label'  => __( 'Section', 'ultra-max-core' ),
				'fields' => $this->head_fields( __( 'Recommended for you', 'ultra-max-core' ) ),
			),
			array(
				'id'     => 'tabs_section',
				'label'  => __( 'Tabs', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'          => 'tabs',
						'type'        => 'repeater',
						'label'       => __( 'Tabs', 'ultra-max-core' ),
						'title_field' => 'label',
						'fields'      => array(
							array(
								'id'      => 'label',
								'type'    => 'text',
								'label'   => __( 'Tab name', 'ultra-max-core' ),
								'default' => __( 'Newest', 'ultra-max-core' ),
							),
							array(
								'id'      => 'source',
								'type'    => 'select',
								'label'   => __( 'Products', 'ultra-max-core' ),
								'options' => Catalog::sources(),
								'default' => 'recent',
							),
							array(
								'id'       => 'categories',
								'type'     => 'terms',
								'taxonomy' => 'product_cat',
								'label'    => __( 'Only from these categories', 'ultra-max-core' ),
							),
							array(
								'id'        => 'products',
								'type'      => 'posts',
								'post_type' => 'product',
								'label'     => __( 'Choose products', 'ultra-max-core' ),
							),
						),
						'default'     => array(
							$tab( __( 'Newest', 'ultra-max-core' ), 'recent' ),
							$tab( __( 'Best selling', 'ultra-max-core' ), 'best_selling' ),
							$tab( __( 'On sale', 'ultra-max-core' ), 'sale' ),
						),
					),
					array(
						'id'      => 'count',
						'type'    => 'number',
						'label'   => __( 'Products per tab', 'ultra-max-core' ),
						'default' => 10,
						'min'     => 1,
						'max'     => 24,
					),
					array(
						'id'      => 'in_stock',
						'type'    => 'switch',
						'label'   => __( 'Hide out of stock products', 'ultra-max-core' ),
						'default' => true,
					),
				),
			),
			array(
				'id'     => 'display_section',
				'label'  => __( 'Display', 'ultra-max-core' ),
				'fields' => ( new Products() )->display_fields(),
			),
		);
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$lister = new Products();
		$items  = array();
		foreach ( $settings->rows( 'tabs' ) as $tab ) {
			$source   = $tab->string( 'source' );
			$products = Catalog::products(
				array(
					'source'     => 'category' === $source ? 'recent' : $source,
					'count'      => max( 1, $settings->int( 'count' ) ),
					'categories' => $tab->ids( 'categories' ),
					'ids'        => $tab->ids( 'products' ),
					'in_stock'   => $settings->bool( 'in_stock' ),
				)
			);
			$listing  = $lister->listing( $settings, $products, $tab->string( 'label' ) );
			if ( ! $listing->is_empty() ) {
				$items[] = array(
					'label'   => $tab->string( 'label' ),
					'content' => $listing,
				);
			}
		}
		if ( array() === $items ) {
			return Html::none();
		}
		$head = SectionHead::render(
			array(
				'title' => $settings->string( 'title' ),
				'more'  => array(
					'label' => $settings->string( 'more_label' ),
					'href'  => $settings->href( 'more_link' ),
				),
				'class' => 'mb-0',
			)
		);
		$tabs = Tabs::render(
			array(
				'id'      => 'um-tabs-' . substr( md5( (string) wp_json_encode( $settings->all() ) ), 0, 8 ),
				'label'   => '' === $settings->string( 'title' ) ? __( 'Products', 'ultra-max-core' ) : $settings->string( 'title' ),
				'items'   => $items,
				'variant' => 'pill',
				'start'   => $head,
			)
		);
		return $this->frame( $settings, $tabs );
	}
}
