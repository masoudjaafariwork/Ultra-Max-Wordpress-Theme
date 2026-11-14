<?php
/**
 * City courier shipping method (SHIP-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Shipping;

use UltraMax\Core\Lib\Foundation\Brand;

/**
 * The store's own courier, offered only in one province and, if the store
 * lists them, only in some of its cities, each with its own price
 * (Rates::cities()). City names match however the shopper typed them
 * (Arabic or Persian yeh and kaf, half spaces).
 */
class CourierMethod extends \WC_Shipping_Method {

	public const ID = 'um_courier';

	/**
	 * Sets up the method.
	 *
	 * @param int $instance_id Instance id.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id          = self::ID;
		$this->instance_id = absint( $instance_id );
		/* translators: %s: product name. */
		$this->method_title       = sprintf( __( 'City courier (%s)', 'ultra-max-core' ), Brand::name() );
		$this->method_description = __( 'Your own courier, only in the cities you choose, each at its own price.', 'ultra-max-core' );
		$this->supports           = array( 'shipping-zones', 'instance-settings', 'instance-settings-modal' );
		$this->init();
	}

	/**
	 * Loads the settings.
	 */
	public function init(): void {
		$this->instance_form_fields = self::fields();
		$this->title                = $this->text( 'title', __( 'Store courier', 'ultra-max-core' ) );
		$this->tax_status           = 'none';
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
			'title'     => array(
				'title'   => __( 'Name shown to shoppers', 'ultra-max-core' ),
				'type'    => 'text',
				'default' => __( 'Store courier', 'ultra-max-core' ),
			),
			'province'  => array(
				'title'   => __( 'Province', 'ultra-max-core' ),
				'type'    => 'select',
				'default' => '',
				'options' => array( '' => __( 'The store\'s province', 'ultra-max-core' ) ) + ( is_array( $states ) ? array_map( 'wp_strip_all_tags', $states ) : array() ),
			),
			'cities'    => array(
				'title'       => __( 'Cities', 'ultra-max-core' ),
				'type'        => 'textarea',
				'default'     => '',
				'description' => __( 'One city per line, with its own price if it differs: "Tehran = 90000". Leave empty for every city of the province.', 'ultra-max-core' ),
				'css'         => 'min-height:100px',
			),
			'cost'      => array(
				'title'   => __( 'Price', 'ultra-max-core' ),
				'type'    => 'price',
				'default' => '90000',
			),
			'free_from' => array(
				'title'       => __( 'Free from an order of', 'ultra-max-core' ),
				'type'        => 'price',
				'default'     => '',
				'description' => __( 'Leave empty for never free.', 'ultra-max-core' ),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Adds the rate when the courier goes to the address.
	 *
	 * @param array<string, mixed> $package Package.
	 */
	public function calculate_shipping( $package = array() ): void {
		$package     = is_array( $package ) ? $package : array();
		$destination = is_array( $package['destination'] ?? null ) ? $package['destination'] : array();
		$province    = $this->text( 'province', '' );
		$province    = '' === $province ? WC()->countries->get_base_state() : $province;
		if ( 'IR' !== ( $destination['country'] ?? 'IR' ) || ( $destination['state'] ?? '' ) !== $province ) {
			return;
		}
		$cost = Rates::courier(
			Rates::cities( $this->text( 'cities', '' ) ),
			is_string( $destination['city'] ?? null ) ? $destination['city'] : '',
			(float) wc_format_decimal( $this->text( 'cost', '0' ) )
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
