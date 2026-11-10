<?php
/**
 * Social links widget (HOME-12, HDR-11).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\SocialLinks;
use UltraMax\Core\Widgets\Values;

/**
 * Links to the store's pages on social networks and messengers.
 */
final class Social extends GeneralWidget {

	/**
	 * Networks offered, with their icon.
	 *
	 * @return array<string, array{label: string, icon: string}>
	 */
	public static function networks(): array {
		return array(
			'instagram' => array(
				'label' => __( 'Instagram', 'ultra-max-core' ),
				'icon'  => 'insta',
			),
			'telegram'  => array(
				'label' => __( 'Telegram', 'ultra-max-core' ),
				'icon'  => 'send',
			),
			'whatsapp'  => array(
				'label' => __( 'WhatsApp', 'ultra-max-core' ),
				'icon'  => 'chat',
			),
			'eitaa'     => array(
				'label' => __( 'Eitaa', 'ultra-max-core' ),
				'icon'  => 'chat',
			),
			'bale'      => array(
				'label' => __( 'Bale', 'ultra-max-core' ),
				'icon'  => 'chat',
			),
			'aparat'    => array(
				'label' => __( 'Aparat', 'ultra-max-core' ),
				'icon'  => 'play',
			),
			'linkedin'  => array(
				'label' => __( 'LinkedIn', 'ultra-max-core' ),
				'icon'  => 'users',
			),
			'x'         => array(
				'label' => __( 'X', 'ultra-max-core' ),
				'icon'  => 'link',
			),
			'website'   => array(
				'label' => __( 'Website', 'ultra-max-core' ),
				'icon'  => 'globe',
			),
		);
	}

	/**
	 * Id.
	 */
	public function id(): string {
		return 'social';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Social networks', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-social-icons';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-12', 'HDR-02', 'HDR-11' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'social' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		$options = array();
		foreach ( self::networks() as $id => $network ) {
			$options[ $id ] = $network['label'];
		}
		return $this->one(
			__( 'Networks', 'ultra-max-core' ),
			array(
				array(
					'id'          => 'items',
					'type'        => 'repeater',
					'label'       => __( 'Links', 'ultra-max-core' ),
					'title_field' => 'network',
					'fields'      => array(
						array(
							'id'      => 'network',
							'type'    => 'select',
							'label'   => __( 'Network', 'ultra-max-core' ),
							'options' => $options,
							'default' => 'instagram',
						),
						array(
							'id'    => 'link',
							'type'  => 'url',
							'label' => __( 'Link to your page', 'ultra-max-core' ),
						),
					),
					'default'     => array(
						array( 'network' => 'instagram' ),
						array( 'network' => 'telegram' ),
					),
				),
				array(
					'id'      => 'size',
					'type'    => 'select',
					'label'   => __( 'Size', 'ultra-max-core' ),
					'default' => 'md',
					'options' => array(
						'sm' => __( 'Small', 'ultra-max-core' ),
						'md' => __( 'Medium', 'ultra-max-core' ),
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
		$networks = self::networks();
		$items    = array();
		foreach ( $settings->rows( 'items' ) as $row ) {
			$network = $networks[ $row->string( 'network' ) ] ?? null;
			if ( null !== $network && '' !== $row->href( 'link' ) ) {
				$items[] = array(
					'label' => $network['label'],
					'href'  => $row->href( 'link' ),
					'icon'  => $network['icon'],
				);
			}
		}
		return SocialLinks::render(
			array(
				'label' => __( 'Our pages on social networks', 'ultra-max-core' ),
				'items' => $items,
				'size'  => 'sm' === $settings->string( 'size' ) ? 'sm' : 'md',
			)
		);
	}
}
