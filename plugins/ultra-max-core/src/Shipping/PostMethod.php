<?php
/**
 * Iran Post shipping method (SHIP-01).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Shipping;

use UltraMax\Core\Lib\Foundation\Brand;

/**
 * Pishtaz (express) or Sefareshi (registered) post, priced by the parcel's
 * weight and whether it stays in the store's province, from a table the
 * store types (Rates::brackets()). Added to a WooCommerce shipping zone
 * like any other method; one instance per service.
 */
class PostMethod extends \WC_Shipping_Method {

	public const ID = 'um_post';

	/**
	 * Sets up the method.
	 *
	 * @param int $instance_id Instance id.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id          = self::ID;
		$this->instance_id = absint( $instance_id );
		/* translators: %s: product name. */
		$this->method_title       = sprintf( __( 'Iran Post (%s)', 'ultra-max-core' ), Brand::name() );
		$this->method_description = __( 'Express or registered post, priced by weight and by whether the parcel stays in your province.', 'ultra-max-core' );
		$this->supports           = array( 'shipping-zones', 'instance-settings', 'instance-settings-modal' );
		$this->init();
	}

	/**
	 * Loads the settings.
	 */
	public function init(): void {
		$this->instance_form_fields = self::fields();
		$this->title                = $this->text( 'title', __( 'Express post', 'ultra-max-core' ) );
		$this->tax_status           = $this->text( 'tax_status', 'none' );
		add_action(
			'woocommerce_update_options_shipping_' . $this->id,
			function (): void {
				$this->process_admin_options();
			}
		);
	}

	/**
	 * A setting as text.
	 *
	 * @param string $key      Setting.
	 * @param string $fallback Value when it is not set.
	 */
	private function text( string $key, string $fallback = '' ): string {
		$value = $this->get_option( $key, $fallback );
		return is_scalar( $value ) ? (string) $value : $fallback;
	}

	/**
	 * The settings of an instance.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function fields(): array {
		$states = function_exists( 'WC' ) ? WC()->countries->get_states( 'IR' ) : array();
		return array(
			'title'      => array(
				'title'   => __( 'Name shown to shoppers', 'ultra-max-core' ),
				'type'    => 'text',
				'default' => __( 'Express post', 'ultra-max-core' ),
			),
			'service'    => array(
				'title'   => __( 'Service', 'ultra-max-core' ),
				'type'    => 'select',
				'default' => 'pishtaz',
				'options' => array(
					'pishtaz'   => __( 'Express post (Pishtaz)', 'ultra-max-core' ),
					'sefareshi' => __( 'Registered post (Sefareshi)', 'ultra-max-core' ),
				),
			),
			'origin'     => array(
				'title'       => __( 'Sent from province', 'ultra-max-core' ),
				'type'        => 'select',
				'default'     => '',
				'options'     => array( '' => __( 'The store\'s province', 'ultra-max-core' ) ) + ( is_array( $states ) ? array_map( 'wp_strip_all_tags', $states ) : array() ),
				'description' => __( 'Parcels to this province pay the "in the province" price.', 'ultra-max-core' ),
				'desc_tip'    => true,
			),
			'rates'      => array(
				'title'       => __( 'Prices by weight', 'ultra-max-core' ),
				'type'        => 'textarea',
				'default'     => "0.5 = 65000 | 85000\n1 = 75000 | 100000\n2 = 95000 | 130000\n5 = 150000 | 210000",
				'description' => __( 'One line per weight: up to how many kilograms = price in the province | price to other provinces. Check today\'s post tariff before you sell.', 'ultra-max-core' ),
				'css'         => 'min-height:120px;direction:ltr;font-family:monospace',
			),
			'extra'      => array(
				'title'       => __( 'Every kilogram above the table', 'ultra-max-core' ),
				'type'        => 'price',
				'default'     => '25000',
				'description' => __( 'Added for every kilogram started beyond the last line.', 'ultra-max-core' ),
				'desc_tip'    => true,
			),
			'handling'   => array(
				'title'       => __( 'Packing price', 'ultra-max-core' ),
				'type'        => 'price',
				'default'     => '0',
				'description' => __( 'Added to every parcel.', 'ultra-max-core' ),
				'desc_tip'    => true,
			),
			'empty'      => array(
				'title'       => __( 'Weight of a product without one (kg)', 'ultra-max-core' ),
				'type'        => 'decimal',
				'default'     => '0.5',
				'description' => __( 'Used for products whose weight is not filled in.', 'ultra-max-core' ),
				'desc_tip'    => true,
			),
			'free_from'  => array(
				'title'       => __( 'Free from an order of', 'ultra-max-core' ),
				'type'        => 'price',
				'default'     => '',
				'description' => __( 'Leave empty for never free.', 'ultra-max-core' ),
				'desc_tip'    => true,
			),
			'tax_status' => array(
				'title'   => __( 'Tax status', 'ultra-max-core' ),
				'type'    => 'select',
				'default' => 'none',
				'options' => array(
					'taxable' => __( 'Taxable', 'ultra-max-core' ),
					'none'    => __( 'None', 'ultra-max-core' ),
				),
			),
		);
	}

	/**
	 * The weight of a parcel in kilograms.
	 *
	 * @param array<string, mixed> $package Package.
	 * @param float                $fallback Weight of a product without one.
	 */
	public static function weight( array $package, float $fallback ): float {
		$weight = 0.0;
		foreach ( is_array( $package['contents'] ?? null ) ? $package['contents'] : array() as $item ) {
			$product = is_array( $item ) ? ( $item['data'] ?? null ) : null;
			if ( ! $product instanceof \WC_Product || ! $product->needs_shipping() ) {
				continue;
			}
			$own     = $product->get_weight();
			$kilos   = is_numeric( $own ) && (float) $own > 0 ? (float) wc_get_weight( (float) $own, 'kg' ) : $fallback;
			$weight += $kilos * ( is_numeric( $item['quantity'] ?? null ) ? (float) $item['quantity'] : 1.0 );
		}
		return $weight;
	}

	/**
	 * Adds the rate of a parcel.
	 *
	 * @param array<string, mixed> $package Package.
	 */
	public function calculate_shipping( $package = array() ): void {
		$package     = is_array( $package ) ? $package : array();
		$destination = is_array( $package['destination'] ?? null ) ? $package['destination'] : array();
		if ( 'IR' !== ( $destination['country'] ?? 'IR' ) ) {
			return;
		}
		$origin = $this->text( 'origin', '' );
		$origin = '' === $origin ? WC()->countries->get_base_state() : $origin;
		$empty  = $this->get_option( 'empty', '0.5' );
		$cost   = Rates::post(
			Rates::brackets( $this->text( 'rates', '' ) ),
			self::weight( $package, is_numeric( $empty ) ? (float) $empty : 0.5 ),
			( $destination['state'] ?? '' ) === $origin,
			(float) wc_format_decimal( $this->text( 'extra', '0' ) ),
			(float) wc_format_decimal( $this->text( 'handling', '0' ) )
		);
		if ( null === $cost ) {
			return;
		}
		$free  = $this->get_option( 'free_from', '' );
		$total = is_numeric( $package['contents_cost'] ?? null ) ? (float) $package['contents_cost'] : 0.0;
		if ( is_numeric( $free ) && (float) $free > 0 && $total >= (float) $free ) {
			$cost = 0.0;
		}
		$this->add_rate(
			array(
				'id'      => $this->get_rate_id(),
				'label'   => $this->title,
				'cost'    => $cost,
				'package' => $package,
			)
		);
	}
}
