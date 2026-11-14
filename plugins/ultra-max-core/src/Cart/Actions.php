<?php
/**
 * Changes of the cart in place (CART-01, CART-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Cart;

use UltraMax\Core\Product\Quantity;

/**
 * The cart page, its widgets and the side cart send every change to
 * `?wc-ajax=um_cart`: a quantity, a removal, a move to or from the "buy
 * later" list, a discount code. The answer draws the parts the page shows
 * again, with WooCommerce's fragments for the header counters.
 *
 * Every change needs the `um-cart` token (WooCommerce bases it on the
 * shopper's session for guests). Showing the side cart needs none.
 *
 * The same "later" actions work without JavaScript as links
 * (`?um-cart=later&key=…`), which go back to the page they came from.
 */
final class Actions {

	public const AJAX  = 'um_cart';
	public const NONCE = 'um-cart';
	public const QUERY = 'um-cart';

	/**
	 * Hooks the endpoint and the link handler.
	 */
	public static function register(): void {
		add_action( 'wc_ajax_' . self::AJAX, array( self::class, 'handle' ) );
		add_action( 'wp_loaded', array( self::class, 'links' ), 30 );
	}

	/**
	 * The endpoint address.
	 */
	public static function endpoint(): string {
		return \WC_AJAX::get_endpoint( self::AJAX );
	}

	/**
	 * A posted text value.
	 *
	 * @param string $key Field name.
	 */
	private static function posted( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Checked in handle(); sanitized below.
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		return is_string( $value ) ? sanitize_text_field( $value ) : '';
	}

	/**
	 * Applies one change.
	 *
	 * @param string $action Action.
	 * @param string $key    Line or entry key.
	 * @param int    $qty    Quantity.
	 * @param string $code   Coupon code.
	 * @return array{0: bool, 1: string} Success and a message.
	 */
	public static function apply( string $action, string $key = '', int $qty = 0, string $code = '' ): array {
		$cart = WC()->cart;
		switch ( $action ) {
			case 'set':
				$item = $cart->get_cart_item( $key );
				if ( array() === $item || ! ( $item['data'] ?? null ) instanceof \WC_Product ) {
					return array( false, __( 'This item is no longer in your cart.', 'ultra-max-core' ) );
				}
				if ( $qty <= 0 ) {
					return self::apply( 'remove', $key );
				}
				$product = $item['data'];
				$problem = Quantity::problem( $product, $qty );
				if ( '' === $problem && ! $product->has_enough_stock( $qty ) ) {
					/* translators: %s: number. */
					$problem = sprintf( __( 'Only %s left.', 'ultra-max-core' ), \UltraMax\Core\Store\Format::number( (int) $product->get_stock_quantity() ) );
				}
				if ( '' !== $problem ) {
					return array( false, $problem );
				}
				/** This filter is documented in WooCommerce's WC_Form_Handler::update_cart_action(). */
				$passed = apply_filters( 'woocommerce_update_cart_validation', true, $key, $item, $qty ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
				if ( true !== $passed ) {
					return array( false, self::errors( __( 'This quantity cannot be ordered.', 'ultra-max-core' ) ) );
				}
				$cart->set_quantity( $key, $qty, true );
				return array( true, __( 'Your cart was updated.', 'ultra-max-core' ) );
			case 'remove':
				if ( ! $cart->remove_cart_item( $key ) ) {
					return array( false, __( 'This item is no longer in your cart.', 'ultra-max-core' ) );
				}
				return array( true, __( 'The item was removed from your cart.', 'ultra-max-core' ) );
			case 'later':
				if ( ! Later::enabled() || ! Later::move( $key ) ) {
					return array( false, __( 'This item is no longer in your cart.', 'ultra-max-core' ) );
				}
				return array( true, __( 'The item was moved to your buy later list.', 'ultra-max-core' ) );
			case 'restore':
				$result = Later::restore( $key );
				return true === $result ? array( true, __( 'The item was moved to your cart.', 'ultra-max-core' ) ) : array( false, $result );
			case 'restore-all':
				$problems = array();
				foreach ( Later::items() as $saved ) {
					$result = Later::restore( $saved['key'] );
					if ( true !== $result ) {
						$problems[] = $result;
					}
				}
				return array() === $problems ? array( true, __( 'Every item was moved to your cart.', 'ultra-max-core' ) ) : array( false, implode( ' ', array_unique( $problems ) ) );
			case 'forget':
				Later::forget( $key );
				return array( true, __( 'The item was removed from your list.', 'ultra-max-core' ) );
			case 'coupon':
				$code = wc_format_coupon_code( $code );
				if ( '' === $code ) {
					return array( false, __( 'Enter a discount code.', 'ultra-max-core' ) );
				}
				$applied = $cart->apply_coupon( $code );
				$message = self::errors( '' );
				wc_clear_notices();
				return $applied ? array( true, __( 'The discount code was applied.', 'ultra-max-core' ) ) : array( false, '' === $message ? __( 'This discount code cannot be used.', 'ultra-max-core' ) : $message );
			case 'uncoupon':
				$cart->remove_coupon( wc_format_coupon_code( $code ) );
				wc_clear_notices();
				return array( true, __( 'The discount code was removed.', 'ultra-max-core' ) );
			case 'view':
				return array( true, '' );
		}
		return array( false, __( 'Something went wrong. Please try again.', 'ultra-max-core' ) );
	}

	/**
	 * WooCommerce's waiting error notices as one text, cleared.
	 *
	 * @param string $fallback Text when there are none.
	 */
	private static function errors( string $fallback ): string {
		$texts = array();
		foreach ( wc_get_notices( 'error' ) as $notice ) {
			$text = is_array( $notice ) && is_string( $notice['notice'] ?? null ) ? $notice['notice'] : '';
			if ( '' !== $text ) {
				$texts[] = wp_strip_all_tags( $text );
			}
		}
		wc_clear_notices();
		return array() === $texts ? $fallback : implode( ' ', $texts );
	}

	/**
	 * Handles the request and answers with JSON.
	 */
	public static function handle(): void {
		if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
			wp_send_json( array( 'success' => false ) );
		}
		wc_nocache_headers();
		$action  = self::posted( 'um_action' );
		$context = self::posted( 'context' );
		if ( 'view' !== $action && false === wp_verify_nonce( self::posted( 'nonce' ), self::NONCE ) ) {
			wp_send_json(
				array(
					'success' => false,
					'reload'  => 'side' !== $context,
					'message' => __( 'This page was open for too long. Please try again.', 'ultra-max-core' ),
				)
			);
		}
		$qty                  = self::posted( 'qty' );
		list( $ok, $message ) = self::apply( $action, self::posted( 'key' ), is_numeric( $qty ) ? (int) $qty : 0, self::posted( 'code' ) );
		WC()->cart->calculate_totals();
		$reply = array(
			'success'   => $ok,
			'message'   => $message,
			'count'     => (int) WC()->cart->get_cart_contents_count(),
			'fragments' => self::fragments(),
		);
		if ( 'side' === $context ) {
			$reply['parts'] = array( 'side' => (string) SideCart::content() );
		} elseif ( 'blocks' === $context ) {
			// The block cart keeps its own state; it reads the cart again on load.
			$reply['reload'] = $ok && 'view' !== $action;
		} elseif ( WC()->cart->is_empty() && in_array( $action, array( 'set', 'remove', 'later' ), true ) ) {
			$reply['reload'] = true;
		} else {
			$reply['parts'] = self::parts();
		}
		wp_send_json( $reply );
	}

	/**
	 * The parts the page asked for, drawn again.
	 *
	 * @return array<string, string>
	 */
	private static function parts(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Checked in handle(); names are matched against a fixed list and values only JSON-decoded.
		$asked = isset( $_POST['parts'] ) && is_array( $_POST['parts'] ) ? wp_unslash( $_POST['parts'] ) : array();
		$out   = array();
		foreach ( is_array( $asked ) ? $asked : array() as $name => $args ) {
			$name = (string) $name;
			// Suggestions stay as they were; drawing them again would move the slider.
			if ( ! in_array( $name, Page::PARTS, true ) || 'suggestions' === $name ) {
				continue;
			}
			$decoded      = is_string( $args ) && '' !== $args ? json_decode( $args, true ) : array();
			$out[ $name ] = (string) Page::part( $name, is_array( $decoded ) ? $decoded : array() );
		}
		return $out;
	}

	/**
	 * WooCommerce's fragments, with our header counters.
	 *
	 * @return array<string, mixed>
	 */
	public static function fragments(): array {
		/** This filter is documented in WooCommerce's WC_AJAX::get_refreshed_fragments(). */
		$fragments = apply_filters( 'woocommerce_add_to_cart_fragments', array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
		return is_array( $fragments ) ? $fragments : array();
	}

	/**
	 * Handles the "later" links without JavaScript.
	 */
	public static function links(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checked below.
		$action = isset( $_GET[ self::QUERY ] ) && is_string( $_GET[ self::QUERY ] ) ? sanitize_key( wp_unslash( $_GET[ self::QUERY ] ) ) : '';
		if ( '' === $action || ! function_exists( 'WC' ) || null === WC()->cart ) {
			return;
		}
		$nonce = isset( $_GET['_wpnonce'] ) && is_string( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! in_array( $action, array( 'later', 'restore', 'forget', 'restore-all' ), true ) || false === wp_verify_nonce( $nonce, self::NONCE ) ) {
			return;
		}
		$key                  = isset( $_GET['key'] ) && is_string( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		list( $ok, $message ) = self::apply( $action, $key );
		if ( '' !== $message ) {
			wc_add_notice( $message, $ok ? 'success' : 'error' );
		}
		$back = wp_get_referer();
		wp_safe_redirect( false === $back ? wc_get_cart_url() : remove_query_arg( array( self::QUERY, 'key', '_wpnonce' ), $back ) );
		exit;
	}
}
