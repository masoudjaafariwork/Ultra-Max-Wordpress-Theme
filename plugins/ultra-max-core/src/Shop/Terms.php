<?php
/**
 * Fields of product categories, tags, brands and attributes (PLP-02, PLP-10, PLP-11, SRCH-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Shop;

/**
 * Adds to WordPress's own term screens:
 *
 * - categories: which filters the category shows (PLP-02);
 * - categories, tags and brands: a long text under the list (PLP-10);
 * - brands: the Latin name, so "novix" finds نوویکس (SRCH-02); the logo
 *   is WooCommerce's own brand image (PLP-11);
 * - attributes: "show values as colour swatches", and a colour for each
 *   value of such an attribute (PLP-01, PLP-07); "show values as
 *   pictures", with a picture for each value (PDP-04); the attribute's
 *   group in the specification table and whether it is a key feature
 *   (PDP-06).
 *
 * These are WordPress's classic term forms, so the fields use its markup.
 * Both the product list and the product page modules use them.
 */
final class Terms {

	public const NONCE         = 'ultra_max_term_fields';
	public const SWATCH_OPTION = 'ultra_max_color_attributes';
	public const IMAGE_OPTION  = 'ultra_max_image_attributes';
	public const SPECS_OPTION  = 'ultra_max_attribute_specs';
	public const IMAGE_META    = 'um_image';

	/**
	 * Whether the fields are hooked already.
	 *
	 * @var bool
	 */
	private static bool $registered = false;

	/**
	 * Registers the fields.
	 */
	public static function register(): void {
		if ( ! is_admin() || self::$registered ) {
			return;
		}
		self::$registered = true;
		add_action( 'admin_init', array( self::class, 'hooks' ) );
		add_action( 'woocommerce_after_add_attribute_fields', array( self::class, 'attribute_add_field' ) );
		add_action( 'woocommerce_after_edit_attribute_fields', array( self::class, 'attribute_edit_field' ) );
		add_action( 'woocommerce_attribute_added', array( self::class, 'attribute_saved' ), 10, 2 );
		add_action( 'woocommerce_attribute_updated', array( self::class, 'attribute_saved' ), 10, 2 );
	}

	/**
	 * Attribute taxonomies shown as colour swatches.
	 *
	 * @return list<string>
	 */
	public static function swatch_taxonomies(): array {
		$stored = get_option( self::SWATCH_OPTION, array() );
		return array_values( array_filter( is_array( $stored ) ? $stored : array(), 'is_string' ) );
	}

	/**
	 * Attribute taxonomies shown as pictures (PDP-04).
	 *
	 * @return list<string>
	 */
	public static function image_taxonomies(): array {
		$stored = get_option( self::IMAGE_OPTION, array() );
		return array_values( array_filter( is_array( $stored ) ? $stored : array(), 'is_string' ) );
	}

	/**
	 * The specification group and key feature mark of each attribute (PDP-06).
	 *
	 * @return array<string, array{group: string, key: bool}>
	 */
	public static function attribute_specs(): array {
		$stored = get_option( self::SPECS_OPTION, array() );
		$out    = array();
		foreach ( is_array( $stored ) ? $stored : array() as $taxonomy => $spec ) {
			if ( is_string( $taxonomy ) && is_array( $spec ) ) {
				$out[ $taxonomy ] = array(
					'group' => is_string( $spec['group'] ?? null ) ? $spec['group'] : '',
					'key'   => true === ( $spec['key'] ?? false ),
				);
			}
		}
		return $out;
	}

	/**
	 * Sets the specification group and key mark of an attribute.
	 *
	 * @param string $taxonomy Attribute taxonomy.
	 * @param string $group    Group name; empty for "General".
	 * @param bool   $key      Whether it is a key feature.
	 */
	public static function set_spec( string $taxonomy, string $group, bool $key ): void {
		$specs              = self::attribute_specs();
		$specs[ $taxonomy ] = array(
			'group' => $group,
			'key'   => $key,
		);
		update_option( self::SPECS_OPTION, $specs, true );
	}

	/**
	 * Turns pictures for the values of an attribute on or off.
	 *
	 * @param string $taxonomy Attribute taxonomy.
	 * @param bool   $on       Whether its values show as pictures.
	 */
	public static function set_images( string $taxonomy, bool $on ): void {
		$list = array_values( array_diff( self::image_taxonomies(), array( $taxonomy ) ) );
		if ( $on ) {
			$list[] = $taxonomy;
		}
		update_option( self::IMAGE_OPTION, $list, true );
	}

	/**
	 * Hooks the term forms.
	 */
	public static function hooks(): void {
		foreach ( array( 'product_cat', 'product_tag', 'product_brand' ) as $taxonomy ) {
			add_action( $taxonomy . '_edit_form_fields', array( self::class, 'edit_fields' ), 20 );
			add_action( 'edited_' . $taxonomy, array( self::class, 'save' ) );
			add_action( 'created_' . $taxonomy, array( self::class, 'save' ) );
		}
		add_action( 'product_brand_add_form_fields', array( self::class, 'add_latin' ) );
		foreach ( self::swatch_taxonomies() as $taxonomy ) {
			add_action( $taxonomy . '_add_form_fields', array( self::class, 'add_color' ) );
			add_action( $taxonomy . '_edit_form_fields', array( self::class, 'edit_color' ) );
			add_action( 'edited_' . $taxonomy, array( self::class, 'save' ) );
			add_action( 'created_' . $taxonomy, array( self::class, 'save' ) );
		}
		foreach ( self::image_taxonomies() as $taxonomy ) {
			add_action( $taxonomy . '_add_form_fields', array( self::class, 'add_image' ) );
			add_action( $taxonomy . '_edit_form_fields', array( self::class, 'edit_image' ) );
			add_action( 'edited_' . $taxonomy, array( self::class, 'save' ) );
			add_action( 'created_' . $taxonomy, array( self::class, 'save' ) );
		}
	}

	/**
	 * A picture chooser: an attachment id with a preview. The product page
	 * admin script opens the media library for it.
	 *
	 * @param int $image Attachment id.
	 */
	public static function image_field( int $image ): string {
		$preview = $image > 0 ? wp_get_attachment_image( $image, 'thumbnail', false, array( 'style' => 'max-width:80px;height:auto' ) ) : '';
		return sprintf(
			'<div data-um-media><input type="hidden" name="um_image" value="%1$s" data-um-media-value><div data-um-media-preview>%2$s</div><button type="button" class="button" data-um-media-choose>%3$s</button> <button type="button" class="button-link" data-um-media-clear%4$s>%5$s</button></div>',
			esc_attr( $image > 0 ? (string) $image : '' ),
			$preview,
			esc_html__( 'Choose picture', 'ultra-max-core' ),
			$image > 0 ? '' : ' hidden',
			esc_html__( 'Remove', 'ultra-max-core' )
		);
	}

	/**
	 * The picture field on the "add value" form of a picture attribute.
	 */
	public static function add_image(): void {
		self::nonce();
		printf(
			'<div class="form-field"><label>%1$s</label>%2$s<p>%3$s</p></div>',
			esc_html__( 'Picture', 'ultra-max-core' ),
			self::image_field( 0 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in image_field().
			esc_html__( 'Shown on the product page instead of the name. Without one, the picture of the first variation with this value shows.', 'ultra-max-core' )
		);
	}

	/**
	 * The picture field on the edit screen of a picture attribute value.
	 *
	 * @param mixed $term Term.
	 */
	public static function edit_image( mixed $term ): void {
		if ( ! $term instanceof \WP_Term ) {
			return;
		}
		self::nonce();
		$image = get_term_meta( $term->term_id, self::IMAGE_META, true );
		printf(
			'<tr class="form-field"><th scope="row">%1$s</th><td>%2$s</td></tr>',
			esc_html__( 'Picture', 'ultra-max-core' ),
			self::image_field( is_numeric( $image ) ? (int) $image : 0 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in image_field().
		);
	}

	/**
	 * The nonce field of our term fields.
	 */
	private static function nonce(): void {
		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );
	}

	/**
	 * Fields on the edit screen of a category, tag or brand.
	 *
	 * @param mixed $term Term.
	 */
	public static function edit_fields( mixed $term ): void {
		if ( ! $term instanceof \WP_Term ) {
			return;
		}
		self::nonce();
		if ( 'product_brand' === $term->taxonomy ) {
			$latin = get_term_meta( $term->term_id, Filters::LATIN_META, true );
			printf(
				'<tr class="form-field"><th scope="row"><label for="um-latin-name">%1$s</label></th><td><input id="um-latin-name" type="text" name="um_latin_name" value="%2$s" dir="ltr"><p class="description">%3$s</p></td></tr>',
				esc_html__( 'Latin name', 'ultra-max-core' ),
				esc_attr( is_string( $latin ) ? $latin : '' ),
				esc_html__( 'The brand name in English letters. Search finds the brand by either name.', 'ultra-max-core' )
			);
		}
		if ( 'product_cat' === $term->taxonomy ) {
			$own = Filters::term_keys( $term->term_id );
			$out = '';
			foreach ( Filters::choices() as $key => $label ) {
				if ( 'attributes' === $key ) {
					continue;
				}
				$out .= sprintf(
					'<label style="display:block;margin:4px 0"><input type="checkbox" name="um_filters[]" value="%1$s"%2$s> %3$s</label>',
					esc_attr( $key ),
					checked( null !== $own && in_array( $key, $own, true ), true, false ),
					esc_html( $label )
				);
			}
			printf(
				'<tr class="form-field"><th scope="row">%1$s</th><td><label style="display:block;margin-bottom:8px"><input type="radio" name="um_filters_mode" value="inherit"%2$s> %3$s</label><label style="display:block;margin-bottom:8px"><input type="radio" name="um_filters_mode" value="own"%4$s> %5$s</label><fieldset>%6$s</fieldset><p class="description">%7$s</p></td></tr>',
				esc_html__( 'Filters of this category', 'ultra-max-core' ),
				checked( null === $own, true, false ),
				esc_html__( 'Same as the parent category, or the store\'s filters', 'ultra-max-core' ),
				checked( null !== $own, true, false ),
				esc_html__( 'Only the filters ticked below', 'ultra-max-core' ),
				$out, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
				esc_html__( 'Subcategories use these filters too, unless they choose their own.', 'ultra-max-core' )
			);
		}
		$text = get_term_meta( $term->term_id, Archive::BOTTOM_META, true );
		echo '<tr class="form-field"><th scope="row"><label for="um_bottom_description">' . esc_html__( 'Text under the list', 'ultra-max-core' ) . '</label></th><td>';
		wp_editor(
			is_string( $text ) ? $text : '',
			'um_bottom_description',
			array(
				'textarea_name' => 'um_bottom_description',
				'textarea_rows' => 8,
				'media_buttons' => false,
			)
		);
		echo '<p class="description">' . esc_html__( 'A buying guide or longer text for search engines, shown under the products and folded to a few lines.', 'ultra-max-core' ) . '</p></td></tr>';
	}

	/**
	 * The Latin name field on the "add brand" form.
	 */
	public static function add_latin(): void {
		self::nonce();
		printf(
			'<div class="form-field"><label for="um-latin-name">%1$s</label><input id="um-latin-name" type="text" name="um_latin_name" value="" dir="ltr"><p>%2$s</p></div>',
			esc_html__( 'Latin name', 'ultra-max-core' ),
			esc_html__( 'The brand name in English letters. Search finds the brand by either name.', 'ultra-max-core' )
		);
	}

	/**
	 * The colour field on the "add value" form of a swatch attribute.
	 */
	public static function add_color(): void {
		self::nonce();
		printf(
			'<div class="form-field"><label for="um-color">%1$s</label><input id="um-color" type="color" name="um_color" value="#23252e"></div>',
			esc_html__( 'Colour', 'ultra-max-core' )
		);
	}

	/**
	 * The colour field on the edit screen of a swatch attribute value.
	 *
	 * @param mixed $term Term.
	 */
	public static function edit_color( mixed $term ): void {
		if ( ! $term instanceof \WP_Term ) {
			return;
		}
		self::nonce();
		$color = get_term_meta( $term->term_id, Filters::COLOR_META, true );
		printf(
			'<tr class="form-field"><th scope="row"><label for="um-color">%1$s</label></th><td><input id="um-color" type="color" name="um_color" value="%2$s"></td></tr>',
			esc_html__( 'Colour', 'ultra-max-core' ),
			esc_attr( is_string( $color ) && '' !== $color ? $color : '#23252e' )
		);
	}

	/**
	 * A posted text field, or null when it was not sent. Callers check the
	 * nonce and sanitize the value.
	 *
	 * @param string $key Field name.
	 */
	private static function field( string $key ): ?string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- See above.
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : null;
		return is_string( $value ) ? $value : null;
	}

	/**
	 * Saves our fields of a term.
	 *
	 * @param mixed $term_id Term id.
	 */
	public static function save( mixed $term_id ): void {
		$term_id = is_numeric( $term_id ) ? (int) $term_id : 0;
		$nonce   = sanitize_text_field( self::field( self::NONCE . '_nonce' ) ?? '' );
		if ( $term_id <= 0 || ! wp_verify_nonce( $nonce, self::NONCE ) || ! current_user_can( 'manage_product_terms' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce's own capability.
			return;
		}
		$latin = self::field( 'um_latin_name' );
		if ( null !== $latin ) {
			update_term_meta( $term_id, Filters::LATIN_META, sanitize_text_field( $latin ) );
		}
		$color = self::field( 'um_color' );
		if ( null !== $color ) {
			update_term_meta( $term_id, Filters::COLOR_META, (string) sanitize_hex_color( $color ) );
		}
		$image = self::field( 'um_image' );
		if ( null !== $image ) {
			update_term_meta( $term_id, self::IMAGE_META, absint( $image ) );
		}
		$mode = self::field( 'um_filters_mode' );
		if ( 'own' === $mode ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked above; each value is compared with the known filters.
			$posted = isset( $_POST['um_filters'] ) && is_array( $_POST['um_filters'] ) ? wp_unslash( $_POST['um_filters'] ) : array();
			$chosen = array();
			foreach ( is_array( $posted ) ? $posted : array() as $key ) {
				if ( is_string( $key ) && isset( Filters::choices()[ $key ] ) ) {
					$chosen[] = $key;
				}
			}
			update_term_meta( $term_id, Filters::TERM_META, $chosen );
		} elseif ( 'inherit' === $mode ) {
			delete_term_meta( $term_id, Filters::TERM_META );
		}
		$text = self::field( 'um_bottom_description' );
		if ( null !== $text ) {
			update_term_meta( $term_id, Archive::BOTTOM_META, wp_kses_post( $text ) );
		}
	}

	/**
	 * "Colour swatches" on the "add attribute" form.
	 */
	public static function attribute_add_field(): void {
		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );
		printf(
			'<div class="form-field"><label for="um-swatch"><input id="um-swatch" type="checkbox" name="um_swatch" value="1"> %1$s</label><p class="description">%2$s</p></div>',
			esc_html__( 'Show values as colour swatches', 'ultra-max-core' ),
			esc_html__( 'Each value gets a colour. Filters and product cards show the colours.', 'ultra-max-core' )
		);
		printf(
			'<div class="form-field"><label for="um-images"><input id="um-images" type="checkbox" name="um_images" value="1"> %1$s</label><p class="description">%2$s</p></div>',
			esc_html__( 'Show values as pictures on the product page', 'ultra-max-core' ),
			esc_html__( 'Each value can have a picture, such as a pattern or a design.', 'ultra-max-core' )
		);
		printf(
			'<div class="form-field"><label for="um-spec-group">%1$s</label><input id="um-spec-group" type="text" name="um_spec_group" value=""><p class="description">%2$s</p></div><div class="form-field"><label for="um-spec-key"><input id="um-spec-key" type="checkbox" name="um_spec_key" value="1"> %3$s</label></div>',
			esc_html__( 'Specification group', 'ultra-max-core' ),
			esc_html__( 'Such as "Display" or "Camera". The specification table groups attributes by it; leave empty for "General".', 'ultra-max-core' ),
			esc_html__( 'Show among the key features at the top of the product page', 'ultra-max-core' )
		);
	}

	/**
	 * "Colour swatches" on the "edit attribute" form.
	 */
	public static function attribute_edit_field(): void {
		$id   = isset( $_GET['edit'] ) && is_numeric( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only reads which attribute is edited.
		$name = $id > 0 ? wc_attribute_taxonomy_name_by_id( $id ) : '';
		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );
		printf(
			'<tr class="form-field"><th scope="row" valign="top"><label for="um-swatch">%1$s</label></th><td><input id="um-swatch" type="checkbox" name="um_swatch" value="1"%2$s><p class="description">%3$s</p></td></tr>',
			esc_html__( 'Show values as colour swatches', 'ultra-max-core' ),
			checked( '' !== $name && in_array( $name, self::swatch_taxonomies(), true ), true, false ),
			esc_html__( 'Each value gets a colour. Filters and product cards show the colours.', 'ultra-max-core' )
		);
		printf(
			'<tr class="form-field"><th scope="row" valign="top"><label for="um-images">%1$s</label></th><td><input id="um-images" type="checkbox" name="um_images" value="1"%2$s><p class="description">%3$s</p></td></tr>',
			esc_html__( 'Show values as pictures on the product page', 'ultra-max-core' ),
			checked( '' !== $name && in_array( $name, self::image_taxonomies(), true ), true, false ),
			esc_html__( 'Each value can have a picture, such as a pattern or a design.', 'ultra-max-core' )
		);
		$spec = self::attribute_specs()[ $name ] ?? array(
			'group' => '',
			'key'   => false,
		);
		printf(
			'<tr class="form-field"><th scope="row" valign="top"><label for="um-spec-group">%1$s</label></th><td><input id="um-spec-group" type="text" name="um_spec_group" value="%2$s"><p class="description">%3$s</p></td></tr><tr class="form-field"><th scope="row" valign="top"><label for="um-spec-key">%4$s</label></th><td><input id="um-spec-key" type="checkbox" name="um_spec_key" value="1"%5$s></td></tr>',
			esc_html__( 'Specification group', 'ultra-max-core' ),
			esc_attr( $spec['group'] ),
			esc_html__( 'Such as "Display" or "Camera". The specification table groups attributes by it; leave empty for "General".', 'ultra-max-core' ),
			esc_html__( 'Show among the key features at the top of the product page', 'ultra-max-core' ),
			checked( $spec['key'], true, false )
		);
	}

	/**
	 * Saves "colour swatches" of an attribute.
	 *
	 * @param mixed $id   Attribute id.
	 * @param mixed $data Attribute data.
	 */
	public static function attribute_saved( mixed $id, mixed $data = array() ): void {
		$nonce = sanitize_text_field( self::field( self::NONCE . '_nonce' ) ?? '' );
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) || ! current_user_can( 'manage_product_terms' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce's own capability.
			return;
		}
		$slug = is_array( $data ) && is_string( $data['attribute_name'] ?? null ) ? $data['attribute_name'] : '';
		if ( '' === $slug ) {
			return;
		}
		$taxonomy = wc_attribute_taxonomy_name( $slug );
		$swatch   = '1' === self::field( 'um_swatch' );
		$images   = '1' === self::field( 'um_images' );
		$key      = '1' === self::field( 'um_spec_key' );
		self::set_swatch( $taxonomy, $swatch );
		self::set_images( $taxonomy, $images );
		self::set_spec( $taxonomy, sanitize_text_field( self::field( 'um_spec_group' ) ?? '' ), $key );
	}

	/**
	 * Turns colour swatches of an attribute on or off.
	 *
	 * @param string $taxonomy Attribute taxonomy, such as pa_color.
	 * @param bool   $on       Whether its values are colours.
	 */
	public static function set_swatch( string $taxonomy, bool $on ): void {
		$list = array_values( array_diff( self::swatch_taxonomies(), array( $taxonomy ) ) );
		if ( $on ) {
			$list[] = $taxonomy;
		}
		update_option( self::SWATCH_OPTION, $list, true );
	}
}
