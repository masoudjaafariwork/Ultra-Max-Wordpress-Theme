<?php
/**
 * Delivery estimate and the free shipping bar (PDP-18, PDP-19).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Admin\Rest as AdminRest;
use UltraMax\Core\Lib\Foundation\Jalali;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\DeliveryEstimate;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\ProgressNote;
use UltraMax\Core\Store\Badges;
use UltraMax\Core\Store\Format;

/**
 * Every WooCommerce shipping method gets two fields in its own settings:
 * the first and last working day of delivery. The product page adds the
 * days a product needs to leave the store (its own, or the store's
 * default) and counts working days only: Fridays, optionally Thursdays,
 * and the store's holidays (Jalali dates) are skipped.
 *
 * The shopper chooses a province; the methods of the shipping zone that
 * covers it are listed with their dates.
 *
 * Free shipping starts at the store's amount, or else at the minimum of
 * WooCommerce's "free shipping" method in the store's own zone.
 *
 * @phpstan-import-type DeliveryRow from DeliveryEstimate
 */
final class Delivery {

	public const MIN = 'um_days_min';
	public const MAX = 'um_days_max';

	/**
	 * Hooks the shipping fields, the buy box rows and the endpoint.
	 */
	public static function register(): void {
		add_filter( 'woocommerce_shipping_methods', array( self::class, 'method_fields' ), 999 );
		add_filter( 'ultra_max_buy_box_rows', array( self::class, 'row' ), 20, 2 );
		add_filter( 'ultra_max_buy_box_after', array( self::class, 'bar' ), 30, 2 );
		add_filter( 'ultra_max_free_shipping_amount', array( self::class, 'free_amount' ) );
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
	}

	/**
	 * Adds the delivery days to every shipping method's settings.
	 *
	 * @param mixed $methods Methods by id.
	 * @return mixed
	 */
	public static function method_fields( mixed $methods ): mixed {
		foreach ( is_array( $methods ) ? array_keys( $methods ) : array() as $id ) {
			if ( is_string( $id ) && ! has_filter( 'woocommerce_shipping_instance_form_fields_' . $id, array( self::class, 'fields' ) ) ) {
				add_filter( 'woocommerce_shipping_instance_form_fields_' . $id, array( self::class, 'fields' ) );
			}
		}
		return $methods;
	}

	/**
	 * The two fields.
	 *
	 * @param mixed $fields Method fields.
	 * @return mixed
	 */
	public static function fields( mixed $fields ): mixed {
		if ( ! is_array( $fields ) ) {
			return $fields;
		}
		$fields[ self::MIN ] = array(
			'title'             => __( 'Delivery from (working days)', 'ultra-max-core' ),
			'type'              => 'number',
			'default'           => '',
			'description'       => __( 'For the delivery estimate on product pages. Leave empty to show no date for this method.', 'ultra-max-core' ),
			'desc_tip'          => true,
			'custom_attributes' => array( 'min' => '0' ),
		);
		$fields[ self::MAX ] = array(
			'title'             => __( 'Delivery to (working days)', 'ultra-max-core' ),
			'type'              => 'number',
			'default'           => '',
			'desc_tip'          => true,
			'custom_attributes' => array( 'min' => '0' ),
		);
		return $fields;
	}

	/**
	 * Holidays from the settings, as Gregorian Y-m-d dates.
	 *
	 * @return array<string, true>
	 */
	public static function holidays(): array {
		$lines = preg_split( '/\R/', Store::string( 'product_holidays' ) );
		$out   = array();
		foreach ( false === $lines ? array() : $lines as $line ) {
			$date = Jalali::parse( trim( $line ) );
			if ( null !== $date ) {
				list( $year, $month, $day )                              = Jalali::to_gregorian( $date[0], $date[1], $date[2] );
				$out[ sprintf( '%04d-%02d-%02d', $year, $month, $day ) ] = true;
			}
		}
		return $out;
	}

	/**
	 * The date a number of working days after a moment.
	 *
	 * @param \DateTimeImmutable  $from     Start, in the site's time zone.
	 * @param int                 $days     Working days to add.
	 * @param array<string, true> $holidays Holidays.
	 */
	public static function add_working_days( \DateTimeImmutable $from, int $days, array $holidays ): \DateTimeImmutable {
		$date     = $from;
		$thursday = Store::bool( 'product_thursday_off' );
		$added    = 0;
		$guard    = 0;
		while ( $added < $days && $guard < 365 ) {
			$date = $date->modify( '+1 day' );
			++$guard;
			$weekday = (int) $date->format( 'w' );
			if ( 5 === $weekday || ( $thursday && 4 === $weekday ) || isset( $holidays[ $date->format( 'Y-m-d' ) ] ) ) {
				continue;
			}
			++$added;
		}
		return $date;
	}

	/**
	 * Working days a product needs before it leaves the store.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function preparation( \WC_Product $product ): int {
		$own = Meta::int( Meta::owner( $product ), Meta::SHIP );
		return max( 0, $own > 0 ? $own : Store::int( 'product_ship_days' ) );
	}

	/**
	 * The country of the store and its provinces.
	 *
	 * @return array{country: string, states: array<string, string>}
	 */
	public static function places(): array {
		$country = WC()->countries->get_base_country();
		$states  = WC()->countries->get_states( $country );
		$out     = array();
		foreach ( is_array( $states ) ? $states : array() as $code => $name ) {
			$out[ (string) $code ] = wp_strip_all_tags( html_entity_decode( (string) $name, ENT_QUOTES, 'UTF-8' ) );
		}
		return array(
			'country' => $country,
			'states'  => $out,
		);
	}

	/**
	 * The province to show first: the shopper's, or the store's.
	 *
	 * @param array<string, string> $states Provinces.
	 */
	public static function default_state( array $states ): string {
		$state = function_exists( 'WC' ) && WC()->customer instanceof \WC_Customer ? WC()->customer->get_shipping_state() : '';
		if ( '' !== $state && isset( $states[ $state ] ) ) {
			return $state;
		}
		return WC()->countries->get_base_state();
	}

	/**
	 * A delivery date as text, such as "Saturday 25 Aban".
	 *
	 * @param \DateTimeImmutable $date Date.
	 */
	private static function day( \DateTimeImmutable $date ): string {
		return Format::date( $date->setTime( 12, 0 )->getTimestamp(), 'l j F' );
	}

	/**
	 * A span of delivery dates, such as "25 to 27 Aban".
	 *
	 * @param \DateTimeImmutable $from First day.
	 * @param \DateTimeImmutable $to   Last day.
	 */
	public static function span( \DateTimeImmutable $from, \DateTimeImmutable $to ): string {
		if ( $from->format( 'Y-m-d' ) === $to->format( 'Y-m-d' ) ) {
			return self::day( $from );
		}
		$start = $from->setTime( 12, 0 )->getTimestamp();
		$end   = $to->setTime( 12, 0 )->getTimestamp();
		$same  = Jalali::format( 'Y-m', $start ) === Jalali::format( 'Y-m', $end );
		/* translators: 1: first date, 2: last date. */
		return sprintf( __( '%1$s to %2$s', 'ultra-max-core' ), Format::date( $start, $same ? 'j' : 'j F' ), Format::date( $end, 'j F' ) );
	}

	/**
	 * Delivery rows of a product for a province.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $state   Province code.
	 * @return list<DeliveryRow>
	 */
	public static function rows( \WC_Product $product, string $state ): array {
		$today    = new \DateTimeImmutable( 'now', wp_timezone() );
		$holidays = self::holidays();
		$prepare  = self::preparation( $product );
		$places   = self::places();
		$zone     = \WC_Shipping_Zones::get_zone_matching_package(
			array(
				'destination' => array(
					'country'  => $places['country'],
					'state'    => $state,
					'postcode' => '',
					'city'     => '',
				),
			)
		);
		$rows     = array();
		foreach ( $zone->get_shipping_methods( true ) as $method ) {
			if ( ! $method instanceof \WC_Shipping_Method ) {
				continue;
			}
			$min = $method->get_option( self::MIN, '' );
			$max = $method->get_option( self::MAX, '' );
			if ( ! is_numeric( $min ) && ! is_numeric( $max ) ) {
				continue;
			}
			$min    = is_numeric( $min ) ? (int) $min : (int) $max;
			$max    = is_numeric( $max ) ? max( $min, (int) $max ) : $min;
			$from   = self::add_working_days( $today, $prepare + $min, $holidays );
			$to     = self::add_working_days( $today, $prepare + $max, $holidays );
			$rows[] = array(
				'title' => wp_strip_all_tags( $method->get_title() ),
				'text'  => self::span( $from, $to ),
			);
		}
		if ( array() === $rows ) {
			$rows[] = array(
				'title' => __( 'Preparation', 'ultra-max-core' ),
				'text'  => 0 === $prepare
					? __( 'Leaves the store today', 'ultra-max-core' )
					/* translators: %s: date. */
					: sprintf( __( 'Leaves the store on %s', 'ultra-max-core' ), self::day( self::add_working_days( $today, $prepare, $holidays ) ) ),
			);
		}
		return $rows;
	}

	/**
	 * The estimate of a product.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function estimate( \WC_Product $product ): Html {
		if ( ! Store::bool( 'product_delivery' ) || ! $product->needs_shipping() ) {
			return Html::none();
		}
		$places = self::places();
		$state  = self::default_state( $places['states'] );
		$props  = array(
			'id'       => 'um-delivery-' . $product->get_id(),
			'product'  => $product->get_id(),
			'label'    => __( 'Delivery to', 'ultra-max-core' ),
			'rows'     => self::rows( $product, $state ),
			'endpoint' => rest_url( AdminRest::NAMESPACE . '/delivery' ),
		);
		if ( array() !== $places['states'] ) {
			$props['states'] = $places['states'];
			$props['state']  = $state;
		}
		return DeliveryEstimate::render( $props );
	}

	/**
	 * Adds the estimate to the buy box.
	 *
	 * @param mixed $rows    Rows.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function row( mixed $rows, mixed $product ): mixed {
		if ( ! is_array( $rows ) || ! $product instanceof \WC_Product ) {
			return $rows;
		}
		$estimate = self::estimate( $product );
		if ( ! $estimate->is_empty() ) {
			$rows[] = array(
				'icon' => 'truck',
				'text' => $estimate,
			);
		}
		return $rows;
	}

	/**
	 * The minimum of WooCommerce's free shipping in the store's zone, when
	 * the store set no amount of its own.
	 *
	 * @param mixed $amount Amount from the settings.
	 * @return mixed
	 */
	public static function free_amount( mixed $amount ): mixed {
		if ( is_numeric( $amount ) && (float) $amount > 0 ) {
			return $amount;
		}
		static $found = null;
		if ( null === $found ) {
			$found  = 0.0;
			$places = self::places();
			$zone   = \WC_Shipping_Zones::get_zone_matching_package(
				array(
					'destination' => array(
						'country'  => $places['country'],
						'state'    => WC()->countries->get_base_state(),
						'postcode' => '',
						'city'     => '',
					),
				)
			);
			foreach ( $zone->get_shipping_methods( true ) as $method ) {
				if ( $method instanceof \WC_Shipping_Method && 'free_shipping' === $method->id && in_array( $method->get_option( 'requires' ), array( 'min_amount', 'either' ), true ) ) {
					$min = $method->get_option( 'min_amount' );
					if ( is_numeric( $min ) && (float) $min > 0 ) {
						$found = 0.0 === $found ? (float) $min : min( $found, (float) $min );
					}
				}
			}
		}
		return $found;
	}

	/**
	 * The free shipping bar (PDP-19): how much more the cart needs, with
	 * this product in it.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function free_bar( \WC_Product $product ): Html {
		$free = Badges::free_shipping();
		if ( ! Store::bool( 'product_free_bar' ) || $free <= 0 || ! $product->needs_shipping() ) {
			return Html::none();
		}
		$cart  = function_exists( 'WC' ) && null !== WC()->cart ? (float) WC()->cart->get_displayed_subtotal() : 0.0;
		$total = $cart + (float) wc_get_price_to_display( $product );
		if ( $total >= $free ) {
			return ProgressNote::render(
				array(
					'text'  => Html::trusted( esc_html__( 'This order ships free', 'ultra-max-core' ) ),
					'value' => 100,
					'label' => __( 'Free shipping reached', 'ultra-max-core' ),
					'icon'  => 'truck',
					'tone'  => 'success',
				)
			);
		}
		return ProgressNote::render(
			array(
				'text'  => Html::trusted(
					sprintf(
						/* translators: %s: amount of money. */
						esc_html__( 'Add %s more to ship free', 'ultra-max-core' ),
						'<b>' . esc_html( Format::money( $free - $total ) . ' ' . Format::currency() ) . '</b>'
					)
				),
				'value' => (int) floor( $total / $free * 100 ),
				'label' => __( 'On the way to free shipping', 'ultra-max-core' ),
				'icon'  => 'truck',
				'tone'  => 'success',
			)
		);
	}

	/**
	 * Adds the free shipping bar under the buy box's form.
	 *
	 * @param mixed $pieces  Pieces.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function bar( mixed $pieces, mixed $product ): mixed {
		if ( is_array( $pieces ) && $product instanceof \WC_Product ) {
			$pieces[] = self::free_bar( $product );
		}
		return $pieces;
	}

	/**
	 * Registers GET /delivery?product=&state=.
	 */
	public static function routes(): void {
		register_rest_route(
			AdminRest::NAMESPACE,
			'/delivery',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * The delivery rows of a public product for a province.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function rest( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id      = $request->get_param( 'product' );
		$state   = $request->get_param( 'state' );
		$product = wc_get_product( is_numeric( $id ) ? (int) $id : 0 );
		$places  = self::places();
		if ( ! $product instanceof \WC_Product || 'publish' !== $product->get_status() || ! $product->is_visible() || '' !== (string) get_post_field( 'post_password', $product->get_id() ) ) {
			return new \WP_Error( 'ultra_max_no_product', __( 'This product could not be found.', 'ultra-max-core' ), array( 'status' => 404 ) );
		}
		$state = is_string( $state ) && isset( $places['states'][ $state ] ) ? $state : WC()->countries->get_base_state();
		return new \WP_REST_Response( array( 'rows' => DeliveryEstimate::rows( self::rows( $product, $state ) ) ) );
	}
}
