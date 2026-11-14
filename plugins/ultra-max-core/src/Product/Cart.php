<?php
/**
 * Adding products from a product page (PDP-05, PDP-21, PDP-22).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

/**
 * The purchase form posts here in place (`?wc-ajax=um_add_to_cart`). The
 * product is added the way WooCommerce's own form adds it: the same
 * validation filters (so quantity limits and other plugins' checks apply),
 * the same variation matching, the same notices, which come back as text.
 *
 * "Buy now" (PDP-21) goes on to the checkout, in place and without
 * JavaScript alike. Several products can be added together, for "bought
 * together" (PDP-22).
 */
final class Cart {

	public const ACTION = 'um_add_to_cart';

	/**
	 * Hooks the endpoint and the redirect.
	 */
	public static function register(): void {
		add_action( 'wc_ajax_' . self::ACTION, array( self::class, 'handle' ) );
		add_filter( 'woocommerce_add_to_cart_redirect', array( self::class, 'redirect' ), 20 );
		add_filter( 'wc_add_to_cart_message_html', array( self::class, 'message' ), 20 );
	}

	/**
	 * Whether the shopper asked to buy now.
	 */
	private static function buy_now(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce's add to cart has no nonce either; this only picks where to go next.
		return isset( $_REQUEST['um-buy-now'] ) && '1' === $_REQUEST['um-buy-now'];
	}

	/**
	 * "Buy now" without JavaScript: WooCommerce added the product, now go to
	 * the checkout.
	 *
	 * @param mixed $url Address WooCommerce goes to.
	 * @return mixed
	 */
	public static function redirect( mixed $url ): mixed {
		return self::buy_now() ? wc_get_checkout_url() : $url;
	}

	/**
	 * No "added to your cart" notice on the checkout after "buy now".
	 *
	 * @param mixed $message Notice.
	 * @return mixed
	 */
	public static function message( mixed $message ): mixed {
		return self::buy_now() ? '' : $message;
	}

	/**
	 * A posted text field.
	 *
	 * @param string $key Field name.
	 */
	private static function posted( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- See buy_now(); callers sanitize.
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Adds one product, or explains why not.
	 *
	 * @param int                   $product_id   Product id.
	 * @param int                   $quantity     Quantity.
	 * @param int                   $variation_id Variation id, 0 to find it from the values.
	 * @param array<string, string> $values       Chosen attribute values by field name.
	 * @return true|string True, or the reason.
	 */
	public static function add( int $product_id, int $quantity, int $variation_id = 0, array $values = array() ): bool|string {
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof \WC_Product || 'publish' !== $product->get_status() || ! $product->is_visible() || '' !== $product->get_post_password() ) {
			return __( 'This product cannot be bought.', 'ultra-max-core' );
		}
		$variation = array();
		if ( $product instanceof \WC_Product_Variable ) {
			if ( $variation_id <= 0 ) {
				$variation_id = ( new \WC_Product_Data_Store_CPT() )->find_matching_product_variation( $product, $values );
			}
			$found = $variation_id > 0 ? wc_get_product( $variation_id ) : null;
			if ( ! $found instanceof \WC_Product_Variation || $found->get_parent_id() !== $product_id ) {
				return __( 'Choose the options of this product first.', 'ultra-max-core' );
			}
			// "Any" values come from the shopper's choice, as in WooCommerce's form.
			foreach ( $found->get_variation_attributes() as $field => $value ) {
				$field               = (string) $field;
				$variation[ $field ] = '' === $value ? ( $values[ $field ] ?? '' ) : (string) $value;
				if ( '' === $variation[ $field ] ) {
					return __( 'Choose the options of this product first.', 'ultra-max-core' );
				}
			}
		} elseif ( ! $product->is_type( 'simple' ) ) {
			return __( 'This product cannot be bought.', 'ultra-max-core' );
		}
		/** This filter is documented in WooCommerce's WC_Form_Handler. */
		$passed = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
		$added  = false;
		if ( true === $passed ) {
			$added = false !== WC()->cart->add_to_cart( $product_id, max( 1, $quantity ), $variation_id, $variation );
		}
		$errors = array();
		foreach ( wc_get_notices( 'error' ) as $notice ) {
			$text = is_array( $notice ) && is_string( $notice['notice'] ?? null ) ? $notice['notice'] : ( is_string( $notice ) ? $notice : '' );
			if ( '' !== $text ) {
				$errors[] = wp_strip_all_tags( $text );
			}
		}
		wc_clear_notices();
		if ( $added ) {
			/** This action is documented in WooCommerce's WC_AJAX::add_to_cart(). */
			do_action( 'woocommerce_ajax_added_to_cart', $product_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own action.
			return true;
		}
		return array() === $errors ? __( 'Something went wrong. Please try again.', 'ultra-max-core' ) : implode( ' ', $errors );
	}

	/**
	 * The fragments WooCommerce and our header send after a change.
	 *
	 * @return array<string, mixed>
	 */
	private static function fragments(): array {
		ob_start();
		woocommerce_mini_cart();
		/** This filter is documented in WooCommerce's WC_AJAX::get_refreshed_fragments(). */
		$fragments = apply_filters( 'woocommerce_add_to_cart_fragments', array( 'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . ob_get_clean() . '</div>' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
		return is_array( $fragments ) ? $fragments : array();
	}

	/**
	 * Handles the request and answers with JSON.
	 */
	public static function handle(): void {
		if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
			wp_send_json( array( 'success' => false ) );
		}
		$values = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- See buy_now(); each value is cleaned below.
		foreach ( array_keys( $_POST ) as $key ) {
			if ( is_string( $key ) && str_starts_with( $key, 'attribute_' ) ) {
				$clean                            = wc_clean( self::posted( $key ) );
				$values[ sanitize_title( $key ) ] = is_string( $clean ) ? $clean : '';
			}
		}
		$items = array();
		$many  = self::posted( 'um_items' );
		if ( '' !== $many ) {
			foreach ( explode( ',', $many ) as $id ) {
				if ( is_numeric( $id ) && (int) $id > 0 ) {
					$items[] = array( (int) $id, 1, 0 );
				}
			}
		} else {
			$quantity = self::posted( 'quantity' );
			$items[]  = array( absint( self::posted( 'um_product' ) ), (int) wc_stock_amount( is_numeric( $quantity ) ? (float) $quantity : 1 ), absint( self::posted( 'variation_id' ) ) );
		}
		$problems = array();
		$added    = 0;
		$single   = 1 === count( $items );
		foreach ( array_slice( $items, 0, 10 ) as list( $id, $quantity, $variation_id ) ) {
			$result = self::add( $id, $quantity, $variation_id, $single ? $values : array() );
			if ( true === $result ) {
				++$added;
			} else {
				$problems[] = $result;
			}
		}
		$reply = array(
			'success'   => $added > 0 && array() === $problems,
			'message'   => array() === $problems ? __( 'Added to your cart.', 'ultra-max-core' ) : implode( ' ', array_unique( $problems ) ),
			'fragments' => self::fragments(),
			'cart_hash' => WC()->cart->get_cart_hash(),
		);
		if ( $reply['success'] && self::buy_now() ) {
			$reply['redirect'] = wc_get_checkout_url();
		}
		wp_send_json( $reply );
	}
}
