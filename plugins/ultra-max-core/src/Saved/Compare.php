<?php
/**
 * The compare page (SAVE-02, SAVE-03).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Saved;

use UltraMax\Core\Lib\UI\CompareTable;
use UltraMax\Core\Lib\UI\EmptyState;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\ProductCard;
use UltraMax\Core\Lib\UI\SaveButton;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Product\Related;
use UltraMax\Core\Product\Specs;
use UltraMax\Core\Store\Cards;
use UltraMax\Core\Store\Format;

/**
 * One renderer for the comparison: the compare page's shortcode and its
 * Elementor widget. Rows come from the products' specifications (PDP-06),
 * in their groups, after brand, rating and availability.
 */
final class Compare {

	public const SHORTCODE = 'um_compare_page';

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
	 * The public products being compared.
	 *
	 * @return list<\WC_Product>
	 */
	public static function products(): array {
		return Related::visible( array_reverse( Lists::ids( Lists::COMPARE ) ) );
	}

	/**
	 * The availability of a product as text.
	 *
	 * @param \WC_Product $product Product.
	 */
	private static function stock( \WC_Product $product ): string {
		if ( ! $product->is_in_stock() ) {
			return __( 'Out of stock', 'ultra-max-core' );
		}
		$left = $product->get_stock_quantity();
		if ( null !== $left && $left > 0 && $left <= 3 ) {
			/* translators: %s: number of items left. */
			return sprintf( __( 'Only %s left in stock', 'ultra-max-core' ), Format::number( $left ) );
		}
		return __( 'In stock', 'ultra-max-core' );
	}

	/**
	 * The rating of a product as text.
	 *
	 * @param \WC_Product $product Product.
	 */
	private static function rating( \WC_Product $product ): string {
		$rating = (float) $product->get_average_rating();
		if ( $rating <= 0 ) {
			return '';
		}
		/* translators: 1: rating such as 4.5, 2: number of reviews. */
		return sprintf( __( '%1$s out of 5 (%2$s reviews)', 'ultra-max-core' ), Format::digits( number_format( $rating, 1, Format::persian() ? '٫' : '.', '' ) ), Format::number( $product->get_review_count() ) );
	}

	/**
	 * The rows of the table: one per label, in the order they first appear.
	 *
	 * @param array $products Products.
	 * @phpstan-param list<\WC_Product> $products
	 * @return list<array{label: string, values: list<string>, group?: string}>
	 */
	public static function rows( array $products ): array {
		$count = count( $products );
		$rows  = array(
			'brand'  => array(
				'label'  => __( 'Brand', 'ultra-max-core' ),
				'values' => array_map( array( Cards::class, 'brand' ), $products ),
			),
			'rating' => array(
				'label'  => __( 'Shoppers\' rating', 'ultra-max-core' ),
				'values' => array_map( array( self::class, 'rating' ), $products ),
			),
			'stock'  => array(
				'label'  => __( 'Availability', 'ultra-max-core' ),
				'values' => array_map( array( self::class, 'stock' ), $products ),
			),
		);
		foreach ( $products as $index => $product ) {
			foreach ( Specs::groups( $product ) as $group ) {
				foreach ( $group['rows'] as $row ) {
					$key = $group['title'] . "\0" . $row['label'];
					if ( ! isset( $rows[ $key ] ) ) {
						$rows[ $key ] = array(
							'label'  => $row['label'],
							'values' => array_fill( 0, $count, '' ),
							'group'  => $group['title'],
						);
					}
					$rows[ $key ]['values'][ $index ] = $row['value'];
				}
			}
		}
		$out = array();
		foreach ( $rows as $row ) {
			if ( '' === implode( '', $row['values'] ) ) {
				continue;
			}
			$row['values'] = array_values( $row['values'] );
			$out[]         = $row;
		}
		return $out;
	}

	/**
	 * Where a shopper finds more products like those compared: the first
	 * product's category, or the shop.
	 *
	 * @param array $products Products.
	 * @phpstan-param list<\WC_Product> $products
	 */
	private static function more( array $products ): string {
		$ids  = array() === $products ? array() : wc_get_product_term_ids( $products[0]->get_id(), 'product_cat' );
		$link = array() === $ids ? '' : get_term_link( (int) end( $ids ), 'product_cat' );
		if ( is_string( $link ) ) {
			return $link;
		}
		return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	}

	/**
	 * The table, or a message when nothing is compared.
	 */
	public static function page(): Html {
		if ( ! Saved::enabled( Saved::COMPARE ) ) {
			return Html::none();
		}
		$products = self::products();
		if ( array() === $products ) {
			return EmptyState::render(
				array(
					'icon'     => 'compare',
					'title'    => __( 'Nothing to compare yet', 'ultra-max-core' ),
					'text'     => __( 'Press the compare button on up to four products of one kind to see them side by side.', 'ultra-max-core' ),
					'children' => UI::button(
						array(
							'href'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
							'children' => __( 'Go to the shop', 'ultra-max-core' ),
						)
					),
				)
			);
		}
		$columns = array();
		foreach ( $products as $product ) {
			$card    = Cards::props( $product, 'classic', array( 'priority' => false ) );
			$actions = array();
			if ( isset( $card['add'] ) ) {
				$actions[] = UI::button(
					array(
						'href'     => $card['add']['href'],
						'size'     => 'sm',
						'children' => $card['add']['label'],
						'attrs'    => $card['add']['ajax'] ? array( 'data-um-add' => (string) $card['add']['id'] ) : array(),
					)
				);
			}
			if ( Saved::enabled( Saved::WISHLIST ) ) {
				$actions[] = Buttons::button( Lists::WISHLIST, $product->get_id(), 'button' );
			}
			$columns[] = array(
				'id'      => $product->get_id(),
				'title'   => $card['title'],
				'href'    => $card['href'],
				'image'   => $card['image'],
				'price'   => Html::trusted( ProductCard::price( $card ) ),
				'actions' => $actions,
				'remove'  => SaveButton::render(
					array(
						'list'    => 'compare',
						'product' => $product->get_id(),
						/* translators: %s: product name. */
						'label'   => sprintf( __( 'Remove %s from the comparison', 'ultra-max-core' ), $card['title'] ),
						'look'    => 'card',
						'icon'    => 'close',
					)
				),
			);
		}
		$props = array(
			'caption'     => __( 'Products side by side', 'ultra-max-core' ),
			'products'    => $columns,
			'rows'        => self::rows( $products ),
			'differences' => __( 'Only show differences', 'ultra-max-core' ),
		);
		if ( count( $products ) < Lists::LIMITS[ Lists::COMPARE ] ) {
			$props['add'] = array(
				'label' => __( 'Add a product', 'ultra-max-core' ),
				'href'  => self::more( $products ),
			);
		}
		return Html::trusted( sprintf( '<div class="um-compare-page" data-um-component="product-card">%s</div>', CompareTable::render( $props ) ) );
	}
}
