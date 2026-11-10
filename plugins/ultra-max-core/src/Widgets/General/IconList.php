<?php
/**
 * Icon list widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\IconList as Component;
use UltraMax\Core\Widgets\Values;

/**
 * A list of lines with icons and optional links: features, contact
 * details, footer links.
 */
final class IconList extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'icon-list';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'List with icons', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-bullet-list';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'icon-list' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'List', 'ultra-max-core' ),
			array(
				array(
					'id'    => 'title',
					'type'  => 'text',
					'label' => __( 'Title', 'ultra-max-core' ),
				),
				array(
					'id'          => 'items',
					'type'        => 'repeater',
					'label'       => __( 'Lines', 'ultra-max-core' ),
					'title_field' => 'text',
					'fields'      => array(
						array(
							'id'      => 'text',
							'type'    => 'text',
							'label'   => __( 'Text', 'ultra-max-core' ),
							'default' => __( 'A line', 'ultra-max-core' ),
						),
						array(
							'id'    => 'icon',
							'type'  => 'icon',
							'label' => __( 'Icon', 'ultra-max-core' ),
						),
						array(
							'id'    => 'link',
							'type'  => 'url',
							'label' => __( 'Link', 'ultra-max-core' ),
						),
					),
					'default'     => array(
						array( 'text' => __( 'A line', 'ultra-max-core' ) ),
					),
				),
				array(
					'id'      => 'layout',
					'type'    => 'select',
					'label'   => __( 'Layout', 'ultra-max-core' ),
					'default' => 'column',
					'options' => array(
						'column' => __( 'One under another', 'ultra-max-core' ),
						'row'    => __( 'In a row', 'ultra-max-core' ),
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
			if ( '' !== $row->string( 'text' ) ) {
				$items[] = array(
					'text' => $row->string( 'text' ),
					'icon' => $row->string( 'icon' ),
					'href' => $row->href( 'link' ),
				);
			}
		}
		$props = array(
			'items'  => $items,
			'layout' => 'row' === $settings->string( 'layout' ) ? 'row' : 'column',
		);
		if ( '' !== $settings->string( 'title' ) ) {
			$props['label'] = $settings->string( 'title' );
		}
		$list = Component::render( $props );
		if ( '' === $settings->string( 'title' ) ) {
			return $list;
		}
		return Html::from( array( Component::title( $settings->string( 'title' ) ), $list ) );
	}
}
