<?php
/**
 * App links widget (HDR-11).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Footer;

use UltraMax\Core\Lib\UI\AppLinks as Component;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;
use UltraMax\Core\Widgets\Widget;

/**
 * Links to the store's apps: app stores, a direct download, or the page
 * that explains how to add the store to the phone's home screen.
 */
final class AppLinks extends Widget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'app-links';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'App links', 'ultra-max-core' );
	}

	/**
	 * Panel group.
	 */
	public function group(): string {
		return 'footer';
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-download-button';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HDR-11' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'footer' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		$link = static fn ( string $label, string $icon ): array => array(
			'label' => $label,
			'icon'  => $icon,
		);
		return array(
			array(
				'id'     => 'apps_section',
				'label'  => __( 'Apps', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'      => 'label',
						'type'    => 'text',
						'label'   => __( 'Label', 'ultra-max-core' ),
						'default' => __( 'Store app:', 'ultra-max-core' ),
					),
					array(
						'id'          => 'items',
						'type'        => 'repeater',
						'label'       => __( 'Links', 'ultra-max-core' ),
						'title_field' => 'label',
						'fields'      => array(
							array(
								'id'    => 'label',
								'type'  => 'text',
								'label' => __( 'Text', 'ultra-max-core' ),
							),
							array(
								'id'      => 'icon',
								'type'    => 'icon',
								'label'   => __( 'Icon', 'ultra-max-core' ),
								'default' => 'mobile',
							),
							array(
								'id'    => 'link',
								'type'  => 'url',
								'label' => __( 'Link', 'ultra-max-core' ),
							),
						),
						'default'     => array(
							$link( __( 'Add to home screen', 'ultra-max-core' ), 'mobile' ),
							$link( __( 'Direct download', 'ultra-max-core' ), 'download' ),
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
			if ( '' !== $row->string( 'label' ) ) {
				$items[] = array(
					'label' => $row->string( 'label' ),
					'href'  => '' === $row->href( 'link' ) ? '#' : $row->href( 'link' ),
					'icon'  => '' === $row->string( 'icon' ) ? 'mobile' : $row->string( 'icon' ),
				);
			}
		}
		return Component::render(
			array(
				'label' => $settings->string( 'label' ),
				'items' => $items,
			)
		);
	}
}
