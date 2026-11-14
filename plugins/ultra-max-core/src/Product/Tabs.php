<?php
/**
 * The store's and each product's own tabs (PDP-14).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Lib\Settings\Store;

/**
 * Tabs are added through WooCommerce's own product tabs filter, so they
 * show on our page as sections and on WooCommerce's page as tabs alike.
 *
 * - Store tabs (settings): for every product, or only for products of the
 *   categories named in the tab.
 * - Product tabs: any number on the product's edit screen.
 *
 * Content may hold basic HTML and shortcodes, such as a reusable block
 * (`[ultra_max_layout id="12"]`).
 */
final class Tabs {

	/**
	 * Hooks the tabs and their fields.
	 */
	public static function register(): void {
		add_filter( 'woocommerce_product_tabs', array( self::class, 'tabs' ), 50 );
		add_action( 'ultra_max_product_fields', array( self::class, 'fields' ), 20 );
		add_action( 'ultra_max_save_product_fields', array( self::class, 'save' ) );
	}

	/**
	 * Whether a product is in one of the named categories or their children.
	 *
	 * @param int    $product_id Product id.
	 * @param string $slugs      Category slugs separated by commas; empty for all.
	 */
	private static function in_categories( int $product_id, string $slugs ): bool {
		$wanted = array_values( array_filter( array_map( 'sanitize_title', explode( ',', $slugs ) ) ) );
		if ( array() === $wanted ) {
			return true;
		}
		$terms = get_the_terms( $product_id, 'product_cat' );
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			foreach ( array_merge( array( $term->term_id ), get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) ) as $id ) {
				$found = get_term( (int) $id, 'product_cat' );
				if ( $found instanceof \WP_Term && in_array( $found->slug, $wanted, true ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * The tabs of a product: the store's first, then its own.
	 *
	 * @param \WC_Product $product Product.
	 * @return list<array{title: string, content: string}>
	 */
	public static function for( \WC_Product $product ): array {
		$out = array();
		foreach ( Store::rows( 'product_tabs' ) as $row ) {
			$title   = is_string( $row['title'] ?? null ) ? trim( $row['title'] ) : '';
			$content = is_string( $row['content'] ?? null ) ? trim( $row['content'] ) : '';
			if ( '' !== $title && '' !== $content && self::in_categories( $product->get_id(), is_string( $row['categories'] ?? null ) ? $row['categories'] : '' ) ) {
				$out[] = array(
					'title'   => $title,
					'content' => $content,
				);
			}
		}
		return array_merge( $out, Meta::rows( $product, Meta::TABS ) );
	}

	/**
	 * Adds the tabs to WooCommerce's list.
	 *
	 * @param mixed $tabs Tabs.
	 * @return mixed
	 */
	public static function tabs( mixed $tabs ): mixed {
		global $product;
		if ( ! is_array( $tabs ) || ! $product instanceof \WC_Product ) {
			return $tabs;
		}
		foreach ( self::for( $product ) as $index => $tab ) {
			$content                    = $tab['content'];
			$tabs[ 'um_tab_' . $index ] = array(
				'title'    => $tab['title'],
				'priority' => 40 + $index,
				'callback' => static function () use ( $content ): void {
					echo wp_kses_post( wpautop( do_shortcode( wp_kses_post( $content ) ) ) );
				},
			);
		}
		return $tabs;
	}

	/**
	 * The product's own tabs on its edit screen.
	 *
	 * @param mixed $product Product.
	 */
	public static function fields( mixed $product ): void {
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$row  = static fn ( string $index, string $title, string $content ): string => sprintf(
			'<div data-um-row style="border:1px solid #dcdcde;border-radius:4px;padding:8px;margin:0 0 8px"><input type="text" class="widefat" name="um_tabs[%1$s][title]" value="%2$s" placeholder="%3$s" style="margin-bottom:6px"><textarea class="widefat" rows="4" name="um_tabs[%1$s][content]" placeholder="%4$s">%5$s</textarea><button type="button" class="button-link button-link-delete" data-um-row-remove>%6$s</button></div>',
			esc_attr( $index ),
			esc_attr( $title ),
			esc_attr__( 'Tab title', 'ultra-max-core' ),
			esc_attr__( 'Content. Basic HTML and shortcodes work.', 'ultra-max-core' ),
			esc_textarea( $content ),
			esc_html__( 'Remove', 'ultra-max-core' )
		);
		$rows = '';
		foreach ( Meta::rows( $product, Meta::TABS ) as $index => $tab ) {
			$rows .= $row( (string) $index, $tab['title'], $tab['content'] );
		}
		$count = count( Meta::rows( $product, Meta::TABS ) );
		printf(
			'<div class="options_group"><div class="form-field" style="padding:9px 12px"><p><strong>%1$s</strong><br><span class="description">%2$s</span></p><div id="um-product-tabs" data-next="%3$d">%4$s<template data-um-row-template>%5$s</template></div><button type="button" class="button" data-um-row-add="um-product-tabs">%6$s</button></div></div>',
			esc_html__( 'Tabs of this product', 'ultra-max-core' ),
			esc_html__( 'They show after the specifications, with the store\'s own tabs from the settings.', 'ultra-max-core' ),
			(int) $count,
			$rows, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in $row.
			$row( '__i__', '', '' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in $row.
			esc_html__( 'Add a tab', 'ultra-max-core' )
		);
	}

	/**
	 * Saves the product's own tabs.
	 *
	 * @param mixed $product Product.
	 */
	public static function save( mixed $product ): void {
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- WooCommerce checked its nonce; each value is sanitized below.
		$posted = isset( $_POST['um_tabs'] ) && is_array( $_POST['um_tabs'] ) ? wp_unslash( $_POST['um_tabs'] ) : array();
		$rows   = array();
		foreach ( is_array( $posted ) ? $posted : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$title   = is_string( $row['title'] ?? null ) ? sanitize_text_field( $row['title'] ) : '';
			$content = is_string( $row['content'] ?? null ) ? wp_kses_post( $row['content'] ) : '';
			if ( '' !== $title && '' !== trim( $content ) ) {
				$rows[] = array(
					'title'   => $title,
					'content' => $content,
				);
			}
		}
		$product->update_meta_data( Meta::TABS, array_slice( $rows, 0, 20 ) );
	}
}
