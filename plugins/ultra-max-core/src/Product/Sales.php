<?php
/**
 * Sales counter and the stock bar of an offer (PDP-16, PDP-17).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\ProgressNote;
use UltraMax\Core\Store\Format;

/**
 * Only real numbers: the counter sums the units of paid orders of the last
 * days from WooCommerce's own order lookup table (which works with HPOS),
 * and is cached for an hour; when the table is missing, no counter shows.
 * The stock bar of an offer compares units sold with units left in stock.
 */
final class Sales {

	/**
	 * Hooks the counter and the bar into the buy box.
	 */
	public static function register(): void {
		add_filter( 'ultra_max_buy_box_rows', array( self::class, 'row' ), 30, 2 );
		add_filter( 'ultra_max_buy_box_after', array( self::class, 'after' ), 10, 2 );
		add_action( 'woocommerce_order_status_changed', array( self::class, 'flush_order' ) );
	}

	/**
	 * Units of a product sold in paid orders of the last days, or null when
	 * the store has no order lookup table.
	 *
	 * @param int $product_id Product id.
	 * @param int $days       Days back.
	 */
	public static function sold( int $product_id, int $days ): ?int {
		global $wpdb;
		$key    = 'um_sold_' . $product_id . '_' . $days;
		$cached = get_transient( $key );
		if ( is_numeric( $cached ) ) {
			return (int) $cached;
		}
		$lookup = $wpdb->prefix . 'wc_order_product_lookup';
		$stats  = $wpdb->prefix . 'wc_order_stats';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached above for an hour.
		if ( $lookup !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $lookup ) ) ) {
			return null;
		}
		$since = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached for an hour.
		$sum  = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT SUM(l.product_qty) FROM %i l INNER JOIN %i s ON s.order_id = l.order_id WHERE l.product_id = %d AND l.date_created >= %s AND s.status IN (%s, %s, %s)',
				$lookup,
				$stats,
				$product_id,
				$since,
				'wc-processing',
				'wc-completed',
				'wc-on-hold'
			)
		);
		$sold = is_numeric( $sum ) ? (int) $sum : 0;
		set_transient( $key, $sold, HOUR_IN_SECONDS );
		return $sold;
	}

	/**
	 * Forgets the counters of an order's products when its status changes.
	 *
	 * @param mixed $order_id Order id.
	 */
	public static function flush_order( mixed $order_id ): void {
		$order = is_numeric( $order_id ) ? wc_get_order( (int) $order_id ) : null;
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		$days = max( 1, Store::int( 'product_sales_days' ) );
		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof \WC_Order_Item_Product ) {
				delete_transient( 'um_sold_' . $item->get_product_id() . '_' . $days );
			}
		}
	}

	/**
	 * Adds "N sold in the last days" to the buy box.
	 *
	 * @param mixed $rows    Rows.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function row( mixed $rows, mixed $product ): mixed {
		if ( ! is_array( $rows ) || ! $product instanceof \WC_Product || ! Store::bool( 'product_sales' ) ) {
			return $rows;
		}
		$days = max( 1, Store::int( 'product_sales_days' ) );
		$sold = self::sold( $product->get_id(), $days );
		if ( null !== $sold && $sold >= max( 1, Store::int( 'product_sales_min' ) ) ) {
			$rows[] = array(
				'icon' => 'fire',
				/* translators: 1: number of units, 2: number of days. */
				'text' => sprintf( _n( '%1$s sold in the last %2$s days', '%1$s sold in the last %2$s days', $sold, 'ultra-max-core' ), Format::number( $sold ), Format::number( $days ) ),
			);
		}
		return $rows;
	}

	/**
	 * The stock bar of a product on sale: units sold against units left.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function stock_bar( \WC_Product $product ): Html {
		$stock = $product->get_stock_quantity();
		if ( ! Store::bool( 'product_stock_bar' ) || ! $product->is_on_sale() || ! $product->managing_stock() || null === $stock || $stock <= 0 ) {
			return Html::none();
		}
		$sold  = max( 0, (int) $product->get_total_sales() );
		$share = $sold + $stock > 0 ? (int) round( $sold / ( $sold + $stock ) * 100 ) : 0;
		return ProgressNote::render(
			array(
				/* translators: %s: units left. */
				'text'  => sprintf( _n( '%s left at this price', '%s left at this price', (int) $stock, 'ultra-max-core' ), Format::number( (int) $stock ) ),
				/* translators: %s: percent. */
				'end'   => sprintf( __( '%s sold', 'ultra-max-core' ), Format::percent( $share ) ),
				'value' => $share,
				/* translators: %s: percent. */
				'label' => sprintf( __( '%s of the offer sold', 'ultra-max-core' ), Format::percent( $share ) ),
				'icon'  => 'box',
				'tone'  => 'accent',
			)
		);
	}

	/**
	 * Adds the stock bar under the buy box's form.
	 *
	 * @param mixed $pieces  Pieces.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function after( mixed $pieces, mixed $product ): mixed {
		if ( is_array( $pieces ) && $product instanceof \WC_Product ) {
			$pieces[] = self::stock_bar( $product );
		}
		return $pieces;
	}
}
