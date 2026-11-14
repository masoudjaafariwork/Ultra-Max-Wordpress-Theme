<?php
/**
 * Wishlist and compare buttons (SAVE-01, SAVE-02, BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Saved;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\SaveButton;
use UltraMax\Core\Store\Pages;

/**
 * Puts the buttons where the other modules left room for them: the corner
 * of product cards (`ultra_max_card_actions`) and of the product page's
 * gallery (`ultra_max_product_tools`). The header and bottom bar links go
 * to the wishlist and compare pages.
 */
final class Buttons {

	/**
	 * Hooks the buttons and links.
	 */
	public static function register(): void {
		add_filter( 'ultra_max_card_actions', array( self::class, 'card' ), 10, 3 );
		add_filter( 'ultra_max_product_tools', array( self::class, 'tools' ), 10, 2 );
		add_filter( 'ultra_max_header_link', array( self::class, 'header_link' ), 10, 2 );
		add_filter( 'ultra_max_wishlist_url', array( self::class, 'wishlist_url' ) );
	}

	/**
	 * The button of one list.
	 *
	 * @param string $kind    wishlist or compare.
	 * @param int    $product Product id.
	 * @param string $look    card, tool or button.
	 */
	public static function button( string $kind, int $product, string $look = 'card' ): Html {
		$wishlist = Lists::WISHLIST === $kind;
		return SaveButton::render(
			array(
				'list'    => $wishlist ? 'wishlist' : 'compare',
				'product' => $product,
				'label'   => $wishlist ? __( 'Add to wishlist', 'ultra-max-core' ) : __( 'Compare', 'ultra-max-core' ),
				'text'    => $wishlist ? __( 'Wishlist', 'ultra-max-core' ) : __( 'Compare', 'ultra-max-core' ),
				'look'    => in_array( $look, SaveButton::LOOKS, true ) ? $look : 'card',
			)
		);
	}

	/**
	 * Buttons in a card's corner.
	 *
	 * @param mixed $actions Buttons.
	 * @param mixed $product Product.
	 * @param mixed $variant Card design.
	 * @return mixed
	 */
	public static function card( mixed $actions, mixed $product, mixed $variant = 'classic' ): mixed {
		if ( ! is_array( $actions ) || ! $product instanceof \WC_Product ) {
			return $actions;
		}
		if ( Saved::enabled( Saved::WISHLIST ) && Store::bool( 'wishlist_card' ) ) {
			$actions[] = self::button( Lists::WISHLIST, $product->get_id() );
		}
		// The small supermarket card has room for one button only.
		if ( 'mini' !== $variant && Saved::enabled( Saved::COMPARE ) && Store::bool( 'compare_card' ) ) {
			$actions[] = self::button( Lists::COMPARE, $product->get_id() );
		}
		return $actions;
	}

	/**
	 * Buttons over the product page's gallery.
	 *
	 * @param mixed $tools   Buttons.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function tools( mixed $tools, mixed $product ): mixed {
		if ( ! is_array( $tools ) || ! $product instanceof \WC_Product ) {
			return $tools;
		}
		$add = array();
		if ( Saved::enabled( Saved::WISHLIST ) && Store::bool( 'wishlist_product' ) ) {
			$add[] = self::button( Lists::WISHLIST, $product->get_id(), 'tool' );
		}
		if ( Saved::enabled( Saved::COMPARE ) && Store::bool( 'compare_product' ) ) {
			$add[] = self::button( Lists::COMPARE, $product->get_id(), 'tool' );
		}
		return array_merge( $add, $tools );
	}

	/**
	 * Where the header's wishlist and compare links go when no link was
	 * chosen in the widget.
	 *
	 * @param mixed $href Address.
	 * @param mixed $key  wishlist or compare.
	 * @return mixed
	 */
	public static function header_link( mixed $href, mixed $key ): mixed {
		if ( 'wishlist' === $key && Saved::enabled( Saved::WISHLIST ) ) {
			return Pages::url( 'wishlist' );
		}
		if ( 'compare' === $key && Saved::enabled( Saved::COMPARE ) ) {
			return Pages::url( 'compare' );
		}
		return $href;
	}

	/**
	 * The bottom bar's wishlist item (HDR-10).
	 *
	 * @param mixed $url Address.
	 * @return mixed
	 */
	public static function wishlist_url( mixed $url ): mixed {
		return Saved::enabled( Saved::WISHLIST ) ? Pages::url( 'wishlist' ) : $url;
	}
}
