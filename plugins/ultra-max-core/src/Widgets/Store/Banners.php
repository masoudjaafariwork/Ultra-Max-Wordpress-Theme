<?php
/**
 * Banners widget (HOME-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\Banners as Component;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * One, two, three or four banners in a row, each with a photo (and its
 * phone version) or a gradient, text, a button and a product image.
 */
final class Banners extends StoreWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'banners';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Banners', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-banner';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-05' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'banners' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'banners_section',
				'label'  => __( 'Banners', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'      => 'columns',
						'type'    => 'select',
						'label'   => __( 'Banners per row', 'ultra-max-core' ),
						'default' => '4',
						'options' => array(
							'1' => '1',
							'2' => '2',
							'3' => '3',
							'4' => '4',
						),
					),
					array(
						'id'          => 'banners',
						'type'        => 'repeater',
						'label'       => __( 'Banners', 'ultra-max-core' ),
						'title_field' => 'title',
						'fields'      => array(
							array(
								'id'      => 'title',
								'type'    => 'text',
								'label'   => __( 'Title', 'ultra-max-core' ),
								'default' => __( 'Up to 12% off', 'ultra-max-core' ),
							),
							array(
								'id'      => 'kicker',
								'type'    => 'text',
								'label'   => __( 'Small line', 'ultra-max-core' ),
								'default' => __( 'Work laptops', 'ultra-max-core' ),
							),
							array(
								'id'    => 'button',
								'type'  => 'text',
								'label' => __( 'Button text', 'ultra-max-core' ),
							),
							array(
								'id'    => 'link',
								'type'  => 'url',
								'label' => __( 'Link', 'ultra-max-core' ),
							),
							array(
								'id'    => 'photo',
								'type'  => 'media',
								'label' => __( 'Background photo', 'ultra-max-core' ),
							),
							array(
								'id'    => 'photo_mobile',
								'type'  => 'media',
								'label' => __( 'Background photo for phones', 'ultra-max-core' ),
							),
							array(
								'id'      => 'from',
								'type'    => 'color',
								'label'   => __( 'Colour 1', 'ultra-max-core' ),
								'default' => '#e1e0fe',
							),
							array(
								'id'      => 'to',
								'type'    => 'color',
								'label'   => __( 'Colour 2', 'ultra-max-core' ),
								'default' => '#c4c1fb',
							),
							array(
								'id'    => 'image',
								'type'  => 'media',
								'label' => __( 'Product image', 'ultra-max-core' ),
							),
							array(
								'id'      => 'light',
								'type'    => 'switch',
								'label'   => __( 'White text', 'ultra-max-core' ),
								'help'    => __( 'For dark backgrounds.', 'ultra-max-core' ),
								'default' => false,
							),
						),
						'default'     => array(
							array( 'title' => __( 'Up to 12% off', 'ultra-max-core' ) ),
							array(
								'title' => __( 'Buy in instalments', 'ultra-max-core' ),
								'from'  => '#ffe8c2',
								'to'    => '#ffb347',
							),
						),
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
		$columns = max( 1, min( 4, $settings->int( 'columns' ) ) );
		$banners = array();
		foreach ( $settings->rows( 'banners' ) as $row ) {
			$banner = array(
				'href'   => '' === $row->href( 'link' ) ? '#' : $row->href( 'link' ),
				'title'  => $row->string( 'title' ),
				'kicker' => $row->string( 'kicker' ),
				'button' => $row->string( 'button' ),
				'from'   => $row->string( 'from' ),
				'to'     => $row->string( 'to' ),
				'image'  => $this->image( $row->media( 'image' ) ),
				'dark'   => ! $row->bool( 'light' ),
			);
			$photo  = $row->media( 'photo' );
			if ( '' !== $photo['url'] ) {
				$banner['photo'] = array(
					'url'    => $photo['url'],
					'mobile' => $row->media( 'photo_mobile' )['url'],
				);
			}
			$banners[] = $banner;
		}
		return Component::render(
			array(
				'banners' => $banners,
				'columns' => match ( $columns ) {
					1 => 1,
					2 => 2,
					3 => 3,
					default => 4,
				},
			)
		);
	}
}
