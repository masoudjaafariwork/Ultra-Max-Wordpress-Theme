<?php
/**
 * Hero slider widget (HOME-01).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\HeroSlider as Component;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * The big slider at the top of the home page, with touch support and a
 * separate photo for phones.
 */
final class HeroSlider extends StoreWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'hero-slider';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Hero slider', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-slides';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-01' );
	}

	/**
	 * Keywords.
	 *
	 * @return list<string>
	 */
	public function keywords(): array {
		return array( 'slider', 'hero', 'banner', 'carousel' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'carousel', 'hero-slider' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'slides_section',
				'label'  => __( 'Slides', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'          => 'slides',
						'type'        => 'repeater',
						'label'       => __( 'Slides', 'ultra-max-core' ),
						'title_field' => 'title',
						'fields'      => array(
							array(
								'id'      => 'title',
								'type'    => 'text',
								'label'   => __( 'Title', 'ultra-max-core' ),
								'default' => __( 'New phones with up to 25% off', 'ultra-max-core' ),
							),
							array(
								'id'      => 'kicker',
								'type'    => 'text',
								'label'   => __( 'Small label', 'ultra-max-core' ),
								'default' => __( 'Autumn festival', 'ultra-max-core' ),
							),
							array(
								'id'    => 'text',
								'type'  => 'text',
								'label' => __( 'Text', 'ultra-max-core' ),
							),
							array(
								'id'      => 'button',
								'type'    => 'text',
								'label'   => __( 'Button text', 'ultra-max-core' ),
								'default' => __( 'Shop now', 'ultra-max-core' ),
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
								'help'  => __( 'Optional. Without a photo the two colours below make the background.', 'ultra-max-core' ),
							),
							array(
								'id'    => 'photo_mobile',
								'type'  => 'media',
								'label' => __( 'Background photo for phones', 'ultra-max-core' ),
								'help'  => __( 'Optional. A taller photo that phones download instead of the large one.', 'ultra-max-core' ),
							),
							array(
								'id'      => 'from',
								'type'    => 'color',
								'label'   => __( 'Colour 1', 'ultra-max-core' ),
								'default' => '#3d34ad',
							),
							array(
								'id'      => 'to',
								'type'    => 'color',
								'label'   => __( 'Colour 2', 'ultra-max-core' ),
								'default' => '#7f78f0',
							),
							array(
								'id'    => 'image',
								'type'  => 'media',
								'label' => __( 'Product image', 'ultra-max-core' ),
							),
							array(
								'id'      => 'dark',
								'type'    => 'switch',
								'label'   => __( 'Dark text', 'ultra-max-core' ),
								'help'    => __( 'For light backgrounds.', 'ultra-max-core' ),
								'default' => false,
							),
						),
						'default'     => array(
							array( 'title' => __( 'New phones with up to 25% off', 'ultra-max-core' ) ),
							array(
								'title' => __( 'Headphones and speakers', 'ultra-max-core' ),
								'from'  => '#0a4f51',
								'to'    => '#35aeb0',
							),
						),
					),
				),
			),
			array(
				'id'     => 'options_section',
				'label'  => __( 'Options', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'      => 'height',
						'type'    => 'select',
						'label'   => __( 'Height', 'ultra-max-core' ),
						'default' => 'md',
						'options' => array(
							'sm' => __( 'Short', 'ultra-max-core' ),
							'md' => __( 'Medium', 'ultra-max-core' ),
							'lg' => __( 'Tall', 'ultra-max-core' ),
						),
					),
					array(
						'id'      => 'autoplay',
						'type'    => 'number',
						'label'   => __( 'Seconds per slide', 'ultra-max-core' ),
						'help'    => __( '0 stops automatic sliding.', 'ultra-max-core' ),
						'default' => 5,
						'min'     => 0,
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
		$slides = array();
		foreach ( $settings->rows( 'slides' ) as $index => $row ) {
			$slide = array(
				'href'      => '' === $row->href( 'link' ) ? '#' : $row->href( 'link' ),
				'title'     => $row->string( 'title' ),
				'kicker'    => $row->string( 'kicker' ),
				'text'      => $row->string( 'text' ),
				'button'    => $row->string( 'button' ),
				'from'      => $row->string( 'from' ),
				'to'        => $row->string( 'to' ),
				'image'     => $this->image( $row->media( 'image' ), 'large', 0 === $index ),
				'dark_text' => $row->bool( 'dark' ),
			);
			$photo = $row->media( 'photo' );
			if ( '' !== $photo['url'] ) {
				$mobile              = $row->media( 'photo_mobile' );
				$slide['background'] = array(
					'url'    => $photo['url'],
					'mobile' => $mobile['url'],
				);
			}
			$slides[] = $slide;
		}
		if ( array() === $slides ) {
			return Html::none();
		}
		$height = $settings->string( 'height' );
		return Component::render(
			array_merge(
				self::slider_labels(),
				array(
					'label'    => __( 'Featured offers', 'ultra-max-core' ),
					'slides'   => $slides,
					'autoplay' => $settings->int( 'autoplay' ) * 1000,
					'height'   => in_array( $height, array( 'sm', 'md', 'lg' ), true ) ? $height : 'md',
				)
			)
		);
	}
}
