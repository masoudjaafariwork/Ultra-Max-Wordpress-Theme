<?php
/**
 * Gallery widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Gallery as Component;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * Images in a grid that open large, one after another.
 */
final class Gallery extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'gallery';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Gallery', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-gallery-grid';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'gallery' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Images', 'ultra-max-core' ),
			array(
				array(
					'id'          => 'images',
					'type'        => 'repeater',
					'label'       => __( 'Images', 'ultra-max-core' ),
					'title_field' => 'caption',
					'fields'      => array(
						array(
							'id'    => 'image',
							'type'  => 'media',
							'label' => __( 'Image', 'ultra-max-core' ),
						),
						array(
							'id'    => 'caption',
							'type'  => 'text',
							'label' => __( 'Caption', 'ultra-max-core' ),
						),
					),
					'default'     => array(),
				),
				array(
					'id'      => 'columns',
					'type'    => 'select',
					'label'   => __( 'Columns on desktop', 'ultra-max-core' ),
					'default' => '4',
					'options' => array(
						'2' => '2',
						'3' => '3',
						'4' => '4',
						'5' => '5',
						'6' => '6',
					),
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
		$images = array();
		foreach ( $settings->rows( 'images' ) as $row ) {
			$media = $row->media( 'image' );
			$full  = $media['id'] > 0 ? wp_get_attachment_image_url( $media['id'], 'large' ) : $media['url'];
			if ( ! is_string( $full ) || '' === $full ) {
				continue;
			}
			$images[] = array(
				'thumb'   => $this->image( $media, 'medium' ),
				'full'    => $full,
				'caption' => $row->string( 'caption' ),
			);
		}
		$columns = $settings->int( 'columns' );
		return Component::render(
			array(
				'label'    => __( 'Gallery', 'ultra-max-core' ),
				'images'   => $images,
				'columns'  => match ( $columns ) {
					2 => 2,
					3 => 3,
					5 => 5,
					6 => 6,
					default => 4,
				},
				'close'    => __( 'Close', 'ultra-max-core' ),
				'previous' => __( 'Previous image', 'ultra-max-core' ),
				'next'     => __( 'Next image', 'ultra-max-core' ),
			)
		);
	}
}
