<?php
/**
 * Quick links widget.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\QuickLinks as Component;
use UltraMax\Core\Widgets\Values;

/**
 * Coloured icon tiles for the most used places of the store, such as
 * offers, best sellers and order tracking.
 */
final class QuickLinks extends StoreWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'quick-links';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Quick links', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-apps';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-12' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'quick-links' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		$item = static fn ( string $label, string $icon, string $color ): array => array(
			'label' => $label,
			'icon'  => $icon,
			'color' => $color,
		);
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
								'default' => __( 'Offers', 'ultra-max-core' ),
							),
							array(
								'id'      => 'icon',
								'type'    => 'icon',
								'label'   => __( 'Icon', 'ultra-max-core' ),
								'default' => 'fire',
							),
							array(
								'id'      => 'color',
								'type'    => 'color',
								'label'   => __( 'Colour', 'ultra-max-core' ),
								'default' => '#ff9a1f',
							),
							array(
								'id'    => 'link',
								'type'  => 'url',
								'label' => __( 'Link', 'ultra-max-core' ),
							),
						),
						'default'     => array(
							$item( __( 'Amazing offers', 'ultra-max-core' ), 'fire', '#ff9a1f' ),
							$item( __( 'Supermarket', 'ultra-max-core' ), 'store', '#0e9f6e' ),
							$item( __( 'Discounts', 'ultra-max-core' ), 'percent', '#4c42d3' ),
							$item( __( 'Best sellers', 'ultra-max-core' ), 'star', '#1c74d0' ),
						),
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
			$items[] = array(
				'label' => $row->string( 'label' ),
				'href'  => '' === $row->href( 'link' ) ? '#' : $row->href( 'link' ),
				'icon'  => '' === $row->string( 'icon' ) ? 'grid' : $row->string( 'icon' ),
				'color' => $row->string( 'color' ),
			);
		}
		return Component::render(
			array(
				'label' => __( 'Quick links', 'ultra-max-core' ),
				'items' => $items,
			)
		);
	}
}
