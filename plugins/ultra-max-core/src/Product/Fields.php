<?php
/**
 * The product page's fields on WooCommerce's product screen.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Lib\Foundation\Assets as Registrar;

/**
 * An "Ultra Max" tab in WooCommerce's Product data box: Latin name,
 * warranty, preparation time, videos, quantity limits, a badge and the
 * unit; and a list of extra pictures for each variation (PDP-03).
 *
 * WooCommerce checks its own nonce and the user's rights before it calls
 * the save hooks used here.
 */
final class Fields {

	public const PANEL = 'um_product_data';

	/**
	 * Hooks the fields.
	 */
	public static function register(): void {
		if ( ! is_admin() ) {
			return;
		}
		add_filter( 'woocommerce_product_data_tabs', array( self::class, 'tab' ) );
		add_action( 'woocommerce_product_data_panels', array( self::class, 'panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( self::class, 'save' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( self::class, 'variation_fields' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( self::class, 'save_variation' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
	}

	/**
	 * The media chooser script on product and attribute value screens.
	 */
	public static function assets(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen instanceof \WP_Screen ) {
			return;
		}
		$product = 'product' === $screen->post_type && 'post' === $screen->base;
		$term    = str_starts_with( (string) $screen->taxonomy, 'pa_' );
		if ( ! $product && ! $term ) {
			return;
		}
		wp_enqueue_media();
		$registrar = new Registrar( plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'assets/build', plugins_url( 'assets/build', ULTRA_MAX_CORE_FILE ) );
		if ( $registrar->register_script( 'ultra-max-product-admin', 'core/product-admin', array( 'media-editor' ) ) ) {
			wp_add_inline_script(
				'ultra-max-product-admin',
				'window.ultraMaxProductAdmin = ' . wp_json_encode(
					array(
						'choose'   => __( 'Choose pictures', 'ultra-max-core' ),
						'use'      => __( 'Use these pictures', 'ultra-max-core' ),
						'remove'   => __( 'Remove', 'ultra-max-core' ),
						'addRow'   => __( 'Add a tab', 'ultra-max-core' ),
						'rowTitle' => __( 'Tab title', 'ultra-max-core' ),
						'content'  => __( 'Content', 'ultra-max-core' ),
					)
				) . ';',
				'before'
			);
			wp_enqueue_script( 'ultra-max-product-admin' );
		}
	}

	/**
	 * Adds the tab.
	 *
	 * @param mixed $tabs Tabs.
	 * @return mixed
	 */
	public static function tab( mixed $tabs ): mixed {
		if ( is_array( $tabs ) ) {
			$tabs['ultra_max'] = array(
				'label'    => __( 'Product page', 'ultra-max-core' ),
				'target'   => self::PANEL,
				'class'    => array(),
				'priority' => 75,
			);
		}
		return $tabs;
	}

	/**
	 * A text field of the panel.
	 *
	 * @param string                $id          Field id.
	 * @param string                $label       Label.
	 * @param string                $value       Value.
	 * @param string                $description Help.
	 * @param string                $type        Input type.
	 * @param array<string, string> $attributes Extra attributes.
	 */
	private static function text( string $id, string $label, string $value, string $description = '', string $type = 'text', array $attributes = array() ): void {
		woocommerce_wp_text_input(
			array(
				'id'                => $id,
				'label'             => $label,
				'value'             => $value,
				'type'              => $type,
				'description'       => $description,
				'desc_tip'          => '' !== $description,
				'custom_attributes' => $attributes,
			)
		);
	}

	/**
	 * Prints the panel.
	 */
	public static function panel(): void {
		global $post;
		$product = $post instanceof \WP_Post ? wc_get_product( $post->ID ) : null;
		if ( ! $product instanceof \WC_Product ) {
			$product = new \WC_Product_Simple();
		}
		$number = array(
			'min'  => '0',
			'step' => '1',
		);
		echo '<div id="' . esc_attr( self::PANEL ) . '" class="panel woocommerce_options_panel hidden"><div class="options_group">';
		self::text( Meta::LATIN, __( 'Latin name', 'ultra-max-core' ), Meta::text( $product, Meta::LATIN ), __( 'Shown in small letters under the name, as many Iranian stores do.', 'ultra-max-core' ), 'text', array( 'dir' => 'ltr' ) );
		self::text( Meta::WARRANTY, __( 'Warranty', 'ultra-max-core' ), Meta::text( $product, Meta::WARRANTY ), __( 'Such as "18 month warranty". Empty uses the store\'s default; "-" shows none.', 'ultra-max-core' ) );
		self::text( Meta::SHIP, __( 'Days to prepare', 'ultra-max-core' ), (string) Meta::int( $product, Meta::SHIP ), __( 'Working days before this product leaves the store. 0 uses the store\'s default.', 'ultra-max-core' ), 'number', $number );
		self::text( Meta::BADGE, __( 'Badge', 'ultra-max-core' ), Meta::text( $product, Meta::BADGE ), __( 'A short label on the product\'s picture and cards, such as "Limited edition".', 'ultra-max-core' ) );
		self::text( Meta::UNIT, __( 'Unit', 'ultra-max-core' ), Meta::text( $product, Meta::UNIT ), __( 'Shown under the name on small cards, such as "250 g".', 'ultra-max-core' ) );
		echo '</div><div class="options_group">';
		woocommerce_wp_textarea_input(
			array(
				'id'          => Meta::VIDEOS,
				'label'       => __( 'Videos', 'ultra-max-core' ),
				'value'       => Meta::text( $product, Meta::VIDEOS ),
				'description' => __( 'One address per line: a YouTube or Aparat page, or a video file from the media library. They show after the pictures.', 'ultra-max-core' ),
				'desc_tip'    => true,
				'rows'        => 3,
				'style'       => 'direction:ltr',
			)
		);
		echo '</div><div class="options_group">';
		self::text( Meta::QTY_MIN, __( 'Minimum quantity', 'ultra-max-core' ), (string) Meta::int( $product, Meta::QTY_MIN ), __( 'The fewest a shopper can buy. 0 or 1 for no minimum.', 'ultra-max-core' ), 'number', $number );
		self::text( Meta::QTY_MAX, __( 'Maximum quantity', 'ultra-max-core' ), (string) Meta::int( $product, Meta::QTY_MAX ), __( 'The most in one order. 0 for no limit.', 'ultra-max-core' ), 'number', $number );
		self::text( Meta::QTY_STEP, __( 'Sold in steps of', 'ultra-max-core' ), (string) Meta::int( $product, Meta::QTY_STEP ), __( 'Such as 6 for a box of six. 0 or 1 for single items.', 'ultra-max-core' ), 'number', $number );
		echo '</div>';
		/**
		 * Prints more fields in the product page tab of the product screen.
		 *
		 * @param \WC_Product $product Product.
		 */
		do_action( 'ultra_max_product_fields', $product );
		echo '</div>';
	}

	/**
	 * A posted value of WooCommerce's product form; WooCommerce checked the
	 * nonce before calling us.
	 *
	 * @param string $key Field name.
	 */
	public static function posted( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Checked by WooCommerce's meta box save; callers sanitize.
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Saves the panel.
	 *
	 * @param mixed $product Product.
	 */
	public static function save( mixed $product ): void {
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		foreach ( array( Meta::LATIN, Meta::WARRANTY, Meta::BADGE, Meta::UNIT ) as $key ) {
			$product->update_meta_data( $key, sanitize_text_field( self::posted( $key ) ) );
		}
		foreach ( array( Meta::SHIP, Meta::QTY_MIN, Meta::QTY_MAX, Meta::QTY_STEP ) as $key ) {
			$product->update_meta_data( $key, (string) absint( self::posted( $key ) ) );
		}
		$lines  = array();
		$posted = preg_split( '/\R/', self::posted( Meta::VIDEOS ) );
		foreach ( false === $posted ? array() : $posted as $line ) {
			$url = esc_url_raw( trim( $line ) );
			if ( '' !== $url ) {
				$lines[] = $url;
			}
		}
		$product->update_meta_data( Meta::VIDEOS, implode( "\n", $lines ) );
		/**
		 * Saves more fields of the product page tab.
		 *
		 * @param \WC_Product $product Product.
		 */
		do_action( 'ultra_max_save_product_fields', $product );
	}

	/**
	 * Extra pictures of a variation.
	 *
	 * @param mixed $loop      Position of the variation in the form.
	 * @param mixed $data      Variation data.
	 * @param mixed $variation Variation post.
	 */
	public static function variation_fields( mixed $loop, mixed $data, mixed $variation ): void {
		if ( ! is_numeric( $loop ) || ! $variation instanceof \WP_Post ) {
			return;
		}
		$product = wc_get_product( $variation->ID );
		$ids     = $product instanceof \WC_Product ? Meta::ids( $product, Meta::GALLERY ) : array();
		$preview = '';
		foreach ( $ids as $id ) {
			$preview .= wp_get_attachment_image( $id, 'thumbnail', false, array( 'style' => 'width:48px;height:48px;object-fit:cover;margin:2px' ) );
		}
		printf(
			'<div class="form-row form-row-full" data-um-media data-multiple><label>%1$s</label><input type="hidden" name="um_gallery[%2$d]" value="%3$s" data-um-media-value><div data-um-media-preview>%4$s</div><p><button type="button" class="button" data-um-media-choose>%5$s</button> <button type="button" class="button-link" data-um-media-clear%6$s>%7$s</button></p><p class="description">%8$s</p></div>',
			esc_html__( 'More pictures of this variation', 'ultra-max-core' ),
			(int) $loop,
			esc_attr( implode( ',', $ids ) ),
			$preview, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Made by wp_get_attachment_image().
			esc_html__( 'Choose pictures', 'ultra-max-core' ),
			array() === $ids ? ' hidden' : '',
			esc_html__( 'Remove', 'ultra-max-core' ),
			esc_html__( 'When the shopper chooses this variation, the gallery shows its picture and these.', 'ultra-max-core' )
		);
	}

	/**
	 * Saves the extra pictures of a variation.
	 *
	 * @param mixed $variation_id Variation id.
	 * @param mixed $loop         Position in the form.
	 */
	public static function save_variation( mixed $variation_id, mixed $loop ): void {
		$variation = is_numeric( $variation_id ) ? wc_get_product( (int) $variation_id ) : null;
		if ( ! $variation instanceof \WC_Product || ! is_numeric( $loop ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- WooCommerce checked the "save-variations" nonce; each id is checked below.
		$posted = isset( $_POST['um_gallery'] ) && is_array( $_POST['um_gallery'] ) ? wp_unslash( $_POST['um_gallery'] ) : array();
		$value  = is_array( $posted ) && is_string( $posted[ (int) $loop ] ?? null ) ? $posted[ (int) $loop ] : '';
		$ids    = array();
		foreach ( explode( ',', $value ) as $id ) {
			if ( is_numeric( $id ) && (int) $id > 0 && 'attachment' === get_post_type( (int) $id ) ) {
				$ids[] = (int) $id;
			}
		}
		$variation->update_meta_data( Meta::GALLERY, implode( ',', array_unique( $ids ) ) );
		$variation->save_meta_data();
	}
}
