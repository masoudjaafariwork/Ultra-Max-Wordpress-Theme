<?php
/**
 * The product list module (PLP-01 to PLP-13, BLD-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Shop;

use UltraMax\Core\Elementor\Data;
use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Widgets\Widgets;

/**
 * Takes over the shop, product category, tag, brand and attribute pages
 * and the product search. Off, it registers nothing: WooCommerce's own
 * list template shows, and no file or query of this module runs.
 *
 * The list uses WooCommerce's main query and its own hooks around the
 * content, so the theme frame, notices and other plugins keep working;
 * only the inside of the list is ours.
 */
final class Shop {

	public const MODULE = 'shop-archive';

	/**
	 * Whether the module is on and WooCommerce is active.
	 */
	public static function enabled(): bool {
		return Modules::enabled( self::MODULE ) && function_exists( 'wc_get_product' );
	}

	/**
	 * Registers the module.
	 */
	public static function register(): void {
		if ( ! Modules::enabled( self::MODULE ) ) {
			return;
		}
		add_filter( 'ultra_max_layout_types', array( self::class, 'types' ) );
		Terms::register();
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		Query::register();
		Seo::register();
		add_filter( 'template_include', array( self::class, 'template' ), 50 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'assets' ), 20 );
		add_action( 'template_redirect', array( self::class, 'hooks' ) );
	}

	/**
	 * Makes the shop list and card layout types available (BLD-01, PLP-08).
	 *
	 * @param mixed $types Layout types.
	 * @return mixed
	 */
	public static function types( mixed $types ): mixed {
		if ( is_array( $types ) ) {
			foreach ( array( 'shop-archive', 'product-card' ) as $type ) {
				if ( isset( $types[ $type ] ) && is_array( $types[ $type ] ) ) {
					$types[ $type ]['ready'] = true;
				}
			}
		}
		return $types;
	}

	/**
	 * Our list template for product lists.
	 *
	 * @param mixed $template Template file.
	 * @return mixed
	 */
	public static function template( mixed $template ): mixed {
		if ( ! Scope::is_list() || is_feed() ) {
			return $template;
		}
		return plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'templates/shop-archive.php';
	}

	/**
	 * Our list prints its own breadcrumbs inside the list.
	 */
	public static function hooks(): void {
		if ( Scope::is_list() ) {
			remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		}
	}

	/**
	 * Queues the list's files in the head, and those of a designed card.
	 */
	public static function assets(): void {
		if ( ! Scope::is_list() ) {
			return;
		}
		foreach ( array( 'shop', 'product-card', 'countdown' ) as $asset ) {
			Assets::enqueue_widget( $asset );
		}
		$design = Archive::design();
		if ( str_starts_with( $design, 'layout:' ) ) {
			$layout = (int) substr( $design, 7 );
			Data::enqueue( $layout );
			Widgets::enqueue_for( $layout );
		}
	}
}
