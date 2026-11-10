<?php
/**
 * Before and after widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\ImageCompare as Component;
use UltraMax\Core\Widgets\Values;

/**
 * Two images with a handle to slide between them.
 */
final class ImageCompare extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'image-compare';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Before and after', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-image-before-after';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'image-compare' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Images', 'ultra-max-core' ),
			array(
				array(
					'id'    => 'before',
					'type'  => 'media',
					'label' => __( 'Before', 'ultra-max-core' ),
				),
				array(
					'id'    => 'after',
					'type'  => 'media',
					'label' => __( 'After', 'ultra-max-core' ),
				),
				array(
					'id'      => 'before_label',
					'type'    => 'text',
					'label'   => __( 'Before label', 'ultra-max-core' ),
					'default' => __( 'Before', 'ultra-max-core' ),
				),
				array(
					'id'      => 'after_label',
					'type'    => 'text',
					'label'   => __( 'After label', 'ultra-max-core' ),
					'default' => __( 'After', 'ultra-max-core' ),
				),
				array(
					'id'      => 'start',
					'type'    => 'number',
					'label'   => __( 'Handle position (percent)', 'ultra-max-core' ),
					'default' => 50,
					'min'     => 0,
					'max'     => 100,
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
		$before = $this->image( $settings->media( 'before' ), 'large' );
		$after  = $this->image( $settings->media( 'after' ), 'large' );
		if ( $before->is_empty() || $after->is_empty() ) {
			return Html::none();
		}
		return Component::render(
			array(
				'before'       => $before,
				'after'        => $after,
				'label'        => __( 'Slide to compare the images', 'ultra-max-core' ),
				'before_label' => $settings->string( 'before_label' ),
				'after_label'  => $settings->string( 'after_label' ),
				'start'        => $settings->int( 'start' ),
			)
		);
	}
}
