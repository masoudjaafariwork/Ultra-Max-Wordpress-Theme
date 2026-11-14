<?php
/**
 * Our cart features in WooCommerce's block cart and checkout (CART-02 to
 * CART-05, CART-10, CHK-10).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Cart;

use UltraMax\Core\Lib\Foundation\Assets as Registrar;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Lib\UI\CartSummary;
use UltraMax\Core\Lib\UI\Html;

/**
 * When the store keeps WooCommerce's own cart and checkout blocks:
 *
 * - the Store API cart carries `extensions.ultra-max.html`: the saving,
 *   the free shipping bar and the order limit, drawn by our components;
 *   a small script shows it in the blocks' order summary;
 * - the cart block is followed by the "buy later" list and the
 *   suggestions, drawn by the same renderer as our own cart page;
 * - the order limit stops the checkout through the Store API's cart
 *   errors (Limits).
 */
final class Blocks {

	public const SCRIPT    = 'ultra-max-wc-blocks';
	public const NAMESPACE = 'ultra-max';

	/**
	 * Hooks the Store API data and the block additions.
	 */
	public static function register(): void {
		add_action( 'woocommerce_blocks_loaded', array( self::class, 'store_api' ) );
		add_filter( 'render_block_woocommerce/cart', array( self::class, 'after_cart' ), 10, 1 );
		add_filter( 'render_block_woocommerce/checkout', array( self::class, 'script_for' ), 10, 1 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'early' ), 20 );
	}

	/**
	 * Queues the files in the head when the cart or checkout page is
	 * WooCommerce's own, so nothing shows unstyled.
	 */
	public static function early(): void {
		if ( ! Cart::ours() && ( is_cart() || is_checkout() ) ) {
			self::enqueue();
			Assets::enqueue_widget( 'product-card' );
			Assets::enqueue_widget( 'carousel' );
		}
	}

	/**
	 * Registers our data on the Store API cart.
	 */
	public static function store_api(): void {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => 'cart',
				'namespace'       => self::NAMESPACE,
				'data_callback'   => array( self::class, 'data' ),
				'schema_callback' => array( self::class, 'schema' ),
				'schema_type'     => ARRAY_A,
			)
		);
	}

	/**
	 * The data of the cart.
	 *
	 * @return array{html: string}
	 */
	public static function data(): array {
		return array( 'html' => (string) self::extras() );
	}

	/**
	 * The schema of the data.
	 *
	 * @return array<string, mixed>
	 */
	public static function schema(): array {
		return array(
			'html' => array(
				'description' => __( 'The saving, free shipping progress and order limit of the cart, as markup.', 'ultra-max-core' ),
				'type'        => 'string',
				'readonly'    => true,
			),
		);
	}

	/**
	 * The saving, the free shipping bar and the order limit.
	 */
	public static function extras(): Html {
		$cart = function_exists( 'WC' ) ? WC()->cart : null;
		if ( ! $cart instanceof \WC_Cart || $cart->is_empty() ) {
			return Html::none();
		}
		$amounts = Totals::of( $cart );
		$rows    = array();
		if ( Store::bool( 'cart_savings' ) && $amounts['saving'] > 0 ) {
			$rows[] = array(
				'label' => __( 'Your saving', 'ultra-max-core' ),
				'value' => Totals::saving_text( $amounts ),
				'tone'  => 'save',
			);
		}
		$extras = array( Totals::free_bar( $cart ), Page::limit_alert() );
		if ( array() === $rows && '' === (string) Html::from( $extras ) ) {
			return Html::none();
		}
		return CartSummary::render(
			array(
				'rows'   => $rows,
				'extras' => $extras,
				'sticky' => false,
				'class'  => 'um-block-summary',
			)
		);
	}

	/**
	 * Registers and queues the blocks script and the cart styles.
	 */
	public static function enqueue(): void {
		if ( ! wp_script_is( self::SCRIPT, 'registered' ) ) {
			$registrar = new Registrar( plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'assets/build', plugins_url( 'assets/build', ULTRA_MAX_CORE_FILE ) );
			$registrar->register_script( self::SCRIPT, 'core/wc-blocks', array( 'wp-plugins', 'wp-element', 'wp-data', 'wc-blocks-checkout' ) );
		}
		wp_enqueue_script( self::SCRIPT );
		Assets::enqueue_widget( 'cart' );
	}

	/**
	 * Adds the script to a page with the checkout block.
	 *
	 * @param mixed $content Block markup.
	 * @return mixed
	 */
	public static function script_for( mixed $content ): mixed {
		self::enqueue();
		return $content;
	}

	/**
	 * Adds the "buy later" list and the suggestions after the cart block.
	 *
	 * @param mixed $content Block markup.
	 * @return mixed
	 */
	public static function after_cart( mixed $content ): mixed {
		if ( ! is_string( $content ) || Cart::ours() || ! function_exists( 'WC' ) || null === WC()->cart ) {
			return $content;
		}
		self::enqueue();
		Assets::enqueue_widget( 'product-card' );
		Assets::enqueue_widget( 'carousel' );
		$parts = array( Page::part( 'later' ), Page::part( 'suggestions' ) );
		return $content . Page::root( Html::from( $parts ), 'blocks' );
	}
}
