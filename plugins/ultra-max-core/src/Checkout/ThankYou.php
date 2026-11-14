<?php
/**
 * The thank you page (BLD-06, CHK-09).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Checkout;

use UltraMax\Core\Builder\Builder;
use UltraMax\Core\Cart\Totals;
use UltraMax\Core\Lib\UI\Button;
use UltraMax\Core\Lib\UI\DetailsCard;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\OrderDone;
use UltraMax\Core\Lib\UI\OrderLines;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Store\Format;

/**
 * WooCommerce decides who may see an order's page (the order key, the
 * owner's login or the guest's email) and then shows its thank you
 * template; ours replaces only that template. Its hooks stay: the
 * gateway's own `woocommerce_thankyou_<gateway>` prints the payment
 * instructions, and `woocommerce_thankyou` runs for tracking codes and
 * other plugins (WooCommerce's own order table is not printed again).
 *
 * Every piece is also an order widget (BLD-06) for a thank you layout
 * designed with Elementor.
 */
final class ThankYou {

	/**
	 * The order of the page, set while it draws.
	 *
	 * @var \WC_Order|null
	 */
	private static ?\WC_Order $order = null;

	/**
	 * The order the page shows, or the newest order of the editor while a
	 * layout is designed.
	 */
	public static function order(): ?\WC_Order {
		if ( null !== self::$order ) {
			return self::$order;
		}
		if ( current_user_can( 'edit_theme_options' ) && ( is_singular( \UltraMax\Core\Builder\Layouts::POST_TYPE ) || wp_doing_ajax() || defined( 'REST_REQUEST' ) ) ) {
			$orders = wc_get_orders(
				array(
					'limit'   => 1,
					'orderby' => 'date',
					'order'   => 'DESC',
					'type'    => 'shop_order',
				)
			);
			$first  = is_array( $orders ) ? ( $orders[0] ?? null ) : null;
			return $first instanceof \WC_Order ? $first : null;
		}
		return null;
	}

	/**
	 * The whole page for an order.
	 *
	 * @param \WC_Order $order Order.
	 */
	public static function page( \WC_Order $order ): Html {
		self::$order = $order;
		$left        = array(
			'desktop' => true,
			'tablet'  => true,
			'mobile'  => true,
		);
		ob_start();
		if ( Builder::enabled() ) {
			/** This filter is documented in themes/ultra-max/src/Template.php */
			$covered = apply_filters( 'ultra_max_do_location', array_map( '__return_false', $left ), Checkout::THANKS );
			$covered = is_array( $covered ) ? $covered : array();
			foreach ( array_keys( $left ) as $device ) {
				$left[ $device ] = true !== ( $covered[ $device ] ?? false );
			}
		}
		$parts = array( Html::wp( (string) ob_get_clean() ) );
		if ( in_array( true, $left, true ) ) {
			$parts[] = Html::trusted(
				sprintf(
					'<div class="%1$s">%2$s<div class="grid min-w-0 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]"><div class="grid min-w-0 gap-4">%3$s%4$s</div><div class="grid min-w-0 gap-4">%5$s</div></div></div>',
					esc_attr( trim( 'grid min-w-0 gap-5 ' . UI::visibility( $left ) ) ),
					self::done( $order ),
					self::instructions( $order ),
					self::items( $order ),
					self::customer( $order )
				)
			);
		}
		self::$order = null;
		return Html::trusted( sprintf( '<div class="um-thank-you grid min-w-0 gap-5">%s</div>', Html::from( $parts ) ) );
	}

	/**
	 * The thanks and the order's key facts.
	 *
	 * @param \WC_Order $order Order.
	 */
	public static function done( \WC_Order $order ): Html {
		$failed  = $order->has_status( 'failed' );
		$waiting = $order->needs_payment() && ! $failed;
		$actions = array();
		if ( $failed || $waiting ) {
			$actions[] = Button::render(
				array(
					'children' => __( 'Pay now', 'ultra-max-core' ),
					'href'     => $order->get_checkout_payment_url(),
				)
			);
		}
		if ( is_user_logged_in() && $order->get_customer_id() === get_current_user_id() ) {
			$actions[] = Button::render(
				array(
					'children' => __( 'See the order', 'ultra-max-core' ),
					'href'     => $order->get_view_order_url(),
					'variant'  => $actions ? 'outline' : 'primary',
				)
			);
		}
		$actions[] = Button::render(
			array(
				'children' => __( 'Back to the shop', 'ultra-max-core' ),
				'href'     => wc_get_page_permalink( 'shop' ),
				'variant'  => 'ghost',
			)
		);
		$created   = $order->get_date_created();
		return OrderDone::render(
			array(
				'title'   => $failed ? __( 'The payment did not go through', 'ultra-max-core' ) : ( $waiting ? __( 'Your order was placed and waits for payment', 'ultra-max-core' ) : __( 'Thank you! Your order was placed', 'ultra-max-core' ) ),
				'text'    => $failed
					? __( 'Your bank declined the payment or it was cancelled. You can try again; your order is kept.', 'ultra-max-core' )
					: __( 'We sent the details to you. You can follow the order on your account page.', 'ultra-max-core' ),
				'tone'    => $failed || $waiting ? 'warning' : 'success',
				'facts'   => array(
					array(
						'label' => __( 'Order number', 'ultra-max-core' ),
						'value' => Format::digits( $order->get_order_number() ),
					),
					array(
						'label' => __( 'Date', 'ultra-max-core' ),
						'value' => null === $created ? '' : Format::date( $created->getTimestamp() ),
					),
					array(
						'label' => __( 'Amount', 'ultra-max-core' ),
						'value' => Totals::money( (float) $order->get_total() ),
					),
					array(
						'label' => __( 'Payment method', 'ultra-max-core' ),
						'value' => wp_strip_all_tags( $order->get_payment_method_title() ),
					),
				),
				'actions' => $actions,
			)
		);
	}

	/**
	 * The gateway's instructions and other plugins' thank you output.
	 *
	 * @param \WC_Order $order Order.
	 */
	public static function instructions( \WC_Order $order ): Html {
		ob_start();
		/** This action is documented in WooCommerce's checkout/thankyou.php template. */
		do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		$gateway = trim( (string) ob_get_clean() );
		ob_start();
		$has = has_action( 'woocommerce_thankyou', 'woocommerce_order_details_table' );
		if ( false !== $has ) {
			remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', $has );
		}
		/** This action is documented in WooCommerce's checkout/thankyou.php template. */
		do_action( 'woocommerce_thankyou', $order->get_id() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		if ( false !== $has ) {
			add_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', $has );
		}
		$others = trim( (string) ob_get_clean() );
		if ( '' === $gateway && '' === $others ) {
			return Html::none();
		}
		return Html::from(
			array(
				'' === $gateway ? Html::none() : DetailsCard::render(
					array(
						'title'    => __( 'How to pay', 'ultra-max-core' ),
						'icon'     => 'card',
						'children' => Html::wp( $gateway ),
					)
				),
				Html::wp( $others ),
			)
		);
	}

	/**
	 * The order's products and amounts.
	 *
	 * @param \WC_Order $order Order.
	 */
	public static function items( \WC_Order $order ): Html {
		$items = array();
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			$product = $item->get_product();
			$meta    = array();
			foreach ( $item->get_all_formatted_meta_data() as $entry ) {
				$meta[] = wp_strip_all_tags( (string) $entry->display_key ) . ': ' . wp_strip_all_tags( (string) $entry->display_value );
			}
			$entry = array(
				'image'    => $product instanceof \WC_Product ? Html::wp( $product->get_image( 'woocommerce_gallery_thumbnail', array( 'alt' => '' ) ) ) : Html::none(),
				'title'    => wp_strip_all_tags( $item->get_name() ),
				/* translators: %s: quantity. */
				'quantity' => sprintf( __( 'Quantity: %s', 'ultra-max-core' ), Format::number( (int) $item->get_quantity() ) ),
				'total'    => Totals::money( (float) $order->get_line_subtotal( $item, $order->get_prices_include_tax(), true ) ),
				'meta'     => implode( '، ', $meta ),
			);
			if ( $product instanceof \WC_Product && $product->is_visible() ) {
				$entry['href'] = $product->get_permalink();
			}
			$items[] = $entry;
		}
		$rows = array();
		foreach ( $order->get_order_item_totals() as $key => $total ) {
			if ( 'payment_method' === $key ) {
				continue;
			}
			$rows[] = array(
				'label'  => rtrim( wp_strip_all_tags( (string) ( $total['label'] ?? '' ) ), ':' ),
				// WooCommerce's amounts as text, then in the store's digits; markup and entities would break.
				'value'  => Format::digits( trim( html_entity_decode( wp_strip_all_tags( (string) ( $total['value'] ?? '' ) ), ENT_QUOTES, 'UTF-8' ) ) ),
				'strong' => 'order_total' === $key,
			);
		}
		return OrderLines::render(
			array(
				'title' => __( 'Your order', 'ultra-max-core' ),
				'items' => $items,
				'rows'  => $rows,
			)
		);
	}

	/**
	 * The customer's details: name, mobile, email, address, buyer details.
	 *
	 * @param \WC_Order $order Order.
	 */
	public static function customer( \WC_Order $order ): Html {
		$group   = '' !== $order->get_shipping_address_1() ? 'shipping' : 'billing';
		$states  = WC()->countries->get_states( (string) $order->{"get_{$group}_country"}() );
		$state   = (string) $order->{"get_{$group}_state"}();
		$state   = is_array( $states ) && isset( $states[ $state ] ) ? wp_strip_all_tags( html_entity_decode( (string) $states[ $state ], ENT_QUOTES, 'UTF-8' ) ) : $state;
		$address = implode( '، ', array_filter( array( $state, (string) $order->{"get_{$group}_city"}(), (string) $order->{"get_{$group}_address_1"}(), (string) $order->{"get_{$group}_address_2"}() ) ) );
		$lines   = array(
			array(
				'label' => __( 'Recipient', 'ultra-max-core' ),
				'value' => '' !== trim( $order->get_shipping_last_name() ) ? trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() ) : trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			),
			array(
				'label' => __( 'Mobile', 'ultra-max-core' ),
				'value' => Format::digits( $order->get_billing_phone() ),
			),
			array(
				'label' => __( 'Email', 'ultra-max-core' ),
				'value' => $order->get_billing_email(),
				'ltr'   => true,
			),
			array(
				'label' => __( 'Address', 'ultra-max-core' ),
				'value' => $address,
			),
			array(
				'label' => __( 'Postal code', 'ultra-max-core' ),
				'value' => Format::digits( (string) $order->{"get_{$group}_postcode"}() ),
			),
		);
		foreach ( Fields::own() as $spec ) {
			if ( in_array( $spec['type'], array( 'checkbox' ), true ) ) {
				continue;
			}
			$value = Fields::value( $order, $spec['id'] );
			if ( '' === $value ) {
				continue;
			}
			$lines[] = array(
				'label' => $spec['label'],
				'value' => $spec['options'][ $value ] ?? ( $spec['ltr'] ? Format::digits( $value ) : $value ),
			);
		}
		if ( '' !== $order->get_customer_note() ) {
			$lines[] = array(
				'label' => __( 'Note', 'ultra-max-core' ),
				'value' => $order->get_customer_note(),
			);
		}
		return DetailsCard::render(
			array(
				'title' => __( 'Delivery details', 'ultra-max-core' ),
				'icon'  => 'pin',
				'lines' => $lines,
			)
		);
	}
}
