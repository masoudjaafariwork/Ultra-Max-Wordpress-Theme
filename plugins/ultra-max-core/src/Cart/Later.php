<?php
/**
 * The "buy later" list (CART-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Cart;

use UltraMax\Core\Product\Cart as Adder;
use UltraMax\Core\Product\Quantity;

/**
 * Products moved out of the cart to buy another time, with the options
 * the shopper chose. A logged in shopper's list lives on the account, so
 * it follows them to every device (CART-07); a guest's lives in their
 * WooCommerce session and joins the account list when they log in.
 *
 * @phpstan-type Entry array{product: int, variation: int, values: array<string, string>, added: int}
 */
final class Later {

	public const META = 'um_later';
	public const MOST = 50;

	/**
	 * Hooks the merge on login.
	 */
	public static function register(): void {
		add_action( 'wp_login', array( self::class, 'merge' ), 20, 2 );
	}

	/**
	 * Whether the list is on.
	 */
	public static function enabled(): bool {
		return \UltraMax\Core\Lib\Settings\Store::bool( 'cart_later' );
	}

	/**
	 * A clean list from a stored value.
	 *
	 * @param mixed $value Stored value.
	 * @return array<string, Entry>
	 */
	public static function clean( mixed $value ): array {
		$out = array();
		foreach ( is_array( $value ) ? $value : array() as $entry ) {
			if ( ! is_array( $entry ) || ! is_numeric( $entry['product'] ?? null ) || (int) $entry['product'] <= 0 ) {
				continue;
			}
			$values = array();
			foreach ( is_array( $entry['values'] ?? null ) ? $entry['values'] : array() as $name => $choice ) {
				if ( is_string( $name ) && is_scalar( $choice ) ) {
					$values[ sanitize_title( $name ) ] = sanitize_text_field( (string) $choice );
				}
			}
			ksort( $values );
			$clean                      = array(
				'product'   => (int) $entry['product'],
				'variation' => is_numeric( $entry['variation'] ?? null ) ? max( 0, (int) $entry['variation'] ) : 0,
				'values'    => $values,
				'added'     => is_numeric( $entry['added'] ?? null ) ? (int) $entry['added'] : 0,
			);
			$out[ self::key( $clean ) ] = $clean;
		}
		return array_slice( $out, -self::MOST, null, true );
	}

	/**
	 * The key of an entry: the same product with the same options is one entry.
	 *
	 * @param array $entry Entry.
	 * @phpstan-param Entry $entry
	 */
	public static function key( array $entry ): string {
		return substr( md5( (string) wp_json_encode( array( $entry['product'], $entry['variation'], $entry['values'] ) ) ), 0, 12 );
	}

	/**
	 * The shopper's list.
	 *
	 * @return array<string, Entry>
	 */
	public static function all(): array {
		if ( is_user_logged_in() ) {
			return self::clean( get_user_meta( get_current_user_id(), self::META, true ) );
		}
		$session = function_exists( 'WC' ) ? WC()->session : null;
		return $session instanceof \WC_Session ? self::clean( $session->get( self::META ) ) : array();
	}

	/**
	 * Saves the shopper's list.
	 *
	 * @param array $entries Entries.
	 * @phpstan-param array<string, Entry> $entries
	 */
	private static function save( array $entries ): void {
		$list = array_values( self::clean( $entries ) );
		if ( is_user_logged_in() ) {
			array() === $list ? delete_user_meta( get_current_user_id(), self::META ) : update_user_meta( get_current_user_id(), self::META, $list );
			return;
		}
		$session = function_exists( 'WC' ) ? WC()->session : null;
		if ( $session instanceof \WC_Session ) {
			if ( $session instanceof \WC_Session_Handler && ! $session->has_session() ) {
				$session->set_customer_session_cookie( true );
			}
			$session->set( self::META, $list );
		}
	}

	/**
	 * Products of the list that can still be shown, newest first.
	 *
	 * @return list<array{key: string, entry: Entry, product: \WC_Product}>
	 */
	public static function items(): array {
		$out = array();
		foreach ( array_reverse( self::all(), true ) as $key => $entry ) {
			$product = wc_get_product( $entry['product'] );
			if ( ! $product instanceof \WC_Product || 'publish' !== $product->get_status() || ! $product->is_visible() || '' !== $product->get_post_password() ) {
				continue;
			}
			$out[] = array(
				'key'     => (string) $key,
				'entry'   => $entry,
				'product' => $product,
			);
		}
		return $out;
	}

	/**
	 * Moves a cart line to the list.
	 *
	 * @param string $cart_key Cart line key.
	 */
	public static function move( string $cart_key ): bool {
		$cart = WC()->cart;
		$item = $cart->get_cart_item( $cart_key );
		if ( array() === $item ) {
			return false;
		}
		$values = array();
		foreach ( is_array( $item['variation'] ?? null ) ? $item['variation'] : array() as $name => $value ) {
			$values[ (string) $name ] = is_scalar( $value ) ? (string) $value : '';
		}
		$entry   = array(
			'product'   => is_numeric( $item['product_id'] ?? null ) ? (int) $item['product_id'] : 0,
			'variation' => is_numeric( $item['variation_id'] ?? null ) ? (int) $item['variation_id'] : 0,
			'values'    => $values,
			'added'     => time(),
		);
		$entries = self::all();
		$clean   = self::clean( array( $entry ) );
		$entries = array_merge( $entries, $clean );
		self::save( $entries );
		return $cart->remove_cart_item( $cart_key );
	}

	/**
	 * Moves an entry back to the cart.
	 *
	 * @param string $key Entry key.
	 * @return true|string True, or why not.
	 */
	public static function restore( string $key ): bool|string {
		$entries = self::all();
		if ( ! isset( $entries[ $key ] ) ) {
			return __( 'This product is no longer in your list.', 'ultra-max-core' );
		}
		$entry    = $entries[ $key ];
		$product  = wc_get_product( $entry['variation'] > 0 ? $entry['variation'] : $entry['product'] );
		$quantity = $product instanceof \WC_Product ? Quantity::limits( $product )['min'] : 1;
		$result   = Adder::add( $entry['product'], $quantity, $entry['variation'], $entry['values'] );
		if ( true === $result ) {
			unset( $entries[ $key ] );
			self::save( $entries );
		}
		return $result;
	}

	/**
	 * Removes an entry.
	 *
	 * @param string $key Entry key.
	 */
	public static function forget( string $key ): void {
		$entries = self::all();
		unset( $entries[ $key ] );
		self::save( $entries );
	}

	/**
	 * A guest's list joins the account's when they log in.
	 *
	 * @param mixed $login User name.
	 * @param mixed $user  User.
	 */
	public static function merge( mixed $login, mixed $user ): void {
		$session = function_exists( 'WC' ) ? WC()->session : null;
		if ( ! $user instanceof \WP_User || ! $session instanceof \WC_Session ) {
			return;
		}
		$guest = self::clean( $session->get( self::META ) );
		if ( array() === $guest ) {
			return;
		}
		$own = self::clean( get_user_meta( $user->ID, self::META, true ) );
		update_user_meta( $user->ID, self::META, array_values( self::clean( array_merge( $own, $guest ) ) ) );
		$session->set( self::META, null );
	}
}
