<?php
/**
 * The lists endpoint (SAVE-01, SAVE-02, SAVE-03).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Saved;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Product\Related;
use UltraMax\Core\Store\Pages;

/**
 * `?wc-ajax=um_lists`.
 *
 * - GET answers the shopper's lists. A logged in shopper also gets the
 *   token their changes must carry.
 * - POST with `op` add or remove, `list` and `id` changes a list and
 *   answers the new lists with a message for the toast.
 *
 * Only public products can be saved: private, draft, password protected
 * and hidden products are refused, and the pages that show the lists never
 * show them either. Changing a guest's lists only changes their own
 * cookies, so guests need no token, which keeps pages cacheable.
 */
final class Endpoint {

	public const ACTION = 'um_lists';
	public const NONCE  = 'ultra_max_lists';

	/**
	 * Hooks the endpoint.
	 */
	public static function register(): void {
		add_action( 'wc_ajax_' . self::ACTION, array( self::class, 'handle' ) );
	}

	/**
	 * The lists of the modules that are on.
	 *
	 * @return array<string, list<int>>
	 */
	public static function state(): array {
		$out = array();
		foreach ( array(
			Lists::WISHLIST => Saved::WISHLIST,
			Lists::COMPARE  => Saved::COMPARE,
		) as $kind => $module ) {
			$out[ $kind ] = Saved::enabled( $module ) ? Lists::ids( $kind ) : array();
		}
		return $out;
	}

	/**
	 * A posted text field.
	 *
	 * @param string $key Field name.
	 */
	private static function posted( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Checked in handle(); callers compare with known values.
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		return is_string( $value ) ? sanitize_text_field( $value ) : '';
	}

	/**
	 * Whether a product may be saved: public, and not a variation.
	 *
	 * @param int $id Product id.
	 */
	public static function allowed( int $id ): ?\WC_Product {
		$found = Related::visible( array( $id ) );
		return $found[0] ?? null;
	}

	/**
	 * Category ids of a product.
	 *
	 * @param int $id Product id.
	 * @return list<int>
	 */
	private static function categories( int $id ): array {
		$terms = wc_get_product_term_ids( $id, 'product_cat' );
		return array_values( array_map( 'intval', $terms ) );
	}

	/**
	 * Whether a product shares a category with the products already compared
	 * (SAVE-03). Always true when the store allows mixing categories.
	 *
	 * @param int   $id       Product id.
	 * @param array $compared Ids already in the list.
	 * @phpstan-param list<int> $compared
	 */
	public static function same_category( int $id, array $compared ): bool {
		if ( array() === $compared || ! Store::bool( 'compare_same_category' ) ) {
			return true;
		}
		$mine = self::categories( $id );
		foreach ( $compared as $other ) {
			if ( array() === array_intersect( $mine, self::categories( $other ) ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Changes a list.
	 *
	 * @param string $op      add or remove.
	 * @param string $kind    wishlist or compare.
	 * @param int    $id      Product id.
	 * @param bool   $replace Start a new comparison with this product.
	 * @return array<string, mixed> Reply.
	 */
	public static function change( string $op, string $kind, int $id, bool $replace = false ): array {
		$module = Lists::WISHLIST === $kind ? Saved::WISHLIST : Saved::COMPARE;
		if ( ! Lists::known( $kind ) || ! Saved::enabled( $module ) ) {
			return array( 'success' => false );
		}
		if ( 'remove' === $op ) {
			Lists::remove( $kind, $id );
			return array(
				'success' => true,
				'message' => Lists::WISHLIST === $kind ? __( 'Removed from your wishlist.', 'ultra-max-core' ) : __( 'Removed from the comparison.', 'ultra-max-core' ),
			);
		}
		if ( null === self::allowed( $id ) ) {
			return array(
				'success' => false,
				'message' => __( 'This product cannot be saved.', 'ultra-max-core' ),
			);
		}
		if ( Lists::COMPARE === $kind ) {
			$current = Lists::ids( $kind );
			if ( $replace ) {
				Lists::save( $kind, array( $id ) );
			} elseif ( ! in_array( $id, $current, true ) && ! self::same_category( $id, $current ) ) {
				return array(
					'success'  => false,
					'message'  => __( 'Only products of one category can be compared.', 'ultra-max-core' ),
					'conflict' => array( 'label' => __( 'Start a new comparison', 'ultra-max-core' ) ),
				);
			} elseif ( ! in_array( $id, $current, true ) && count( $current ) >= Lists::LIMITS[ Lists::COMPARE ] ) {
				return array(
					'success' => false,
					/* translators: %s: number of products, such as 4. */
					'message' => sprintf( __( 'Up to %s products can be compared. Remove one first.', 'ultra-max-core' ), number_format_i18n( Lists::LIMITS[ Lists::COMPARE ] ) ),
					'link'    => array(
						'label' => __( 'See the comparison', 'ultra-max-core' ),
						'href'  => Pages::url( 'compare' ),
					),
				);
			} else {
				Lists::add( $kind, $id );
			}
			return array(
				'success' => true,
				'message' => __( 'Added to the comparison.', 'ultra-max-core' ),
				'link'    => array(
					'label' => __( 'Compare', 'ultra-max-core' ),
					'href'  => Pages::url( 'compare' ),
				),
			);
		}
		Lists::add( $kind, $id );
		$reply = array(
			'success' => true,
			'message' => __( 'Added to your wishlist.', 'ultra-max-core' ),
			'link'    => array(
				'label' => __( 'See the list', 'ultra-max-core' ),
				'href'  => Pages::url( 'wishlist' ),
			),
		);
		if ( ! is_user_logged_in() ) {
			$reply['message'] = __( 'Added to your wishlist. Log in to keep it on every device.', 'ultra-max-core' );
		}
		return $reply;
	}

	/**
	 * Handles the request and answers with JSON.
	 */
	public static function handle(): void {
		nocache_headers();
		$method = isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( $_SERVER['REQUEST_METHOD'] ) ) : 'GET';
		if ( 'POST' !== $method ) {
			$reply = array( 'lists' => self::state() );
			if ( is_user_logged_in() ) {
				$reply['nonce'] = wp_create_nonce( self::NONCE );
			}
			wp_send_json( $reply );
		}
		if ( is_user_logged_in() && ! wp_verify_nonce( self::posted( 'nonce' ), self::NONCE ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Your session has expired. Reload the page and try again.', 'ultra-max-core' ),
					'lists'   => self::state(),
				)
			);
		}
		$op             = self::posted( 'op' );
		$reply          = self::change(
			in_array( $op, array( 'add', 'remove' ), true ) ? $op : 'add',
			self::posted( 'list' ),
			absint( self::posted( 'id' ) ),
			'1' === self::posted( 'replace' )
		);
		$reply['lists'] = self::state();
		wp_send_json( $reply );
	}
}
