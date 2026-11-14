<?php
/**
 * The cart page (CART-01 to CART-05, CART-10, BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Cart;

use UltraMax\Core\Builder\Builder;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Alert;
use UltraMax\Core\Lib\UI\Badge;
use UltraMax\Core\Lib\UI\Button;
use UltraMax\Core\Lib\UI\CartBox;
use UltraMax\Core\Lib\UI\CartSummary;
use UltraMax\Core\Lib\UI\CheckoutBar;
use UltraMax\Core\Lib\UI\CouponForm;
use UltraMax\Core\Lib\UI\EmptyState;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\SavedItems;
use UltraMax\Core\Lib\UI\Tabs;
use UltraMax\Core\Store\Cards;
use UltraMax\Core\Store\Format;

/**
 * One renderer for every piece of the cart page. The built-in page, the
 * cart widgets (BLD-06) and the endpoint that redraws the page after a
 * change all call these, so a cart designed with Elementor behaves exactly
 * like the built-in one.
 *
 * Every piece is a named part (`data-um-cart-part`). The cart behaviour
 * asks the endpoint to draw the parts on the page again after each change.
 *
 * The built-in arrangement follows design/cart.html: "cart" and "buy
 * later" tabs; the lines beside the totals; complementary products; and a
 * checkout bar on phones.
 */
final class Page {

	public const PARTS = array( 'lines', 'summary', 'coupon', 'later', 'suggestions', 'bar', 'tab-cart', 'tab-later', 'empty' );

	/**
	 * The root around the cart parts, which the cart behaviour mounts on.
	 *
	 * @param Html   $children Parts.
	 * @param string $context  cart or blocks.
	 */
	public static function root( Html $children, string $context = 'cart' ): Html {
		return Html::trusted(
			sprintf(
				'<div class="um-cart-page grid min-w-0 grid-cols-[minmax(0,1fr)] gap-5" data-um-component="cart" data-endpoint="%1$s" data-nonce="%2$s" data-context="%3$s">%4$s</div>',
				esc_url( Actions::endpoint() ),
				esc_attr( wp_create_nonce( Actions::NONCE ) ),
				esc_attr( $context ),
				$children
			)
		);
	}

	/**
	 * The whole page: a cart layout designed with Elementor, or the built-in arrangement.
	 */
	public static function page(): Html {
		$left = array(
			'desktop' => true,
			'tablet'  => true,
			'mobile'  => true,
		);
		ob_start();
		if ( Builder::enabled() ) {
			/** This filter is documented in themes/ultra-max/src/Template.php */
			$covered = apply_filters( 'ultra_max_do_location', array_map( '__return_false', $left ), Cart::LOCATION );
			$covered = is_array( $covered ) ? $covered : array();
			foreach ( array_keys( $left ) as $device ) {
				$left[ $device ] = true !== ( $covered[ $device ] ?? false );
			}
		}
		$parts = array( Html::wp( (string) ob_get_clean() ) );
		if ( in_array( true, $left, true ) ) {
			$parts[] = self::arrangement( \UltraMax\Core\Lib\UI\UI::visibility( $left ) );
		}
		return self::root( Html::from( $parts ) );
	}

	/**
	 * The built-in arrangement of design/cart.html.
	 *
	 * @param string $classes Visibility classes for the devices it covers.
	 */
	public static function arrangement( string $classes = '' ): Html {
		if ( WC()->cart->is_empty() ) {
			return Html::trusted(
				sprintf(
					'<div class="%1$s">%2$s%3$s%4$s</div>',
					esc_attr( trim( 'grid min-w-0 gap-5 ' . $classes ) ),
					self::part( 'empty' ),
					self::part( 'later' ),
					self::part( 'suggestions' )
				)
			);
		}
		$grid = Html::trusted(
			sprintf(
				'<div class="grid min-w-0 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">%1$s<div class="grid min-w-0 gap-3 lg:sticky lg:top-[calc(var(--wp-admin--admin-bar--height,0px)+var(--um-header-offset,0px)+16px)]">%2$s%3$s</div></div>',
				self::part( 'lines' ),
				self::part( 'summary', array( 'sticky' => false ) ),
				self::part( 'coupon' )
			)
		);
		$main = $grid;
		if ( Later::enabled() ) {
			$main = Tabs::render(
				array(
					'id'    => 'um-cart-tabs',
					'label' => __( 'Cart and buy later', 'ultra-max-core' ),
					'items' => array(
						array(
							'label'   => Html::trusted( esc_html__( 'Cart', 'ultra-max-core' ) . ' ' . self::part( 'tab-cart' ) ),
							'content' => $grid,
						),
						array(
							'label'   => Html::trusted( esc_html__( 'Buy later', 'ultra-max-core' ) . ' ' . self::part( 'tab-later' ) ),
							'content' => self::part( 'later' ),
						),
					),
				)
			);
		}
		return Html::trusted(
			sprintf(
				'<div class="%1$s">%2$s%3$s%4$s</div>',
				esc_attr( trim( 'grid min-w-0 gap-5 ' . $classes ) ),
				$main,
				self::part( 'suggestions' ),
				self::part( 'bar' )
			)
		);
	}

	/**
	 * One named part, wrapped so the page can draw it again.
	 *
	 * @param string               $name Part name, one of PARTS.
	 * @param array<string, mixed> $args Options, such as a widget's.
	 */
	public static function part( string $name, array $args = array() ): Html {
		$inner = self::draw( $name, $args );
		$tag   = in_array( $name, array( 'tab-cart', 'tab-later' ), true ) ? 'span' : 'div';
		return Html::trusted(
			sprintf(
				'<%1$s class="%2$s" data-um-cart-part="%3$s" data-args="%4$s">%5$s</%1$s>',
				$tag,
				'span' === $tag ? 'inline-flex' : 'min-w-0',
				esc_attr( $name ),
				esc_attr( array() === $args ? '' : (string) wp_json_encode( $args ) ),
				$inner
			)
		);
	}

	/**
	 * The inside of a part.
	 *
	 * @param string               $name Part name.
	 * @param array<string, mixed> $args Options.
	 */
	public static function draw( string $name, array $args = array() ): Html {
		return match ( $name ) {
			'lines'       => self::lines( $args ),
			'summary'     => self::summary( (bool) ( $args['sticky'] ?? true ) ),
			'coupon'      => self::coupon(),
			'later'       => self::later(),
			'suggestions' => Suggestions::section( is_numeric( $args['count'] ?? null ) ? (int) $args['count'] : 0 ),
			'bar'         => self::bar(),
			'tab-cart'    => self::count_badge( (int) WC()->cart->get_cart_contents_count(), 'neutral' ),
			'tab-later'   => self::count_badge( count( Later::items() ), 'brand' ),
			'empty'       => WC()->cart->is_empty() ? self::empty() : Html::none(),
			default       => Html::none(),
		};
	}

	/**
	 * A number in a tab.
	 *
	 * @param int    $count Number.
	 * @param string $tone  Badge tone.
	 */
	private static function count_badge( int $count, string $tone ): Html {
		if ( 0 === $count ) {
			return Html::none();
		}
		return Badge::render(
			array(
				'children' => Format::number( $count ),
				'tone'     => 'brand' === $tone ? 'brand' : 'neutral',
			)
		);
	}

	/**
	 * The lines in their box (CART-01).
	 *
	 * @param array<string, mixed> $args Options: heading (h1, h2).
	 */
	public static function lines( array $args = array() ): Html {
		$cart = WC()->cart;
		if ( $cart->is_empty() ) {
			return Html::none();
		}
		$count = (int) $cart->get_cart_contents_count();
		return CartBox::render(
			array(
				'title'   => __( 'Your cart', 'ultra-max-core' ),
				/* translators: %s: number of items. */
				'count'   => sprintf( _n( '%s item', '%s items', $count, 'ultra-max-core' ), Format::number( $count ) ),
				'heading' => 'h2' === ( $args['heading'] ?? 'h1' ) ? 'h2' : 'h1',
				'lines'   => Lines::all(),
				'action'  => wc_get_cart_url(),
				'nonce'   => Html::trusted( wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce', false, false ) ),
				'update'  => __( 'Update cart', 'ultra-max-core' ),
			)
		);
	}

	/**
	 * The rows of the totals box on the cart.
	 *
	 * @return list<array{label: string|Html, value: string|Html, tone?: 'save'|'total'|'strong', key?: string}>
	 */
	public static function rows(): array {
		$amounts = Totals::of( WC()->cart );
		$rows    = array(
			array(
				/* translators: %s: number of items. */
				'label' => sprintf( __( 'Items (%s)', 'ultra-max-core' ), Format::number( $amounts['count'] ) ),
				'value' => Totals::money( $amounts['regular'] ),
				'key'   => 'regular',
			),
			array(
				'label' => __( 'Cart total', 'ultra-max-core' ),
				'value' => Totals::money( $amounts['total'] ),
				'tone'  => 'strong',
				'key'   => 'total',
			),
		);
		if ( Store::bool( 'cart_savings' ) && $amounts['saving'] > 0 ) {
			$rows[] = array(
				'label' => __( 'Your saving', 'ultra-max-core' ),
				'value' => Totals::saving_text( $amounts ),
				'tone'  => 'save',
				'key'   => 'saving',
			);
		}
		return $rows;
	}

	/**
	 * The order limit message (CART-10), or nothing.
	 */
	public static function limit_alert(): Html {
		$problem = Limits::problem();
		return '' === $problem ? Html::none() : Alert::render(
			array(
				'tone'     => 'warning',
				'children' => $problem,
			)
		);
	}

	/**
	 * The totals box (CART-03, CART-04, CART-10).
	 *
	 * @param bool $sticky Stay in view on desktop.
	 */
	public static function summary( bool $sticky = true ): Html {
		$cart = WC()->cart;
		if ( $cart->is_empty() ) {
			return Html::none();
		}
		$button = self::checkout_button( true );
		$note   = Store::string( 'cart_note' );
		$props  = array(
			'rows'     => self::rows(),
			'extras'   => array( Totals::free_bar( $cart ), self::limit_alert() ),
			'actions'  => array( $button ),
			'footnote' => $cart->needs_shipping() ? __( 'Shipping is worked out from the address, delivery time, weight and size of the parcel.', 'ultra-max-core' ) : '',
			'sticky'   => $sticky,
			'label'    => __( 'Cart totals', 'ultra-max-core' ),
		);
		if ( '' !== $note ) {
			$props['note'] = array(
				'icon' => 'info',
				'text' => $note,
			);
		}
		return CartSummary::render( $props );
	}

	/**
	 * The coupon form, when coupons are on.
	 */
	public static function coupon(): Html {
		$cart = WC()->cart;
		if ( ! wc_coupons_enabled() || $cart->is_empty() ) {
			return Html::none();
		}
		$chips = array();
		foreach ( $cart->get_coupons() as $code => $coupon ) {
			$amount  = (float) $cart->get_coupon_discount_amount( (string) $code, ! $cart->display_prices_including_tax() );
			$chips[] = array(
				'code'   => (string) $code,
				'remove' => add_query_arg( 'remove_coupon', rawurlencode( (string) $code ), wc_get_cart_url() ),
				'amount' => '-' . Totals::money( $amount ),
			);
		}
		return \UltraMax\Core\Lib\UI\Box::render(
			array(
				'padding'  => 'md',
				'children' => CouponForm::render(
					array(
						'id'      => 'um-cart-coupon',
						'title'   => __( 'Discount code', 'ultra-max-core' ),
						'label'   => __( 'Discount code', 'ultra-max-core' ),
						'submit'  => __( 'Apply', 'ultra-max-core' ),
						/* translators: %s: coupon code. */
						'remove'  => __( 'Remove code %s', 'ultra-max-core' ),
						'action'  => wc_get_cart_url(),
						'hidden'  => array( 'woocommerce-cart-nonce' => wp_create_nonce( 'woocommerce-cart' ) ),
						'coupons' => $chips,
					)
				),
			)
		);
	}

	/**
	 * The "buy later" list (CART-02).
	 */
	public static function later(): Html {
		if ( ! Later::enabled() ) {
			return Html::none();
		}
		$items = array();
		foreach ( Later::items() as $saved ) {
			$items[] = array(
				'key'     => $saved['key'],
				'card'    => Cards::card( $saved['product'], 'swatch', array( 'priority' => false ) ),
				'restore' => Lines::action_url( 'restore', $saved['key'] ),
				'remove'  => Lines::action_url( 'forget', $saved['key'] ),
			);
		}
		if ( array() === $items && WC()->cart->is_empty() ) {
			return Html::none();
		}
		return SavedItems::render(
			array(
				'title'  => __( 'Buy later', 'ultra-max-core' ),
				'text'   => __( 'Products you set aside for another time', 'ultra-max-core' ),
				'items'  => $items,
				'all'    => Lines::action_url( 'restore-all' ),
				'labels' => array(
					'restore' => __( 'Move to cart', 'ultra-max-core' ),
					'remove'  => __( 'Remove from the list', 'ultra-max-core' ),
					'all'     => __( 'Move all to cart', 'ultra-max-core' ),
					'empty'   => __( 'Nothing here yet. Use "Move to buy later" on a cart item to keep it for another time.', 'ultra-max-core' ),
				),
			)
		);
	}

	/**
	 * The phone checkout bar.
	 */
	public static function bar(): Html {
		$cart = WC()->cart;
		if ( $cart->is_empty() ) {
			return Html::none();
		}
		return CheckoutBar::render(
			array(
				'label'  => __( 'Cart total', 'ultra-max-core' ),
				'amount' => Totals::money_html( Totals::of( $cart )['total'] ),
				'action' => self::checkout_button( false ),
			)
		);
	}

	/**
	 * The button to the checkout; a disabled button outside the order limits.
	 *
	 * @param bool $box In the totals box (hidden on phones, which have the bar).
	 */
	public static function checkout_button( bool $box ): Html {
		$props = array(
			'children' => __( 'Confirm and check out', 'ultra-max-core' ),
			'size'     => 'lg',
		);
		if ( '' === Limits::problem() ) {
			$props['href'] = wc_get_checkout_url();
		} else {
			$props['disabled'] = true;
		}
		if ( $box ) {
			$props['block'] = true;
			$props['class'] = 'max-md:hidden';
		}
		return Button::render( $props );
	}

	/**
	 * The empty cart.
	 */
	public static function empty(): Html {
		$links = array(
			Button::render(
				array(
					'children' => __( 'Go to the shop', 'ultra-max-core' ),
					'href'     => wc_get_page_permalink( 'shop' ),
					'variant'  => 'soft',
					'size'     => 'sm',
				)
			),
			Button::render(
				array(
					'children' => __( 'Best sellers', 'ultra-max-core' ),
					'href'     => add_query_arg( 'orderby', 'popularity', wc_get_page_permalink( 'shop' ) ),
					'variant'  => 'soft',
					'size'     => 'sm',
				)
			),
		);
		return \UltraMax\Core\Lib\UI\Box::render(
			array(
				'padding'  => 'lg',
				'children' => EmptyState::render(
					array(
						'icon'     => 'cart',
						'title'    => __( 'Your cart is empty!', 'ultra-max-core' ),
						'text'     => __( 'You can find more products on these pages:', 'ultra-max-core' ),
						'heading'  => 'h1',
						'children' => $links,
					)
				),
			)
		);
	}
}
