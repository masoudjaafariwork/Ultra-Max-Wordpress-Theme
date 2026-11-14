<?php
/**
 * The customer account (ACC-01 to ACC-08, BLD-07).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Account;

use UltraMax\Core\Builder\Builder;
use UltraMax\Core\Checkout\AddressBook;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\AccountLayout;
use UltraMax\Core\Lib\UI\AccountNav;
use UltraMax\Core\Lib\UI\AccountSection;
use UltraMax\Core\Lib\UI\AddressCard;
use UltraMax\Core\Lib\UI\Alert;
use UltraMax\Core\Lib\UI\EmptyState;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\InfoGrid;
use UltraMax\Core\Lib\UI\OrderCard;
use UltraMax\Core\Lib\UI\OrderProducts;
use UltraMax\Core\Lib\UI\OrderStats;
use UltraMax\Core\Lib\UI\OrderSteps;
use UltraMax\Core\Lib\UI\Pagination;
use UltraMax\Core\Lib\UI\ProfileCard;
use UltraMax\Core\Lib\UI\RecentProducts;
use UltraMax\Core\Lib\UI\SummaryRows;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Product\Product;
use UltraMax\Core\Product\Related;
use UltraMax\Core\Saved\Saved;
use UltraMax\Core\Saved\Wishlist;
use UltraMax\Core\Store\Format;

/**
 * One renderer for every piece of the account. WooCommerce still routes
 * the account's addresses (its endpoints) and keeps its forms, notices
 * and hooks; we draw the frame, the menu and each page, as in
 * design/account.html and design/order.html. The account widgets (BLD-07)
 * call the same methods, so a designed account behaves like this one.
 */
final class Page {

	/**
	 * Icons of the menu items WooCommerce and we know.
	 */
	private const ICONS = array(
		'dashboard'       => 'home',
		'orders'          => 'box',
		'lists'           => 'heart',
		'activity'        => 'chat',
		'edit-address'    => 'pin',
		'recent'          => 'history',
		'edit-account'    => 'user',
		'downloads'       => 'download',
		'payment-methods' => 'card',
		'customer-logout' => 'logout',
	);

	/**
	 * The whole account of a logged in shopper: a layout designed with
	 * Elementor or the built-in frame.
	 */
	public static function frame(): Html {
		$left = array(
			'desktop' => true,
			'tablet'  => true,
			'mobile'  => true,
		);
		ob_start();
		if ( Builder::enabled() ) {
			/** This filter is documented in themes/ultra-max/src/Template.php */
			$covered = apply_filters( 'ultra_max_do_location', array_map( '__return_false', $left ), Account::LOCATION );
			$covered = is_array( $covered ) ? $covered : array();
			foreach ( array_keys( $left ) as $device ) {
				$left[ $device ] = true !== ( $covered[ $device ] ?? false );
			}
		}
		$parts = array( Html::wp( (string) ob_get_clean() ) );
		if ( in_array( true, $left, true ) ) {
			$parts[] = AccountLayout::render(
				array(
					'side'  => Html::from( array( self::profile(), self::nav() ) ),
					'main'  => self::content(),
					'class' => UI::visibility( $left ),
				)
			);
		}
		return Html::from( $parts );
	}

	/**
	 * The shopper's name and mobile number.
	 */
	public static function profile(): Html {
		$user = wp_get_current_user();
		if ( 0 === $user->ID ) {
			return Html::none();
		}
		$name  = trim( $user->first_name . ' ' . $user->last_name );
		$phone = get_user_meta( $user->ID, 'billing_phone', true );
		$phone = is_string( $phone ) ? $phone : '';
		return ProfileCard::render(
			array(
				'name'     => '' === $name ? $user->display_name : $name,
				'line'     => '' === $phone ? $user->user_email : Format::digits( $phone ),
				'line_dir' => 'ltr',
				'edit'     => array(
					'label' => __( 'Edit your details', 'ultra-max-core' ),
					'href'  => wc_get_account_endpoint_url( 'edit-account' ),
				),
			)
		);
	}

	/**
	 * The account menu.
	 */
	public static function nav(): Html {
		$items = array();
		foreach ( wc_get_account_menu_items() as $endpoint => $label ) {
			$endpoint = (string) $endpoint;
			$classes  = wc_get_account_menu_item_classes( $endpoint );
			$items[]  = array(
				'label'   => (string) $label,
				'href'    => wc_get_account_endpoint_url( $endpoint ),
				'icon'    => self::ICONS[ $endpoint ] ?? 'info',
				'current' => str_contains( $classes, 'is-active' ),
				'out'     => 'customer-logout' === $endpoint,
			);
		}
		ob_start();
		/** This action is documented in WooCommerce's myaccount/my-account.php template. */
		do_action( 'woocommerce_account_navigation' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook, for other plugins' additions.
		$extra = (string) ob_get_clean();
		return Html::from(
			array(
				AccountNav::render(
					array(
						'label' => __( 'Account menu', 'ultra-max-core' ),
						'items' => $items,
					)
				),
				Html::wp( $extra ),
			)
		);
	}

	/**
	 * The page of the chosen menu item: WooCommerce's notices, then the
	 * endpoint's content (WooCommerce routes it to our renderers).
	 */
	public static function content(): Html {
		ob_start();
		/** This action is documented in WooCommerce's myaccount/my-account.php template. */
		do_action( 'woocommerce_account_content' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		return Html::trusted( sprintf( '<div class="woocommerce-MyAccount-content grid min-w-0 content-start gap-4">%s</div>', (string) ob_get_clean() ) );
	}

	/**
	 * Tiles of the shopper's orders by status (ACC-01).
	 */
	public static function stats(): Html {
		$user = get_current_user_id();
		if ( $user <= 0 ) {
			return Html::none();
		}
		$counts = Orders::counts( $user );
		$items  = array();
		foreach ( Orders::groups() as $id => $group ) {
			$items[] = array(
				'label' => $group['label'],
				/* translators: %s: number of orders. */
				'count' => sprintf( _n( '%s order', '%s orders', $counts[ $id ] ?? 0, 'ultra-max-core' ), Format::number( $counts[ $id ] ?? 0 ) ),
				'icon'  => $group['icon'],
				'tone'  => $group['tone'],
				'href'  => add_query_arg( 'status', $id, wc_get_account_endpoint_url( 'orders' ) ),
			);
		}
		return OrderStats::render(
			array(
				'label' => __( 'Your orders by status', 'ultra-max-core' ),
				'items' => $items,
			)
		);
	}

	/**
	 * The latest orders of each group in tabs (ACC-01).
	 */
	public static function latest(): Html {
		$user  = get_current_user_id();
		$items = array();
		foreach ( Orders::groups() as $id => $group ) {
			$found = Orders::query( $user, $id, 1, 3 );
			$cards = array_map( array( Orders::class, 'card' ), $found['orders'] );
			if ( array() === $cards ) {
				/* translators: %s: group of orders, such as Cancelled. */
				$cards = array( Html::trusted( sprintf( '<p class="m-0 py-6 text-center">%s</p>', esc_html( sprintf( __( 'No orders here: %s', 'ultra-max-core' ), $group['label'] ) ) ) ) );
			}
			$items[] = array(
				'label'   => $found['total'] > 0 ? sprintf( '%1$s (%2$s)', $group['label'], Format::number( $found['total'] ) ) : $group['label'],
				'content' => Html::trusted( sprintf( '<div class="grid gap-3 pt-4">%s</div>', Html::from( $cards ) ) ),
			);
		}
		if ( array() === $items ) {
			return Html::none();
		}
		return UI::tabs(
			array(
				'id'    => 'um-account-latest',
				'label' => __( 'Latest orders', 'ultra-max-core' ),
				'items' => $items,
			)
		);
	}

	/**
	 * The dashboard (ACC-01, ACC-04, ACC-07, ACC-08).
	 */
	public static function dashboard(): Html {
		$sections = array(
			AccountSection::render(
				array(
					'title'    => __( 'My orders', 'ultra-max-core' ),
					'children' => self::stats(),
					'more'     => array(
						'label' => __( 'See all', 'ultra-max-core' ),
						'href'  => wc_get_account_endpoint_url( 'orders' ),
					),
				)
			),
			AccountSection::render(
				array(
					'title'    => __( 'Latest orders', 'ultra-max-core' ),
					'children' => self::latest(),
				)
			),
		);
		$lists    = self::wishlist_slider();
		if ( ! $lists->is_empty() ) {
			$sections[] = AccountSection::render(
				array(
					'title'    => __( 'My lists', 'ultra-max-core' ),
					'children' => $lists,
					'more'     => array(
						'label' => __( 'See all', 'ultra-max-core' ),
						'href'  => wc_get_account_endpoint_url( Endpoints::LISTS ),
					),
				)
			);
		}
		$sections[] = self::recent();
		$sections[] = AccountSection::render(
			array(
				'title'    => __( 'Account details', 'ultra-max-core' ),
				'children' => self::info(),
				'more'     => array(
					'label' => __( 'Edit', 'ultra-max-core' ),
					'href'  => wc_get_account_endpoint_url( 'edit-account' ),
				),
			)
		);
		return Html::from( $sections );
	}

	/**
	 * The latest products of the wishlist in a slider.
	 */
	public static function wishlist_slider(): Html {
		if ( ! Saved::enabled( Saved::WISHLIST ) ) {
			return Html::none();
		}
		return Related::slider( array_slice( Wishlist::products(), 0, 10 ), __( 'Wishlist', 'ultra-max-core' ), 'hover' );
	}

	/**
	 * The account details at a glance (ACC-07).
	 */
	public static function info(): Html {
		$user = wp_get_current_user();
		if ( 0 === $user->ID ) {
			return Html::none();
		}
		$items = array(
			array(
				'label' => __( 'Name', 'ultra-max-core' ),
				'value' => trim( $user->first_name . ' ' . $user->last_name ),
			),
		);
		if ( Store::bool( 'account_mobile' ) ) {
			$phone   = get_user_meta( $user->ID, 'billing_phone', true );
			$items[] = array(
				'label' => __( 'Mobile number', 'ultra-max-core' ),
				'value' => is_string( $phone ) ? Format::digits( $phone ) : '',
				'num'   => true,
				'dir'   => 'ltr',
			);
		}
		if ( Store::bool( 'account_national_code' ) ) {
			$code    = get_user_meta( $user->ID, Details::NATIONAL, true );
			$items[] = array(
				'label' => __( 'National code', 'ultra-max-core' ),
				'value' => is_string( $code ) ? Format::digits( $code ) : '',
				'num'   => true,
				'dir'   => 'ltr',
			);
		}
		$items[] = array(
			'label' => __( 'Email', 'ultra-max-core' ),
			'value' => $user->user_email,
			'dir'   => 'ltr',
		);
		if ( Store::bool( 'account_birthday' ) ) {
			$items[] = array(
				'label' => __( 'Birthday', 'ultra-max-core' ),
				'value' => Details::birthday_text( $user->ID ),
			);
		}
		return InfoGrid::render(
			array(
				'items' => $items,
				'blank' => __( 'Not given', 'ultra-max-core' ),
			)
		);
	}

	/**
	 * The shopper's recent views (ACC-08), kept in their browser only.
	 */
	public static function recent(): Html {
		if ( ! Product::enabled() || ! Store::bool( 'account_recent' ) ) {
			return Html::none();
		}
		return RecentProducts::render(
			array(
				'title'    => __( 'Recent views', 'ultra-max-core' ),
				'endpoint' => Related::endpoint(),
				'limit'    => 12,
			)
		);
	}

	/**
	 * The orders page (ACC-01): tabs by status and the orders of one.
	 *
	 * @param int $page Page number.
	 */
	public static function orders( int $page = 1 ): Html {
		$user   = get_current_user_id();
		$groups = Orders::groups();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only picks a tab.
		$chosen = isset( $_GET['status'] ) && is_string( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'current';
		$chosen = isset( $groups[ $chosen ] ) ? $chosen : 'current';
		$per    = max( 5, Store::int( 'account_orders_per_page' ) );
		$found  = Orders::query( $user, $chosen, max( 1, $page ), $per );
		$counts = Orders::counts( $user );
		$tabs   = array();
		foreach ( $groups as $id => $group ) {
			$tabs[] = UI::button(
				array(
					'href'     => add_query_arg( 'status', $id, wc_get_account_endpoint_url( 'orders' ) ),
					'variant'  => $id === $chosen ? 'soft' : 'ghost',
					'size'     => 'sm',
					'children' => ( $counts[ $id ] ?? 0 ) > 0 ? sprintf( '%1$s (%2$s)', $group['label'], Format::number( $counts[ $id ] ?? 0 ) ) : $group['label'],
					'attrs'    => $id === $chosen ? array( 'aria-current' => 'page' ) : array(),
				)
			);
		}
		$list  = array() === $found['orders']
			? EmptyState::render(
				array(
					'icon'     => 'box',
					/* translators: %s: group of orders, such as Cancelled. */
					'title'    => sprintf( __( 'No orders here: %s', 'ultra-max-core' ), $groups[ $chosen ]['label'] ),
					'children' => UI::button(
						array(
							'href'     => wc_get_page_permalink( 'shop' ),
							'children' => __( 'Go to the shop', 'ultra-max-core' ),
						)
					),
				)
			)
			: Html::trusted( sprintf( '<div class="grid gap-3">%s</div>', Html::from( array_map( array( Orders::class, 'card' ), $found['orders'] ) ) ) );
		$parts = array(
			Html::trusted( sprintf( '<nav class="mb-4 flex flex-wrap gap-2" aria-label="%1$s">%2$s</nav>', esc_attr__( 'Orders by status', 'ultra-max-core' ), Html::from( $tabs ) ) ),
			$list,
		);
		if ( $found['pages'] > 1 ) {
			$parts[] = Pagination::render(
				array(
					'label'    => __( 'Pages of orders', 'ultra-max-core' ),
					'current'  => max( 1, $page ),
					'total'    => $found['pages'],
					'base'     => add_query_arg( 'status', $chosen, wc_get_endpoint_url( 'orders', '%#%', wc_get_page_permalink( 'myaccount' ) ) ),
					'previous' => __( 'Previous', 'ultra-max-core' ),
					'next'     => __( 'Next', 'ultra-max-core' ),
					'class'    => 'mt-4',
				)
			);
		}
		return AccountSection::render(
			array(
				'title'    => __( 'Orders', 'ultra-max-core' ),
				'children' => $parts,
			)
		);
	}

	/**
	 * The buttons of an order: pay, cancel and those other plugins add,
	 * such as the invoice (phase 6).
	 *
	 * @param \WC_Order $order Order.
	 * @return list<Html>
	 */
	private static function actions( \WC_Order $order ): array {
		$out = array();
		foreach ( wc_get_account_orders_actions( $order ) as $key => $action ) {
			if ( 'view' === $key || ! is_array( $action ) || ! is_string( $action['url'] ?? null ) || ! is_string( $action['name'] ?? null ) ) {
				continue;
			}
			$out[] = UI::button(
				array(
					'href'     => $action['url'],
					'size'     => 'sm',
					'variant'  => 'pay' === $key ? 'primary' : 'outline',
					'children' => $action['name'],
				)
			);
		}
		return $out;
	}

	/**
	 * The products of an order (ACC-02).
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
			$visible = $product instanceof \WC_Product && $product->is_visible();
			$details = array(
				/* translators: %s: quantity. */
				sprintf( __( 'Quantity: %s', 'ultra-max-core' ), Format::number( $item->get_quantity() ) ),
			);
			foreach ( $item->get_all_formatted_meta_data() as $meta ) {
				if ( is_object( $meta ) && isset( $meta->display_key, $meta->display_value ) ) {
					$details[] = wp_strip_all_tags( (string) $meta->display_key ) . ': ' . trim( wp_strip_all_tags( (string) $meta->display_value ) );
				}
			}
			$actions = array();
			if ( $visible && 'completed' === $order->get_status() && comments_open( $product->get_id() ) ) {
				$actions[] = UI::button(
					array(
						'href'     => (string) $product->get_permalink() . '#reviews',
						'size'     => 'sm',
						'variant'  => 'outline',
						'children' => __( 'Write a review', 'ultra-max-core' ),
					)
				);
			}
			if ( $visible && $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
				$actions[] = UI::button(
					array(
						'href'     => $product->add_to_cart_url(),
						'size'     => 'sm',
						'variant'  => 'ghost',
						'children' => __( 'Buy again', 'ultra-max-core' ),
						'attrs'    => array( 'rel' => 'nofollow' ),
					)
				);
			}
			$image   = $product instanceof \WC_Product ? $product->get_image(
				'woocommerce_thumbnail',
				array(
					'loading' => 'lazy',
					'alt'     => '',
				)
			) : '';
			$items[] = array(
				'title'   => $item->get_name(),
				'href'    => $visible ? (string) $product->get_permalink() : '',
				'image'   => Html::wp( is_string( $image ) ? $image : '' ),
				'details' => $details,
				'total'   => html_entity_decode( wp_strip_all_tags( $order->get_formatted_line_subtotal( $item ) ), ENT_QUOTES, 'UTF-8' ),
				'actions' => $actions,
			);
		}
		return OrderProducts::render(
			array(
				'label' => __( 'Products of this order', 'ultra-max-core' ),
				'items' => $items,
			)
		);
	}

	/**
	 * The progress of an order with its tracking code (ACC-02).
	 *
	 * @param \WC_Order $order   Order.
	 * @param bool      $heading Whether to show its heading row.
	 */
	public static function progress( \WC_Order $order, bool $heading = true ): Html {
		$status = Orders::status( $order );
		if ( Orders::stopped( $order ) ) {
			$body = Alert::render(
				array(
					'tone'     => 'returned' === Orders::group_of( $order->get_status() ) ? 'info' : 'danger',
					'children' => sprintf(
						/* translators: %s: order status, such as Cancelled. */
						__( 'This order is %s.', 'ultra-max-core' ),
						$status['label']
					),
				)
			);
		} else {
			$body = Html::from(
				array(
					OrderSteps::render(
						array(
							'label' => __( 'Order progress', 'ultra-max-core' ),
							'steps' => Orders::steps( $order ),
						)
					),
					Tracking::render( $order ),
				)
			);
		}
		if ( ! $heading ) {
			return Html::trusted( sprintf( '<div class="grid gap-5">%s</div>', $body ) );
		}
		return AccountSection::render(
			array(
				'title'    => __( 'Order progress', 'ultra-max-core' ),
				'end'      => OrderCard::status( $status ),
				'children' => Html::trusted( sprintf( '<div class="grid gap-5">%s</div>', $body ) ),
			)
		);
	}

	/**
	 * One order (ACC-02): its facts, progress and tracking code, products,
	 * recipient, payment and notes from the store.
	 *
	 * @param int $order_id Order id.
	 */
	public static function order( int $order_id ): Html {
		$order = wc_get_order( $order_id );
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce's own capability.
		if ( ! $order instanceof \WC_Order || ! current_user_can( 'view_order', $order_id ) ) {
			return Alert::render(
				array(
					'tone'     => 'danger',
					'children' => __( 'This order cannot be found.', 'ultra-max-core' ),
				)
			);
		}
		$created  = $order->get_date_created();
		$facts    = InfoGrid::render(
			array(
				'columns' => 4,
				'items'   => array(
					array(
						'label' => __( 'Order number', 'ultra-max-core' ),
						'value' => Format::digits( $order->get_order_number() ),
						'num'   => true,
					),
					array(
						'label' => __( 'Placed on', 'ultra-max-core' ),
						'value' => null === $created ? '' : Format::date( $created->getTimestamp(), 'j F Y، H:i' ),
					),
					array(
						'label' => __( 'Total', 'ultra-max-core' ),
						'value' => Orders::total( $order ),
						'num'   => true,
					),
					array(
						'label' => __( 'Payment method', 'ultra-max-core' ),
						'value' => $order->get_payment_method_title(),
					),
				),
			)
		);
		$head     = Html::trusted(
			sprintf(
				'<div class="flex flex-wrap items-center justify-between gap-3">%1$s<div class="flex flex-wrap gap-2">%2$s</div></div>',
				UI::button(
					array(
						'href'     => wc_get_account_endpoint_url( 'orders' ),
						'variant'  => 'ghost',
						'size'     => 'sm',
						'children' => array( UI::icon( is_rtl() ? 'arrow-right' : 'arrow-left', 'size-4' ), __( 'Back to the orders', 'ultra-max-core' ) ),
					)
				),
				Html::from( self::actions( $order ) )
			)
		);
		$totals   = array();
		$rows     = $order->get_order_item_totals();
		$last_key = array_key_last( $rows );
		foreach ( $rows as $key => $row ) {
			if ( 'payment_method' === $key ) {
				continue;
			}
			$totals[] = array(
				'label'  => rtrim( wp_strip_all_tags( (string) $row['label'] ), ':： ' ),
				'value'  => Html::wp( wp_kses_post( (string) $row['value'] ) ),
				'strong' => $key === $last_key,
			);
		}
		$address = $order->has_shipping_address() ? $order->get_formatted_shipping_address() : $order->get_formatted_billing_address();
		$lines   = array_values( array_filter( array_map( static fn ( string $line ): string => trim( wp_strip_all_tags( $line ) ), preg_split( '/<br\s*\/?>/i', $address ) ? preg_split( '/<br\s*\/?>/i', $address ) : array() ) ) );
		$phone   = $order->get_billing_phone();
		if ( '' !== $phone ) {
			$lines[] = Format::digits( $phone );
		}
		$aside = array(
			AccountSection::render(
				array(
					'title'    => __( 'Recipient', 'ultra-max-core' ),
					'children' => Html::trusted( sprintf( '<div class="grid gap-1">%s</div>', implode( '', array_map( static fn ( string $line ): string => '<span>' . esc_html( $line ) . '</span>', $lines ) ) ) ),
				)
			),
			AccountSection::render(
				array(
					'title'    => __( 'Payment details', 'ultra-max-core' ),
					'children' => SummaryRows::render( array( 'rows' => $totals ) ),
				)
			),
		);
		$notes = $order->get_customer_order_notes();
		if ( array() !== $notes ) {
			$items = array();
			foreach ( $notes as $note ) {
				$time    = strtotime( (string) $note->comment_date_gmt . ' UTC' );
				$items[] = array(
					'label' => false === $time ? '' : Format::date( $time, 'j F Y، H:i' ),
					'value' => wp_strip_all_tags( (string) $note->comment_content ),
				);
			}
			$aside[] = AccountSection::render(
				array(
					'title'    => __( 'Updates from the store', 'ultra-max-core' ),
					'children' => InfoGrid::render(
						array(
							'items'   => $items,
							'columns' => 2,
						)
					),
				)
			);
		}
		ob_start();
		// WooCommerce's own order table is ours above; other plugins' additions to this hook stay.
		remove_action( 'woocommerce_view_order', 'woocommerce_order_details_table', 10 );
		/** This action is documented in WooCommerce's myaccount/view-order.php template. */
		do_action( 'woocommerce_view_order', $order_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
		$extra = (string) ob_get_clean();
		return Html::from(
			array(
				$head,
				AccountSection::render(
					array(
						'title'    => __( 'Order summary', 'ultra-max-core' ),
						'children' => $facts,
					)
				),
				self::progress( $order ),
				Html::trusted(
					sprintf(
						'<div class="grid gap-4">%1$s<div class="grid items-start gap-4 md:grid-cols-2">%2$s</div></div>',
						AccountSection::render(
							array(
								'title'    => __( 'Products', 'ultra-max-core' ),
								'children' => self::items( $order ),
							)
						),
						Html::from( $aside )
					)
				),
				Html::wp( $extra ),
			)
		);
	}

	/**
	 * "My lists" (ACC-04): the wishlist, and lists other parts add, such as
	 * "buy later" (CART-02) and "tell me when" (PDP-12).
	 */
	public static function lists(): Html {
		$items = array();
		if ( Saved::enabled( Saved::WISHLIST ) ) {
			$items[] = array(
				'label'   => __( 'Wishlist', 'ultra-max-core' ),
				'content' => Html::trusted( sprintf( '<div class="pt-4">%s</div>', Wishlist::page( 4 ) ) ),
			);
		}
		/**
		 * Filters the tabs of "My lists" in the account.
		 *
		 * @param array $items Tabs: label and content (Html).
		 * @phpstan-param list<array{label: string, content: Html}> $items
		 */
		$filtered = apply_filters( 'ultra_max_account_lists', $items );
		$tabs     = array();
		foreach ( is_array( $filtered ) ? $filtered : array() as $tab ) {
			if ( is_array( $tab ) && is_string( $tab['label'] ?? null ) && ( $tab['content'] ?? null ) instanceof Html ) {
				$tabs[] = array(
					'label'   => $tab['label'],
					'content' => $tab['content'],
				);
			}
		}
		$body = array() === $tabs
			? EmptyState::render(
				array(
					'icon'  => 'heart',
					'title' => __( 'No lists yet', 'ultra-max-core' ),
				)
			)
			: UI::tabs(
				array(
					'id'      => 'um-account-lists',
					'label'   => __( 'My lists', 'ultra-max-core' ),
					'variant' => 'pill',
					'items'   => $tabs,
				)
			);
		return AccountSection::render(
			array(
				'title'    => __( 'My lists', 'ultra-max-core' ),
				'children' => $body,
			)
		);
	}

	/**
	 * "Reviews and questions" (ACC-05).
	 *
	 * @param int $page Page number.
	 */
	public static function activity( int $page = 1 ): Html {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only picks a tab.
		$kind  = isset( $_GET['type'] ) && 'questions' === $_GET['type'] ? 'questions' : 'reviews';
		$found = Activity::query( get_current_user_id(), $kind, $page );
		$base  = wc_get_account_endpoint_url( Endpoints::ACTIVITY );
		$tabs  = array();
		foreach ( array(
			'reviews'   => __( 'My reviews', 'ultra-max-core' ),
			'questions' => __( 'My questions', 'ultra-max-core' ),
		) as $key => $label ) {
			$tabs[] = UI::button(
				array(
					'href'     => add_query_arg( 'type', $key, $base ),
					'variant'  => $key === $kind ? 'soft' : 'ghost',
					'size'     => 'sm',
					'children' => $label,
					'attrs'    => $key === $kind ? array( 'aria-current' => 'page' ) : array(),
				)
			);
		}
		$body  = array() === $found['items']
			? EmptyState::render(
				array(
					'icon'  => 'chat',
					'title' => 'questions' === $kind ? __( 'You have not asked a question yet', 'ultra-max-core' ) : __( 'You have not written a review yet', 'ultra-max-core' ),
					'text'  => __( 'Your words help other shoppers choose.', 'ultra-max-core' ),
				)
			)
			: Activity::render( $found['items'], $kind );
		$parts = array(
			Html::trusted( sprintf( '<nav class="mb-4 flex flex-wrap gap-2" aria-label="%1$s">%2$s</nav>', esc_attr__( 'Reviews or questions', 'ultra-max-core' ), Html::from( $tabs ) ) ),
			$body,
		);
		$pages = (int) ceil( $found['total'] / Activity::PER_PAGE );
		if ( $pages > 1 ) {
			$parts[] = Pagination::render(
				array(
					'label'    => __( 'Pages', 'ultra-max-core' ),
					'current'  => max( 1, $page ),
					'total'    => $pages,
					'base'     => add_query_arg( 'type', $kind, wc_get_endpoint_url( Endpoints::ACTIVITY, '%#%', wc_get_page_permalink( 'myaccount' ) ) ),
					'previous' => __( 'Previous', 'ultra-max-core' ),
					'next'     => __( 'Next', 'ultra-max-core' ),
					'class'    => 'mt-4',
				)
			);
		}
		return AccountSection::render(
			array(
				'title'    => __( 'Reviews and questions', 'ultra-max-core' ),
				'children' => $parts,
			)
		);
	}

	/**
	 * The saved addresses (ACC-06), or WooCommerce's form for one of them.
	 *
	 * @param string $type billing, shipping, or empty for the list.
	 */
	public static function addresses( string $type = '' ): Html {
		if ( '' !== $type ) {
			ob_start();
			woocommerce_account_edit_address( $type );
			return AccountSection::render(
				array(
					'title'    => 'shipping' === $type ? __( 'Shipping address', 'ultra-max-core' ) : __( 'Billing address', 'ultra-max-core' ),
					'children' => Html::trusted( sprintf( '<div class="um-account-form">%s</div>', (string) ob_get_clean() ) ),
				)
			);
		}
		$types = array( 'billing' => __( 'Billing address', 'ultra-max-core' ) );
		if ( wc_shipping_enabled() && ! wc_ship_to_billing_address_only() ) {
			$types['shipping'] = __( 'Shipping address', 'ultra-max-core' );
		}
		$cards = array();
		foreach ( $types as $key => $title ) {
			$formatted = wc_get_account_formatted_address( $key );
			$split     = preg_split( '/<br\s*\/?>/i', $formatted );
			$cards[]   = AddressCard::render(
				array(
					'title'   => $title,
					'lines'   => array_values( array_filter( array_map( static fn ( string $line ): string => trim( wp_strip_all_tags( $line ) ), is_array( $split ) ? $split : array() ) ) ),
					'actions' => array(
						UI::button(
							array(
								'href'     => wc_get_endpoint_url( 'edit-address', $key ),
								'variant'  => 'outline',
								'size'     => 'sm',
								'children' => array( UI::icon( 'edit', 'size-4' ), __( 'Edit', 'ultra-max-core' ) ),
							)
						),
					),
					'empty'   => __( 'You have not saved this address yet.', 'ultra-max-core' ),
				)
			);
		}
		$sections = array(
			AccountSection::render(
				array(
					'title'    => __( 'Addresses', 'ultra-max-core' ),
					'children' => Html::trusted( sprintf( '<div class="grid gap-4 md:grid-cols-2">%s</div>', Html::from( $cards ) ) ),
				)
			),
		);
		$book     = self::address_book();
		if ( ! $book->is_empty() ) {
			$sections[] = $book;
		}
		return Html::from( $sections );
	}

	/**
	 * The addresses the checkout saved (CHK-02), each with "Remove".
	 */
	public static function address_book(): Html {
		if ( ! class_exists( AddressBook::class ) || ! AddressBook::enabled() ) {
			return Html::none();
		}
		$cards = array();
		foreach ( AddressBook::of( get_current_user_id() ) as $address ) {
			$name    = trim( $address['first_name'] . ' ' . $address['last_name'] );
			$cards[] = AddressCard::render(
				array(
					'title'   => '' === $name ? __( 'Saved address', 'ultra-max-core' ) : $name,
					'lines'   => array_values( array_filter( array( AddressBook::place( $address ), AddressBook::summary( $address ) ) ) ),
					'actions' => array(
						Html::trusted(
							sprintf(
								'<form method="post">%1$s<input type="hidden" name="um_address_remove" value="%2$s">%3$s</form>',
								wp_nonce_field( Account::ADDRESS_NONCE, 'um_address_nonce', true, false ),
								esc_attr( $address['id'] ),
								UI::button(
									array(
										'type'     => 'submit',
										'variant'  => 'ghost',
										'size'     => 'sm',
										'children' => array( UI::icon( 'trash', 'size-4' ), __( 'Remove', 'ultra-max-core' ) ),
									)
								)
							)
						),
					),
					'empty'   => '',
				)
			);
		}
		if ( array() === $cards ) {
			return Html::none();
		}
		return AccountSection::render(
			array(
				'title'    => __( 'Address book', 'ultra-max-core' ),
				'children' => Html::from(
					array(
						Html::trusted( sprintf( '<p class="mt-0 mb-4">%s</p>', esc_html__( 'Addresses you used at checkout. Choose one of them in your next order.', 'ultra-max-core' ) ) ),
						Html::trusted( sprintf( '<div class="grid gap-4 md:grid-cols-2">%s</div>', Html::from( $cards ) ) ),
					)
				),
			)
		);
	}

	/**
	 * The account details form (ACC-07): WooCommerce's, with our fields.
	 */
	public static function details(): Html {
		ob_start();
		woocommerce_account_edit_account();
		return AccountSection::render(
			array(
				'title'    => __( 'Account details', 'ultra-max-core' ),
				'children' => Html::trusted( sprintf( '<div class="um-account-form">%s</div>', (string) ob_get_clean() ) ),
			)
		);
	}

	/**
	 * The recent views page (ACC-08).
	 */
	public static function recent_page(): Html {
		$recent = self::recent();
		return $recent->is_empty()
			? EmptyState::render(
				array(
					'icon'  => 'history',
					'title' => __( 'Recent views are off', 'ultra-max-core' ),
				)
			)
			: Html::from(
				array(
					$recent,
					Html::trusted( sprintf( '<p class="m-0" data-um-recent-empty>%s</p>', esc_html__( 'Products you open show here. They are kept only in this browser.', 'ultra-max-core' ) ) ),
				)
			);
	}
}
