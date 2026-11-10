<?php
/**
 * Image hotspots widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Hotspots as Component;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Store\Format;
use UltraMax\Core\Widgets\Values;

/**
 * Numbered points on an image; each opens a small card, optionally for a
 * product with its price and link.
 */
final class Hotspots extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'hotspots';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Image hotspots', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-image-hotspot';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'hotspots' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Image and points', 'ultra-max-core' ),
			array(
				array(
					'id'    => 'image',
					'type'  => 'media',
					'label' => __( 'Image', 'ultra-max-core' ),
				),
				array(
					'id'          => 'spots',
					'type'        => 'repeater',
					'label'       => __( 'Points', 'ultra-max-core' ),
					'title_field' => 'title',
					'fields'      => array(
						array(
							'id'      => 'x',
							'type'    => 'number',
							'label'   => __( 'From the left (percent)', 'ultra-max-core' ),
							'default' => 50,
							'min'     => 0,
							'max'     => 100,
						),
						array(
							'id'      => 'y',
							'type'    => 'number',
							'label'   => __( 'From the top (percent)', 'ultra-max-core' ),
							'default' => 50,
							'min'     => 0,
							'max'     => 100,
						),
						array(
							'id'        => 'product',
							'type'      => 'posts',
							'post_type' => 'product',
							'multiple'  => false,
							'label'     => __( 'Product', 'ultra-max-core' ),
							'help'      => __( 'Its name, price and link fill the card.', 'ultra-max-core' ),
						),
						array(
							'id'    => 'title',
							'type'  => 'text',
							'label' => __( 'Or a title', 'ultra-max-core' ),
						),
						array(
							'id'    => 'text',
							'type'  => 'text',
							'label' => __( 'Text', 'ultra-max-core' ),
						),
						array(
							'id'    => 'link',
							'type'  => 'url',
							'label' => __( 'Link', 'ultra-max-core' ),
						),
					),
					'default'     => array(),
				),
			)
		);
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$image = $this->image( $settings->media( 'image' ), 'large' );
		if ( $image->is_empty() ) {
			return Html::none();
		}
		$spots = array();
		foreach ( $settings->rows( 'spots' ) as $row ) {
			$spot     = array(
				'x'     => $row->float( 'x' ),
				'y'     => $row->float( 'y' ),
				'title' => $row->string( 'title' ),
				'text'  => $row->string( 'text' ),
				'href'  => $row->href( 'link' ),
			);
			$products = $row->ids( 'product' );
			$product  = array() !== $products && function_exists( 'wc_get_product' ) ? wc_get_product( $products[0] ) : null;
			if ( $product instanceof \WC_Product ) {
				$spot['title']      = '' === $spot['title'] ? wp_strip_all_tags( $product->get_name() ) : $spot['title'];
				$spot['href']       = (string) $product->get_permalink();
				$spot['link_label'] = __( 'View product', 'ultra-max-core' );
				$price              = $product->get_price();
				if ( is_numeric( $price ) ) {
					$spot['price'] = Format::money( (float) $price ) . ' ' . Format::currency();
				}
			}
			if ( '' !== $spot['title'] ) {
				$spots[] = $spot;
			}
		}
		return Component::render(
			array(
				'image' => $image,
				'label' => __( 'Image with points', 'ultra-max-core' ),
				'spots' => $spots,
			)
		);
	}
}
