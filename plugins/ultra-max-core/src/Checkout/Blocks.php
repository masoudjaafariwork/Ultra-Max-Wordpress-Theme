<?php
/**
 * Our checkout features in WooCommerce's block checkout (CHK-02, CHK-03, CHK-10).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Checkout;

use UltraMax\Core\Cart\Blocks as CartBlocks;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Lib\UI\CheckoutSection;
use UltraMax\Core\Lib\UI\Html;

/**
 * When the store keeps WooCommerce's block checkout: the saved addresses
 * show above it and fill its address, its city fields suggest the
 * province's cities, and our fields and rules reach it through
 * WooCommerce's own extension points (Fields).
 */
final class Blocks {

	/**
	 * Hooks the block.
	 */
	public static function register(): void {
		add_filter( 'render_block_woocommerce/checkout', array( self::class, 'before_checkout' ), 20, 1 );
	}

	/**
	 * Adds the saved addresses and the city data to the checkout block.
	 *
	 * @param mixed $content Block markup.
	 * @return mixed
	 */
	public static function before_checkout( mixed $content ): mixed {
		if ( ! is_string( $content ) || Checkout::ours() ) {
			return $content;
		}
		CartBlocks::enqueue();
		if ( Store::bool( 'checkout_city_list' ) ) {
			wp_add_inline_script( CartBlocks::SCRIPT, 'window.ultraMaxBlocks=' . wp_json_encode( array( 'cities' => Cities::all() ), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . ';', 'before' );
		}
		$cards = AddressBook::cards( AddressBook::current(), false );
		if ( $cards->is_empty() ) {
			return $content;
		}
		Assets::enqueue_widget( 'checkout' );
		$book = CheckoutSection::render(
			array(
				'title'    => __( 'Your saved addresses', 'ultra-max-core' ),
				'children' => $cards,
				'class'    => 'mb-4',
			)
		);
		return Html::trusted( sprintf( '<div class="um-blocks-addresses" data-um-blocks-addresses>%s</div>', $book ) ) . $content;
	}
}
