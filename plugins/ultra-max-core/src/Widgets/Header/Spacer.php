<?php
/**
 * Spacer widget (HDR-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Separator;
use UltraMax\Core\Widgets\Values;

/**
 * Empty space between header items: a fixed width, or all the free space
 * so the following items move to the far end of the row.
 */
final class Spacer extends HeaderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'spacer';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Header spacer', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-spacer';
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'space_section',
				'label'  => __( 'Space', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'      => 'grow',
						'type'    => 'switch',
						'label'   => __( 'Take all free space', 'ultra-max-core' ),
						'default' => true,
					),
					array(
						'id'        => 'width',
						'type'      => 'number',
						'label'     => __( 'Width in pixels', 'ultra-max-core' ),
						'default'   => 24,
						'min'       => 0,
						'max'       => 400,
						'condition' => array( 'grow!' => 'yes' ),
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
		return $settings->bool( 'grow' )
			? Separator::render( array( 'kind' => 'grow' ) )
			: Separator::render(
				array(
					'kind' => 'space',
					'size' => $settings->int( 'width' ),
				)
			);
	}
}
