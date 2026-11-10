<?php
/**
 * Button widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Icon;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Widgets\Values;

/**
 * A button link in one of the store's button styles.
 */
final class Button extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'button';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Store button', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-button';
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Button', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'text',
					'type'    => 'text',
					'label'   => __( 'Text', 'ultra-max-core' ),
					'default' => __( 'Shop now', 'ultra-max-core' ),
				),
				array(
					'id'    => 'link',
					'type'  => 'url',
					'label' => __( 'Link', 'ultra-max-core' ),
				),
				array(
					'id'      => 'variant',
					'type'    => 'select',
					'label'   => __( 'Style', 'ultra-max-core' ),
					'default' => 'primary',
					'options' => array(
						'primary' => __( 'Brand colour', 'ultra-max-core' ),
						'accent'  => __( 'Accent colour', 'ultra-max-core' ),
						'outline' => __( 'Outlined', 'ultra-max-core' ),
						'soft'    => __( 'Soft', 'ultra-max-core' ),
						'neutral' => __( 'White', 'ultra-max-core' ),
						'ghost'   => __( 'Text only', 'ultra-max-core' ),
					),
				),
				array(
					'id'      => 'size',
					'type'    => 'select',
					'label'   => __( 'Size', 'ultra-max-core' ),
					'default' => 'md',
					'options' => array(
						'sm' => __( 'Small', 'ultra-max-core' ),
						'md' => __( 'Medium', 'ultra-max-core' ),
						'lg' => __( 'Large', 'ultra-max-core' ),
					),
				),
				array(
					'id'    => 'icon',
					'type'  => 'icon',
					'label' => __( 'Icon', 'ultra-max-core' ),
				),
				array(
					'id'      => 'block',
					'type'    => 'switch',
					'label'   => __( 'Full width', 'ultra-max-core' ),
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
		$variant = $settings->string( 'variant' );
		$size    = $settings->string( 'size' );
		$text    = $settings->string( 'text' );
		return UI::button(
			array(
				'children' => '' === $settings->string( 'icon' ) ? $text : array( Icon::render( $settings->string( 'icon' ), 'size-5' ), $text ),
				'href'     => '' === $settings->href( 'link' ) ? '#' : $settings->href( 'link' ),
				'variant'  => in_array( $variant, array( 'primary', 'outline', 'soft', 'ghost', 'neutral', 'accent' ), true ) ? $variant : 'primary',
				'size'     => in_array( $size, array( 'sm', 'md', 'lg' ), true ) ? $size : 'md',
				'block'    => $settings->bool( 'block' ),
			)
		);
	}
}
