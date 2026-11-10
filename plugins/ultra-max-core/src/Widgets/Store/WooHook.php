<?php
/**
 * WooCommerce hook widget (BLD-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * Runs a WooCommerce action where the widget sits, so plugins that print
 * through WooCommerce's hooks (badges, payment notes, size guides) still
 * show inside an Elementor layout.
 */
final class WooHook extends StoreWidget {

	/**
	 * Actions offered in the list.
	 */
	public const HOOKS = array(
		'woocommerce_before_main_content',
		'woocommerce_after_main_content',
		'woocommerce_before_single_product',
		'woocommerce_before_single_product_summary',
		'woocommerce_single_product_summary',
		'woocommerce_after_single_product_summary',
		'woocommerce_after_single_product',
		'woocommerce_before_add_to_cart_form',
		'woocommerce_after_add_to_cart_form',
		'woocommerce_product_meta_start',
		'woocommerce_product_meta_end',
		'woocommerce_share',
		'woocommerce_before_shop_loop',
		'woocommerce_after_shop_loop',
		'woocommerce_archive_description',
		'woocommerce_before_cart',
		'woocommerce_after_cart',
		'woocommerce_before_checkout_form',
		'woocommerce_after_checkout_form',
		'woocommerce_thankyou',
		'woocommerce_account_dashboard',
	);

	/**
	 * Id.
	 */
	public function id(): string {
		return 'woo-hook';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'WooCommerce hook', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-woocommerce';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-08' );
	}

	/**
	 * Keywords.
	 *
	 * @return list<string>
	 */
	public function keywords(): array {
		return array( 'hook', 'action', 'woocommerce', 'plugin' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'hook_section',
				'label'  => __( 'Hook', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'      => 'hook',
						'type'    => 'select',
						'label'   => __( 'WooCommerce action', 'ultra-max-core' ),
						'help'    => __( 'Whatever other plugins print at this place appears here.', 'ultra-max-core' ),
						'options' => array_combine( self::HOOKS, self::HOOKS ) + array( 'custom' => __( 'Another action', 'ultra-max-core' ) ),
						'default' => 'woocommerce_single_product_summary',
					),
					array(
						'id'        => 'custom',
						'type'      => 'text',
						'label'     => __( 'Action name', 'ultra-max-core' ),
						'condition' => array( 'hook' => 'custom' ),
					),
				),
			),
		);
	}

	/**
	 * The action to run, or an empty string when the name is not allowed.
	 *
	 * @param Values $settings Settings.
	 */
	public static function hook( Values $settings ): string {
		$hook = $settings->string( 'hook' );
		if ( 'custom' === $hook ) {
			$hook = sanitize_key( $settings->string( 'custom' ) );
			// Only WooCommerce and Ultra Max actions; never core actions such as wp_head.
			return 1 === preg_match( '/^(woocommerce|ultra_max)_[a-z0-9_]+$/', $hook ) ? $hook : '';
		}
		return in_array( $hook, self::HOOKS, true ) ? $hook : '';
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$hook = self::hook( $settings );
		if ( '' === $hook ) {
			return Html::none();
		}
		ob_start();
		do_action( $hook ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Runs a WooCommerce action chosen from a fixed list.
		return Html::wp( (string) ob_get_clean() );
	}
}
