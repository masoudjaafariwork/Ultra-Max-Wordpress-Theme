<?php
/**
 * The wishlist, compare and quick view modules (SAVE-01 to SAVE-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Saved;

use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Lib\UI\Toast;
use UltraMax\Core\Store\Pages;

/**
 * Three modules with their own switches. Each one off registers nothing:
 * no button, no page, no file. While the wishlist or compare module is on,
 * one small script marks saved products and fills the counters on every
 * page, because a header counter can be anywhere.
 */
final class Saved {

	public const WISHLIST = 'wishlist';
	public const COMPARE  = 'compare';
	public const QUICK    = 'quick-view';

	/**
	 * Whether a module is on and WooCommerce is active.
	 *
	 * @param string $module wishlist, compare or quick-view.
	 */
	public static function enabled( string $module ): bool {
		return Modules::enabled( $module ) && function_exists( 'wc_get_product' );
	}

	/**
	 * Whether the wishlist or compare list is on.
	 */
	public static function lists(): bool {
		return self::enabled( self::WISHLIST ) || self::enabled( self::COMPARE );
	}

	/**
	 * Registers the modules that are on.
	 */
	public static function register(): void {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		if ( self::lists() ) {
			Lists::register();
			Endpoint::register();
			Buttons::register();
			add_filter( 'ultra_max_pages', array( self::class, 'pages' ) );
			add_action( 'template_redirect', array( self::class, 'no_cache' ) );
			add_filter( 'ultra_max_content_prose', array( self::class, 'prose' ) );
		}
		if ( self::lists() || self::enabled( self::QUICK ) ) {
			add_action( 'wp_enqueue_scripts', array( self::class, 'assets' ), 20 );
			add_action( 'wp_footer', array( self::class, 'toast' ), 5 );
		}
		if ( self::enabled( self::WISHLIST ) ) {
			Wishlist::register();
		}
		if ( self::enabled( self::COMPARE ) ) {
			Compare::register();
		}
		if ( self::enabled( self::QUICK ) ) {
			QuickView::register();
		}
	}

	/**
	 * The pages of the modules that are on.
	 *
	 * @param mixed $pages Pages.
	 * @return mixed
	 */
	public static function pages( mixed $pages ): mixed {
		if ( ! is_array( $pages ) ) {
			return $pages;
		}
		if ( self::enabled( self::WISHLIST ) ) {
			$pages['wishlist'] = array(
				'title'     => __( 'Wishlist', 'ultra-max-core' ),
				'slug'      => 'wishlist',
				'shortcode' => Wishlist::SHORTCODE,
			);
		}
		if ( self::enabled( self::COMPARE ) ) {
			$pages['compare'] = array(
				'title'     => __( 'Compare products', 'ultra-max-core' ),
				'slug'      => 'compare',
				'shortcode' => Compare::SHORTCODE,
			);
		}
		return $pages;
	}

	/**
	 * The wishlist and compare pages show one shopper's lists: no page cache
	 * may keep them.
	 */
	public static function no_cache(): void {
		if ( Pages::is( 'wishlist' ) || Pages::is( 'compare' ) ) {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- The constant page cache plugins read.
			}
			nocache_headers();
		}
	}

	/**
	 * The wishlist and compare pages hold cards and a table, not writing.
	 *
	 * @param mixed $prose Whether the theme styles the content as writing.
	 * @return mixed
	 */
	public static function prose( mixed $prose ): mixed {
		return Pages::is( 'wishlist' ) || Pages::is( 'compare' ) || self::has_shortcode() ? false : $prose;
	}

	/**
	 * Queues the saved script on every page, and the card styles on the
	 * wishlist and compare pages.
	 */
	public static function assets(): void {
		Assets::enqueue_widget( 'saved' );
		if ( Pages::is( 'wishlist' ) || Pages::is( 'compare' ) || self::has_shortcode() ) {
			Assets::enqueue_widget( 'product-card' );
		}
	}

	/**
	 * Whether the page's content holds the wishlist or compare shortcode.
	 */
	private static function has_shortcode(): bool {
		$post = get_post();
		return is_singular() && $post instanceof \WP_Post && ( has_shortcode( $post->post_content, Wishlist::SHORTCODE ) || has_shortcode( $post->post_content, Compare::SHORTCODE ) );
	}

	/**
	 * Prints the empty toast once. It names the lists that are on, so the
	 * script asks for nothing else.
	 */
	public static function toast(): void {
		$lists = array();
		if ( self::enabled( self::WISHLIST ) ) {
			$lists[] = Lists::WISHLIST;
		}
		if ( self::enabled( self::COMPARE ) ) {
			$lists[] = Lists::COMPARE;
		}
		Toast::render(
			array(
				'close' => __( 'Close', 'ultra-max-core' ),
				'lists' => implode( ',', $lists ),
			)
		)->print();
	}
}
