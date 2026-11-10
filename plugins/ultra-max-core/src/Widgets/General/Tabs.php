<?php
/**
 * Tabs widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Builder\Layouts;
use UltraMax\Core\Builder\Renderer;
use UltraMax\Core\Elementor\Data;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Widgets\Values;

/**
 * Tabs with text, or with a reusable block in each tab for richer content.
 */
final class Tabs extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'tabs';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Store tabs', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-tabs';
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Tabs', 'ultra-max-core' ),
			array(
				array(
					'id'          => 'items',
					'type'        => 'repeater',
					'label'       => __( 'Tabs', 'ultra-max-core' ),
					'title_field' => 'title',
					'fields'      => array(
						array(
							'id'      => 'title',
							'type'    => 'text',
							'label'   => __( 'Tab name', 'ultra-max-core' ),
							'default' => __( 'Tab', 'ultra-max-core' ),
						),
						array(
							'id'    => 'content',
							'type'  => 'wysiwyg',
							'label' => __( 'Content', 'ultra-max-core' ),
						),
						array(
							'id'        => 'block',
							'type'      => 'posts',
							'post_type' => Layouts::POST_TYPE,
							'multiple'  => false,
							'label'     => __( 'Or a reusable block', 'ultra-max-core' ),
						),
					),
					'default'     => array(
						array( 'title' => __( 'Description', 'ultra-max-core' ) ),
						array( 'title' => __( 'Specifications', 'ultra-max-core' ) ),
					),
				),
				array(
					'id'      => 'look',
					'type'    => 'select',
					'label'   => __( 'Look', 'ultra-max-core' ),
					'default' => 'line',
					'options' => array(
						'line' => __( 'Underlined', 'ultra-max-core' ),
						'pill' => __( 'Pills', 'ultra-max-core' ),
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
		$items = array();
		foreach ( $settings->rows( 'items' ) as $row ) {
			$blocks  = $row->ids( 'block' );
			$content = Html::wp( wpautop( wp_kses_post( $row->string( 'content' ) ) ) );
			if ( array() !== $blocks && 'block' === Layouts::type( $blocks[0] ) ) {
				Data::enqueue( $blocks[0] );
				$content = Renderer::content( $blocks[0] );
			}
			$items[] = array(
				'label'   => $row->string( 'title' ),
				'content' => $content,
			);
		}
		if ( array() === $items ) {
			return Html::none();
		}
		return UI::tabs(
			array(
				'id'      => 'um-tabs-' . substr( md5( (string) wp_json_encode( $settings->all() ) ), 0, 8 ),
				'label'   => __( 'Tabs', 'ultra-max-core' ),
				'items'   => $items,
				'variant' => 'pill' === $settings->string( 'look' ) ? 'pill' : 'line',
			)
		);
	}
}
