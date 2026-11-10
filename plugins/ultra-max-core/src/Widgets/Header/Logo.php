<?php
/**
 * Logo widget (HDR-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Widgets\Values;

/**
 * The store logo: the site's logo from Appearance > Customize, another
 * image, or the brand mark with the store's name.
 */
final class Logo extends HeaderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'logo';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Logo', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-site-logo';
	}

	/**
	 * No extra asset: the logo is part of the main stylesheet.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array();
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'logo_section',
				'label'  => __( 'Logo', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'    => 'image',
						'type'  => 'media',
						'label' => __( 'Another logo image', 'ultra-max-core' ),
						'help'  => __( 'Leave empty to use the site logo.', 'ultra-max-core' ),
					),
					array(
						'id'      => 'size',
						'type'    => 'select',
						'label'   => __( 'Size', 'ultra-max-core' ),
						'default' => 'md',
						'options' => array(
							'md' => __( 'Medium', 'ultra-max-core' ),
							'lg' => __( 'Large', 'ultra-max-core' ),
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
		$image = $settings->media( 'image' )['id'];
		if ( $image <= 0 ) {
			$mod   = get_theme_mod( 'custom_logo', 0 );
			$image = is_numeric( $mod ) ? (int) $mod : 0;
		}
		return UI::logo(
			array(
				'name'     => get_bloginfo( 'name' ),
				'href'     => home_url( '/' ),
				'image_id' => $image,
				'size'     => 'lg' === $settings->string( 'size' ) ? 'lg' : 'md',
			)
		);
	}
}
