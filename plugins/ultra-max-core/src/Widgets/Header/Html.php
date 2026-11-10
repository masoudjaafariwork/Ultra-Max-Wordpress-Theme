<?php
/**
 * HTML widget (HDR-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

use UltraMax\Core\Lib\UI\Html as Markup;
use UltraMax\Core\Widgets\Values;

/**
 * A piece of HTML in a header or footer, such as a badge code or a short
 * notice. Tags allowed in posts are kept; scripts are removed, so the
 * widget is safe for every editor.
 */
final class Html extends HeaderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'html';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'HTML', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-code';
	}

	/**
	 * No extra asset.
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
				'id'     => 'html_section',
				'label'  => __( 'HTML', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'    => 'code',
						'type'  => 'code',
						'label' => __( 'HTML code', 'ultra-max-core' ),
						'help'  => __( 'Scripts are removed. For tracking codes use Ultra Max > Settings > Custom code.', 'ultra-max-core' ),
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
	public function render( Values $settings ): Markup {
		return Markup::wp( wp_kses_post( $settings->string( 'code' ) ) );
	}
}
