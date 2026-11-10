<?php
/**
 * Testimonials widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Carousel;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Testimonial;
use UltraMax\Core\Store\Format;
use UltraMax\Core\Widgets\Store\StoreWidget;
use UltraMax\Core\Widgets\Values;

/**
 * Customers' words with name, photo and stars, in a slider.
 */
final class Testimonials extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'testimonials';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Customer reviews', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-testimonial-carousel';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'carousel', 'testimonials' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Reviews', 'ultra-max-core' ),
			array(
				array(
					'id'          => 'items',
					'type'        => 'repeater',
					'label'       => __( 'Reviews', 'ultra-max-core' ),
					'title_field' => 'name',
					'fields'      => array(
						array(
							'id'      => 'quote',
							'type'    => 'textarea',
							'label'   => __( 'Words', 'ultra-max-core' ),
							'default' => __( 'My order arrived quickly and well packed. I will buy again.', 'ultra-max-core' ),
						),
						array(
							'id'      => 'name',
							'type'    => 'text',
							'label'   => __( 'Name', 'ultra-max-core' ),
							'default' => __( 'A happy customer', 'ultra-max-core' ),
						),
						array(
							'id'    => 'role',
							'type'  => 'text',
							'label' => __( 'City or role', 'ultra-max-core' ),
						),
						array(
							'id'    => 'photo',
							'type'  => 'media',
							'label' => __( 'Photo', 'ultra-max-core' ),
						),
						array(
							'id'      => 'rating',
							'type'    => 'number',
							'label'   => __( 'Stars', 'ultra-max-core' ),
							'default' => 5,
							'min'     => 0,
							'max'     => 5,
						),
					),
					'default'     => array(
						array( 'name' => __( 'A happy customer', 'ultra-max-core' ) ),
						array( 'name' => __( 'A returning customer', 'ultra-max-core' ) ),
						array( 'name' => __( 'A new customer', 'ultra-max-core' ) ),
					),
				),
				array(
					'id'      => 'per_view',
					'type'    => 'number',
					'label'   => __( 'Reviews per row on desktop', 'ultra-max-core' ),
					'default' => 3,
					'min'     => 1,
					'max'     => 4,
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
		$slides = array();
		foreach ( $settings->rows( 'items' ) as $row ) {
			$rating   = max( 0, min( 5, $row->int( 'rating' ) ) );
			$slides[] = Testimonial::render(
				array(
					'quote'        => $row->string( 'quote' ),
					'name'         => $row->string( 'name' ),
					'role'         => $row->string( 'role' ),
					'photo'        => $this->image( $row->media( 'photo' ), 'thumbnail' ),
					'rating'       => $rating,
					/* translators: %s: number of stars. */
					'rating_label' => sprintf( __( '%s out of 5 stars', 'ultra-max-core' ), Format::number( $rating ) ),
				)
			);
		}
		return Carousel::render(
			array_merge(
				StoreWidget::slider_labels(),
				array(
					'label'    => __( 'Customer reviews', 'ultra-max-core' ),
					'slides'   => $slides,
					'per_view' => array(
						'mobile'  => 1,
						'tablet'  => 2,
						'desktop' => max( 1, min( 4, $settings->int( 'per_view' ) ) ),
					),
					'gap'      => 'md',
					'arrows'   => 'outside',
				)
			)
		);
	}
}
