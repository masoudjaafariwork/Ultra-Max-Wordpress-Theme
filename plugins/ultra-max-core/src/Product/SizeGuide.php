<?php
/**
 * Size guides (PDP-13).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Lib\UI\Button;
use UltraMax\Core\Lib\UI\Dialog;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Icon;
use UltraMax\Core\Lib\UI\Prose;

/**
 * A size guide is written once in Products, Size guides with WordPress's
 * own editor, usually a table. A category chooses its guide on its edit
 * screen and its subcategories follow it; a product can choose another one
 * or none. The product page shows a "Size guide" button that opens it.
 */
final class SizeGuide {

	public const TYPE   = 'um_size_guide';
	public const NONCE  = 'ultra_max_size_guide';
	public const DIALOG = 'um-size-guide';

	/**
	 * Hooks the guides.
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'post_type' ) );
		add_action( 'product_cat_edit_form_fields', array( self::class, 'term_field' ), 30 );
		add_action( 'edited_product_cat', array( self::class, 'save_term' ) );
		add_action( 'ultra_max_product_fields', array( self::class, 'product_field' ) );
		add_action( 'ultra_max_save_product_fields', array( self::class, 'save_product' ) );
		add_filter( 'ultra_max_product_page_extras', array( self::class, 'dialog_extra' ), 10, 3 );
	}

	/**
	 * The size guide post type, under Products.
	 */
	public static function post_type(): void {
		register_post_type(
			self::TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Size guides', 'ultra-max-core' ),
					'singular_name' => __( 'Size guide', 'ultra-max-core' ),
					'add_new_item'  => __( 'Add size guide', 'ultra-max-core' ),
					'edit_item'     => __( 'Edit size guide', 'ultra-max-core' ),
					'menu_name'     => __( 'Size guides', 'ultra-max-core' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=product',
				'show_in_rest'    => true,
				'supports'        => array( 'title', 'editor', 'revisions' ),
				'capability_type' => 'product',
				'map_meta_cap'    => true,
				'rewrite'         => false,
				'query_var'       => false,
			)
		);
	}

	/**
	 * Every published guide, id => title.
	 *
	 * @return array<int, string>
	 */
	public static function guides(): array {
		$out = array();
		foreach ( get_posts(
			array(
				'post_type'      => self::TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		) as $post ) {
			$out[ $post->ID ] = '' === $post->post_title ? '#' . $post->ID : $post->post_title;
		}
		return $out;
	}

	/**
	 * The options of a guide chooser.
	 *
	 * @param int  $current Chosen value: an id, 0 to follow, -1 for none.
	 * @param bool $product Whether it is the product's chooser.
	 */
	private static function options( int $current, bool $product ): string {
		$out  = sprintf( '<option value="0"%1$s>%2$s</option>', selected( 0, $current, false ), esc_html( $product ? __( 'The category\'s guide', 'ultra-max-core' ) : __( 'Same as the parent category', 'ultra-max-core' ) ) );
		$out .= sprintf( '<option value="-1"%1$s>%2$s</option>', selected( -1, $current, false ), esc_html__( 'No size guide', 'ultra-max-core' ) );
		foreach ( self::guides() as $id => $title ) {
			$out .= sprintf( '<option value="%1$d"%2$s>%3$s</option>', $id, selected( $id, $current, false ), esc_html( $title ) );
		}
		return $out;
	}

	/**
	 * The chooser on a category's edit screen.
	 *
	 * @param mixed $term Term.
	 */
	public static function term_field( mixed $term ): void {
		if ( ! $term instanceof \WP_Term ) {
			return;
		}
		$value = get_term_meta( $term->term_id, Meta::GUIDE, true );
		wp_nonce_field( self::NONCE, self::NONCE );
		printf(
			'<tr class="form-field"><th scope="row"><label for="um-size-guide">%1$s</label></th><td><select id="um-size-guide" name="um_size_guide">%2$s</select><p class="description">%3$s</p></td></tr>',
			esc_html__( 'Size guide', 'ultra-max-core' ),
			self::options( is_numeric( $value ) ? (int) $value : 0, false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in options().
			esc_html__( 'Write guides in Products, Size guides. Products of this category and its subcategories show the guide.', 'ultra-max-core' )
		);
	}

	/**
	 * Saves a category's guide.
	 *
	 * @param mixed $term_id Term id.
	 */
	public static function save_term( mixed $term_id ): void {
		$nonce = isset( $_POST[ self::NONCE ] ) && is_string( $_POST[ self::NONCE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ) : '';
		if ( ! is_numeric( $term_id ) || ! wp_verify_nonce( $nonce, self::NONCE ) || ! current_user_can( 'manage_product_terms' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce's own capability.
			return;
		}
		$value = isset( $_POST['um_size_guide'] ) && is_numeric( $_POST['um_size_guide'] ) ? (int) $_POST['um_size_guide'] : 0;
		if ( 0 === $value ) {
			delete_term_meta( (int) $term_id, Meta::GUIDE );
		} else {
			update_term_meta( (int) $term_id, Meta::GUIDE, max( -1, $value ) );
		}
	}

	/**
	 * The chooser in the product's tab.
	 *
	 * @param mixed $product Product.
	 */
	public static function product_field( mixed $product ): void {
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		printf(
			'<div class="options_group"><p class="form-field"><label for="um-product-size-guide">%1$s</label><select id="um-product-size-guide" name="%2$s">%3$s</select></p></div>',
			esc_html__( 'Size guide', 'ultra-max-core' ),
			esc_attr( Meta::GUIDE ),
			self::options( Meta::int( $product, Meta::GUIDE ), true ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in options().
		);
	}

	/**
	 * Saves the product's choice.
	 *
	 * @param mixed $product Product.
	 */
	public static function save_product( mixed $product ): void {
		if ( $product instanceof \WC_Product ) {
			$value = Fields::posted( Meta::GUIDE );
			$product->update_meta_data( Meta::GUIDE, is_numeric( $value ) ? (string) max( -1, (int) $value ) : '0' );
		}
	}

	/**
	 * The guide of a product: its own, or its deepest category's, or that
	 * category's nearest parent's.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function for( \WC_Product $product ): ?\WP_Post {
		$owner = Meta::owner( $product );
		$own   = Meta::int( $owner, Meta::GUIDE );
		if ( -1 === $own ) {
			return null;
		}
		$id = $own;
		if ( 0 === $id ) {
			$terms = get_the_terms( $owner->get_id(), 'product_cat' );
			$best  = -1;
			foreach ( is_array( $terms ) ? $terms : array() as $term ) {
				$chain = array_merge( array( $term->term_id ), get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );
				foreach ( $chain as $depth => $term_id ) {
					$value = get_term_meta( (int) $term_id, Meta::GUIDE, true );
					if ( is_numeric( $value ) && 0 !== (int) $value ) {
						$level = count( $chain ) - $depth;
						if ( $level > $best ) {
							$best = $level;
							$id   = (int) $value;
						}
						break;
					}
				}
			}
		}
		$post = $id > 0 ? get_post( $id ) : null;
		return $post instanceof \WP_Post && self::TYPE === $post->post_type && 'publish' === $post->post_status ? $post : null;
	}

	/**
	 * The "Size guide" button.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function button( \WC_Product $product ): Html {
		if ( null === self::for( $product ) ) {
			return Html::none();
		}
		return Button::render(
			array(
				'children' => array( Icon::render( 'grid', 'size-4' ), __( 'Size guide', 'ultra-max-core' ) ),
				'variant'  => 'ghost',
				'size'     => 'sm',
				'opens'    => self::DIALOG,
				'class'    => 'justify-self-start',
			)
		);
	}

	/**
	 * The guide's window.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function dialog( \WC_Product $product ): Html {
		static $printed = false;
		$guide          = self::for( $product );
		if ( $printed || null === $guide ) {
			return Html::none();
		}
		$printed = true;
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress's own content filter.
		$content = (string) apply_filters( 'the_content', $guide->post_content );
		return Dialog::render(
			array(
				'id'       => self::DIALOG,
				'title'    => '' === $guide->post_title ? __( 'Size guide', 'ultra-max-core' ) : $guide->post_title,
				'size'     => 'lg',
				'children' => Prose::render( array( 'children' => Html::wp( $content ) ) ),
			)
		);
	}

	/**
	 * Adds the window to the built-in page.
	 *
	 * @param mixed $pieces  Pieces.
	 * @param mixed $where   Place.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function dialog_extra( mixed $pieces, mixed $where, mixed $product ): mixed {
		if ( is_array( $pieces ) && 'dialogs' === $where && $product instanceof \WC_Product ) {
			$pieces[] = self::dialog( $product );
		}
		return $pieces;
	}
}
