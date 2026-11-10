<?php
/**
 * Products widget (HOME-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\Carousel;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\ProductGrid;
use UltraMax\Core\Store\Cards;
use UltraMax\Core\Store\Catalog;
use UltraMax\Core\Widgets\Values;

/**
 * Products from any source (newest, best selling, on sale, most viewed,
 * featured, top rated, categories or hand-picked) as a grid or a slider,
 * in any card design.
 */
final class Products extends StoreWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'products';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Products', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-products';
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
	 * Keywords.
	 *
	 * @return list<string>
	 */
	public function keywords(): array {
		return array( 'products', 'grid', 'slider', 'carousel', 'woocommerce' );
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
	 * Display controls shared with product tabs.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function display_fields(): array {
		$columns = static fn ( string $id, string $label, int $initial, int $min, int $max ): array => array(
			'id'      => $id,
			'type'    => 'number',
			'label'   => $label,
			'default' => $initial,
			'min'     => $min,
			'max'     => $max,
		);
		return array(
			array(
				'id'      => 'layout',
				'type'    => 'select',
				'label'   => __( 'Layout', 'ultra-max-core' ),
				'default' => 'carousel',
				'options' => array(
					'carousel' => __( 'Slider', 'ultra-max-core' ),
					'grid'     => __( 'Grid', 'ultra-max-core' ),
				),
			),
			array(
				'id'      => 'design',
				'type'    => 'select',
				'label'   => __( 'Card design', 'ultra-max-core' ),
				'default' => 'hover',
				'options' => $this->designs(),
			),
			$columns( 'columns', __( 'Columns on desktop', 'ultra-max-core' ), 5, 3, 6 ),
			$columns( 'columns_tablet', __( 'Columns on tablet', 'ultra-max-core' ), 3, 2, 4 ),
			$columns( 'columns_mobile', __( 'Columns on mobile', 'ultra-max-core' ), 2, 1, 3 ),
		);
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'head_section',
				'label'  => __( 'Section', 'ultra-max-core' ),
				'fields' => $this->head_fields( __( 'Recommended for you', 'ultra-max-core' ) ),
			),
			array(
				'id'     => 'query_section',
				'label'  => __( 'Products', 'ultra-max-core' ),
				'fields' => $this->query_fields(),
			),
			array(
				'id'     => 'display_section',
				'label'  => __( 'Display', 'ultra-max-core' ),
				'fields' => $this->display_fields(),
			),
		);
	}

	/**
	 * The products as a grid or a slider.
	 *
	 * @param Values $settings Settings.
	 * @param array  $products Products.
	 * @param string $label    Accessible name of the list.
	 * @phpstan-param list<\WC_Product> $products
	 */
	public function listing( Values $settings, array $products, string $label ): Html {
		$design = $settings->string( 'design' );
		$design = in_array( $design, array( 'classic', 'hover', 'deal', 'mini' ), true ) ? $design : 'hover';
		$cards  = array();
		foreach ( $products as $product ) {
			$cards[] = Cards::card( $product, $design );
		}
		if ( array() === $cards ) {
			return Html::none();
		}
		$per = array(
			'mobile'  => $settings->int( 'columns_mobile' ),
			'tablet'  => $settings->int( 'columns_tablet' ),
			'desktop' => $settings->int( 'columns' ),
		);
		if ( 'grid' === $settings->string( 'layout' ) ) {
			return ProductGrid::render(
				array(
					'label'   => $label,
					'items'   => array_map( static fn ( Html $card ): array => array( 'card' => $card ), $cards ),
					'columns' => $per,
					'lined'   => 'classic' === $design,
				)
			);
		}
		return Carousel::render(
			array_merge(
				self::slider_labels(),
				array(
					'label'    => $label,
					'slides'   => $cards,
					'per_view' => array(
						'mobile'  => min( 4, max( 1, $per['mobile'] ) ),
						'tablet'  => $per['tablet'],
						'desktop' => $per['desktop'],
					),
					'gap'      => 'sm',
					'arrows'   => 'outside',
				)
			)
		);
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$label = '' === $settings->string( 'title' ) ? __( 'Products', 'ultra-max-core' ) : $settings->string( 'title' );
		return $this->section( $settings, $this->listing( $settings, Catalog::products( $this->query( $settings ) ), $label ) );
	}
}
