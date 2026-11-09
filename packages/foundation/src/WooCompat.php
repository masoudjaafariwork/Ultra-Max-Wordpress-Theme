<?php
/**
 * WooCommerce feature compatibility declarations.
 *
 * @package UltraMax\Foundation
 */

declare(strict_types=1);

namespace UltraMax\Foundation;

/**
 * Declares that a plugin works with HPOS and with block cart and checkout.
 */
final class WooCompat {

	/**
	 * Registers the declarations for one plugin.
	 *
	 * @param string $plugin_file Absolute path of the plugin's main file.
	 */
	public static function declare( string $plugin_file ): void {
		add_action(
			'before_woocommerce_init',
			static function () use ( $plugin_file ): void {
				$features = '\Automattic\WooCommerce\Utilities\FeaturesUtil';
				if ( ! class_exists( $features ) ) {
					return;
				}
				$features::declare_compatibility( 'custom_order_tables', $plugin_file, true );
				$features::declare_compatibility( 'cart_checkout_blocks', $plugin_file, true );
			}
		);
	}
}
