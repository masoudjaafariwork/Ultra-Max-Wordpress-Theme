<?php
/**
 * Content slider widget (HOME-12, nested slider).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Builder\Layouts;
use UltraMax\Core\Builder\Renderer;
use UltraMax\Core\Elementor\Data;
use UltraMax\Core\Lib\UI\Carousel;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Store\StoreWidget;
use UltraMax\Core\Widgets\Values;

/**
 * A slider whose slides are designed in Elementor: each slide is a
 * reusable block (Ultra Max > Layouts), so a slide can hold any widgets,
 * even other sliders. This works with Elementor Free, which has no nested
 * slider of its own.
 */
final class Slider extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'slider';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Content slider', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-slider-push';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'carousel' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Slides', 'ultra-max-core' ),
			array(
				array(
					'id'        => 'blocks',
					'type'      => 'posts',
					'post_type' => Layouts::POST_TYPE,
					'label'     => __( 'Slides (reusable blocks)', 'ultra-max-core' ),
					'help'      => __( 'Make each slide in Ultra Max > Layouts > Reusable blocks, then choose them here in order.', 'ultra-max-core' ),
				),
				array(
					'id'      => 'per_view',
					'type'    => 'number',
					'label'   => __( 'Slides per view on desktop', 'ultra-max-core' ),
					'default' => 1,
					'min'     => 1,
					'max'     => 4,
				),
				array(
					'id'      => 'autoplay',
					'type'    => 'number',
					'label'   => __( 'Seconds per slide', 'ultra-max-core' ),
					'help'    => __( '0 stops automatic sliding.', 'ultra-max-core' ),
					'default' => 0,
					'min'     => 0,
					'max'     => 30,
				),
				array(
					'id'      => 'dots',
					'type'    => 'switch',
					'label'   => __( 'Show dots', 'ultra-max-core' ),
					'default' => false,
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
		foreach ( $settings->ids( 'blocks' ) as $id ) {
			if ( 'block' !== Layouts::type( $id ) ) {
				continue;
			}
			Data::enqueue( $id );
			$content = Renderer::content( $id );
			if ( ! $content->is_empty() ) {
				$slides[] = $content;
			}
		}
		$per = max( 1, min( 4, $settings->int( 'per_view' ) ) );
		return Carousel::render(
			array_merge(
				StoreWidget::slider_labels(),
				array(
					'label'    => __( 'Slides', 'ultra-max-core' ),
					'slides'   => $slides,
					'per_view' => array(
						'mobile'  => 1,
						'tablet'  => min( 2, $per ),
						'desktop' => $per,
					),
					'gap'      => $per > 1 ? 'md' : 'none',
					'arrows'   => 'outside',
					'dots'     => $settings->bool( 'dots' ),
					'loop'     => count( $slides ) > $per,
					'autoplay' => $settings->int( 'autoplay' ) * 1000,
				)
			)
		);
	}
}
