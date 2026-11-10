<?php
/**
 * Heading widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\SectionHead;
use UltraMax\Core\Widgets\Values;

/**
 * A section title in the store's style, with an optional icon and a "see
 * all" link.
 */
final class Heading extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'heading';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Section title', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-t-letter';
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Title', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'title',
					'type'    => 'text',
					'label'   => __( 'Title', 'ultra-max-core' ),
					'default' => __( 'Section title', 'ultra-max-core' ),
				),
				array(
					'id'    => 'icon',
					'type'  => 'icon',
					'label' => __( 'Icon', 'ultra-max-core' ),
				),
				array(
					'id'      => 'level',
					'type'    => 'select',
					'label'   => __( 'Heading level', 'ultra-max-core' ),
					'default' => '2',
					'options' => array(
						'2' => 'H2',
						'3' => 'H3',
					),
				),
				array(
					'id'      => 'align',
					'type'    => 'select',
					'label'   => __( 'Alignment', 'ultra-max-core' ),
					'default' => 'start',
					'options' => array(
						'start'  => __( 'Start', 'ultra-max-core' ),
						'center' => __( 'Centre', 'ultra-max-core' ),
					),
				),
				array(
					'id'      => 'more_label',
					'type'    => 'text',
					'label'   => __( '"See all" text', 'ultra-max-core' ),
					'default' => __( 'See all', 'ultra-max-core' ),
				),
				array(
					'id'    => 'more_link',
					'type'  => 'url',
					'label' => __( '"See all" link', 'ultra-max-core' ),
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
		$props = array(
			'title' => $settings->string( 'title' ),
			'level' => '3' === $settings->string( 'level' ) ? 3 : 2,
			'align' => 'center' === $settings->string( 'align' ) ? 'center' : 'start',
			'more'  => array(
				'label' => $settings->string( 'more_label' ),
				'href'  => $settings->href( 'more_link' ),
			),
			'class' => 'mb-0',
		);
		if ( '' !== $settings->string( 'icon' ) ) {
			$props['icon'] = $settings->string( 'icon' );
		}
		return SectionHead::render( $props );
	}
}
