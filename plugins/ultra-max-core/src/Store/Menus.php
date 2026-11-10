<?php
/**
 * Menus for the header widgets, with our extra item fields (HDR-06, HDR-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Store;

use UltraMax\Core\Builder\Layouts;
use UltraMax\Core\Builder\Renderer;
use UltraMax\Core\Elementor\Data;
use UltraMax\Core\Lib\Foundation\Brand;
use UltraMax\Core\Lib\UI\Icon;
use UltraMax\Core\Lib\UI\Image;
use UltraMax\Core\Lib\UI\NavBar;

/**
 * Reads a WordPress menu as a tree. Each item may carry, from the fields
 * we add to Appearance > Menus: an icon, a small image, a "new" or "hot"
 * badge, and a reusable block shown as its mega panel.
 *
 * @phpstan-type MenuNode array{
 *     id: int,
 *     label: string,
 *     href: string,
 *     current: bool,
 *     icon: string,
 *     image: int,
 *     badge: string,
 *     badge_text: string,
 *     mega: int,
 *     mega_width: string,
 *     children: list<array<string, mixed>>,
 * }
 * @phpstan-import-type NavItem from NavBar
 */
final class Menus {

	public const ICON       = '_um_icon';
	public const IMAGE      = '_um_image';
	public const BADGE      = '_um_badge';
	public const BADGE_TEXT = '_um_badge_text';
	public const MEGA       = '_um_mega';
	public const MEGA_WIDTH = '_um_mega_width';

	/**
	 * Registers the extra fields on the menus screen.
	 */
	public static function register(): void {
		add_action( 'wp_nav_menu_item_custom_fields', array( self::class, 'fields' ) );
		add_action( 'wp_update_nav_menu_item', array( self::class, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'media' ) );
	}

	/**
	 * Menus to choose from, by id.
	 *
	 * @return array<int|string, string>
	 */
	public static function choices(): array {
		$out = array( '' => __( 'Menu at the "Primary menu" location', 'ultra-max-core' ) );
		foreach ( wp_get_nav_menus() as $menu ) {
			$out[ (string) $menu->term_id ] = $menu->name;
		}
		return $out;
	}

	/**
	 * The menu id of a choice: a menu id, or the menu at a location.
	 *
	 * @param string $choice   Menu id, or empty.
	 * @param string $location Location used when no menu is chosen.
	 */
	public static function menu_id( string $choice, string $location = 'primary' ): int {
		if ( is_numeric( $choice ) && (int) $choice > 0 ) {
			return (int) $choice;
		}
		$locations = get_nav_menu_locations();
		return isset( $locations[ $location ] ) ? (int) $locations[ $location ] : 0;
	}

	/**
	 * A menu as a tree.
	 *
	 * @param int $menu  Menu id.
	 * @param int $depth Levels to keep.
	 * @return list<MenuNode>
	 */
	public static function tree( int $menu, int $depth = 3 ): array {
		if ( $menu <= 0 ) {
			return array();
		}
		$items = wp_get_nav_menu_items( $menu, array( 'update_post_term_cache' => false ) );
		if ( ! is_array( $items ) ) {
			return array();
		}
		_wp_menu_item_classes_by_context( $items );
		$nodes  = array();
		$parent = array();
		foreach ( $items as $item ) {
			if ( ! $item instanceof \WP_Post ) {
				continue;
			}
			$classes             = is_array( $item->classes ?? null ) ? $item->classes : array();
			$nodes[ $item->ID ]  = array(
				'id'         => $item->ID,
				'label'      => wp_strip_all_tags( is_string( $item->title ?? null ) ? $item->title : '' ),
				'href'       => is_string( $item->url ?? null ) ? $item->url : '#',
				'current'    => in_array( 'current-menu-item', $classes, true ) || in_array( 'current-menu-ancestor', $classes, true ),
				'icon'       => self::meta( $item->ID, self::ICON ),
				'image'      => (int) self::meta( $item->ID, self::IMAGE ),
				'badge'      => self::meta( $item->ID, self::BADGE ),
				'badge_text' => self::meta( $item->ID, self::BADGE_TEXT ),
				'mega'       => (int) self::meta( $item->ID, self::MEGA ),
				'mega_width' => self::meta( $item->ID, self::MEGA_WIDTH ),
				'children'   => array(),
			);
			$parent_id           = get_post_meta( $item->ID, '_menu_item_menu_item_parent', true );
			$parent[ $item->ID ] = is_numeric( $parent_id ) ? (int) $parent_id : 0;
		}
		return self::children( $nodes, $parent, 0, $depth );
	}

	/**
	 * Children of one node, built recursively.
	 *
	 * @param array<int, MenuNode> $nodes  Nodes by id.
	 * @param array<int, int>      $parents Parent of each node.
	 * @param int                  $of     Parent id.
	 * @param int                  $depth  Levels left.
	 * @return list<MenuNode>
	 */
	private static function children( array $nodes, array $parents, int $of, int $depth ): array {
		$out = array();
		if ( $depth <= 0 ) {
			return $out;
		}
		foreach ( $nodes as $id => $node ) {
			if ( ( $parents[ $id ] ?? 0 ) === $of ) {
				$node['children'] = self::children( $nodes, $parents, $id, $depth - 1 );
				$out[]            = $node;
			}
		}
		return $out;
	}

	/**
	 * A text meta value of a menu item.
	 *
	 * @param int    $id  Item id.
	 * @param string $key Meta key.
	 */
	private static function meta( int $id, string $key ): string {
		$value = get_post_meta( $id, $key, true );
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Props of a NavBar item.
	 *
	 * @param array $node      Node.
	 * @param bool  $with_mega Whether to render mega panels.
	 * @phpstan-param MenuNode $node
	 * @return NavItem
	 */
	public static function nav_item( array $node, bool $with_mega = true ): array {
		$item = array(
			'label'   => $node['label'],
			'href'    => $node['href'],
			'current' => $node['current'],
		);
		if ( '' !== $node['icon'] && Icon::exists( $node['icon'] ) ) {
			$item['icon'] = $node['icon'];
		}
		if ( $node['image'] > 0 ) {
			$item['image'] = Image::render(
				array(
					'id'   => $node['image'],
					'size' => 'thumbnail',
				)
			);
		}
		$badge = self::badge( $node['badge'], $node['badge_text'] );
		if ( null !== $badge ) {
			$item['badge'] = $badge;
		}
		if ( $with_mega && $node['mega'] > 0 && Layouts::POST_TYPE === get_post_type( $node['mega'] ) ) {
			Data::enqueue( $node['mega'] );
			$item['mega']       = Renderer::content( $node['mega'] );
			$item['mega_width'] = in_array( $node['mega_width'], array( 'full', 'container', 'auto' ), true ) ? $node['mega_width'] : 'container';
			return $item;
		}
		$children = array();
		foreach ( $node['children'] as $child ) {
			$children[] = array(
				'label'   => is_string( $child['label'] ?? null ) ? $child['label'] : '',
				'href'    => is_string( $child['href'] ?? null ) ? $child['href'] : '#',
				'current' => true === ( $child['current'] ?? false ),
			);
		}
		if ( array() !== $children ) {
			$item['children'] = $children;
		}
		return $item;
	}

	/**
	 * A badge from the item fields.
	 *
	 * @param string $kind new, hot or custom.
	 * @param string $text Custom text.
	 * @return array{label: string, tone: 'new'|'hot'|'brand'}|null
	 */
	public static function badge( string $kind, string $text ): ?array {
		return match ( $kind ) {
			'new'   => array(
				'label' => '' === $text ? __( 'New', 'ultra-max-core' ) : $text,
				'tone'  => 'new',
			),
			'hot'   => array(
				'label' => '' === $text ? __( 'Hot', 'ultra-max-core' ) : $text,
				'tone'  => 'hot',
			),
			'custom' => '' === $text ? null : array(
				'label' => $text,
				'tone'  => 'brand',
			),
			default => null,
		};
	}

	/**
	 * Prints our fields under each menu item.
	 *
	 * @param mixed $item_id Item id.
	 */
	public static function fields( mixed $item_id ): void {
		$id = is_numeric( $item_id ) ? (int) $item_id : 0;
		if ( $id <= 0 ) {
			return;
		}
		wp_nonce_field( 'ultra_max_menu_item', 'ultra_max_menu_nonce', false );
		$icons = array( '' => __( 'None', 'ultra-max-core' ) );
		foreach ( Icon::names() as $name ) {
			$icons[ $name ] = $name;
		}
		$blocks = array( '0' => __( 'None (normal sub-menu)', 'ultra-max-core' ) );
		foreach ( get_posts(
			array(
				'post_type'      => Layouts::POST_TYPE,
				'posts_per_page' => 100,
				'meta_key'       => Layouts::TYPE_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Admin screen only.
				'meta_value'     => 'block', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Admin screen only.
			)
		) as $block ) {
			if ( $block instanceof \WP_Post ) {
				$blocks[ (string) $block->ID ] = $block->post_title;
			}
		}
		echo '<div class="um-menu-fields" style="clear:both;padding:8px 0;border-top:1px dashed #dcdcde;margin-top:8px">';
		printf( '<p class="description"><strong>%s</strong></p>', esc_html( Brand::name() ) );
		self::select( $id, self::ICON, __( 'Icon', 'ultra-max-core' ), $icons );
		self::select(
			$id,
			self::BADGE,
			__( 'Badge', 'ultra-max-core' ),
			array(
				''       => __( 'None', 'ultra-max-core' ),
				'new'    => __( 'New', 'ultra-max-core' ),
				'hot'    => __( 'Hot', 'ultra-max-core' ),
				'custom' => __( 'Custom text', 'ultra-max-core' ),
			)
		);
		printf(
			'<p class="description description-wide"><label for="um-badge-text-%1$d">%2$s<br><input type="text" class="widefat" id="um-badge-text-%1$d" name="um_badge_text[%1$d]" value="%3$s"></label></p>',
			absint( $id ),
			esc_html__( 'Badge text (optional)', 'ultra-max-core' ),
			esc_attr( self::meta( $id, self::BADGE_TEXT ) )
		);
		printf(
			'<p class="description description-wide"><label for="um-image-%1$d">%2$s</label><br><input type="hidden" id="um-image-%1$d" name="um_image[%1$d]" value="%3$s"><button type="button" class="button um-menu-image" data-target="um-image-%1$d">%4$s</button> <span class="um-menu-image-name">%5$s</span></p>',
			absint( $id ),
			esc_html__( 'Small image', 'ultra-max-core' ),
			esc_attr( self::meta( $id, self::IMAGE ) ),
			esc_html__( 'Choose image', 'ultra-max-core' ),
			esc_html( (int) self::meta( $id, self::IMAGE ) > 0 ? '#' . self::meta( $id, self::IMAGE ) : '' )
		);
		self::select( $id, self::MEGA, __( 'Mega menu (a reusable block)', 'ultra-max-core' ), $blocks );
		self::select(
			$id,
			self::MEGA_WIDTH,
			__( 'Mega menu width', 'ultra-max-core' ),
			array(
				'container' => __( 'Content width', 'ultra-max-core' ),
				'full'      => __( 'Full width', 'ultra-max-core' ),
				'auto'      => __( 'As wide as its content', 'ultra-max-core' ),
			)
		);
		echo '</div>';
	}

	/**
	 * Prints one select field.
	 *
	 * @param int                       $id      Item id.
	 * @param string                    $key     Meta key.
	 * @param string                    $label   Label.
	 * @param array<int|string, string> $options Options.
	 */
	private static function select( int $id, string $key, string $label, array $options ): void {
		$name    = ltrim( $key, '_' );
		$current = self::meta( $id, $key );
		printf( '<p class="description description-wide"><label for="%1$s-%2$d">%3$s<br><select class="widefat" id="%1$s-%2$d" name="%1$s[%2$d]">', esc_attr( $name ), absint( $id ), esc_html( $label ) );
		foreach ( $options as $value => $text ) {
			printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( (string) $value ), selected( $current, (string) $value, false ), esc_html( $text ) );
		}
		echo '</select></label></p>';
	}

	/**
	 * Saves our fields of one menu item.
	 *
	 * @param mixed $menu_id Menu id.
	 * @param mixed $item_id Item id.
	 */
	public static function save( mixed $menu_id, mixed $item_id ): void {
		$id = is_numeric( $item_id ) ? (int) $item_id : 0;
		if ( $id <= 0 || ! current_user_can( 'edit_theme_options' ) || ! isset( $_POST['ultra_max_menu_nonce'] ) || ! is_string( $_POST['ultra_max_menu_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ultra_max_menu_nonce'] ) ), 'ultra_max_menu_item' ) ) {
			return;
		}
		$fields = array(
			self::ICON       => 'um_icon',
			self::IMAGE      => 'um_image',
			self::BADGE      => 'um_badge',
			self::BADGE_TEXT => 'um_badge_text',
			self::MEGA       => 'um_mega',
			self::MEGA_WIDTH => 'um_mega_width',
		);
		foreach ( $fields as $key => $name ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below per field.
			$raw   = isset( $_POST[ $name ] ) && is_array( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : array();
			$value = is_array( $raw ) && isset( $raw[ $id ] ) && is_scalar( $raw[ $id ] ) ? sanitize_text_field( (string) $raw[ $id ] ) : '';
			$value = self::clean( $key, $value );
			if ( '' === $value || '0' === $value ) {
				delete_post_meta( $id, $key );
			} else {
				update_post_meta( $id, $key, $value );
			}
		}
	}

	/**
	 * Cleans one field value.
	 *
	 * @param string $key   Meta key.
	 * @param string $value Submitted value.
	 */
	private static function clean( string $key, string $value ): string {
		if ( self::ICON === $key ) {
			return Icon::exists( $value ) ? $value : '';
		}
		if ( self::IMAGE === $key || self::MEGA === $key ) {
			return (string) absint( $value );
		}
		if ( self::BADGE === $key ) {
			return in_array( $value, array( 'new', 'hot', 'custom' ), true ) ? $value : '';
		}
		if ( self::MEGA_WIDTH === $key ) {
			return in_array( $value, array( 'full', 'container', 'auto' ), true ) ? $value : 'container';
		}
		return $value;
	}

	/**
	 * The media picker for the image field, on the menus screen only.
	 *
	 * @param mixed $hook Admin page.
	 */
	public static function media( mixed $hook ): void {
		if ( 'nav-menus.php' !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_add_inline_script(
			'media-editor',
			'document.addEventListener("click",function(e){var b=e.target.closest(".um-menu-image");if(!b||!window.wp||!wp.media)return;e.preventDefault();var f=wp.media({multiple:false,library:{type:"image"}});f.on("select",function(){var a=f.state().get("selection").first().toJSON();var i=document.getElementById(b.dataset.target);if(i){i.value=a.id;}var n=b.parentNode.querySelector(".um-menu-image-name");if(n){n.textContent=a.filename||("#"+a.id);}});f.open();});'
		);
	}
}
