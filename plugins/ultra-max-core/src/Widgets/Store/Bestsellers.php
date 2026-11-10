<?php
/**
 * Numbered best sellers widget (HOME-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\RankedList;
use UltraMax\Core\Store\Catalog;
use UltraMax\Core\Store\Format;
use UltraMax\Core\Widgets\Values;

/**
 * Best selling products as a numbered list: image, rank and name.
 */
final class Bestsellers extends StoreWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'bestsellers';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Numbered best sellers', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-number-field';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-08' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'ranked-list' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		$head               = $this->head_fields( __( 'Best selling products', 'ultra-max-core' ) );
		$head[1]['default'] = 'fire';
		return array(
			array(
				'id'     => 'head_section',
				'label'  => __( 'Section', 'ultra-max-core' ),
				'fields' => $head,
			),
			array(
				'id'     => 'query_section',
				'label'  => __( 'Products', 'ultra-max-core' ),
				'fields' => $this->query_fields( 'best_selling', 9 ),
			),
		);
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$items = array();
		foreach ( Catalog::products( $this->query( $settings ) ) as $index => $product ) {
			$image   = $product->get_image( 'woocommerce_gallery_thumbnail', array( 'loading' => 'lazy' ) );
			$items[] = array(
				'title' => wp_strip_all_tags( $product->get_name() ),
				'href'  => (string) $product->get_permalink(),
				'image' => Html::wp( is_string( $image ) ? $image : '' ),
				'rank'  => Format::digits( (string) ( $index + 1 ) ),
			);
		}
		return $this->section(
			$settings,
			RankedList::render(
				array(
					'label' => '' === $settings->string( 'title' ) ? __( 'Best selling products', 'ultra-max-core' ) : $settings->string( 'title' ),
					'items' => $items,
				)
			)
		);
	}
}
