<?php
/**
 * The side cart (CART-01).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Cart;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Lib\UI\Button;
use UltraMax\Core\Lib\UI\Drawer;
use UltraMax\Core\Lib\UI\EmptyState;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\SideCart as Component;

/**
 * A drawer at the end of every page except the cart and the checkout. The
 * header's cart button opens it (and still links to the cart page without
 * JavaScript). Its content comes from the endpoint when it opens, so pages
 * stay cacheable, and it opens by itself after a product is added if the
 * store wants that.
 */
final class SideCart {

	public const ID = 'um-side-cart';

	/**
	 * Hooks the drawer.
	 */
	public static function register(): void {
		add_action( 'wp_footer', array( self::class, 'drawer' ), 5 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'assets' ), 20 );
		add_filter( 'ultra_max_side_cart', array( self::class, 'id' ) );
	}

	/**
	 * Whether this page has the drawer.
	 */
	public static function shows(): bool {
		return Store::bool( 'cart_side' ) && ! is_cart() && ! is_checkout() && ! is_admin();
	}

	/**
	 * The drawer id for the header's cart buttons, or an empty text.
	 *
	 * @param mixed $id Id so far.
	 * @return mixed
	 */
	public static function id( mixed $id ): mixed {
		return self::shows() ? self::ID : $id;
	}

	/**
	 * Queues the cart files.
	 */
	public static function assets(): void {
		if ( self::shows() ) {
			Assets::enqueue_widget( 'cart' );
		}
	}

	/**
	 * Prints the drawer with its empty holder.
	 */
	public static function drawer(): void {
		if ( ! self::shows() ) {
			return;
		}
		Drawer::render(
			array(
				'id'       => self::ID,
				'title'    => __( 'Your cart', 'ultra-max-core' ),
				'side'     => 'end',
				'children' => Component::shell(
					array(
						'endpoint'    => Actions::endpoint(),
						'loading'     => __( 'Loading your cart…', 'ultra-max-core' ),
						'error'       => __( 'Your cart could not be loaded. Open the cart page instead.', 'ultra-max-core' ),
						'open_on_add' => Store::bool( 'cart_open_on_add' ),
					)
				),
			)
		)->print();
	}

	/**
	 * What the drawer shows now.
	 */
	public static function content(): Html {
		$cart = WC()->cart;
		if ( $cart->is_empty() ) {
			return Component::render(
				array(
					'lines' => array(),
					'empty' => EmptyState::render(
						array(
							'icon'     => 'cart',
							'title'    => __( 'Your cart is empty!', 'ultra-max-core' ),
							'heading'  => 'h2',
							'children' => Button::render(
								array(
									'children' => __( 'Go to the shop', 'ultra-max-core' ),
									'href'     => wc_get_page_permalink( 'shop' ),
									'variant'  => 'soft',
									'size'     => 'sm',
								)
							),
						)
					),
				)
			);
		}
		$amounts = Totals::of( $cart );
		$rows    = array(
			array(
				'label' => __( 'Cart total', 'ultra-max-core' ),
				'value' => Totals::money( $amounts['total'] ),
			),
		);
		if ( Store::bool( 'cart_savings' ) && $amounts['saving'] > 0 ) {
			$rows[] = array(
				'label' => __( 'Your saving', 'ultra-max-core' ),
				'value' => Totals::saving_text( $amounts ),
				'tone'  => 'save',
			);
		}
		$checkout = array(
			'children' => __( 'Check out', 'ultra-max-core' ),
			'block'    => true,
		);
		if ( '' === Limits::problem( $cart ) ) {
			$checkout['href'] = wc_get_checkout_url();
		} else {
			$checkout['disabled'] = true;
		}
		return Component::render(
			array(
				'lines'   => Lines::all( true ),
				'rows'    => $rows,
				'extras'  => array( Totals::free_bar( $cart ), Page::limit_alert() ),
				'actions' => array(
					Button::render(
						array(
							'children' => __( 'View cart', 'ultra-max-core' ),
							'href'     => wc_get_cart_url(),
							'variant'  => 'outline',
							'block'    => true,
						)
					),
					Button::render( $checkout ),
				),
				'nonce'   => wp_create_nonce( Actions::NONCE ),
				'count'   => $amounts['count'],
			)
		);
	}
}
