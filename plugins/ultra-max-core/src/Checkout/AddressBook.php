<?php
/**
 * The shopper's saved addresses (CHK-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Checkout;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\AddressCards;
use UltraMax\Core\Lib\UI\Html;

/**
 * A logged in shopper's addresses live on their account (`um_addresses`).
 * Every order adds its shipping address when it is new, so the book fills
 * itself; the checkout, classic or block, shows the book as cards, and a
 * card fills the address fields. The account's "addresses" page manages
 * the book with the same functions.
 *
 * @phpstan-type Address array{id: string, first_name: string, last_name: string, phone: string, country: string, state: string, city: string, address_1: string, address_2: string, postcode: string}
 */
final class AddressBook {

	public const META = 'um_addresses';
	public const MOST = 10;
	public const KEYS = array( 'first_name', 'last_name', 'phone', 'country', 'state', 'city', 'address_1', 'address_2', 'postcode' );

	/**
	 * Hooks the saving of new addresses.
	 */
	public static function register(): void {
		add_action( 'woocommerce_checkout_order_processed', array( self::class, 'from_order' ), 20, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( self::class, 'from_order' ), 20, 1 );
	}

	/**
	 * Whether the book is on.
	 */
	public static function enabled(): bool {
		return Store::bool( 'checkout_address_book' );
	}

	/**
	 * A clean address.
	 *
	 * @param array<mixed> $raw Raw values.
	 * @return Address
	 */
	public static function clean( array $raw ): array {
		$out = array();
		foreach ( self::KEYS as $key ) {
			$out[ $key ] = is_scalar( $raw[ $key ] ?? null ) ? sanitize_text_field( (string) $raw[ $key ] ) : '';
		}
		$out['country'] = '' === $out['country'] ? 'IR' : strtoupper( $out['country'] );
		$id             = is_string( $raw['id'] ?? null ) && '' !== $raw['id'] ? sanitize_key( $raw['id'] ) : self::fingerprint( $out );
		return array_merge( array( 'id' => $id ), $out );
	}

	/**
	 * A key that is the same for the same place, however it was typed.
	 *
	 * @param array<string, string> $address Address.
	 */
	public static function fingerprint( array $address ): string {
		$place = Iran::letters( mb_strtolower( ( $address['address_1'] ?? '' ) . ' ' . ( $address['address_2'] ?? '' ) ) );
		return substr( md5( $place . '|' . Iran::digits( $address['postcode'] ?? '' ) ), 0, 10 );
	}

	/**
	 * A user's addresses.
	 *
	 * @param int $user User id.
	 * @return list<Address>
	 */
	public static function of( int $user ): array {
		$stored = get_user_meta( $user, self::META, true );
		$out    = array();
		foreach ( is_array( $stored ) ? $stored : array() as $raw ) {
			if ( is_array( $raw ) ) {
				$address = self::clean( $raw );
				if ( '' !== $address['address_1'] ) {
					$out[ $address['id'] ] = $address;
				}
			}
		}
		return array_values( $out );
	}

	/**
	 * Saves a user's addresses.
	 *
	 * @param int   $user      User id.
	 * @param array $addresses Addresses.
	 * @phpstan-param list<Address> $addresses
	 */
	public static function save( int $user, array $addresses ): void {
		$list = array_slice( array_values( $addresses ), 0, self::MOST );
		array() === $list ? delete_user_meta( $user, self::META ) : update_user_meta( $user, self::META, $list );
	}

	/**
	 * Adds an address first, or moves a known one first.
	 *
	 * @param int                  $user User id.
	 * @param array<string, mixed> $raw  Address.
	 */
	public static function add( int $user, array $raw ): string {
		$address = self::clean( $raw );
		$address = array_merge( $address, array( 'id' => self::fingerprint( $address ) ) );
		$others  = array_values( array_filter( self::of( $user ), static fn ( array $known ): bool => $known['id'] !== $address['id'] ) );
		self::save( $user, array_merge( array( $address ), $others ) );
		return $address['id'];
	}

	/**
	 * Removes an address.
	 *
	 * @param int    $user User id.
	 * @param string $id   Address id.
	 */
	public static function remove( int $user, string $id ): void {
		self::save( $user, array_values( array_filter( self::of( $user ), static fn ( array $known ): bool => $known['id'] !== $id ) ) );
	}

	/**
	 * Adds an order's shipping address to its customer's book.
	 *
	 * @param mixed $order Order, or its id from the classic checkout.
	 */
	public static function from_order( mixed $order ): void {
		$order = is_numeric( $order ) ? wc_get_order( (int) $order ) : $order;
		if ( ! $order instanceof \WC_Order || $order->get_customer_id() <= 0 || ! self::enabled() ) {
			return;
		}
		$group = '' !== $order->get_shipping_address_1() ? 'shipping' : 'billing';
		$raw   = array();
		foreach ( self::KEYS as $key ) {
			$getter      = 'get_' . $group . '_' . $key;
			$raw[ $key ] = is_callable( array( $order, $getter ) ) ? (string) $order->{$getter}() : '';
		}
		if ( '' === $raw['phone'] ) {
			$raw['phone'] = $order->get_billing_phone();
		}
		if ( '' !== $raw['address_1'] ) {
			self::add( $order->get_customer_id(), $raw );
		}
	}

	/**
	 * One line about an address: the recipient, the mobile, the postal code.
	 *
	 * @param array $address Address.
	 * @phpstan-param Address $address
	 */
	public static function summary( array $address ): string {
		$parts = array( trim( $address['first_name'] . ' ' . $address['last_name'] ) );
		if ( '' !== $address['phone'] ) {
			$parts[] = $address['phone'];
		}
		if ( '' !== $address['postcode'] ) {
			/* translators: %s: postal code. */
			$parts[] = sprintf( __( 'Postal code %s', 'ultra-max-core' ), $address['postcode'] );
		}
		return implode( ' · ', array_filter( $parts ) );
	}

	/**
	 * The place of an address in one line: province, city, street.
	 *
	 * @param array $address Address.
	 * @phpstan-param Address $address
	 */
	public static function place( array $address ): string {
		$states = WC()->countries->get_states( $address['country'] );
		$state  = is_array( $states ) && isset( $states[ $address['state'] ] ) ? wp_strip_all_tags( html_entity_decode( (string) $states[ $address['state'] ], ENT_QUOTES, 'UTF-8' ) ) : $address['state'];
		return implode( '، ', array_filter( array( $state, $address['city'], $address['address_1'], $address['address_2'] ) ) );
	}

	/**
	 * The cards of the current shopper's book, or nothing.
	 *
	 * @param string $selected Address id to mark as chosen; the first when empty.
	 * @param bool   $with_new Whether to add the "new address" button.
	 */
	public static function cards( string $selected = '', bool $with_new = true ): Html {
		if ( ! self::enabled() || ! is_user_logged_in() ) {
			return Html::none();
		}
		$book = self::of( get_current_user_id() );
		if ( array() === $book ) {
			return Html::none();
		}
		$selected = '' === $selected ? $book[0]['id'] : $selected;
		$cards    = array();
		foreach ( $book as $address ) {
			$fields = $address;
			unset( $fields['id'] );
			$cards[] = array(
				'id'       => $address['id'],
				'title'    => self::place( $address ),
				'text'     => self::summary( $address ),
				'fields'   => $fields,
				'selected' => $address['id'] === $selected,
			);
		}
		$props = array(
			'addresses'      => $cards,
			'label'          => __( 'Your saved addresses', 'ultra-max-core' ),
			'selected_label' => __( 'Chosen', 'ultra-max-core' ),
		);
		if ( $with_new ) {
			$props['new'] = __( 'Add a new address', 'ultra-max-core' );
		}
		return AddressCards::render( $props );
	}

	/**
	 * The id of the book's address that matches the customer's current
	 * address, or an empty text.
	 */
	public static function current(): string {
		if ( ! is_user_logged_in() || ! WC()->customer instanceof \WC_Customer ) {
			return '';
		}
		$now = self::fingerprint(
			array(
				'address_1' => WC()->customer->get_billing_address_1(),
				'address_2' => WC()->customer->get_billing_address_2(),
				'postcode'  => WC()->customer->get_billing_postcode(),
			)
		);
		foreach ( self::of( get_current_user_id() ) as $address ) {
			if ( $address['id'] === $now ) {
				return $now;
			}
		}
		return '';
	}
}
