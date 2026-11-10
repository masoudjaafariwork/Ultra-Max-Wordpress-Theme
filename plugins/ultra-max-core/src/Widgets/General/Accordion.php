<?php
/**
 * Accordion widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Widgets\Values;

/**
 * Questions and answers that fold open, such as a FAQ.
 */
final class Accordion extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'accordion';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Store accordion', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-accordion';
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Items', 'ultra-max-core' ),
			array(
				array(
					'id'          => 'items',
					'type'        => 'repeater',
					'label'       => __( 'Items', 'ultra-max-core' ),
					'title_field' => 'title',
					'fields'      => array(
						array(
							'id'      => 'title',
							'type'    => 'text',
							'label'   => __( 'Question or title', 'ultra-max-core' ),
							'default' => __( 'How long does delivery take?', 'ultra-max-core' ),
						),
						array(
							'id'      => 'content',
							'type'    => 'wysiwyg',
							'label'   => __( 'Answer', 'ultra-max-core' ),
							'default' => __( 'Orders reach most cities in two to four working days.', 'ultra-max-core' ),
						),
					),
					'default'     => array(
						array( 'title' => __( 'How long does delivery take?', 'ultra-max-core' ) ),
					),
				),
				array(
					'id'      => 'first_open',
					'type'    => 'switch',
					'label'   => __( 'Open the first item', 'ultra-max-core' ),
					'default' => true,
				),
				array(
					'id'      => 'multiple',
					'type'    => 'switch',
					'label'   => __( 'Allow several open at once', 'ultra-max-core' ),
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
		$items = array();
		foreach ( $settings->rows( 'items' ) as $index => $row ) {
			$items[] = array(
				'title'   => $row->string( 'title' ),
				'content' => Html::wp( wpautop( wp_kses_post( $row->string( 'content' ) ) ) ),
				'open'    => 0 === $index && $settings->bool( 'first_open' ),
			);
		}
		if ( array() === $items ) {
			return Html::none();
		}
		return UI::accordion(
			array(
				'id'       => 'um-accordion-' . substr( md5( (string) wp_json_encode( $settings->all() ) ), 0, 8 ),
				'items'    => $items,
				'multiple' => $settings->bool( 'multiple' ),
			)
		);
	}
}
