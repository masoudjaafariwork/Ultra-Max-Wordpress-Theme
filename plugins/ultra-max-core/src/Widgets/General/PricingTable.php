<?php
/**
 * Pricing table widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\PricingTable as Component;
use UltraMax\Core\Store\Format;
use UltraMax\Core\Widgets\Values;

/**
 * One plan with its price and what it includes. Put several side by side
 * in an Elementor container.
 */
final class PricingTable extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'pricing-table';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Pricing table', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-price-table';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'pricing-table' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Plan', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'title',
					'type'    => 'text',
					'label'   => __( 'Plan name', 'ultra-max-core' ),
					'default' => __( 'Gold membership', 'ultra-max-core' ),
				),
				array(
					'id'    => 'text',
					'type'  => 'text',
					'label' => __( 'Short text', 'ultra-max-core' ),
				),
				array(
					'id'      => 'price',
					'type'    => 'number',
					'label'   => __( 'Price', 'ultra-max-core' ),
					'default' => 490000,
					'min'     => 0,
				),
				array(
					'id'      => 'period',
					'type'    => 'text',
					'label'   => __( 'Period', 'ultra-max-core' ),
					'default' => __( 'month', 'ultra-max-core' ),
				),
				array(
					'id'          => 'features',
					'type'        => 'repeater',
					'label'       => __( 'What it includes', 'ultra-max-core' ),
					'title_field' => 'text',
					'fields'      => array(
						array(
							'id'      => 'text',
							'type'    => 'text',
							'label'   => __( 'Line', 'ultra-max-core' ),
							'default' => __( 'Free delivery', 'ultra-max-core' ),
						),
						array(
							'id'      => 'included',
							'type'    => 'switch',
							'label'   => __( 'Included', 'ultra-max-core' ),
							'default' => true,
						),
					),
					'default'     => array(
						array( 'text' => __( 'Free delivery', 'ultra-max-core' ) ),
						array( 'text' => __( 'Early access to offers', 'ultra-max-core' ) ),
					),
				),
				array(
					'id'      => 'button',
					'type'    => 'text',
					'label'   => __( 'Button text', 'ultra-max-core' ),
					'default' => __( 'Choose this plan', 'ultra-max-core' ),
				),
				array(
					'id'    => 'link',
					'type'  => 'url',
					'label' => __( 'Button link', 'ultra-max-core' ),
				),
				array(
					'id'    => 'ribbon',
					'type'  => 'text',
					'label' => __( 'Ribbon', 'ultra-max-core' ),
					'help'  => __( 'For example "Most popular".', 'ultra-max-core' ),
				),
				array(
					'id'      => 'featured',
					'type'    => 'switch',
					'label'   => __( 'Highlight this plan', 'ultra-max-core' ),
					'default' => false,
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
		$features = array();
		foreach ( $settings->rows( 'features' ) as $row ) {
			if ( '' !== $row->string( 'text' ) ) {
				$features[] = array(
					'text'     => $row->string( 'text' ),
					'included' => $row->bool( 'included' ),
				);
			}
		}
		return Component::render(
			array(
				'title'          => $settings->string( 'title' ),
				'text'           => $settings->string( 'text' ),
				'price'          => Format::money( $settings->float( 'price' ) ),
				'currency'       => Format::currency(),
				'period'         => $settings->string( 'period' ),
				'features'       => $features,
				'button'         => array(
					'label' => $settings->string( 'button' ),
					'href'  => '' === $settings->href( 'link' ) ? '#' : $settings->href( 'link' ),
				),
				'ribbon'         => $settings->string( 'ribbon' ),
				'featured'       => $settings->bool( 'featured' ),
				'included_label' => __( 'Included:', 'ultra-max-core' ),
				'excluded_label' => __( 'Not included:', 'ultra-max-core' ),
			)
		);
	}
}
