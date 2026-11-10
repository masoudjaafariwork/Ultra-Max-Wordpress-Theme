<?php
/**
 * Cart widget (HDR-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

use UltraMax\Core\Lib\UI\HeaderAction;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Store\Format;
use UltraMax\Core\Widgets\Values;

/**
 * The cart link with the number of items. The number updates in place
 * when a product is added from a product card, through WooCommerce's own
 * cart fragments. The side cart (CART-01) arrives with the cart features.
 */
final class Cart extends HeaderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'cart';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Cart', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-cart';
	}

	/**
	 * Registers the cart count fragment.
	 */
	public static function register(): void {
		add_filter( 'woocommerce_add_to_cart_fragments', array( self::class, 'fragments' ) );
	}

	/**
	 * Adds the new cart count to WooCommerce's fragments.
	 *
	 * @param mixed $fragments Fragments by selector.
	 * @return mixed
	 */
	public static function fragments( mixed $fragments ): mixed {
		if ( is_array( $fragments ) ) {
			$fragments['[data-um-count="cart"]'] = (string) self::badge();
		}
		return $fragments;
	}

	/**
	 * Items in the cart.
	 */
	public static function count(): int {
		return function_exists( 'WC' ) && null !== WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
	}

	/**
	 * The count badge.
	 */
	public static function badge(): Html {
		$count = self::count();
		return HeaderAction::badge( 'cart', $count, Format::number( $count ) );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'cart_section',
				'label'  => __( 'Cart', 'ultra-max-core' ),
				'fields' => array(
					$this->look_field(),
					array(
						'id'      => 'text',
						'type'    => 'text',
						'label'   => __( 'Text', 'ultra-max-core' ),
						'default' => __( 'Cart', 'ultra-max-core' ),
					),
				),
			),
		);
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		if ( ! function_exists( 'wc_get_cart_url' ) ) {
			return Html::none();
		}
		$count = self::count();
		return HeaderAction::render(
			array(
				'label'      => __( 'Cart', 'ultra-max-core' ),
				'text'       => $settings->string( 'text' ),
				'icon'       => 'cart',
				'href'       => wc_get_cart_url(),
				'count'      => $count,
				'count_text' => Format::number( $count ),
				'count_key'  => 'cart',
				'variant'    => self::look( $settings->string( 'look' ) ),
			)
		);
	}
}
