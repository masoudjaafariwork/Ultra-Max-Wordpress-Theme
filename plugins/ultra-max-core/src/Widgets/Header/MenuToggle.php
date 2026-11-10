<?php
/**
 * Mobile menu widget (HDR-02 hamburger menu, HDR-09).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

use UltraMax\Core\Lib\UI\HeaderAction;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Image;
use UltraMax\Core\Lib\UI\MobileMenu;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Store\Categories;
use UltraMax\Core\Store\Menus;
use UltraMax\Core\Widgets\Values;

/**
 * The menu button of phone headers. It opens a side drawer with the
 * account link, quick links and the product categories (or a menu) in as
 * many folding levels as they have.
 */
final class MenuToggle extends HeaderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'menu-toggle';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Mobile menu', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-menu-toggle';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HDR-02', 'HDR-09' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		$quick = static fn ( string $label, string $icon ): array => array(
			'label' => $label,
			'icon'  => $icon,
		);
		return array(
			array(
				'id'     => 'menu_section',
				'label'  => __( 'Menu', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'      => 'source',
						'type'    => 'select',
						'label'   => __( 'Show', 'ultra-max-core' ),
						'default' => 'categories',
						'options' => array(
							'categories' => __( 'Product categories', 'ultra-max-core' ),
							'menu'       => __( 'A menu', 'ultra-max-core' ),
						),
					),
					array(
						'id'        => 'menu',
						'type'      => 'select',
						'label'     => __( 'Menu', 'ultra-max-core' ),
						'options'   => Menus::choices(),
						'default'   => '',
						'condition' => array( 'source' => 'menu' ),
					),
					array(
						'id'      => 'heading',
						'type'    => 'text',
						'label'   => __( 'Heading above the list', 'ultra-max-core' ),
						'default' => __( 'Product categories', 'ultra-max-core' ),
					),
					array(
						'id'      => 'account',
						'type'    => 'switch',
						'label'   => __( 'Show the account link', 'ultra-max-core' ),
						'default' => true,
					),
					array(
						'id'          => 'quick',
						'type'        => 'repeater',
						'label'       => __( 'Quick links', 'ultra-max-core' ),
						'title_field' => 'label',
						'fields'      => array(
							array(
								'id'    => 'label',
								'type'  => 'text',
								'label' => __( 'Text', 'ultra-max-core' ),
							),
							array(
								'id'      => 'icon',
								'type'    => 'icon',
								'label'   => __( 'Icon', 'ultra-max-core' ),
								'default' => 'star',
							),
							array(
								'id'    => 'link',
								'type'  => 'url',
								'label' => __( 'Link', 'ultra-max-core' ),
							),
						),
						'default'     => array(
							$quick( __( 'Offers', 'ultra-max-core' ), 'fire' ),
							$quick( __( 'Discounts', 'ultra-max-core' ), 'percent' ),
							$quick( __( 'Best sellers', 'ultra-max-core' ), 'star' ),
							$quick( __( 'Track order', 'ultra-max-core' ), 'box' ),
						),
					),
				),
			),
		);
	}

	/**
	 * Category nodes as drawer items.
	 *
	 * @param list<array<mixed>> $nodes Category nodes.
	 * @return list<array{label: string, href: string, image?: Html, children?: list<array<string, mixed>>}>
	 */
	private static function categories( array $nodes ): array {
		$out = array();
		foreach ( $nodes as $raw ) {
			$node  = Categories::node( $raw );
			$out[] = array(
				'label'    => $node['name'],
				'href'     => $node['href'],
				'image'    => Image::render(
					array(
						'id'   => $node['image'],
						'size' => 'thumbnail',
					)
				),
				'children' => self::categories( $node['children'] ),
			);
		}
		return $out;
	}

	/**
	 * Menu nodes as drawer items.
	 *
	 * @param list<array<mixed>> $nodes Menu nodes.
	 * @return list<array{label: string, href: string, current?: bool, children?: list<array<string, mixed>>}>
	 */
	private static function menu( array $nodes ): array {
		$out = array();
		foreach ( $nodes as $node ) {
			$children = is_array( $node['children'] ?? null ) ? array_values( array_filter( $node['children'], 'is_array' ) ) : array();
			$out[]    = array(
				'label'    => is_string( $node['label'] ?? null ) ? $node['label'] : '',
				'href'     => is_string( $node['href'] ?? null ) ? $node['href'] : '#',
				'current'  => true === ( $node['current'] ?? false ),
				'children' => self::menu( $children ),
			);
		}
		return $out;
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		static $count = 0;
		++$count;
		$id    = 'um-mobile-menu-' . $count;
		$items = 'menu' === $settings->string( 'source' )
			? self::menu( Menus::tree( Menus::menu_id( $settings->string( 'menu' ) ), 3 ) )
			: self::categories( Categories::tree() );
		$quick = array();
		foreach ( $settings->rows( 'quick' ) as $row ) {
			if ( '' !== $row->string( 'label' ) ) {
				$quick[] = array(
					'label' => $row->string( 'label' ),
					'href'  => '' === $row->href( 'link' ) ? '#' : $row->href( 'link' ),
					'icon'  => '' === $row->string( 'icon' ) ? 'star' : $row->string( 'icon' ),
				);
			}
		}
		$props = array(
			'id'        => $id,
			'title'     => UI::logo(
				array(
					'name'     => get_bloginfo( 'name' ),
					'href'     => home_url( '/' ),
					'image_id' => is_numeric( get_theme_mod( 'custom_logo', 0 ) ) ? (int) get_theme_mod( 'custom_logo', 0 ) : 0,
				)
			),
			'label'     => __( 'Mobile menu', 'ultra-max-core' ),
			'items'     => $items,
			/* translators: %s: category or menu item name. */
			'all_label' => __( 'All of %s', 'ultra-max-core' ),
			'heading'   => $settings->string( 'heading' ),
			'quick'     => $quick,
		);
		if ( $settings->bool( 'account' ) && function_exists( 'wc_get_page_permalink' ) ) {
			$props['account'] = is_user_logged_in()
				? array(
					'href'  => wc_get_page_permalink( 'myaccount' ),
					'title' => __( 'My account', 'ultra-max-core' ),
					'text'  => wp_get_current_user()->display_name,
				)
				: array(
					'href'  => wc_get_page_permalink( 'myaccount' ),
					'title' => __( 'Log in or sign up', 'ultra-max-core' ),
					'text'  => __( 'To follow your orders, log in to your account', 'ultra-max-core' ),
				);
		}
		return Html::from(
			array(
				HeaderAction::render(
					array(
						'label' => __( 'Menu', 'ultra-max-core' ),
						'icon'  => 'menu',
						'open'  => $id,
					)
				),
				MobileMenu::render( $props ),
			)
		);
	}
}
