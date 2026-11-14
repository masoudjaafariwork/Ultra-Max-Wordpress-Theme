<?php
/**
 * Pages the store's modules need, such as the wishlist (SAVE-01, SAVE-02, ACC-09).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Store;

/**
 * Like WooCommerce's cart and checkout pages: each module that needs a page
 * declares it through `ultra_max_pages`, and the page is made once, with
 * the module's shortcode as its content, the first time an administrator
 * opens the dashboard with the module on. The store owner may rename it,
 * move the shortcode into any other page, or design it with Elementor;
 * a page in the bin is made again.
 *
 * Reading a page's address costs one option read, never a query.
 *
 * @phpstan-type PageEntry array{title: string, slug: string, shortcode: string}
 */
final class Pages {

	public const OPTION = 'ultra_max_pages';

	/**
	 * Hooks the making of missing pages.
	 */
	public static function register(): void {
		add_action( 'admin_init', array( self::class, 'ensure' ) );
	}

	/**
	 * Pages declared by the modules that are on.
	 *
	 * @return array<string, PageEntry>
	 */
	public static function declared(): array {
		/**
		 * Filters the pages the store needs, keyed by name.
		 *
		 * @param array<string, array{title: string, slug: string, shortcode: string}> $pages Pages.
		 */
		$pages = apply_filters( 'ultra_max_pages', array() );
		$out   = array();
		foreach ( is_array( $pages ) ? $pages : array() as $key => $page ) {
			if ( is_string( $key ) && is_array( $page ) && is_string( $page['title'] ?? null ) && is_string( $page['slug'] ?? null ) && is_string( $page['shortcode'] ?? null ) ) {
				$out[ $key ] = array(
					'title'     => $page['title'],
					'slug'      => $page['slug'],
					'shortcode' => $page['shortcode'],
				);
			}
		}
		return $out;
	}

	/**
	 * Stored page ids by name.
	 *
	 * @return array<string, int>
	 */
	private static function stored(): array {
		$stored = get_option( self::OPTION, array() );
		$out    = array();
		foreach ( is_array( $stored ) ? $stored : array() as $key => $id ) {
			if ( is_string( $key ) && is_numeric( $id ) ) {
				$out[ $key ] = (int) $id;
			}
		}
		return $out;
	}

	/**
	 * The id of a page, 0 when it was not made yet.
	 *
	 * @param string $key Page name.
	 */
	public static function id( string $key ): int {
		return self::stored()[ $key ] ?? 0;
	}

	/**
	 * The address of a page, or the shop when it does not exist.
	 *
	 * @param string $key Page name.
	 */
	public static function url( string $key ): string {
		$id   = self::id( $key );
		$link = $id > 0 && 'publish' === get_post_status( $id ) ? get_permalink( $id ) : false;
		if ( is_string( $link ) ) {
			return $link;
		}
		$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
		return '' !== $shop ? $shop : home_url( '/' );
	}

	/**
	 * Whether the page being shown is this one.
	 *
	 * @param string $key Page name.
	 */
	public static function is( string $key ): bool {
		$id = self::id( $key );
		return $id > 0 && is_page( $id );
	}

	/**
	 * Makes the declared pages that do not exist.
	 */
	public static function ensure(): void {
		if ( ! current_user_can( 'manage_options' ) && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}
		$stored  = self::stored();
		$changed = false;
		foreach ( self::declared() as $key => $page ) {
			$id     = $stored[ $key ] ?? 0;
			$status = $id > 0 ? get_post_status( $id ) : false;
			if ( false !== $status && 'trash' !== $status ) {
				continue;
			}
			$made = wp_insert_post(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'post_title'     => $page['title'],
					'post_name'      => $page['slug'],
					'post_content'   => '<!-- wp:shortcode -->[' . $page['shortcode'] . ']<!-- /wp:shortcode -->',
					'comment_status' => 'closed',
				),
				true
			);
			if ( is_int( $made ) && $made > 0 ) {
				$stored[ $key ] = $made;
				$changed        = true;
			}
		}
		if ( $changed ) {
			update_option( self::OPTION, $stored, true );
		}
	}
}
