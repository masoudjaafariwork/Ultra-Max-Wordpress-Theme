<?php
/**
 * Copyright bar widget (HDR-11).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Footer;

use UltraMax\Core\Lib\UI\FooterBar;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Store\Format;
use UltraMax\Core\Widgets\Values;
use UltraMax\Core\Widgets\Widget;

/**
 * The bottom line of the footer. {year} becomes the current Jalali year
 * and {site} the store's name.
 */
final class Copyright extends Widget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'copyright';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Copyright line', 'ultra-max-core' );
	}

	/**
	 * Panel group.
	 */
	public function group(): string {
		return 'footer';
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-footer';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HDR-11' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'footer' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'copyright_section',
				'label'  => __( 'Copyright line', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'      => 'text',
						'type'    => 'text',
						'label'   => __( 'Text', 'ultra-max-core' ),
						'help'    => __( '{year} is replaced with the current year and {site} with the store name.', 'ultra-max-core' ),
						'default' => __( 'All rights reserved for {site}. {year}', 'ultra-max-core' ),
					),
					array(
						'id'      => 'top',
						'type'    => 'switch',
						'label'   => __( 'Show "back to top"', 'ultra-max-core' ),
						'default' => true,
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
		$text = strtr(
			$settings->string( 'text' ),
			array(
				'{year}' => Format::date( time(), 'Y' ),
				'{site}' => get_bloginfo( 'name' ),
			)
		);
		return FooterBar::render(
			array(
				'text' => $text,
				'top'  => $settings->bool( 'top' ) ? __( 'Back to top', 'ultra-max-core' ) : '',
			)
		);
	}
}
