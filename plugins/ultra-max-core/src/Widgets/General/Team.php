<?php
/**
 * Team widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\TeamMember;
use UltraMax\Core\Widgets\Values;

/**
 * One person of the team, with photo, role, short bio and social links.
 */
final class Team extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'team-member';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Team member', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-person';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'team' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Person', 'ultra-max-core' ),
			array(
				array(
					'id'    => 'photo',
					'type'  => 'media',
					'label' => __( 'Photo', 'ultra-max-core' ),
				),
				array(
					'id'      => 'name',
					'type'    => 'text',
					'label'   => __( 'Name', 'ultra-max-core' ),
					'default' => __( 'Team member', 'ultra-max-core' ),
				),
				array(
					'id'      => 'role',
					'type'    => 'text',
					'label'   => __( 'Role', 'ultra-max-core' ),
					'default' => __( 'Customer support', 'ultra-max-core' ),
				),
				array(
					'id'    => 'bio',
					'type'  => 'textarea',
					'label' => __( 'Short bio', 'ultra-max-core' ),
				),
				array(
					'id'          => 'links',
					'type'        => 'repeater',
					'label'       => __( 'Links', 'ultra-max-core' ),
					'title_field' => 'label',
					'fields'      => array(
						array(
							'id'    => 'label',
							'type'  => 'text',
							'label' => __( 'Name of the network', 'ultra-max-core' ),
						),
						array(
							'id'      => 'icon',
							'type'    => 'icon',
							'label'   => __( 'Icon', 'ultra-max-core' ),
							'default' => 'insta',
						),
						array(
							'id'    => 'link',
							'type'  => 'url',
							'label' => __( 'Link', 'ultra-max-core' ),
						),
					),
					'default'     => array(),
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
		$links = array();
		foreach ( $settings->rows( 'links' ) as $row ) {
			if ( '' !== $row->href( 'link' ) ) {
				$links[] = array(
					'label' => $row->string( 'label' ),
					'href'  => $row->href( 'link' ),
					'icon'  => '' === $row->string( 'icon' ) ? 'link' : $row->string( 'icon' ),
				);
			}
		}
		return TeamMember::render(
			array(
				'name'  => $settings->string( 'name' ),
				'role'  => $settings->string( 'role' ),
				'bio'   => $settings->string( 'bio' ),
				'photo' => $this->image( $settings->media( 'photo' ), 'medium' ),
				'links' => $links,
			)
		);
	}
}
