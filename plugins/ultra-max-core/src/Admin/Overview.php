<?php
/**
 * Store numbers on the dashboard (ADM-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Admin;

use UltraMax\Core\Lib\Foundation\Jalali;

/**
 * A few numbers that show the store is alive: published products,
 * comments waiting for review, and orders per day for two weeks. Order
 * counts go through `wc_get_orders()`, so they work with HPOS.
 */
final class Overview {

	/**
	 * Numbers and the daily order chart.
	 *
	 * @return array{products: int, pending: int, orders: int, days: list<array{label: string, value: int}>}
	 */
	public static function store(): array {
		$comments = wp_count_comments();
		$products = 0;
		if ( post_type_exists( 'product' ) ) {
			$counts   = wp_count_posts( 'product' );
			$products = isset( $counts->publish ) ? (int) $counts->publish : 0;
		}
		$days  = array();
		$total = 0;
		if ( function_exists( 'wc_get_orders' ) ) {
			$cache = get_transient( 'ultra_max_order_days' );
			if ( is_array( $cache ) && isset( $cache['days'], $cache['total'] ) && is_array( $cache['days'] ) && is_int( $cache['total'] ) ) {
				foreach ( $cache['days'] as $day ) {
					if ( is_array( $day ) && is_string( $day['label'] ?? null ) && is_int( $day['value'] ?? null ) ) {
						$days[] = array(
							'label' => $day['label'],
							'value' => $day['value'],
						);
					}
				}
				$total = $cache['total'];
			} else {
				$today = new \DateTimeImmutable( 'today', wp_timezone() );
				for ( $i = 13; $i >= 0; $i-- ) {
					$day    = $today->modify( "-{$i} days" );
					$ids    = wc_get_orders(
						array(
							'limit'        => -1,
							'return'       => 'ids',
							'status'       => array( 'wc-processing', 'wc-completed', 'wc-on-hold' ),
							'date_created' => $day->getTimestamp() . '...' . ( $day->getTimestamp() + DAY_IN_SECONDS - 1 ),
						)
					);
					$count  = is_array( $ids ) ? count( $ids ) : 0;
					$total += $count;
					$days[] = array(
						'label' => Jalali::format( 'j/n', $day->getTimestamp() ),
						'value' => $count,
					);
				}
				set_transient(
					'ultra_max_order_days',
					array(
						'days'  => $days,
						'total' => $total,
					),
					15 * MINUTE_IN_SECONDS
				);
			}
		}
		return array(
			'products' => $products,
			'pending'  => (int) $comments->moderated,
			'orders'   => $total,
			'days'     => $days,
		);
	}
}
