<?php
/**
 * The wishlist and compare lists of a shopper (SAVE-01, SAVE-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Saved;

/**
 * Where saved products are kept.
 *
 * - The wishlist of a logged in shopper is user meta, so it follows them to
 *   every device. A guest's wishlist is a cookie; when they log in, it is
 *   merged into their account and the cookie goes (SAVE-01).
 * - The compare list is a cookie for everyone: it is a short task, not
 *   something to keep.
 *
 * Lists hold product ids, newest first. Cookies are not HttpOnly: the
 * storefront script reads them to mark the buttons of a cached page.
 */
final class Lists {

	public const WISHLIST = 'wishlist';
	public const COMPARE  = 'compare';
	public const META     = '_um_wishlist';

	/**
	 * Most products in each list.
	 */
	public const LIMITS = array(
		self::WISHLIST => 100,
		self::COMPARE  => 4,
	);

	/**
	 * Cookie of each list.
	 */
	public const COOKIES = array(
		self::WISHLIST => 'um_wishlist',
		self::COMPARE  => 'um_compare',
	);

	/**
	 * Whether a list name is known.
	 *
	 * @param string $kind List name.
	 */
	public static function known( string $kind ): bool {
		return isset( self::LIMITS[ $kind ] );
	}

	/**
	 * Hooks the merge on login.
	 */
	public static function register(): void {
		add_action( 'wp_login', array( self::class, 'merge' ), 10, 2 );
	}

	/**
	 * Clean, unique, positive ids, at most the list's limit.
	 *
	 * @param array  $ids  Ids.
	 * @param string $kind List name.
	 * @phpstan-param array<mixed> $ids
	 * @return list<int>
	 */
	public static function clean( array $ids, string $kind ): array {
		$out = array();
		foreach ( $ids as $id ) {
			if ( is_numeric( $id ) && (int) $id > 0 && ! in_array( (int) $id, $out, true ) ) {
				$out[] = (int) $id;
			}
		}
		return array_slice( $out, 0, self::LIMITS[ $kind ] ?? 0 );
	}

	/**
	 * Ids in a cookie value: numbers joined with dots.
	 *
	 * @param string $value Cookie value.
	 * @param string $kind  List name.
	 * @return list<int>
	 */
	public static function parse( string $value, string $kind ): array {
		return self::clean( explode( '.', $value ), $kind );
	}

	/**
	 * The ids of a list, newest first.
	 *
	 * @param string   $kind List name.
	 * @param int|null $user User id; the current user when null, 0 for the guest cookie.
	 * @return list<int>
	 */
	public static function ids( string $kind, ?int $user = null ): array {
		if ( ! self::known( $kind ) ) {
			return array();
		}
		$user = $user ?? get_current_user_id();
		if ( self::WISHLIST === $kind && $user > 0 ) {
			$stored = get_user_meta( $user, self::META, true );
			return self::clean( is_array( $stored ) ? $stored : array(), $kind );
		}
		return self::parse( self::cookie( $kind ), $kind );
	}

	/**
	 * Replaces a list.
	 *
	 * @param string   $kind List name.
	 * @param array    $ids  Ids, newest first.
	 * @param int|null $user User id; the current user when null.
	 * @phpstan-param list<int> $ids
	 */
	public static function save( string $kind, array $ids, ?int $user = null ): void {
		if ( ! self::known( $kind ) ) {
			return;
		}
		$ids  = self::clean( $ids, $kind );
		$user = $user ?? get_current_user_id();
		if ( self::WISHLIST === $kind && $user > 0 ) {
			if ( array() === $ids ) {
				delete_user_meta( $user, self::META );
			} else {
				update_user_meta( $user, self::META, $ids );
			}
		} else {
			self::set_cookie( $kind, $ids );
		}
		/**
		 * Fires after a saved list changed.
		 *
		 * @param string    $kind List name: wishlist or compare.
		 * @param list<int> $ids  Product ids, newest first.
		 * @param int       $user User id, 0 for a guest.
		 */
		do_action( 'ultra_max_saved_list_changed', $kind, $ids, $user );
	}

	/**
	 * Whether a product is in a list.
	 *
	 * @param string $kind List name.
	 * @param int    $id   Product id.
	 */
	public static function has( string $kind, int $id ): bool {
		return in_array( $id, self::ids( $kind ), true );
	}

	/**
	 * Adds a product at the front.
	 *
	 * @param string $kind List name.
	 * @param int    $id   Product id.
	 * @return list<int> The new list.
	 */
	public static function add( string $kind, int $id ): array {
		$ids = self::clean( array_merge( array( $id ), self::ids( $kind ) ), $kind );
		self::save( $kind, $ids );
		return $ids;
	}

	/**
	 * Takes a product out.
	 *
	 * @param string $kind List name.
	 * @param int    $id   Product id.
	 * @return list<int> The new list.
	 */
	public static function remove( string $kind, int $id ): array {
		$ids = array_values( array_diff( self::ids( $kind ), array( $id ) ) );
		self::save( $kind, $ids );
		return $ids;
	}

	/**
	 * Merges a guest's wishlist into the account that just logged in.
	 *
	 * @param mixed $login User name; unused.
	 * @param mixed $user  User.
	 */
	public static function merge( mixed $login, mixed $user ): void {
		if ( ! $user instanceof \WP_User ) {
			return;
		}
		$guest = self::parse( self::cookie( self::WISHLIST ), self::WISHLIST );
		if ( array() === $guest ) {
			return;
		}
		self::save( self::WISHLIST, array_merge( $guest, self::ids( self::WISHLIST, $user->ID ) ), $user->ID );
		self::set_cookie( self::WISHLIST, array() );
	}

	/**
	 * The raw cookie of a list.
	 *
	 * @param string $kind List name.
	 */
	private static function cookie( string $kind ): string {
		$name = self::COOKIES[ $kind ] ?? '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only digits survive parse().
		$value = '' !== $name && isset( $_COOKIE[ $name ] ) ? wp_unslash( $_COOKIE[ $name ] ) : '';
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Writes a list's cookie for thirty days, or removes it when empty.
	 *
	 * @param string $kind List name.
	 * @param array  $ids  Ids.
	 * @phpstan-param list<int> $ids
	 */
	private static function set_cookie( string $kind, array $ids ): void {
		$name  = self::COOKIES[ $kind ];
		$value = implode( '.', $ids );
		if ( '' === $value ) {
			unset( $_COOKIE[ $name ] );
		} else {
			$_COOKIE[ $name ] = $value;
		}
		if ( headers_sent() ) {
			return;
		}
		$path   = defined( 'COOKIEPATH' ) ? constant( 'COOKIEPATH' ) : '/';
		$domain = defined( 'COOKIE_DOMAIN' ) ? constant( 'COOKIE_DOMAIN' ) : '';
		setcookie(
			$name,
			$value,
			array(
				'expires'  => '' === $value ? time() - YEAR_IN_SECONDS : time() + 30 * DAY_IN_SECONDS,
				'path'     => is_string( $path ) && '' !== $path ? $path : '/',
				'domain'   => is_string( $domain ) ? $domain : '',
				'secure'   => is_ssl(),
				'httponly' => false,
				'samesite' => 'Lax',
			)
		);
	}
}
