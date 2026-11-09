<?php
/**
 * Setup wizard (ADM-03).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Admin;

use UltraMax\Core\Lib\Settings\Store;

/**
 * Installs and activates the plugins the store needs, sets the store
 * name, logo and colour, and opens once after the first activation.
 *
 * WooCommerce and Elementor come from wordpress.org; the Ultra Max
 * plugins ship in the package and only need activating.
 *
 * @phpstan-type PluginRow array{slug: string, name: string, description: string, file: string, source: 'wordpress.org'|'package', required: bool, status: 'active'|'inactive'|'missing'}
 */
final class Wizard {

	public const REDIRECT = 'ultra_max_wizard_redirect';
	public const DONE     = 'ultra_max_wizard_done';

	/**
	 * Registers the first-run redirect.
	 */
	public static function register(): void {
		add_action( 'admin_init', array( self::class, 'maybe_redirect' ) );
	}

	/**
	 * Remembers to open the wizard after activation.
	 */
	public static function activated(): void {
		if ( ! get_option( self::DONE ) ) {
			set_transient( self::REDIRECT, 1, 60 );
		}
	}

	/**
	 * Opens the wizard once, unless several plugins were activated at once.
	 */
	public static function maybe_redirect(): void {
		if ( ! get_transient( self::REDIRECT ) || wp_doing_ajax() || is_network_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		delete_transient( self::REDIRECT );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check of the bulk activation flag.
		if ( isset( $_GET['activate-multi'] ) ) {
			return;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ultra-max-wizard' ) );
		exit;
	}

	/**
	 * The plugins of the second step.
	 *
	 * @return list<PluginRow>
	 */
	public static function plugins(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$installed = get_plugins();
		$rows      = array(
			array( 'woocommerce', __( 'WooCommerce', 'ultra-max-core' ), __( 'The store itself: products, cart and orders.', 'ultra-max-core' ), 'woocommerce/woocommerce.php', 'wordpress.org', true ),
			array( 'elementor', __( 'Elementor', 'ultra-max-core' ), __( 'The free page builder; enough for every demo.', 'ultra-max-core' ), 'elementor/elementor.php', 'wordpress.org', true ),
			array( 'ultra-max-auth', __( 'Ultra Max: SMS login', 'ultra-max-core' ), __( 'Optional, but recommended.', 'ultra-max-core' ), 'ultra-max-auth/ultra-max-auth.php', 'package', false ),
			array( 'ultra-max-invoice', __( 'Ultra Max: invoices and labels', 'ultra-max-core' ), __( 'Optional.', 'ultra-max-core' ), 'ultra-max-invoice/ultra-max-invoice.php', 'package', false ),
			array( 'ultra-max-seo', __( 'Ultra Max: SEO', 'ultra-max-core' ), __( 'Optional. Skip it if you already use another SEO plugin.', 'ultra-max-core' ), 'ultra-max-seo/ultra-max-seo.php', 'package', false ),
		);
		$out       = array();
		foreach ( $rows as [ $slug, $name, $description, $file, $source, $required ] ) {
			$out[] = array(
				'slug'        => $slug,
				'name'        => $name,
				'description' => $description,
				'file'        => $file,
				'source'      => $source,
				'required'    => $required,
				'status'      => is_plugin_active( $file ) ? 'active' : ( isset( $installed[ $file ] ) ? 'inactive' : 'missing' ),
			);
		}
		return $out;
	}

	/**
	 * Installs (from wordpress.org) and activates one plugin of the list.
	 *
	 * @param string $slug Plugin slug.
	 * @return true|\WP_Error
	 */
	public static function install( string $slug ): bool|\WP_Error {
		$row = null;
		foreach ( self::plugins() as $plugin ) {
			if ( $plugin['slug'] === $slug ) {
				$row = $plugin;
			}
		}
		if ( null === $row ) {
			return new \WP_Error( 'unknown_plugin', __( 'This plugin is not part of the setup.', 'ultra-max-core' ) );
		}
		if ( 'missing' === $row['status'] ) {
			if ( 'package' === $row['source'] ) {
				return new \WP_Error( 'not_in_package', __( 'This plugin was not found. Upload it from the Ultra Max package in Plugins > Add New.', 'ultra-max-core' ) );
			}
			if ( ! current_user_can( 'install_plugins' ) ) {
				return new \WP_Error( 'forbidden', __( 'You are not allowed to install plugins.', 'ultra-max-core' ) );
			}
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug,
					'fields' => array( 'sections' => false ),
				)
			);
			if ( is_wp_error( $api ) ) {
				return $api;
			}
			$link = is_object( $api ) && isset( $api->download_link ) && is_string( $api->download_link ) ? $api->download_link : '';
			if ( '' === $link ) {
				return new \WP_Error( 'no_download', __( 'wordpress.org did not return a download link.', 'ultra-max-core' ) );
			}
			$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
			$result   = $upgrader->install( $link );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			if ( true !== $result ) {
				return new \WP_Error( 'install_failed', __( 'The plugin could not be installed. Check the folder permissions of wp-content/plugins.', 'ultra-max-core' ) );
			}
		}
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return new \WP_Error( 'forbidden', __( 'You are not allowed to activate plugins.', 'ultra-max-core' ) );
		}
		$activated = activate_plugin( $row['file'], '', false, true );
		return is_wp_error( $activated ) ? $activated : true;
	}

	/**
	 * Saves the store name, logo and colour of the brand step.
	 *
	 * @param string $name   Store name.
	 * @param int    $logo   Logo attachment id, 0 to keep.
	 * @param string $preset Colour preset or "custom".
	 * @param string $color  Custom colour.
	 */
	public static function brand( string $name, int $logo, string $preset, string $color ): void {
		Store::backup( __( 'Before the setup wizard', 'ultra-max-core' ) );
		if ( '' !== trim( $name ) ) {
			update_option( 'blogname', sanitize_text_field( $name ) );
		}
		if ( $logo > 0 && wp_attachment_is_image( $logo ) ) {
			set_theme_mod( 'custom_logo', $logo );
		}
		Store::save(
			array(
				'color_preset'  => $preset,
				'color_primary' => $color,
			)
		);
	}

	/**
	 * Marks the wizard as finished.
	 */
	public static function finish(): void {
		update_option( self::DONE, time(), false );
	}

	/**
	 * Demos of the demo step. The importer comes with phase 9 (DEMO-01).
	 *
	 * @return list<array{id: string, name: string, description: string, colors: array{0: string, 1: string, 2: string}, image: string}>
	 */
	public static function demos(): array {
		return array(
			array(
				'id'          => 'general',
				'name'        => __( 'General store', 'ultra-max-core' ),
				'description' => __( 'Everything in one store', 'ultra-max-core' ),
				'colors'      => array( '#eef0ff', '#3d34ad', '#7f78f0' ),
				'image'       => 'phone',
			),
			array(
				'id'          => 'digital',
				'name'        => __( 'Mobile and digital', 'ultra-max-core' ),
				'description' => __( 'Phones, laptops and accessories', 'ultra-max-core' ),
				'colors'      => array( '#e8f2fc', '#103c7a', '#4e93f2' ),
				'image'       => 'headphones',
			),
			array(
				'id'          => 'fashion',
				'name'        => __( 'Fashion and clothing', 'ultra-max-core' ),
				'description' => __( 'Colours and sizes on every card', 'ultra-max-core' ),
				'colors'      => array( '#fff3ea', '#b86400', '#ffb347' ),
				'image'       => 'tshirt',
			),
			array(
				'id'          => 'beauty',
				'name'        => __( 'Beauty and health', 'ultra-max-core' ),
				'description' => __( 'Brand, scent and skin type', 'ultra-max-core' ),
				'colors'      => array( '#fdeef5', '#9a4a7c', '#f7c1d9' ),
				'image'       => 'perfume',
			),
			array(
				'id'          => 'grocery',
				'name'        => __( 'Supermarket', 'ultra-max-core' ),
				'description' => __( 'Fast shopping with compact cards', 'ultra-max-core' ),
				'colors'      => array( '#e8f7ef', '#0e5233', '#3db77a' ),
				'image'       => 'juice',
			),
		);
	}
}
