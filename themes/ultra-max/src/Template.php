<?php
/**
 * Pieces the template files print.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

namespace UltraMax\Theme;

use UltraMax\Theme\Lib\Settings\Store;
use UltraMax\Theme\Lib\UI\Box;
use UltraMax\Theme\Lib\UI\Frame;
use UltraMax\Theme\Lib\UI\Html;
use UltraMax\Theme\Lib\UI\SiteHeader;
use UltraMax\Theme\Lib\UI\UI;

/**
 * Composes components into the parts of a page. Template files call these
 * and add only layout classes, as the component rules require.
 */
final class Template {

	/**
	 * A whole number from a value WordPress returns as mixed.
	 *
	 * @param mixed $value Value.
	 */
	public static function int( mixed $value ): int {
		return is_numeric( $value ) ? (int) $value : 0;
	}

	/**
	 * Which sidebar setting applies to the current page.
	 */
	public static function sidebar_position(): string {
		if ( is_page() ) {
			$key = 'sidebar_page';
		} elseif ( is_singular() ) {
			$key = 'sidebar_post';
		} else {
			$key = 'sidebar_blog';
		}
		$position = Store::string( $key );
		return in_array( $position, array( 'start', 'end' ), true ) && is_active_sidebar( 'sidebar' ) ? $position : 'none';
	}

	/**
	 * Opens the content area with its sidebar column (CORE-07).
	 */
	public static function layout_open(): void {
		$sidebar = self::sidebar_position();
		$grid    = match ( $sidebar ) {
			'start' => 'grid items-start gap-6 lg:grid-cols-[var(--um-sidebar)_minmax(0,1fr)]',
			'end'   => 'grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_var(--um-sidebar)]',
			default => 'grid',
		};
		printf(
			'<div class="mx-auto w-full max-w-(--um-container) px-(--um-gutter) pb-6"><div class="%s"><div class="min-w-0%s">',
			esc_attr( $grid ),
			'start' === $sidebar ? ' lg:order-last' : ''
		);
	}

	/**
	 * Closes the content area and prints the sidebar.
	 */
	public static function layout_close(): void {
		echo '</div>';
		if ( 'none' !== self::sidebar_position() ) {
			get_sidebar();
		}
		echo '</div></div>';
	}

	/**
	 * Classes of the sidebar column.
	 */
	public static function sidebar_classes(): string {
		return Store::bool( 'sticky_sidebar' ) ? 'grid min-w-0 gap-4 lg:sticky lg:top-[calc(var(--wp-admin--admin-bar--height,0px)+var(--um-sticky-offset,24px))]' : 'grid min-w-0 gap-4';
	}

	/**
	 * Menu items of a location as a tree of two levels.
	 *
	 * @param string $location Menu location.
	 * @return list<array{label: string, href: string, current?: bool, children?: list<array{label: string, href: string, current?: bool}>}>
	 */
	public static function menu_items( string $location ): array {
		$locations = get_nav_menu_locations();
		if ( ! isset( $locations[ $location ] ) ) {
			return array();
		}
		$items = wp_get_nav_menu_items( $locations[ $location ] );
		if ( ! is_array( $items ) ) {
			return array();
		}
		_wp_menu_item_classes_by_context( $items );
		$tree     = array();
		$children = array();
		foreach ( $items as $item ) {
			if ( ! $item instanceof \WP_Post ) {
				continue;
			}
			$classes = is_array( $item->classes ?? null ) ? $item->classes : array();
			$entry   = array(
				'label'   => wp_strip_all_tags( (string) ( $item->title ?? '' ) ),
				'href'    => (string) ( $item->url ?? '' ),
				'current' => in_array( 'current-menu-item', $classes, true ) || in_array( 'current-menu-ancestor', $classes, true ),
			);
			$parent  = self::int( $item->menu_item_parent ?? 0 );
			if ( 0 === $parent ) {
				$tree[ $item->ID ] = $entry;
			} else {
				$children[ $parent ][] = $entry;
			}
		}
		$out = array();
		foreach ( $tree as $id => $entry ) {
			if ( isset( $children[ $id ] ) ) {
				$entry['children'] = $children[ $id ];
			}
			$out[] = $entry;
		}
		return $out;
	}

	/**
	 * The store logo.
	 *
	 * @param 'md'|'lg' $size Size.
	 */
	public static function logo( string $size = 'md' ): Html {
		return UI::logo(
			array(
				'name'     => get_bloginfo( 'name' ),
				'href'     => home_url( '/' ),
				'image_id' => self::int( get_theme_mod( 'custom_logo', 0 ) ),
				'size'     => $size,
			)
		);
	}

	/**
	 * The search form.
	 *
	 * @param string           $id      Field id.
	 * @param 'soft'|'outline' $variant Look.
	 */
	public static function search_form( string $id, string $variant = 'soft' ): Html {
		// Through get_search_form() so other plugins can filter the form; Theme::search_form() renders it.
		return Html::wp(
			(string) get_search_form(
				array(
					'echo'       => false,
					'aria_label' => __( 'Search', 'ultra-max' ),
					'um_id'      => $id,
					'um_variant' => $variant,
				)
			)
		);
	}

	/**
	 * Our search form component, for the get_search_form filter.
	 *
	 * @param string           $id      Field id.
	 * @param 'soft'|'outline' $variant Look.
	 */
	public static function search_component( string $id, string $variant ): Html {
		return UI::search_form(
			array(
				'id'          => $id,
				'action'      => home_url( '/' ),
				'label'       => __( 'Search', 'ultra-max' ),
				'submit'      => __( 'Search', 'ultra-max' ),
				'value'       => get_search_query(),
				'placeholder' => __( 'Search the store…', 'ultra-max' ),
				'variant'     => $variant,
			)
		);
	}

	/**
	 * Prints a page location from a designed layout, if there is one.
	 *
	 * Elementor Pro's Theme Builder is asked first (BLD-09), then the Ultra
	 * Max layout builder through the `ultra_max_do_location` filter. The
	 * builder may cover only some devices, for example a mobile header; the
	 * theme then prints its own part for the other devices.
	 *
	 * @param string $location header, footer, single or archive.
	 * @return array{desktop: bool, tablet: bool, mobile: bool} Devices that still need the theme's own part.
	 */
	public static function location( string $location ): array {
		$none = array(
			'desktop' => false,
			'tablet'  => false,
			'mobile'  => false,
		);
		if ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( $location ) ) {
			return $none;
		}
		/**
		 * Prints designed layouts for a location and returns the devices they cover.
		 *
		 * @param array{desktop: bool, tablet: bool, mobile: bool} $covered  Devices already covered.
		 * @param string                                            $location Location.
		 */
		$covered = apply_filters( 'ultra_max_do_location', $none, $location );
		$covered = is_array( $covered ) ? $covered : array();
		return array(
			'desktop' => true !== ( $covered['desktop'] ?? false ),
			'tablet'  => true !== ( $covered['tablet'] ?? false ),
			'mobile'  => true !== ( $covered['mobile'] ?? false ),
		);
	}

	/**
	 * Whether any device is left for the theme's own part.
	 *
	 * @param array{desktop: bool, tablet: bool, mobile: bool} $devices Devices left.
	 */
	public static function any( array $devices ): bool {
		return $devices['desktop'] || $devices['tablet'] || $devices['mobile'];
	}

	/**
	 * Visibility classes for the devices left, or none when all are.
	 *
	 * @param array{desktop: bool, tablet: bool, mobile: bool} $devices Devices left.
	 */
	public static function only( array $devices ): string {
		return UI::visibility( $devices );
	}

	/**
	 * The site header and the mobile menu drawer.
	 *
	 * @param array{desktop: bool, tablet: bool, mobile: bool}|null $devices Devices to show it on; all when null.
	 */
	public static function header( ?array $devices = null ): void {
		$items   = self::menu_items( 'primary' );
		$actions = array();
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$actions[] = SiteHeader::action( wc_get_page_permalink( 'myaccount' ), __( 'My account', 'ultra-max' ), 'user' );
			$count     = function_exists( 'WC' ) && null !== WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
			$actions[] = SiteHeader::action( wc_get_cart_url(), __( 'Cart', 'ultra-max' ), 'cart', $count > 0 ? $count : null );
		}

		$props = array(
			'logo'       => self::logo(),
			'menu_label' => __( 'Menu', 'ultra-max' ),
			'search'     => Store::bool( 'header_search' ) ? self::search_form( 'um-header-search' ) : Html::none(),
			'nav'        => UI::menu(
				array(
					'label' => __( 'Main menu', 'ultra-max' ),
					'items' => $items,
				)
			),
			'actions'    => $actions,
			'sticky'     => Store::bool( 'sticky_header' ),
			'class'      => null === $devices ? '' : self::only( $devices ),
		);
		if ( array() !== $items ) {
			$props['drawer'] = 'um-menu-drawer';
		}
		UI::site_header( $props )->print();

		if ( array() !== $items ) {
			UI::drawer(
				array(
					'id'       => 'um-menu-drawer',
					'title'    => self::logo(),
					'side'     => 'start',
					'class'    => 'um-mobile-drawer',
					'children' => UI::menu(
						array(
							'label'   => __( 'Main menu', 'ultra-max' ),
							'items'   => $items,
							'variant' => 'tree',
						)
					),
				)
			)->print();
		}
	}

	/**
	 * The site footer.
	 *
	 * @param array{desktop: bool, tablet: bool, mobile: bool}|null $devices Devices to show it on; all when null.
	 */
	public static function footer( ?array $devices = null ): void {
		$columns = array();
		for ( $i = 1; $i <= 4; $i++ ) {
			if ( is_active_sidebar( 'footer-' . $i ) ) {
				ob_start();
				dynamic_sidebar( 'footer-' . $i );
				$columns[] = Html::wp( (string) ob_get_clean() );
			}
		}
		UI::site_footer(
			array(
				'logo'      => self::logo(),
				'about'     => get_bloginfo( 'description' ),
				'menu'      => UI::menu(
					array(
						'label'   => __( 'Footer menu', 'ultra-max' ),
						'items'   => self::menu_items( 'footer' ),
						'variant' => 'list',
					)
				),
				'columns'   => $columns,
				'copyright' => sprintf(
					/* translators: 1: year, 2: site name. */
					__( '© %1$s %2$s. All rights reserved.', 'ultra-max' ),
					wp_date( 'Y' ),
					get_bloginfo( 'name' )
				),
				'class'     => null === $devices ? '' : self::only( $devices ),
			)
		)->print();
	}

	/**
	 * The page title area with breadcrumbs (CORE-08).
	 *
	 * @param string|Html $title       Title.
	 * @param string|Html $description Description under the title.
	 * @param Html|null   $meta        Meta line.
	 * @param bool|null   $show_title  Whether to show the title; the setting when null.
	 */
	public static function page_header( string|Html $title, string|Html $description = '', ?Html $meta = null, ?bool $show_title = null ): void {
		$devices = Store::devices( 'breadcrumbs' );
		$crumbs  = Html::none();
		if ( $devices['desktop'] || $devices['tablet'] || $devices['mobile'] ) {
			$crumbs = UI::breadcrumbs(
				array(
					'label' => __( 'Breadcrumbs', 'ultra-max' ),
					'items' => Breadcrumbs::trail(),
					'class' => UI::visibility( $devices ),
				)
			);
		}
		$variant = Store::string( 'page_title_style' );
		$align   = Store::string( 'page_title_align' );
		UI::page_header(
			array(
				'title'       => $title,
				'description' => $description,
				'breadcrumbs' => $crumbs,
				'meta'        => $meta ?? Html::none(),
				'variant'     => in_array( $variant, array( 'plain', 'soft', 'brand' ), true ) ? $variant : 'plain',
				'align'       => 'center' === $align ? 'center' : 'start',
				'show_title'  => $show_title ?? Store::bool( 'page_title' ),
			)
		)->print();
	}

	/**
	 * Date, author, category and comments of a post.
	 *
	 * @param \WP_Post $post      Post.
	 * @param bool     $with_more Whether to add author and comments.
	 */
	public static function post_meta( \WP_Post $post, bool $with_more = false ): Html {
		$items = array(
			array(
				'icon'         => 'calendar',
				'label'        => (string) get_the_date( '', $post ),
				'datetime'     => (string) get_the_date( 'c', $post ),
				'hidden_label' => __( 'Published on', 'ultra-max' ),
			),
		);
		if ( $with_more ) {
			$items[] = array(
				'icon'         => 'user',
				'label'        => get_the_author_meta( 'display_name', (int) $post->post_author ),
				'href'         => get_author_posts_url( (int) $post->post_author ),
				'hidden_label' => __( 'Author', 'ultra-max' ),
			);
		}
		$categories = 'post' === $post->post_type ? get_the_category( $post->ID ) : array();
		if ( array() !== $categories ) {
			$link    = get_category_link( $categories[0] );
			$items[] = array(
				'icon'         => 'folder',
				'label'        => $categories[0]->name,
				'href'         => $link,
				'hidden_label' => __( 'Category', 'ultra-max' ),
			);
		}
		if ( $with_more && ( comments_open( $post ) || get_comments_number( $post ) > 0 ) ) {
			$count   = (int) get_comments_number( $post );
			$items[] = array(
				'icon'  => 'chat',
				/* translators: %s: number of comments. */
				'label' => sprintf( _n( '%s comment', '%s comments', $count, 'ultra-max' ), number_format_i18n( $count ) ),
				'href'  => get_comments_link( $post ),
			);
		}
		return UI::post_meta( array( 'items' => $items ) );
	}

	/**
	 * The current post as a card.
	 *
	 * @param int $index Position on the page; the first card loads its image first.
	 */
	public static function post_card( int $index ): Html {
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return Html::none();
		}
		$title = get_the_title( $post );
		return UI::post_card(
			array(
				'title'    => '' === $title ? __( '(no title)', 'ultra-max' ) : wp_strip_all_tags( $title ),
				'href'     => (string) get_permalink( $post ),
				'image_id' => (int) get_post_thumbnail_id( $post ),
				'excerpt'  => wp_strip_all_tags( get_the_excerpt( $post ) ),
				'meta'     => self::post_meta( $post ),
				'badge'    => is_sticky( $post->ID ) && is_home() ? __( 'Featured', 'ultra-max' ) : '',
				'priority' => 0 === $index,
				'layout'   => is_search() ? 'list' : 'grid',
			)
		);
	}

	/**
	 * Page links of the main query.
	 */
	public static function pagination(): void {
		global $wp_query;
		$total = $wp_query instanceof \WP_Query ? (int) $wp_query->max_num_pages : 1;
		$big   = 999999999;
		UI::pagination(
			array(
				'label'    => __( 'Pages', 'ultra-max' ),
				'current'  => max( 1, self::int( get_query_var( 'paged' ) ) ),
				'total'    => $total,
				'base'     => str_replace( (string) $big, '%#%', get_pagenum_link( $big, false ) ),
				'first'    => get_pagenum_link( 1, false ),
				'previous' => __( 'Previous', 'ultra-max' ),
				'next'     => __( 'Next', 'ultra-max' ),
			)
		)->print();
	}

	/**
	 * Arguments of the sidebar widget areas.
	 *
	 * @return array{before_widget: string, after_widget: string, before_title: string, after_title: string}
	 */
	public static function widget_args(): array {
		return Box::widget();
	}

	/**
	 * Arguments of the footer widget areas.
	 *
	 * @return array{before_widget: string, after_widget: string, before_title: string, after_title: string}
	 */
	public static function footer_widget_args(): array {
		return Box::footer_widget();
	}

	/**
	 * Opens the page frame (CORE-07).
	 */
	public static function frame_open(): void {
		Frame::skip_link( 'um-main', __( 'Skip to content', 'ultra-max' ) )->print();
		Frame::open( array( 'boxed' => 'boxed' === Store::string( 'site_layout' ) ) )->print();
	}

	/**
	 * Closes the page frame.
	 */
	public static function frame_close(): void {
		Frame::close()->print();
	}

	/**
	 * Opens the main content area.
	 */
	public static function main_open(): void {
		Frame::main_open()->print();
	}

	/**
	 * Closes the main content area.
	 */
	public static function main_close(): void {
		Frame::main_close()->print();
	}

	/**
	 * Body classes of the frame.
	 *
	 * @param mixed $classes Classes.
	 * @return array<mixed>
	 */
	public static function body_class( mixed $classes ): array {
		$classes = is_array( $classes ) ? $classes : array();
		return array_merge( $classes, Frame::body_classes( 'boxed' === Store::string( 'site_layout' ) ) );
	}
}
