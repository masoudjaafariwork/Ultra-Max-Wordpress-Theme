<?php
/**
 * The app-like bottom bar of phones (HDR-10).
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

namespace UltraMax\Theme;

use UltraMax\Theme\Lib\Settings\Modules;
use UltraMax\Theme\Lib\Settings\Store;
use UltraMax\Theme\Lib\UI\BottomNav as Component;

/**
 * A switchable module: off, it prints nothing and loads nothing. Its
 * items come from Ultra Max > Settings > Bottom bar.
 */
final class BottomNav {

	/**
	 * Item kinds the settings offer.
	 *
	 * @return list<array{value: string, label: string}>
	 */
	public static function kinds(): array {
		$kinds = array(
			'home'       => __( 'Home', 'ultra-max' ),
			'categories' => __( 'Categories (opens the menu)', 'ultra-max' ),
			'cart'       => __( 'Cart', 'ultra-max' ),
			'account'    => __( 'My account', 'ultra-max' ),
			'wishlist'   => __( 'Wishlist', 'ultra-max' ),
			'search'     => __( 'Search', 'ultra-max' ),
			'link'       => __( 'Another link', 'ultra-max' ),
		);
		$out   = array();
		foreach ( $kinds as $value => $label ) {
			$out[] = array(
				'value' => $value,
				'label' => $label,
			);
		}
		return $out;
	}

	/**
	 * Default items.
	 *
	 * @return list<array<string, string>>
	 */
	public static function defaults(): array {
		return array(
			array(
				'type'  => 'home',
				'label' => '',
				'url'   => '',
			),
			array(
				'type'  => 'categories',
				'label' => '',
				'url'   => '',
			),
			array(
				'type'  => 'cart',
				'label' => '',
				'url'   => '',
			),
			array(
				'type'  => 'wishlist',
				'label' => '',
				'url'   => '',
			),
			array(
				'type'  => 'account',
				'label' => '',
				'url'   => '',
			),
		);
	}

	/**
	 * Registers the bar when the module is on.
	 */
	public static function register(): void {
		if ( ! Modules::enabled( 'bottom-nav' ) || array() === self::items() ) {
			return;
		}
		Assets::module( 'bottom-nav' );
		add_action( 'wp_footer', array( self::class, 'render' ), 4 );
		add_filter( 'body_class', array( self::class, 'body_class' ) );
	}

	/**
	 * Body class that leaves room for the bar.
	 *
	 * @param mixed $classes Classes.
	 * @return mixed
	 */
	public static function body_class( mixed $classes ): mixed {
		if ( is_array( $classes ) ) {
			$classes[] = 'um-has-bottom-nav';
		}
		return $classes;
	}

	/**
	 * The items of the bar.
	 *
	 * @return list<array{label: string, href: string, icon: string, current?: bool, count?: int, count_text?: string, count_key?: string, opens?: string}>
	 */
	public static function items(): array {
		$woo   = function_exists( 'wc_get_page_permalink' );
		$items = array();
		foreach ( Store::rows( 'bottom_nav_items' ) as $row ) {
			$type  = is_string( $row['type'] ?? null ) ? $row['type'] : '';
			$label = is_string( $row['label'] ?? null ) ? trim( $row['label'] ) : '';
			$url   = is_string( $row['url'] ?? null ) ? $row['url'] : '';
			$item  = match ( $type ) {
				'home'       => array(
					'label'   => __( 'Home', 'ultra-max' ),
					'href'    => home_url( '/' ),
					'icon'    => 'home',
					'current' => is_front_page(),
				),
				'categories' => array(
					'label' => __( 'Categories', 'ultra-max' ),
					'href'  => $woo ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
					'icon'  => 'grid',
					'opens' => '.um-mobile-drawer',
				),
				'cart'       => $woo ? self::cart() : null,
				'account'    => $woo ? array(
					'label'   => __( 'My account', 'ultra-max' ),
					'href'    => wc_get_page_permalink( 'myaccount' ),
					'icon'    => 'user',
					'current' => function_exists( 'is_account_page' ) && is_account_page(),
				) : null,
				'wishlist'   => array(
					'label' => __( 'Wishlist', 'ultra-max' ),
					'href'  => '' !== $url ? $url : ( $woo ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' ) ),
					'icon'  => 'heart',
				),
				'search'     => array(
					'label'   => __( 'Search', 'ultra-max' ),
					'href'    => add_query_arg( 's', '', home_url( '/' ) ),
					'icon'    => 'search',
					'current' => is_search(),
				),
				'link'       => '' === $url ? null : array(
					'label'   => '' === $label ? __( 'Link', 'ultra-max' ) : $label,
					'href'    => $url,
					'icon'    => 'link',
					'current' => false,
				),
				default      => null,
			};
			if ( null === $item ) {
				continue;
			}
			if ( '' !== $label ) {
				$item['label'] = $label;
			}
			$items[] = $item;
		}
		return $items;
	}

	/**
	 * The cart item with its count.
	 *
	 * @return array{label: string, href: string, icon: string, current: bool, count: int, count_text: string, count_key: string}
	 */
	private static function cart(): array {
		$count = function_exists( 'WC' ) && null !== WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
		return array(
			'label'      => __( 'Cart', 'ultra-max' ),
			'href'       => wc_get_cart_url(),
			'icon'       => 'cart',
			'current'    => function_exists( 'is_cart' ) && is_cart(),
			'count'      => $count,
			'count_text' => Dates::digits( number_format_i18n( $count ) ),
			'count_key'  => 'cart',
		);
	}

	/**
	 * Prints the bar.
	 */
	public static function render(): void {
		Component::render(
			array(
				'label' => __( 'Bottom navigation', 'ultra-max' ),
				'items' => self::items(),
			)
		)->print();
	}
}
