<?php
/**
 * Text widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Widgets\Values;

/**
 * Rich text in the store's reading style: paragraphs, lists, links and
 * small headings, with the same typography as posts.
 */
final class Text extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'text';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Store text', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-text';
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Text', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'content',
					'type'    => 'wysiwyg',
					'label'   => __( 'Text', 'ultra-max-core' ),
					'default' => '<p>' . __( 'Write about your store here.', 'ultra-max-core' ) . '</p>',
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
		return UI::prose( array( 'children' => Html::wp( wpautop( wp_kses_post( $settings->string( 'content' ) ) ) ) ) );
	}
}
