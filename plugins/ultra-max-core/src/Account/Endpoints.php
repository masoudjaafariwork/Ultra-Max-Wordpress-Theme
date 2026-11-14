<?php
/**
 * The account's own pages and its menu (ACC-04, ACC-05, ACC-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Account;

/**
 * Three pages join WooCommerce's account as its own endpoints, so they get
 * WooCommerce's addresses (`/my-account/lists/`), titles and menu: "My
 * lists", "Reviews and questions" and "Recent views". The menu follows
 * design/account.html; items other plugins add stay, before "Log out".
 */
final class Endpoints {

	public const LISTS    = 'lists';
	public const ACTIVITY = 'activity';
	public const RECENT   = 'recent';
	public const VERSION  = 'ultra_max_account_endpoints';

	/**
	 * Hooks the endpoints and the menu.
	 */
	public static function register(): void {
		add_filter( 'woocommerce_get_query_vars', array( self::class, 'query_vars' ) );
		add_filter( 'woocommerce_account_menu_items', array( self::class, 'menu' ), 20 );
		add_filter( 'woocommerce_endpoint_' . self::LISTS . '_title', array( self::class, 'title_lists' ) );
		add_filter( 'woocommerce_endpoint_' . self::ACTIVITY . '_title', array( self::class, 'title_activity' ) );
		add_filter( 'woocommerce_endpoint_' . self::RECENT . '_title', array( self::class, 'title_recent' ) );
		add_action( 'init', array( self::class, 'flush' ), 99 );
	}

	/**
	 * The endpoints with their addresses.
	 *
	 * @param mixed $vars WooCommerce's query vars.
	 * @return mixed
	 */
	public static function query_vars( mixed $vars ): mixed {
		if ( is_array( $vars ) ) {
			$vars[ self::LISTS ]    = self::LISTS;
			$vars[ self::ACTIVITY ] = self::ACTIVITY;
			$vars[ self::RECENT ]   = self::RECENT;
		}
		return $vars;
	}

	/**
	 * The addresses work once WordPress has rebuilt its rules: once, after
	 * the module was switched on or updated.
	 */
	public static function flush(): void {
		$version = self::LISTS . self::ACTIVITY . self::RECENT . '1';
		if ( get_option( self::VERSION ) !== $version ) {
			update_option( self::VERSION, $version, true );
			flush_rewrite_rules( false );
		}
	}

	/**
	 * The menu: dashboard, orders, lists, reviews, addresses, recent views,
	 * account details, then others, then log out.
	 *
	 * @param mixed $items Items by endpoint.
	 * @return mixed
	 */
	public static function menu( mixed $items ): mixed {
		if ( ! is_array( $items ) ) {
			return $items;
		}
		$labels = array(
			'dashboard'    => __( 'Activity summary', 'ultra-max-core' ),
			'orders'       => __( 'Orders', 'ultra-max-core' ),
			self::LISTS    => __( 'My lists', 'ultra-max-core' ),
			self::ACTIVITY => __( 'Reviews and questions', 'ultra-max-core' ),
			'edit-address' => __( 'Addresses', 'ultra-max-core' ),
			self::RECENT   => __( 'Recent views', 'ultra-max-core' ),
			'edit-account' => __( 'Account details', 'ultra-max-core' ),
		);
		$out    = array();
		foreach ( $labels as $key => $label ) {
			if ( isset( $items[ $key ] ) || in_array( $key, array( self::LISTS, self::ACTIVITY, self::RECENT ), true ) ) {
				$out[ $key ] = $label;
			}
		}
		foreach ( $items as $key => $label ) {
			if ( ! isset( $out[ $key ] ) && 'customer-logout' !== $key ) {
				$out[ $key ] = $label;
			}
		}
		if ( isset( $items['customer-logout'] ) ) {
			$out['customer-logout'] = $items['customer-logout'];
		}
		return $out;
	}

	/**
	 * Title of "My lists".
	 */
	public static function title_lists(): string {
		return __( 'My lists', 'ultra-max-core' );
	}

	/**
	 * Title of "Reviews and questions".
	 */
	public static function title_activity(): string {
		return __( 'Reviews and questions', 'ultra-max-core' );
	}

	/**
	 * Title of "Recent views".
	 */
	public static function title_recent(): string {
		return __( 'Recent views', 'ultra-max-core' );
	}
}
