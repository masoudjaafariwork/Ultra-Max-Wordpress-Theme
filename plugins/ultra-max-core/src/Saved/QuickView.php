<?php
/**
 * Quick view and buying from a list (SAVE-04, SAVE-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Saved;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\QuickView as Window;
use UltraMax\Core\Lib\UI\SaveButton;
use UltraMax\Core\Product\Cart;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Store\CurrentProduct;

/**
 * An eye button on cards opens the product in a window; "Choose options"
 * of a product with variations opens the same window, so a shopper picks a
 * colour and a size and adds it without leaving the list. The window's
 * pieces are the product page's own (Product\Page), and adding goes through
 * the product page's endpoint with WooCommerce's checks.
 *
 * `?wc-ajax=um_quick_view&id=<id>` answers the window's content for public
 * products only.
 */
final class QuickView {

	public const ACTION = 'um_quick_view';

	/**
	 * Whether a card asked for the window on this page.
	 *
	 * @var bool
	 */
	private static bool $used = false;

	/**
	 * Hooks the buttons, the endpoint and the window.
	 */
	public static function register(): void {
		add_action( 'wc_ajax_' . self::ACTION, array( self::class, 'handle' ) );
		add_filter( 'ultra_max_card_actions', array( self::class, 'card' ), 20, 3 );
		add_filter( 'ultra_max_card_quick_buy', array( self::class, 'quick_buy' ) );
		add_action( 'wp_footer', array( self::class, 'shell' ), 6 );
		// Adding from the window uses the product page's endpoint, also when that module is off.
		if ( ! has_action( 'wc_ajax_' . Cart::ACTION ) ) {
			Cart::register();
		}
	}

	/**
	 * The eye button in a card's corner.
	 *
	 * @param mixed $actions Buttons.
	 * @param mixed $product Product.
	 * @param mixed $variant Card design.
	 * @return mixed
	 */
	public static function card( mixed $actions, mixed $product, mixed $variant = 'classic' ): mixed {
		if ( ! is_array( $actions ) || ! $product instanceof \WC_Product || 'mini' === $variant || ! Store::bool( 'quick_card' ) ) {
			return $actions;
		}
		self::$used = true;
		$actions[]  = SaveButton::render(
			array(
				'list'    => 'quick',
				'product' => $product->get_id(),
				'label'   => __( 'Quick view', 'ultra-max-core' ),
				'look'    => 'card',
			)
		);
		return $actions;
	}

	/**
	 * "Choose options" opens the window (SAVE-05).
	 *
	 * @param mixed $quick Whether to open it.
	 * @return mixed
	 */
	public static function quick_buy( mixed $quick ): mixed {
		if ( Store::bool( 'quick_choose' ) ) {
			self::$used = true;
			return true;
		}
		return $quick;
	}

	/**
	 * Addresses of a registered widget asset's files, as WordPress prints
	 * them, so a page that already has them does not load them again.
	 *
	 * @param string $asset Widget asset.
	 * @return array{css: list<string>, js: list<string>}
	 */
	public static function files( string $asset ): array {
		$handle = Assets::widget_handle( $asset );
		$out    = array(
			'css' => array(),
			'js'  => array(),
		);
		foreach ( array(
			'css' => wp_styles(),
			'js'  => wp_scripts(),
		) as $kind => $registry ) {
			$item = $registry->registered[ $handle ] ?? null;
			if ( $item instanceof \_WP_Dependency && is_string( $item->src ) && '' !== $item->src ) {
				$out[ $kind ][] = is_string( $item->ver ) && '' !== $item->ver ? add_query_arg( 'ver', $item->ver, $item->src ) : $item->src;
			}
		}
		return $out;
	}

	/**
	 * Prints the empty window on pages that show cards.
	 */
	public static function shell(): void {
		if ( ! self::$used && ! wp_script_is( Assets::widget_handle( 'product-card' ), 'enqueued' ) ) {
			return;
		}
		$files = array(
			'css' => array(),
			'js'  => array(),
		);
		foreach ( array( 'product', 'countdown' ) as $asset ) {
			$found        = self::files( $asset );
			$files['css'] = array_merge( $files['css'], $found['css'] );
			$files['js']  = array_merge( $files['js'], $found['js'] );
		}
		Window::shell(
			array(
				'title'    => __( 'Quick view', 'ultra-max-core' ),
				'endpoint' => \WC_AJAX::get_endpoint( self::ACTION ),
				'loading'  => __( 'Loading…', 'ultra-max-core' ),
				'error'    => __( 'This product cannot be shown now. Open its page instead.', 'ultra-max-core' ),
				'assets'   => $files,
			)
		)->print();
	}

	/**
	 * The window's content for one product.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function content( \WC_Product $product ): Html {
		CurrentProduct::push( $product );
		$html = Window::content(
			array(
				'gallery' => Page::gallery( 'slider', false ),
				'heading' => Page::heading( 'h2' ),
				'price'   => Page::price( 'md' ),
				'picker'  => Page::picker(),
				'form'    => Page::form( false ),
				'more'    => array(
					'label' => __( 'See all details', 'ultra-max-core' ),
					'href'  => (string) $product->get_permalink(),
				),
			)
		);
		CurrentProduct::pop();
		return $html;
	}

	/**
	 * Handles the request and answers with JSON.
	 */
	public static function handle(): void {
		nocache_headers();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only, public products only.
		$id      = isset( $_GET['id'] ) && is_string( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$product = Endpoint::allowed( $id );
		if ( null === $product ) {
			wp_send_json( array( 'html' => '' ), 404 );
		}
		wp_send_json(
			array(
				'html'  => (string) self::content( $product ),
				'title' => wp_strip_all_tags( $product->get_name() ),
			)
		);
	}
}
