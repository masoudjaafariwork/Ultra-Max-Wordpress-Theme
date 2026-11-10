<?php
/**
 * Icon box widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\IconBox as Component;
use UltraMax\Core\Widgets\Values;

/**
 * An icon or image with a title, a short text and an optional link.
 */
final class IconBox extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'icon-box';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Icon box', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-icon-box';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'icon-box' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Icon box', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'icon',
					'type'    => 'icon',
					'label'   => __( 'Icon', 'ultra-max-core' ),
					'default' => 'truck',
				),
				array(
					'id'    => 'image',
					'type'  => 'media',
					'label' => __( 'Or an image', 'ultra-max-core' ),
				),
				array(
					'id'      => 'title',
					'type'    => 'text',
					'label'   => __( 'Title', 'ultra-max-core' ),
					'default' => __( 'Fast delivery', 'ultra-max-core' ),
				),
				array(
					'id'      => 'text',
					'type'    => 'textarea',
					'label'   => __( 'Text', 'ultra-max-core' ),
					'default' => __( 'Orders placed before 2 pm leave the same day.', 'ultra-max-core' ),
				),
				array(
					'id'    => 'link',
					'type'  => 'url',
					'label' => __( 'Link', 'ultra-max-core' ),
				),
				array(
					'id'      => 'layout',
					'type'    => 'select',
					'label'   => __( 'Layout', 'ultra-max-core' ),
					'default' => 'stacked',
					'options' => array(
						'stacked' => __( 'Icon above', 'ultra-max-core' ),
						'inline'  => __( 'Icon beside', 'ultra-max-core' ),
					),
				),
				array(
					'id'      => 'align',
					'type'    => 'select',
					'label'   => __( 'Alignment', 'ultra-max-core' ),
					'default' => 'start',
					'options' => array(
						'start'  => __( 'Start', 'ultra-max-core' ),
						'center' => __( 'Centre', 'ultra-max-core' ),
					),
				),
				array(
					'id'      => 'framed',
					'type'    => 'switch',
					'label'   => __( 'In a frame', 'ultra-max-core' ),
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
		return Component::render(
			array(
				'title'  => $settings->string( 'title' ),
				'text'   => $settings->string( 'text' ),
				'icon'   => '' === $settings->string( 'icon' ) ? 'star' : $settings->string( 'icon' ),
				'image'  => $this->image( $settings->media( 'image' ), 'thumbnail' ),
				'href'   => $settings->href( 'link' ),
				'layout' => 'inline' === $settings->string( 'layout' ) ? 'inline' : 'stacked',
				'align'  => 'center' === $settings->string( 'align' ) ? 'center' : 'start',
				'framed' => $settings->bool( 'framed' ),
			)
		);
	}
}
