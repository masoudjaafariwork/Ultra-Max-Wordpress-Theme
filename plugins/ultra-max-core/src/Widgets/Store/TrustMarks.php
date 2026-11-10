<?php
/**
 * Trust badges widget (HOME-11, HDR-11).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\TrustMarks as Component;
use UltraMax\Core\Widgets\Values;

/**
 * Trust and payment marks, such as e-Namad and the Shetab network, and the
 * store's advantages. A mark may be an image with a link, or the code the
 * trust service gives you, pasted as it is.
 */
class TrustMarks extends StoreWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'trust-marks';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Trust badges and advantages', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-lock-user';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-11', 'HDR-11' );
	}

	/**
	 * Keywords.
	 *
	 * @return list<string>
	 */
	public function keywords(): array {
		return array( 'enamad', 'trust', 'payment', 'badge', 'shetab' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'trust-marks' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		$perk = static fn ( string $icon, string $title, string $text ): array => array(
			'icon'  => $icon,
			'title' => $title,
			'text'  => $text,
		);
		return array(
			array(
				'id'     => 'marks_section',
				'label'  => __( 'Trust badges', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'      => 'title',
						'type'    => 'text',
						'label'   => __( 'Title', 'ultra-max-core' ),
						'default' => __( 'Secure payment with every Shetab card', 'ultra-max-core' ),
					),
					array(
						'id'    => 'text',
						'type'  => 'textarea',
						'label' => __( 'Text', 'ultra-max-core' ),
						'rows'  => 2,
					),
					array(
						'id'          => 'marks',
						'type'        => 'repeater',
						'label'       => __( 'Badges', 'ultra-max-core' ),
						'title_field' => 'label',
						'fields'      => array(
							array(
								'id'      => 'label',
								'type'    => 'text',
								'label'   => __( 'Name', 'ultra-max-core' ),
								'default' => __( 'Trust badge', 'ultra-max-core' ),
							),
							array(
								'id'    => 'image',
								'type'  => 'media',
								'label' => __( 'Image', 'ultra-max-core' ),
							),
							array(
								'id'    => 'link',
								'type'  => 'url',
								'label' => __( 'Link', 'ultra-max-core' ),
							),
							array(
								'id'    => 'code',
								'type'  => 'code',
								'label' => __( 'Or the badge code', 'ultra-max-core' ),
								'help'  => __( 'Paste the HTML code the service gives you, for example the e-Namad code. Only administrators who may add code can save it.', 'ultra-max-core' ),
								'rows'  => 4,
							),
						),
						'default'     => array(
							array( 'label' => __( 'e-Namad trust badge', 'ultra-max-core' ) ),
							array( 'label' => __( 'National registration', 'ultra-max-core' ) ),
							array( 'label' => __( 'Shetab network', 'ultra-max-core' ) ),
						),
					),
				),
			),
			array(
				'id'     => 'perks_section',
				'label'  => __( 'Store advantages', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'          => 'perks',
						'type'        => 'repeater',
						'label'       => __( 'Advantages', 'ultra-max-core' ),
						'title_field' => 'title',
						'fields'      => array(
							array(
								'id'      => 'icon',
								'type'    => 'icon',
								'label'   => __( 'Icon', 'ultra-max-core' ),
								'default' => 'truck',
							),
							array(
								'id'    => 'title',
								'type'  => 'text',
								'label' => __( 'Title', 'ultra-max-core' ),
							),
							array(
								'id'    => 'text',
								'type'  => 'text',
								'label' => __( 'Text', 'ultra-max-core' ),
							),
						),
						'default'     => array(
							$perk( 'truck', __( 'Fast delivery', 'ultra-max-core' ), __( 'All over the country', 'ultra-max-core' ) ),
							$perk( 'cash', __( 'Cash on delivery', 'ultra-max-core' ), __( 'In selected cities', 'ultra-max-core' ) ),
							$perk( 'return', __( '7-day returns', 'ultra-max-core' ), __( 'No questions asked', 'ultra-max-core' ) ),
							$perk( 'verified', __( 'Genuine products', 'ultra-max-core' ), __( 'From the brand or its agent', 'ultra-max-core' ) ),
							$perk( 'headset', __( 'Support 7 days a week', 'ultra-max-core' ), __( 'By phone and chat', 'ultra-max-core' ) ),
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
		$marks = array();
		foreach ( $settings->rows( 'marks' ) as $row ) {
			$mark = array(
				'label' => $row->string( 'label' ),
				'image' => $this->image( $row->media( 'image' ), 'thumbnail' ),
				'href'  => $row->href( 'link' ),
			);
			if ( '' !== trim( $row->string( 'code' ) ) ) {
				$mark['code'] = Html::wp( wp_kses( $row->string( 'code' ), self::badge_html() ) );
			}
			$marks[] = $mark;
		}
		$perks = array();
		foreach ( $settings->rows( 'perks' ) as $row ) {
			if ( '' !== $row->string( 'title' ) ) {
				$perks[] = array(
					'icon'  => '' === $row->string( 'icon' ) ? 'check' : $row->string( 'icon' ),
					'title' => $row->string( 'title' ),
					'text'  => $row->string( 'text' ),
				);
			}
		}
		return Component::render(
			array(
				'marks' => $marks,
				'perks' => $perks,
				'title' => $settings->string( 'title' ),
				'text'  => $settings->string( 'text' ),
			)
		);
	}

	/**
	 * Tags a trust badge code may use: a link around an image, as e-Namad
	 * and similar services give. Scripts are never printed.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function badge_html(): array {
		return array(
			'a'   => array(
				'href'           => true,
				'target'         => true,
				'rel'            => true,
				'referrerpolicy' => true,
				'title'          => true,
			),
			'img' => array(
				'src'            => true,
				'alt'            => true,
				'width'          => true,
				'height'         => true,
				'style'          => true,
				'referrerpolicy' => true,
				'code'           => true,
				'id'             => true,
				'loading'        => true,
			),
		);
	}
}
