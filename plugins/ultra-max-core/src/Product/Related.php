<?php
/**
 * Similar, complementary and recently viewed products (PDP-23).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Admin\Rest as AdminRest;
use UltraMax\Core\Lib\UI\Carousel;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Store\Cards;
use UltraMax\Core\Widgets\Store\StoreWidget;

/**
 * - Similar: the product's up-sells, then WooCommerce's related products
 *   (same categories and tags).
 * - Complementary: the product's cross-sells, which WooCommerce also offers
 *   in the cart.
 * - Recently viewed: kept in the shopper's browser; its cards come from a
 *   public endpoint that only ever returns public products.
 */
final class Related {

	public const ROUTE = '/product-cards';

	/**
	 * Registers the endpoint.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
	}

	/**
	 * Products a shopper may see, in the given order.
	 *
	 * @param array $ids Product ids.
	 * @phpstan-param list<int> $ids
	 * @return list<\WC_Product>
	 */
	public static function visible( array $ids ): array {
		$ids = array_values( array_unique( array_filter( $ids ) ) );
		if ( array() === $ids ) {
			return array();
		}
		_prime_post_caches( $ids );
		$out = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product instanceof \WC_Product && ! $product instanceof \WC_Product_Variation && 'publish' === $product->get_status() && $product->is_visible() && '' === $product->get_post_password() ) {
				$out[] = $product;
			}
		}
		return $out;
	}

	/**
	 * Similar products.
	 *
	 * @param \WC_Product $product Product.
	 * @param int         $count   Most to show.
	 * @return list<\WC_Product>
	 */
	public static function similar( \WC_Product $product, int $count ): array {
		$ids = array_map( 'intval', $product->get_upsell_ids() );
		if ( count( $ids ) < $count ) {
			$ids = array_merge( $ids, array_map( 'intval', wc_get_related_products( $product->get_id(), $count, $ids ) ) );
		}
		return array_slice( self::visible( array_values( array_diff( $ids, array( $product->get_id() ) ) ) ), 0, $count );
	}

	/**
	 * Complementary products.
	 *
	 * @param \WC_Product $product Product.
	 * @param int         $count   Most to show.
	 * @return list<\WC_Product>
	 */
	public static function complementary( \WC_Product $product, int $count ): array {
		return array_slice( self::visible( array_values( array_map( 'intval', $product->get_cross_sell_ids() ) ) ), 0, $count );
	}

	/**
	 * Cards of products in a slider.
	 *
	 * @param array  $products Products.
	 * @param string $label    Name of the list.
	 * @param string $design   Card design.
	 * @phpstan-param list<\WC_Product> $products
	 */
	public static function slider( array $products, string $label, string $design = 'hover' ): Html {
		$cards = array();
		foreach ( $products as $product ) {
			$cards[] = Cards::card( $product, $design, array( 'priority' => false ) );
		}
		if ( array() === $cards ) {
			return Html::none();
		}
		return Carousel::render(
			array_merge(
				StoreWidget::slider_labels(),
				array(
					'label'    => $label,
					'slides'   => $cards,
					'per_view' => array(
						'mobile'  => 2,
						'tablet'  => 3,
						'desktop' => 6,
					),
					'gap'      => 'sm',
					'arrows'   => 'outside',
				)
			)
		);
	}

	/**
	 * The address of the recently viewed cards.
	 */
	public static function endpoint(): string {
		return rest_url( AdminRest::NAMESPACE . self::ROUTE );
	}

	/**
	 * Registers GET /product-cards?ids=1,2,3.
	 */
	public static function routes(): void {
		register_rest_route(
			AdminRest::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'cards' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'ids' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Cards of public products, at most twelve.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function cards( \WP_REST_Request $request ): \WP_REST_Response {
		$raw = $request->get_param( 'ids' );
		$ids = array();
		foreach ( explode( ',', is_string( $raw ) ? $raw : '' ) as $id ) {
			if ( is_numeric( $id ) && (int) $id > 0 ) {
				$ids[] = (int) $id;
			}
		}
		$products = self::visible( array_slice( $ids, 0, 12 ) );
		$html     = self::slider( $products, __( 'Your recent views', 'ultra-max-core' ), 'classic' );
		$response = new \WP_REST_Response(
			array(
				'html'  => (string) $html,
				'count' => count( $products ),
			)
		);
		$response->header( 'Cache-Control', 'public, max-age=300' );
		return $response;
	}
}
