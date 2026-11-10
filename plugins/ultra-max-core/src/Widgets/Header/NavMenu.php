<?php
/**
 * Menu widget (HDR-02, HDR-06, HDR-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\NavBar;
use UltraMax\Core\Store\Menus;
use UltraMax\Core\Widgets\Values;

/**
 * A WordPress menu as the header's menu row. Items show the icon, image
 * and badge set in Appearance > Menus, and an item with a reusable block
 * opens it as a mega panel. Use it for the main and the secondary menu.
 */
final class NavMenu extends HeaderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'nav-menu';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Menu', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-nav-menu';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HDR-02', 'HDR-06', 'HDR-08' );
	}

	/**
	 * Keywords.
	 *
	 * @return list<string>
	 */
	public function keywords(): array {
		return array( 'menu', 'nav', 'mega', 'navigation' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'menu_section',
				'label'  => __( 'Menu', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'      => 'menu',
						'type'    => 'select',
						'label'   => __( 'Menu', 'ultra-max-core' ),
						'help'    => __( 'Icons, badges and mega panels are set for each item in Appearance > Menus.', 'ultra-max-core' ),
						'options' => Menus::choices(),
						'default' => '',
					),
					array(
						'id'      => 'align',
						'type'    => 'select',
						'label'   => __( 'Alignment', 'ultra-max-core' ),
						'default' => 'start',
						'options' => array(
							'start'  => __( 'Start', 'ultra-max-core' ),
							'center' => __( 'Centre', 'ultra-max-core' ),
							'end'    => __( 'End', 'ultra-max-core' ),
						),
					),
					array(
						'id'      => 'size',
						'type'    => 'select',
						'label'   => __( 'Text size', 'ultra-max-core' ),
						'default' => 'sm',
						'options' => array(
							'sm' => __( 'Small', 'ultra-max-core' ),
							'md' => __( 'Medium', 'ultra-max-core' ),
						),
					),
					array(
						'id'      => 'label',
						'type'    => 'text',
						'label'   => __( 'Name for screen readers', 'ultra-max-core' ),
						'default' => __( 'Main menu', 'ultra-max-core' ),
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
		foreach ( Menus::tree( Menus::menu_id( $settings->string( 'menu' ) ), 2 ) as $node ) {
			$items[] = Menus::nav_item( $node );
		}
		$align = $settings->string( 'align' );
		return NavBar::render(
			array(
				'label' => $settings->string( 'label' ),
				'items' => $items,
				'align' => in_array( $align, array( 'center', 'end' ), true ) ? $align : 'start',
				'size'  => 'md' === $settings->string( 'size' ) ? 'md' : 'sm',
			)
		);
	}
}
