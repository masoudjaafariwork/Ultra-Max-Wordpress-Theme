<?php
/**
 * Search engine rules for filtered product lists (PLP-13).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Shop;

use UltraMax\Core\Lib\Settings\Store;

/**
 * - One choice of filters has one address: a list asked for in another
 *   spelling, such as the arrays a form without JavaScript sends, is
 *   redirected to the tidy address.
 * - A list with one chosen value ("audio, brand Lumo") may be indexed and
 *   is its own canonical page. Every other combination, a sort order or a
 *   later page of a filtered list gets `noindex, follow` and points its
 *   canonical link at the unfiltered list, so search engines spend no time
 *   on pages without value of their own.
 *
 * Yoast SEO and Rank Math get the same answer through their own filters;
 * without them, the canonical link is printed here.
 */
final class Seo {

	/**
	 * Registers the hooks.
	 */
	public static function register(): void {
		add_action( 'template_redirect', array( self::class, 'tidy' ), 5 );
		add_filter( 'wp_robots', array( self::class, 'robots' ), 20 );
		add_filter( 'wpseo_robots', array( self::class, 'yoast_robots' ) );
		add_filter( 'rank_math/frontend/robots', array( self::class, 'rank_math_robots' ) );
		add_filter( 'wpseo_canonical', array( self::class, 'canonical_filter' ) );
		add_filter( 'rank_math/frontend/canonical', array( self::class, 'canonical_filter' ) );
		add_action( 'wp_head', array( self::class, 'canonical_tag' ), 1 );
	}

	/**
	 * Whether the current list may be indexed.
	 */
	public static function indexable(): bool {
		$state = State::current();
		if ( $state->is_empty() && '' === $state->orderby ) {
			return true;
		}
		$paged = max( 1, absint( is_scalar( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 0 ) );
		return $paged < 2 && $state->indexable( Store::bool( 'shop_index_single' ) );
	}

	/**
	 * The canonical address of the current list.
	 */
	public static function canonical(): string {
		$scope = Scope::current();
		$base  = $scope->url();
		if ( ! self::indexable() ) {
			return $base;
		}
		$url   = State::current()->url( $base );
		$paged = max( 1, absint( is_scalar( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 0 ) );
		if ( $paged > 1 ) {
			$link = get_pagenum_link( $paged, false );
			return is_string( $link ) ? $link : $url;
		}
		return $url;
	}

	/**
	 * Redirects other spellings of a filter choice to the tidy address.
	 */
	public static function tidy(): void {
		if ( ! Scope::is_list() ) {
			return;
		}
		$messy = false;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading public filter choices of a GET list.
		foreach ( wp_unslash( $_GET ) as $key => $value ) {
			if ( ! is_string( $key ) ) {
				continue;
			}
			$ours = '' !== State::taxonomy( $key ) || in_array( $key, array( 'in_stock', 'on_sale', 'min_price', 'max_price', 'rating_filter' ), true );
			if ( $ours && ( is_array( $value ) || '' === $value ) ) {
				$messy = true;
			}
		}
		if ( ! $messy ) {
			return;
		}
		$target = State::current()->url( Scope::current()->url() );
		if ( wp_safe_redirect( $target, 302, 'Ultra Max' ) ) {
			exit;
		}
	}

	/**
	 * Adds noindex to lists that should not be indexed.
	 *
	 * @param mixed $robots Robots directives.
	 * @return mixed
	 */
	public static function robots( mixed $robots ): mixed {
		if ( ! is_array( $robots ) || ! Scope::is_list() || self::indexable() ) {
			return $robots;
		}
		unset( $robots['index'], $robots['max-image-preview'] );
		$robots['noindex'] = true;
		$robots['follow']  = true;
		return $robots;
	}

	/**
	 * Yoast SEO's robots text.
	 *
	 * @param mixed $robots Robots text, such as "index, follow".
	 * @return mixed
	 */
	public static function yoast_robots( mixed $robots ): mixed {
		return Scope::is_list() && ! self::indexable() ? 'noindex, follow' : $robots;
	}

	/**
	 * Rank Math's robots list.
	 *
	 * @param mixed $robots Robots directives.
	 * @return mixed
	 */
	public static function rank_math_robots( mixed $robots ): mixed {
		if ( ! is_array( $robots ) || ! Scope::is_list() || self::indexable() ) {
			return $robots;
		}
		$robots['index']  = 'noindex';
		$robots['follow'] = 'follow';
		return $robots;
	}

	/**
	 * The canonical address for Yoast SEO and Rank Math.
	 *
	 * @param mixed $url Their address.
	 * @return mixed
	 */
	public static function canonical_filter( mixed $url ): mixed {
		return Scope::is_list() ? self::canonical() : $url;
	}

	/**
	 * Whether an SEO plugin prints the canonical link itself.
	 */
	private static function seo_plugin(): bool {
		/**
		 * Filters whether another plugin prints the canonical link of product lists.
		 *
		 * @param bool $other Whether Yoast SEO or Rank Math is active.
		 */
		return (bool) apply_filters( 'ultra_max_shop_canonical_elsewhere', defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) );
	}

	/**
	 * Prints the canonical link of a list when no SEO plugin does.
	 */
	public static function canonical_tag(): void {
		if ( Scope::is_list() && ! self::seo_plugin() ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( self::canonical() ) );
		}
	}
}
