<?php
/**
 * The lines of the cart as component props (CART-01, CART-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Cart;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\CartLine;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Delivery;
use UltraMax\Core\Product\Meta;
use UltraMax\Core\Product\Quantity;
use UltraMax\Core\Shop\Filters;
use UltraMax\Core\Store\Cards;
use UltraMax\Core\Store\Format;

/**
 * One renderer for a cart line, shared by the cart page, its widgets, the
 * side cart and the checkout's list of items.
 *
 * @phpstan-import-type CartLineProps from CartLine
 * @phpstan-import-type CartLineFact from CartLine
 */
final class Lines {

	/**
	 * Texts of the stepper and the buttons.
	 *
	 * @return array{quantity: string, increase: string, decrease: string, remove: string, later: string}
	 */
	public static function labels(): array {
		return array(
			'quantity' => __( 'Quantity', 'ultra-max-core' ),
			'increase' => __( 'One more', 'ultra-max-core' ),
			'decrease' => __( 'One less', 'ultra-max-core' ),
			'remove'   => __( 'Remove from cart', 'ultra-max-core' ),
			'later'    => __( 'Move to buy later', 'ultra-max-core' ),
		);
	}

	/**
	 * The address of a no-JavaScript action on a line or a saved product.
	 *
	 * @param string $action later, restore, forget or restore-all.
	 * @param string $key    Line or entry key.
	 */
	public static function action_url( string $action, string $key = '' ): string {
		return add_query_arg(
			array_filter(
				array(
					Actions::QUERY => $action,
					'key'          => $key,
					'_wpnonce'     => wp_create_nonce( Actions::NONCE ),
				)
			),
			wc_get_cart_url()
		);
	}

	/**
	 * Every line of the cart.
	 *
	 * @param bool $compact Side cart look.
	 * @return list<Html>
	 */
	public static function all( bool $compact = false ): array {
		$cart = WC()->cart;
		$out  = array();
		foreach ( $cart->get_cart() as $key => $item ) {
			$props = is_array( $item ) ? self::props( (string) $key, $item, $compact ) : null;
			if ( null !== $props ) {
				$out[] = CartLine::render( $props );
			}
		}
		return $out;
	}

	/**
	 * The props of one line, or null when its product is gone.
	 *
	 * @param string       $key     Cart line key.
	 * @param array<mixed> $item    Cart item.
	 * @param bool         $compact Side cart look.
	 * @return CartLineProps|null
	 */
	public static function props( string $key, array $item, bool $compact = false ): ?array {
		$product = $item['data'] ?? null;
		/** This filter is documented in WooCommerce's cart/cart.php template. */
		$visible = apply_filters( 'woocommerce_cart_item_visible', true, $item, $key ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
		if ( ! $product instanceof \WC_Product || ! $product->exists() || ! $visible ) {
			return null;
		}
		$quantity = is_numeric( $item['quantity'] ?? null ) ? (int) $item['quantity'] : 1;
		$tax      = WC()->cart->display_prices_including_tax();
		$total    = Totals::current( $product, $quantity, $tax );
		$regular  = Totals::regular( $product, $quantity, $tax );
		$props    = array(
			'key'    => $key,
			'title'  => wp_strip_all_tags( $product->get_title() ),
			'image'  => Html::wp( $product->get_image( 'woocommerce_thumbnail', array( 'alt' => '' ) ) ),
			'total'  => Totals::money_html( $total ),
			'labels' => self::labels(),
			'remove' => wc_get_cart_remove_url( $key ),
			'facts'  => self::facts( $product, $item ),
		);
		if ( $product->is_visible() ) {
			$props['href'] = $product instanceof \WC_Product_Variation ? $product->get_permalink( $item ) : $product->get_permalink();
		}
		if ( $regular > $total ) {
			/* translators: %s: amount of money. */
			$props['saving'] = sprintf( __( '%s off', 'ultra-max-core' ), Totals::money( $regular - $total ) );
		}
		if ( ! $product->is_sold_individually() ) {
			$limits            = Quantity::limits( $product );
			$props['quantity'] = array(
				'value' => $quantity,
				'min'   => $limits['min'],
				'max'   => $limits['max'],
				'step'  => $limits['step'],
			);
		}
		if ( Later::enabled() && ! $compact ) {
			$props['later'] = self::action_url( 'later', $key );
		}
		$notice = self::notice( $product, $quantity );
		if ( '' !== $notice ) {
			$props['notice'] = $notice;
		}
		if ( $compact ) {
			$props['compact'] = true;
		}
		return $props;
	}

	/**
	 * Why a line cannot be bought as it is, or an empty text.
	 *
	 * @param \WC_Product $product  Product.
	 * @param int         $quantity Quantity.
	 */
	public static function notice( \WC_Product $product, int $quantity ): string {
		if ( ! $product->is_in_stock() ) {
			return __( 'Out of stock. Remove it to go on.', 'ultra-max-core' );
		}
		if ( ! $product->has_enough_stock( $quantity ) ) {
			/* translators: %s: number. */
			return sprintf( __( 'Only %s left.', 'ultra-max-core' ), Format::number( (int) $product->get_stock_quantity() ) );
		}
		return Quantity::problem( $product, $quantity );
	}

	/**
	 * Facts under the name: the chosen options, warranty, seller, when it ships.
	 *
	 * @param \WC_Product          $product Product.
	 * @param array<string, mixed> $item    Cart item.
	 * @return list<CartLineFact>
	 */
	public static function facts( \WC_Product $product, array $item ): array {
		$facts  = array();
		$colors = Cards::color_taxonomies();
		foreach ( is_array( $item['variation'] ?? null ) ? $item['variation'] : array() as $name => $value ) {
			$taxonomy = str_replace( 'attribute_', '', (string) $name );
			$value    = is_scalar( $value ) ? (string) $value : '';
			if ( '' === $value ) {
				continue;
			}
			$label = wc_attribute_label( $taxonomy, $product );
			$text  = $value;
			$color = '';
			if ( taxonomy_exists( $taxonomy ) ) {
				$term = get_term_by( 'slug', $value, $taxonomy );
				if ( $term instanceof \WP_Term ) {
					$text  = $term->name;
					$meta  = in_array( $taxonomy, $colors, true ) ? get_term_meta( $term->term_id, Filters::COLOR_META, true ) : '';
					$color = is_string( $meta ) ? $meta : '';
				}
			}
			$fact = array( 'text' => '' !== $color ? $text : $label . ': ' . $text );
			if ( '' !== $color ) {
				$fact['color'] = $color;
			}
			$facts[] = $fact;
		}
		$owner    = Meta::owner( $product );
		$warranty = Meta::text( $owner, Meta::WARRANTY );
		$warranty = '' === $warranty ? Store::string( 'product_warranty' ) : $warranty;
		if ( '' !== $warranty ) {
			$facts[] = array(
				'icon' => 'shield',
				'text' => $warranty,
			);
		}
		if ( Store::bool( 'product_seller' ) ) {
			$facts[] = array(
				'icon' => 'store',
				'text' => wp_strip_all_tags( get_bloginfo( 'name' ) ),
			);
		}
		if ( $product->needs_shipping() ) {
			$days    = Delivery::preparation( $product );
			$facts[] = array(
				'icon' => 'truck',
				'tone' => 'primary',
				'text' => 0 === $days
					? __( 'Ships today', 'ultra-max-core' )
					/* translators: %s: number of working days. */
					: sprintf( _n( 'Ships in %s working day', 'Ships in %s working days', $days, 'ultra-max-core' ), Format::number( $days ) ),
			);
		}
		// Data other plugins add to the line, such as engraving text.
		$extra = wc_get_formatted_cart_item_data( $item, true );
		$split = preg_split( '/\R/', trim( wp_strip_all_tags( $extra ) ) );
		foreach ( false === $split ? array() : $split as $line ) {
			$line = trim( $line );
			if ( '' !== $line && ! self::is_attribute_line( $line, $facts ) ) {
				$facts[] = array( 'text' => $line );
			}
		}
		return $facts;
	}

	/**
	 * Whether a line of WooCommerce's item data repeats an option already shown.
	 *
	 * @param string $line  Line, such as "Colour: Black".
	 * @param array  $facts Facts so far.
	 * @phpstan-param list<CartLineFact> $facts
	 */
	private static function is_attribute_line( string $line, array $facts ): bool {
		$value = trim( (string) substr( $line, (int) strpos( $line, ':' ) + 1 ) );
		foreach ( $facts as $fact ) {
			if ( str_ends_with( $fact['text'], $value ) ) {
				return true;
			}
		}
		return false;
	}
}
