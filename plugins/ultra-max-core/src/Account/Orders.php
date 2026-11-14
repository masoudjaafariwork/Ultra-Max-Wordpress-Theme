<?php
/**
 * A shopper's orders by status and an order's progress (ACC-01, ACC-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Account;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\OrderCard;
use UltraMax\Core\Store\Format;

/**
 * Orders fall in four groups like Digikala's: current, delivered, returned
 * and cancelled. Any status another plugin adds, such as "shipped", counts
 * as current. Everything is read through WooCommerce's order functions, so
 * it works with HPOS and the old post storage alike.
 *
 * @phpstan-type Group array{label: string, statuses: list<string>, icon: string, tone: 'warning'|'success'|'info'|'danger'|'neutral'}
 * @phpstan-type Step array{label: string, icon: string, state: 'done'|'current'|'todo', date?: string}
 */
final class Orders {

	/**
	 * The groups, current first.
	 *
	 * @return array<string, Group>
	 */
	public static function groups(): array {
		$fixed  = array(
			'delivered' => array( 'completed' ),
			'returned'  => array( 'refunded' ),
			'cancelled' => array( 'cancelled', 'failed' ),
		);
		$taken  = array_merge( array( 'checkout-draft', 'trash' ), ...array_values( $fixed ) );
		$others = array();
		foreach ( array_keys( wc_get_order_statuses() ) as $status ) {
			$status = (string) preg_replace( '/^wc-/', '', (string) $status );
			if ( ! in_array( $status, $taken, true ) ) {
				$others[] = $status;
			}
		}
		$groups = array(
			'current'   => array(
				'label'    => __( 'Current', 'ultra-max-core' ),
				'statuses' => $others,
				'icon'     => 'clock',
				'tone'     => 'warning',
			),
			'delivered' => array(
				'label'    => __( 'Delivered', 'ultra-max-core' ),
				'statuses' => $fixed['delivered'],
				'icon'     => 'check',
				'tone'     => 'success',
			),
			'returned'  => array(
				'label'    => __( 'Returned', 'ultra-max-core' ),
				'statuses' => $fixed['returned'],
				'icon'     => 'return',
				'tone'     => 'info',
			),
			'cancelled' => array(
				'label'    => __( 'Cancelled', 'ultra-max-core' ),
				'statuses' => $fixed['cancelled'],
				'icon'     => 'close',
				'tone'     => 'danger',
			),
		);
		/**
		 * Filters the groups of orders in the account.
		 *
		 * @param array<string, array<string, mixed>> $groups Groups keyed by id.
		 */
		$filtered = apply_filters( 'ultra_max_order_groups', $groups );
		$out      = array();
		foreach ( is_array( $filtered ) ? $filtered : array() as $id => $group ) {
			if ( is_string( $id ) && isset( $groups[ $id ] ) && is_array( $group ) && is_array( $group['statuses'] ?? null ) ) {
				$out[ $id ]             = $groups[ $id ];
				$out[ $id ]['statuses'] = array_values( array_filter( $group['statuses'], 'is_string' ) );
			}
		}
		return $out;
	}

	/**
	 * The group of a status.
	 *
	 * @param string $status Status without its wc- prefix.
	 */
	public static function group_of( string $status ): string {
		foreach ( self::groups() as $id => $group ) {
			if ( in_array( $status, $group['statuses'], true ) ) {
				return $id;
			}
		}
		return 'current';
	}

	/**
	 * Orders of a shopper in a group, one page of them.
	 *
	 * @param int    $user     User id.
	 * @param string $group    Group id; every group but drafts when empty.
	 * @param int    $page     Page number, from 1.
	 * @param int    $per_page Orders on a page; 1 to only count.
	 * @return array{orders: list<\WC_Order>, total: int, pages: int}
	 */
	public static function query( int $user, string $group = '', int $page = 1, int $per_page = 10 ): array {
		$groups   = self::groups();
		$statuses = isset( $groups[ $group ] ) ? $groups[ $group ]['statuses'] : array_merge( ...array_values( array_map( static fn ( array $item ): array => $item['statuses'], $groups ) ) );
		$empty    = array(
			'orders' => array(),
			'total'  => 0,
			'pages'  => 0,
		);
		if ( $user <= 0 || array() === $statuses ) {
			return $empty;
		}
		$found = wc_get_orders(
			array(
				'customer_id' => $user,
				'status'      => array_map( static fn ( string $status ): string => 'wc-' . $status, $statuses ),
				'limit'       => max( 1, $per_page ),
				'paged'       => max( 1, $page ),
				'paginate'    => true,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'type'        => 'shop_order',
			)
		);
		if ( ! is_object( $found ) || ! isset( $found->orders, $found->total ) ) {
			return $empty;
		}
		return array(
			'orders' => array_values( array_filter( is_array( $found->orders ) ? $found->orders : array(), static fn ( mixed $order ): bool => $order instanceof \WC_Order ) ),
			'total'  => is_numeric( $found->total ) ? (int) $found->total : 0,
			'pages'  => isset( $found->max_num_pages ) && is_numeric( $found->max_num_pages ) ? (int) $found->max_num_pages : 0,
		);
	}

	/**
	 * How many orders a shopper has in each group.
	 *
	 * @param int $user User id.
	 * @return array<string, int>
	 */
	public static function counts( int $user ): array {
		$out = array();
		foreach ( array_keys( self::groups() ) as $group ) {
			$out[ $group ] = self::query( $user, $group, 1, 1 )['total'];
		}
		return $out;
	}

	/**
	 * The status of an order as a badge: its name, colour and icon.
	 *
	 * @param \WC_Order $order Order.
	 * @return array{label: string, tone: 'warning'|'success'|'info'|'danger'|'neutral', icon: string}
	 */
	public static function status( \WC_Order $order ): array {
		$status = $order->get_status();
		$label  = wc_get_order_status_name( $status );
		if ( 'processing' === $status && null !== Tracking::get( $order ) ) {
			return array(
				'label' => __( 'Sent', 'ultra-max-core' ),
				'tone'  => 'info',
				'icon'  => 'truck',
			);
		}
		$group = self::groups()[ self::group_of( $status ) ] ?? null;
		return array(
			'label' => $label,
			'tone'  => 'processing' === $status ? 'info' : ( null === $group ? 'neutral' : $group['tone'] ),
			'icon'  => 'processing' === $status ? 'box' : ( null === $group ? 'info' : $group['icon'] ),
		);
	}

	/**
	 * The total of an order as plain text with its currency.
	 *
	 * @param \WC_Order $order Order.
	 */
	public static function total( \WC_Order $order ): string {
		return html_entity_decode( wp_strip_all_tags( wc_price( (float) $order->get_total(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * A date as the shopper reads it: Jalali in Persian.
	 *
	 * @param \WC_DateTime|null $date   Date.
	 * @param string            $format Format.
	 */
	public static function date( ?\WC_DateTime $date, string $format = 'j F Y' ): string {
		return null === $date ? '' : Format::date( $date->getTimestamp(), $format );
	}

	/**
	 * Small pictures of an order's products.
	 *
	 * @param \WC_Order $order Order.
	 * @param int       $count Most to show.
	 * @return array{images: list<Html>, more: int}
	 */
	public static function images( \WC_Order $order, int $count = 4 ): array {
		$images = array();
		$items  = $order->get_items();
		foreach ( array_slice( $items, 0, $count ) as $item ) {
			$product  = $item instanceof \WC_Order_Item_Product ? $item->get_product() : null;
			$image    = $product instanceof \WC_Product ? $product->get_image(
				'woocommerce_gallery_thumbnail',
				array(
					'loading' => 'lazy',
					'alt'     => $item->get_name(),
				)
			) : '';
			$images[] = Html::wp( is_string( $image ) ? $image : '' );
		}
		return array(
			'images' => $images,
			'more'   => max( 0, count( $items ) - $count ),
		);
	}

	/**
	 * The card of an order in a list.
	 *
	 * @param \WC_Order $order Order.
	 */
	public static function card( \WC_Order $order ): Html {
		$images = self::images( $order );
		$props  = array(
			'href'   => $order->get_view_order_url(),
			/* translators: %s: order number. */
			'label'  => sprintf( __( 'Order %s', 'ultra-max-core' ), $order->get_order_number() ),
			'status' => self::status( $order ),
			'meta'   => array(
				array(
					'label' => '',
					'value' => self::date( $order->get_date_created() ),
				),
				array(
					'label' => __( 'Order number', 'ultra-max-core' ),
					'value' => Format::digits( $order->get_order_number() ),
					'num'   => true,
				),
				array(
					'label' => __( 'Total', 'ultra-max-core' ),
					'value' => self::total( $order ),
					'num'   => true,
				),
			),
			'images' => $images['images'],
		);
		if ( $images['more'] > 0 ) {
			$props['more'] = '+' . Format::number( $images['more'] );
		}
		return OrderCard::render( $props );
	}

	/**
	 * Where an order is: placed, paid, being prepared, sent, delivered.
	 *
	 * @param \WC_Order $order Order.
	 * @return list<Step>
	 */
	public static function steps( \WC_Order $order ): array {
		$status    = $order->get_status();
		$paid      = $order->is_paid() || in_array( $status, array( 'completed', 'refunded' ), true );
		$parcel    = Tracking::get( $order );
		$delivered = 'completed' === $status;
		$sent      = $delivered || null !== $parcel;
		$short     = 'j F';
		$steps     = array(
			array(
				'label' => __( 'Order placed', 'ultra-max-core' ),
				'icon'  => 'file',
				'state' => 'done',
				'date'  => self::date( $order->get_date_created(), $short ),
			),
			array(
				'label' => __( 'Payment confirmed', 'ultra-max-core' ),
				'icon'  => 'card',
				'state' => $paid ? 'done' : 'current',
				'date'  => self::date( $order->get_date_paid(), $short ),
			),
			array(
				'label' => __( 'Being prepared', 'ultra-max-core' ),
				'icon'  => 'box',
				'state' => $sent ? 'done' : ( $paid ? 'current' : 'todo' ),
			),
			array(
				'label' => __( 'Handed to the carrier', 'ultra-max-core' ),
				'icon'  => 'truck',
				'state' => $delivered ? 'done' : ( $sent ? 'current' : 'todo' ),
				'date'  => null === $parcel || 0 === $parcel['date'] ? '' : Format::date( $parcel['date'], $short ),
			),
			array(
				'label' => __( 'Delivered to you', 'ultra-max-core' ),
				'icon'  => 'home',
				'state' => $delivered ? 'done' : 'todo',
				'date'  => self::date( $order->get_date_completed(), $short ),
			),
		);
		// The step being worked on shows no date of its own yet; a done step without one shows none.
		foreach ( $steps as $index => $step ) {
			if ( 'done' !== $step['state'] ) {
				$steps[ $index ]['date'] = '';
			}
		}
		return $steps;
	}

	/**
	 * Whether an order stopped, so it shows a message instead of its steps.
	 *
	 * @param \WC_Order $order Order.
	 */
	public static function stopped( \WC_Order $order ): bool {
		return in_array( self::group_of( $order->get_status() ), array( 'cancelled', 'returned' ), true );
	}
}
