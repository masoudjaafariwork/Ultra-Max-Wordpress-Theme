<?php
/**
 * The wishlist page (SAVE-01, ACC-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Saved;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Alert;
use UltraMax\Core\Lib\UI\EmptyState;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\ProductGrid;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Product\Related;
use UltraMax\Core\Store\Cards;
use UltraMax\Core\Store\Format;

/**
 * One renderer for the wishlist: the wishlist page's shortcode, its
 * Elementor widget and the account's "My lists" all draw it. A heart on a
 * card of the list takes the product out.
 */
final class Wishlist {

	public const SHORTCODE = 'um_wishlist_page';

	/**
	 * Hooks the shortcode.
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'shortcode' ) );
	}

	/**
	 * The shortcode.
	 */
	public static function shortcode(): string {
		return (string) self::page();
	}

	/**
	 * The public products of the shopper's wishlist.
	 *
	 * @return list<\WC_Product>
	 */
	public static function products(): array {
		return Related::visible( Lists::ids( Lists::WISHLIST ) );
	}

	/**
	 * The card design of lists: the shop's, or the classic card.
	 */
	private static function design(): string {
		$design = Store::string( 'shop_card' );
		return '' === $design ? 'classic' : $design;
	}

	/**
	 * The list, or a message when it is empty.
	 *
	 * @param int $columns Cards in a row on desktop.
	 */
	public static function page( int $columns = 5 ): Html {
		if ( ! Saved::enabled( Saved::WISHLIST ) ) {
			return Html::none();
		}
		$products = self::products();
		$login    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
		if ( array() === $products ) {
			$actions = array(
				UI::button(
					array(
						'href'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
						'children' => __( 'Go to the shop', 'ultra-max-core' ),
					)
				),
			);
			if ( ! is_user_logged_in() ) {
				$actions[] = UI::button(
					array(
						'href'     => $login,
						'variant'  => 'outline',
						'children' => __( 'Log in to see your saved list', 'ultra-max-core' ),
					)
				);
			}
			return EmptyState::render(
				array(
					'icon'     => 'heart',
					'title'    => __( 'Your wishlist is empty', 'ultra-max-core' ),
					'text'     => __( 'Press the heart on any product to keep it here for later.', 'ultra-max-core' ),
					'children' => $actions,
				)
			);
		}
		$design = self::design();
		$items  = array();
		foreach ( $products as $product ) {
			$items[] = array( 'card' => Cards::card( $product, $design, array( 'priority' => false ) ) );
		}
		$parts = array();
		/* translators: %s: number of products. */
		$parts[] = Html::trusted( sprintf( '<p class="m-0">%s</p>', esc_html( sprintf( _n( '%s product in your wishlist', '%s products in your wishlist', count( $products ), 'ultra-max-core' ), Format::number( count( $products ) ) ) ) ) );
		if ( ! is_user_logged_in() ) {
			$parts[] = Alert::render(
				array(
					'tone'     => 'info',
					'role'     => 'none',
					'children' => Html::trusted(
						sprintf(
							'<span>%s</span>',
							sprintf(
								/* translators: %s: link to log in. */
								esc_html__( 'This list is kept in this browser only. %s to keep it on every device.', 'ultra-max-core' ),
								sprintf( '<a href="%1$s">%2$s</a>', esc_url( $login ), esc_html__( 'Log in', 'ultra-max-core' ) )
							)
						)
					),
				)
			);
		}
		$parts[] = ProductGrid::render(
			array(
				'label'   => __( 'Wishlist', 'ultra-max-core' ),
				'items'   => $items,
				'columns' => array(
					'mobile'  => 2,
					'tablet'  => 3,
					'desktop' => max( 2, min( 6, $columns ) ),
				),
				'lined'   => 'classic' === $design,
				'mobile'  => 'cards',
			)
		);
		return Html::trusted( sprintf( '<div class="um-wishlist grid min-w-0 gap-4" data-um-saved-list="wishlist">%s</div>', Html::from( $parts ) ) );
	}
}
