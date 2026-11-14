<?php
/**
 * Parcel tracking codes (SHIP-03, ACC-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Account;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\TrackingBox;
use UltraMax\Core\Lib\UI\Html;

/**
 * The store writes a carrier and a tracking code in the "Parcel tracking"
 * box of an order's edit screen (HPOS and the old order screen alike). The
 * shopper sees them on the order's page, in the order tracking page and in
 * WooCommerce's emails, with a link to the carrier's tracking page.
 *
 * Carriers come from the settings; each has a link in which `{code}` stands
 * for the tracking code. A link without `{code}` opens the carrier's own
 * tracking page, where the shopper pastes the code.
 *
 * @phpstan-type Parcel array{carrier: string, code: string, url: string, label: string, date: int}
 */
final class Tracking {

	public const CARRIER = '_um_tracking_carrier';
	public const CODE    = '_um_tracking_code';
	public const DATE    = '_um_tracking_date';
	public const NONCE   = 'ultra_max_tracking';

	/**
	 * Hooks the edit screen box, saving and the emails.
	 */
	public static function register(): void {
		add_action( 'add_meta_boxes', array( self::class, 'box' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( self::class, 'save_box' ), 50 );
		add_action( 'woocommerce_email_order_meta', array( self::class, 'email' ), 20, 3 );
	}

	/**
	 * The carriers of the settings.
	 *
	 * @return list<array{name: string, link: string}>
	 */
	public static function carriers(): array {
		$out = array();
		foreach ( Store::rows( 'tracking_carriers' ) as $row ) {
			$name = is_string( $row['name'] ?? null ) ? trim( $row['name'] ) : '';
			if ( '' !== $name ) {
				$out[] = array(
					'name' => $name,
					'link' => is_string( $row['link'] ?? null ) ? trim( $row['link'] ) : '',
				);
			}
		}
		return $out;
	}

	/**
	 * The link to follow a parcel, or an empty text.
	 *
	 * @param string $carrier Carrier name.
	 * @param string $code    Tracking code.
	 */
	public static function link( string $carrier, string $code ): string {
		foreach ( self::carriers() as $known ) {
			if ( $known['name'] === $carrier && '' !== $known['link'] ) {
				return esc_url_raw( str_replace( '{code}', rawurlencode( $code ), $known['link'] ) );
			}
		}
		return '';
	}

	/**
	 * The parcel of an order, or null when it has no tracking code yet.
	 *
	 * @param \WC_Order $order Order.
	 * @return Parcel|null
	 */
	public static function get( \WC_Order $order ): ?array {
		$code = $order->get_meta( self::CODE );
		if ( ! is_string( $code ) || '' === trim( $code ) ) {
			return null;
		}
		$carrier = $order->get_meta( self::CARRIER );
		$carrier = is_string( $carrier ) ? $carrier : '';
		$date    = $order->get_meta( self::DATE );
		return array(
			'carrier' => $carrier,
			'code'    => trim( $code ),
			'url'     => self::link( $carrier, trim( $code ) ),
			/* translators: %s: carrier name, such as Post. */
			'label'   => '' === $carrier ? __( 'Tracking code', 'ultra-max-core' ) : sprintf( __( '%s tracking code', 'ultra-max-core' ), $carrier ),
			'date'    => is_numeric( $date ) ? (int) $date : 0,
		);
	}

	/**
	 * Saves a parcel's carrier and code. An empty code removes them.
	 *
	 * @param \WC_Order $order   Order.
	 * @param string    $carrier Carrier name.
	 * @param string    $code    Tracking code.
	 */
	public static function save( \WC_Order $order, string $carrier, string $code ): void {
		$code    = trim( $code );
		$carrier = trim( $carrier );
		$before  = self::get( $order );
		if ( '' === $code ) {
			$order->delete_meta_data( self::CODE );
			$order->delete_meta_data( self::CARRIER );
			$order->delete_meta_data( self::DATE );
			$order->save();
			return;
		}
		if ( null !== $before && $before['code'] === $code && $before['carrier'] === $carrier ) {
			return;
		}
		$order->update_meta_data( self::CARRIER, $carrier );
		$order->update_meta_data( self::CODE, $code );
		$order->update_meta_data( self::DATE, (string) time() );
		$order->save();
		/* translators: 1: carrier name, 2: tracking code. */
		$order->add_order_note( sprintf( __( 'Parcel handed to %1$s. Tracking code: %2$s', 'ultra-max-core' ), '' === $carrier ? __( 'the carrier', 'ultra-max-core' ) : $carrier, $code ), 1, true );
		/**
		 * Fires after a parcel's tracking code was saved, for example to send
		 * it by text message (phase 5).
		 *
		 * @param \WC_Order $order   Order.
		 * @param string    $carrier Carrier name.
		 * @param string    $code    Tracking code.
		 */
		do_action( 'ultra_max_tracking_saved', $order, $carrier, $code );
	}

	/**
	 * The box on the order's edit screen.
	 */
	public static function box(): void {
		$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box( 'um-tracking', __( 'Parcel tracking', 'ultra-max-core' ), array( self::class, 'box_content' ), $screen, 'side', 'high' );
	}

	/**
	 * The fields of the box.
	 *
	 * @param mixed $post_or_order The order, or its post on the old screen.
	 */
	public static function box_content( mixed $post_or_order ): void {
		$order = $post_or_order instanceof \WC_Order ? $post_or_order : ( $post_or_order instanceof \WP_Post ? wc_get_order( $post_or_order->ID ) : null );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		$parcel = self::get( $order );
		$chosen = null === $parcel ? '' : $parcel['carrier'];
		wp_nonce_field( self::NONCE, 'um_tracking_nonce' );
		echo '<p><label for="um-tracking-carrier">' . esc_html__( 'Carrier', 'ultra-max-core' ) . '</label><br><select id="um-tracking-carrier" name="um_tracking_carrier" class="widefat">';
		foreach ( self::carriers() as $carrier ) {
			printf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $carrier['name'] ), selected( $chosen, $carrier['name'], false ) );
		}
		echo '</select></p>';
		printf(
			'<p><label for="um-tracking-code">%1$s</label><br><input type="text" id="um-tracking-code" name="um_tracking_code" class="widefat" dir="ltr" value="%2$s"></p><p class="description">%3$s</p>',
			esc_html__( 'Tracking code', 'ultra-max-core' ),
			esc_attr( null === $parcel ? '' : $parcel['code'] ),
			esc_html__( 'The shopper sees it in their account and in the order emails. Carriers and their links are set in Ultra Max, Settings, Customer account.', 'ultra-max-core' )
		);
	}

	/**
	 * Saves the box with the order.
	 *
	 * @param mixed $order_id Order id.
	 */
	public static function save_box( mixed $order_id ): void {
		$nonce = isset( $_POST['um_tracking_nonce'] ) && is_string( $_POST['um_tracking_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['um_tracking_nonce'] ) ) : '';
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce's own capability.
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) || ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		$order = wc_get_order( is_numeric( $order_id ) ? (int) $order_id : 0 );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		$carrier = isset( $_POST['um_tracking_carrier'] ) && is_string( $_POST['um_tracking_carrier'] ) ? sanitize_text_field( wp_unslash( $_POST['um_tracking_carrier'] ) ) : '';
		$code    = isset( $_POST['um_tracking_code'] ) && is_string( $_POST['um_tracking_code'] ) ? sanitize_text_field( wp_unslash( $_POST['um_tracking_code'] ) ) : '';
		self::save( $order, $carrier, $code );
	}

	/**
	 * The tracking box of the shopper's pages.
	 *
	 * @param \WC_Order $order Order.
	 */
	public static function render( \WC_Order $order ): Html {
		$parcel = self::get( $order );
		if ( null === $parcel ) {
			return Html::none();
		}
		$props = array(
			'title'  => $parcel['label'],
			'code'   => $parcel['code'],
			'copy'   => __( 'Copy', 'ultra-max-core' ),
			'copied' => __( 'Copied', 'ultra-max-core' ),
		);
		if ( '' !== $parcel['url'] ) {
			$props['link'] = array(
				'label' => __( 'Follow the parcel', 'ultra-max-core' ),
				'href'  => $parcel['url'],
			);
		}
		return TrackingBox::render( $props );
	}

	/**
	 * The tracking code in WooCommerce's emails to the shopper.
	 *
	 * @param mixed $order         Order.
	 * @param mixed $sent_to_admin Whether the email goes to the store.
	 * @param mixed $plain_text    Whether the email is plain text.
	 */
	public static function email( mixed $order, mixed $sent_to_admin = false, mixed $plain_text = false ): void {
		if ( ! $order instanceof \WC_Order || true === $sent_to_admin ) {
			return;
		}
		$parcel = self::get( $order );
		if ( null === $parcel ) {
			return;
		}
		if ( true === $plain_text ) {
			echo esc_html( $parcel['label'] . ': ' . $parcel['code'] ) . "\n";
			if ( '' !== $parcel['url'] ) {
				echo esc_url( $parcel['url'] ) . "\n\n";
			}
			return;
		}
		printf(
			'<h2>%1$s</h2><p><strong dir="ltr">%2$s</strong>%3$s</p>',
			esc_html( $parcel['label'] ),
			esc_html( $parcel['code'] ),
			'' === $parcel['url'] ? '' : sprintf( '<br><a href="%1$s">%2$s</a>', esc_url( $parcel['url'] ), esc_html__( 'Follow the parcel', 'ultra-max-core' ) )
		);
	}
}
