<?php
/**
 * Order tracking without logging in (ACC-09).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Account;

use UltraMax\Core\Lib\UI\AccountSection;
use UltraMax\Core\Lib\UI\Alert;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\InfoGrid;
use UltraMax\Core\Lib\UI\OrderCard;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Lib\Foundation\Digits;
use UltraMax\Core\Store\Format;
use UltraMax\Core\Store\Pages;
use UltraMax\Core\Checkout\Iran;

/**
 * Anyone with an order's number and the mobile number it was placed with
 * sees where the order is: its progress and tracking code, its products
 * and total, but no address. Each visitor has ten tries in fifteen
 * minutes, so numbers cannot be guessed one after another.
 *
 * The "Track your order" page is made for the store; the form is also a
 * shortcode and an Elementor widget.
 */
final class Lookup {

	public const SHORTCODE = 'um_order_tracking';
	public const NONCE     = 'ultra_max_track_order';
	public const TRIES     = 10;
	public const WINDOW    = 15 * MINUTE_IN_SECONDS;

	/**
	 * Hooks the shortcode and the page.
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'shortcode' ) );
		add_filter( 'ultra_max_pages', array( self::class, 'pages' ) );
		add_filter( 'ultra_max_content_prose', array( self::class, 'prose' ) );
		add_action( 'template_redirect', array( self::class, 'no_cache' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'assets' ), 20 );
	}

	/**
	 * The tracking page.
	 *
	 * @param mixed $pages Pages.
	 * @return mixed
	 */
	public static function pages( mixed $pages ): mixed {
		if ( is_array( $pages ) ) {
			$pages['order-tracking'] = array(
				'title'     => __( 'Track your order', 'ultra-max-core' ),
				'slug'      => 'track-order',
				'shortcode' => self::SHORTCODE,
			);
		}
		return $pages;
	}

	/**
	 * Whether the page being shown holds the form.
	 */
	private static function here(): bool {
		$post = get_post();
		return Pages::is( 'order-tracking' ) || ( is_singular() && $post instanceof \WP_Post && has_shortcode( $post->post_content, self::SHORTCODE ) );
	}

	/**
	 * The page holds a form, not writing.
	 *
	 * @param mixed $prose Whether the theme styles the content as writing.
	 * @return mixed
	 */
	public static function prose( mixed $prose ): mixed {
		return self::here() ? false : $prose;
	}

	/**
	 * The page answers one visitor: no page cache may keep it.
	 */
	public static function no_cache(): void {
		if ( self::here() ) {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- The constant page cache plugins read.
			}
			nocache_headers();
		}
	}

	/**
	 * Queues the account's files on the page.
	 */
	public static function assets(): void {
		if ( self::here() ) {
			\UltraMax\Core\Lib\UI\Assets::enqueue_widget( 'account' );
		}
	}

	/**
	 * The shortcode.
	 */
	public static function shortcode(): string {
		return (string) self::page();
	}

	/**
	 * Finds an order by its number and mobile number.
	 *
	 * @param string $number Order number as typed.
	 * @param string $mobile Mobile number as typed.
	 */
	public static function find( string $number, string $mobile ): ?\WC_Order {
		$number = trim( Digits::to_latin( $number ) );
		$number = ltrim( $number, '#' );
		$mobile = ( Iran::mobile( $mobile ) ?? '' );
		if ( '' === $number || '' === $mobile ) {
			return null;
		}
		$order = ctype_digit( $number ) ? wc_get_order( (int) $number ) : false;
		/**
		 * Finds an order by a number that is not its id, for plugins that
		 * number orders their own way.
		 *
		 * @param \WC_Order|null $order  Order found by id.
		 * @param string         $number Number as typed.
		 */
		$order = apply_filters( 'ultra_max_find_order', $order instanceof \WC_Order ? $order : null, $number );
		if ( ! $order instanceof \WC_Order || 'shop_order' !== $order->get_type() || in_array( $order->get_status(), array( 'checkout-draft', 'trash' ), true ) ) {
			return null;
		}
		if ( (string) $order->get_order_number() !== $number && (string) $order->get_id() !== $number ) {
			return null;
		}
		return ( Iran::mobile( $order->get_billing_phone() ) ?? '' ) === $mobile ? $order : null;
	}

	/**
	 * The key of this visitor's tries.
	 */
	private static function key(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return 'um_track_' . md5( $ip );
	}

	/**
	 * Counts one try; false when the visitor has used all of theirs.
	 */
	public static function try_once(): bool {
		$tries = get_transient( self::key() );
		$tries = is_numeric( $tries ) ? (int) $tries : 0;
		if ( $tries >= self::TRIES ) {
			return false;
		}
		set_transient( self::key(), $tries + 1, self::WINDOW );
		return true;
	}

	/**
	 * A posted text field.
	 *
	 * @param string $key Field name.
	 */
	private static function posted( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked in page() before any field is used.
		return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	/**
	 * The form, and the order when one was asked for.
	 */
	public static function page(): Html {
		$number  = '';
		$mobile  = '';
		$result  = Html::none();
		$request = isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && '' !== self::posted( 'um_track_number' );
		if ( $request ) {
			$number = self::posted( 'um_track_number' );
			$mobile = self::posted( 'um_track_mobile' );
			if ( ! wp_verify_nonce( self::posted( 'um_track_nonce' ), self::NONCE ) ) {
				$result = self::message( __( 'The form has expired. Please try again.', 'ultra-max-core' ) );
			} elseif ( '' !== self::posted( 'um_track_site' ) ) {
				$result = self::message( __( 'No order was found with this number and mobile number.', 'ultra-max-core' ) );
			} elseif ( ! self::try_once() ) {
				$result = self::message( __( 'Too many tries. Please wait fifteen minutes and try again.', 'ultra-max-core' ) );
			} else {
				$order  = self::find( $number, $mobile );
				$result = null === $order ? self::message( __( 'No order was found with this number and mobile number.', 'ultra-max-core' ) ) : self::result( $order );
			}
		}
		$form = Html::trusted(
			sprintf(
				'<form method="post" class="grid items-end gap-4 md:grid-cols-[1fr_1fr_auto]" action="%1$s">%2$s%3$s%4$s<input type="hidden" name="um_track_nonce" value="%5$s"><label class="sr-only" aria-hidden="true">%6$s<input type="text" name="um_track_site" tabindex="-1" autocomplete="off"></label></form>',
				esc_url( (string) get_permalink() ),
				UI::field(
					array(
						'id'           => 'um-track-number',
						'name'         => 'um_track_number',
						'label'        => __( 'Order number', 'ultra-max-core' ),
						'value'        => $number,
						'required'     => true,
						'dir'          => 'ltr',
						'autocomplete' => 'off',
					)
				),
				UI::field(
					array(
						'id'           => 'um-track-mobile',
						'name'         => 'um_track_mobile',
						'type'         => 'tel',
						'label'        => __( 'Mobile number of the order', 'ultra-max-core' ),
						'value'        => $mobile,
						'required'     => true,
						'dir'          => 'ltr',
						'autocomplete' => 'tel',
						'placeholder'  => '09121234567',
					)
				),
				UI::button(
					array(
						'type'     => 'submit',
						'children' => __( 'Track', 'ultra-max-core' ),
					)
				),
				esc_attr( wp_create_nonce( self::NONCE ) ),
				esc_html__( 'Leave this empty', 'ultra-max-core' )
			)
		);
		return Html::trusted(
			sprintf(
				'<div class="um-scope um-order-lookup grid gap-4">%1$s%2$s</div>',
				AccountSection::render(
					array(
						'title'    => __( 'Find your order', 'ultra-max-core' ),
						'children' => Html::from(
							array(
								Html::trusted( sprintf( '<p class="mt-0 mb-4">%s</p>', esc_html__( 'Enter the order number from your confirmation and the mobile number you ordered with.', 'ultra-max-core' ) ) ),
								$form,
							)
						),
					)
				),
				$result
			)
		);
	}

	/**
	 * A message instead of an order.
	 *
	 * @param string $text Message.
	 */
	private static function message( string $text ): Html {
		return Alert::render(
			array(
				'tone'     => 'warning',
				'role'     => 'alert',
				'children' => $text,
			)
		);
	}

	/**
	 * What a visitor sees of an order: progress, tracking code, products
	 * and total, no address.
	 *
	 * @param \WC_Order $order Order.
	 */
	public static function result( \WC_Order $order ): Html {
		$names = array();
		foreach ( $order->get_items() as $item ) {
			/* translators: 1: product name, 2: quantity. */
			$names[] = sprintf( __( '%1$s × %2$s', 'ultra-max-core' ), $item->get_name(), Format::number( (int) $item->get_quantity() ) );
		}
		$created = $order->get_date_created();
		return AccountSection::render(
			array(
				/* translators: %s: order number. */
				'title'    => sprintf( __( 'Order %s', 'ultra-max-core' ), Format::digits( $order->get_order_number() ) ),
				'end'      => OrderCard::status( Orders::status( $order ) ),
				'id'       => 'um-track-result',
				'children' => Html::from(
					array(
						InfoGrid::render(
							array(
								'columns' => 2,
								'items'   => array(
									array(
										'label' => __( 'Placed on', 'ultra-max-core' ),
										'value' => null === $created ? '' : Format::date( $created->getTimestamp() ),
									),
									array(
										'label' => __( 'Total', 'ultra-max-core' ),
										'value' => Orders::total( $order ),
										'num'   => true,
									),
									array(
										'label' => __( 'Products', 'ultra-max-core' ),
										'value' => implode( '، ', $names ),
									),
								),
								'class'   => 'mb-5',
							)
						),
						Page::progress( $order, false ),
					)
				),
			)
		);
	}
}
