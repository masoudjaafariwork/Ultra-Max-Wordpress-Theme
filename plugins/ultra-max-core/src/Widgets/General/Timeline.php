<?php
/**
 * Timeline widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Timeline as Component;
use UltraMax\Core\Widgets\Values;

/**
 * Steps or events along a line.
 */
final class Timeline extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'timeline';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Timeline', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-time-line';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'timeline' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Steps', 'ultra-max-core' ),
			array(
				array(
					'id'          => 'items',
					'type'        => 'repeater',
					'label'       => __( 'Steps', 'ultra-max-core' ),
					'title_field' => 'title',
					'fields'      => array(
						array(
							'id'    => 'label',
							'type'  => 'text',
							'label' => __( 'Date or step', 'ultra-max-core' ),
						),
						array(
							'id'      => 'title',
							'type'    => 'text',
							'label'   => __( 'Title', 'ultra-max-core' ),
							'default' => __( 'Order placed', 'ultra-max-core' ),
						),
						array(
							'id'    => 'text',
							'type'  => 'textarea',
							'label' => __( 'Text', 'ultra-max-core' ),
						),
						array(
							'id'      => 'icon',
							'type'    => 'icon',
							'label'   => __( 'Icon', 'ultra-max-core' ),
							'default' => 'check',
						),
					),
					'default'     => array(
						array( 'title' => __( 'Order placed', 'ultra-max-core' ) ),
						array(
							'title' => __( 'Packed', 'ultra-max-core' ),
							'icon'  => 'box',
						),
						array(
							'title' => __( 'Delivered', 'ultra-max-core' ),
							'icon'  => 'truck',
						),
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
			$items[] = array(
				'title' => $row->string( 'title' ),
				'label' => $row->string( 'label' ),
				'text'  => $row->string( 'text' ),
				'icon'  => '' === $row->string( 'icon' ) ? 'check' : $row->string( 'icon' ),
			);
		}
		return Component::render(
			array(
				'label' => __( 'Timeline', 'ultra-max-core' ),
				'items' => $items,
			)
		);
	}
}
