<?php
/**
 * Sticky navigation widget (HDR-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\StickyNav as Component;
use UltraMax\Core\Widgets\Values;

/**
 * Links to sections of the same page that stay under the header while
 * scrolling and mark the section in view. Give each section an id in
 * Elementor's Advanced tab ("CSS ID") and write it here.
 */
final class StickyNav extends HeaderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'sticky-nav';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Sticky navigation', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-anchor';
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'links_section',
				'label'  => __( 'Links', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'          => 'items',
						'type'        => 'repeater',
						'label'       => __( 'Links', 'ultra-max-core' ),
						'title_field' => 'label',
						'fields'      => array(
							array(
								'id'      => 'label',
								'type'    => 'text',
								'label'   => __( 'Text', 'ultra-max-core' ),
								'default' => __( 'Section', 'ultra-max-core' ),
							),
							array(
								'id'          => 'target',
								'type'        => 'text',
								'label'       => __( 'Section id', 'ultra-max-core' ),
								'placeholder' => 'deals',
							),
						),
						'default'     => array(),
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
		$items = array();
		foreach ( $settings->rows( 'items' ) as $row ) {
			$target = sanitize_html_class( ltrim( $row->string( 'target' ), '#' ) );
			if ( '' !== $target && '' !== $row->string( 'label' ) ) {
				$items[] = array(
					'label'  => $row->string( 'label' ),
					'target' => $target,
				);
			}
		}
		return Component::render(
			array(
				'label' => __( 'On this page', 'ultra-max-core' ),
				'items' => $items,
			)
		);
	}
}
