<?php
/**
 * The core plugin's settings sections and modules.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core;

use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Lib\Settings\Schema;
use UltraMax\Core\Shop\Filters;
use UltraMax\Core\Store\Cards;

/**
 * Declares custom code (ADM-08), backups (ADM-02) and the core modules.
 */
final class Options {

	/**
	 * Registers the declarations.
	 */
	public static function register(): void {
		add_filter( Schema::FILTER, array( self::class, 'schema' ), 20 );
		add_filter( Modules::FILTER, array( self::class, 'modules' ), 20 );
	}

	/**
	 * Adds the core sections.
	 *
	 * @param mixed $sections Sections keyed by id.
	 * @return array<mixed>
	 */
	public static function schema( mixed $sections ): array {
		$sections = is_array( $sections ) ? $sections : array();
		$scripts  = current_user_can( 'unfiltered_html' ) ? '' : ' ' . __( 'Only administrators who may post unfiltered HTML can change this.', 'ultra-max-core' );

		$sections['code'] = array(
			'title'  => __( 'Custom code', 'ultra-max-core' ),
			'icon'   => 'code',
			'order'  => 90,
			'groups' => array(
				array(
					'id'          => 'custom_code',
					'title'       => __( 'Code for the whole site', 'ultra-max-core' ),
					'icon'        => 'code',
					'features'    => array( 'ADM-08' ),
					'module'      => 'custom-code',
					'description' => __( 'Each post and page also has its own CSS and JavaScript boxes in the editor.', 'ultra-max-core' ),
					'fields'      => array(
						array(
							'id'          => 'code_css',
							'type'        => 'code',
							'language'    => 'css',
							'label'       => __( 'Custom CSS', 'ultra-max-core' ),
							'help'        => __( 'Added to every page after the theme styles.', 'ultra-max-core' ),
							'placeholder' => '.um-site-header { }',
							'keywords'    => 'css style',
						),
						array(
							'id'          => 'code_js',
							'type'        => 'code',
							'language'    => 'js',
							'label'       => __( 'Custom JavaScript', 'ultra-max-core' ),
							'help'        => __( 'Runs at the end of every page. Write plain JavaScript without a script tag.', 'ultra-max-core' ) . $scripts,
							'placeholder' => "console.log( 'hello' );",
							'keywords'    => 'js javascript script',
						),
						array(
							'id'       => 'code_head',
							'type'     => 'code',
							'language' => 'html',
							'label'    => __( 'Code in the page head', 'ultra-max-core' ),
							'help'     => __( 'For verification tags and analytics snippets.', 'ultra-max-core' ) . $scripts,
							'keywords' => 'head analytics google tag manager verification',
						),
						array(
							'id'       => 'code_body',
							'type'     => 'code',
							'language' => 'html',
							'label'    => __( 'Code after the opening body tag', 'ultra-max-core' ),
							'help'     => $scripts,
						),
						array(
							'id'       => 'code_footer',
							'type'     => 'code',
							'language' => 'html',
							'label'    => __( 'Code before the closing body tag', 'ultra-max-core' ),
							'help'     => $scripts,
						),
					),
				),
			),
		);

		$sections['shop'] = self::shop();

		$sections['backup'] = array(
			'title'  => __( 'Backup and reset', 'ultra-max-core' ),
			'icon'   => 'history',
			'order'  => 100,
			'groups' => array(
				array(
					'id'       => 'backups',
					'title'    => __( 'Backup and reset', 'ultra-max-core' ),
					'icon'     => 'history',
					'features' => array( 'ADM-02' ),
					'fields'   => array(
						array(
							'id'    => 'backups_panel',
							'type'  => 'backups',
							'label' => __( 'Backups', 'ultra-max-core' ),
						),
					),
				),
			),
		);

		return $sections;
	}

	/**
	 * Options of a select field from a value => label map.
	 *
	 * @param array<string, string> $map Values and labels.
	 * @return list<array{value: string, label: string}>
	 */
	private static function options( array $map ): array {
		$out = array();
		foreach ( $map as $value => $label ) {
			$out[] = array(
				'value' => (string) $value,
				'label' => $label,
			);
		}
		return $out;
	}

	/**
	 * The product list and search section (PLP-01 to PLP-13, SRCH-01 to SRCH-04).
	 *
	 * @return array<string, mixed>
	 */
	private static function shop(): array {
		$number = static fn ( string $id, string $label, int $initial, int $min, int $max, int $step = 1, string $help = '' ): array => array_filter(
			array(
				'id'      => $id,
				'type'    => 'range',
				'label'   => $label,
				'help'    => $help,
				'default' => $initial,
				'min'     => $min,
				'max'     => $max,
				'step'    => $step,
			),
			static fn ( mixed $value ): bool => '' !== $value
		);
		return array(
			'title'  => __( 'Product lists and search', 'ultra-max-core' ),
			'icon'   => 'grid',
			'order'  => 55,
			'groups' => array(
				array(
					'id'       => 'shop_list',
					'title'    => __( 'Product lists', 'ultra-max-core' ),
					'icon'     => 'grid',
					'features' => array( 'PLP-04', 'PLP-05', 'PLP-07', 'PLP-08', 'PLP-09' ),
					'module'   => 'shop-archive',
					'fields'   => array(
						array(
							'id'      => 'shop_card',
							'type'    => 'select',
							'label'   => __( 'Card design', 'ultra-max-core' ),
							'help'    => __( 'A card you designed with Elementor (Layouts, Product card) is offered here too.', 'ultra-max-core' ),
							'default' => 'classic',
							'options' => self::options( array_merge( Cards::designs(), Cards::layouts() ) ),
						),
						$number( 'shop_columns', __( 'Columns on desktop', 'ultra-max-core' ), 4, 3, 5 ),
						$number( 'shop_columns_tablet', __( 'Columns on tablet', 'ultra-max-core' ), 3, 2, 4 ),
						array(
							'id'      => 'shop_mobile',
							'type'    => 'segmented',
							'label'   => __( 'On phones', 'ultra-max-core' ),
							'default' => 'list',
							'options' => self::options(
								array(
									'list' => __( 'Rows', 'ultra-max-core' ),
									'two'  => __( 'Two columns', 'ultra-max-core' ),
								)
							),
						),
						$number( 'shop_per_page', __( 'Products per page', 'ultra-max-core' ), 24, 8, 96, 4 ),
						array(
							'id'      => 'shop_pagination',
							'type'    => 'segmented',
							'label'   => __( 'More products', 'ultra-max-core' ),
							'help'    => __( 'Page links always stay under the list for search engines.', 'ultra-max-core' ),
							'default' => 'more',
							'options' => self::options(
								array(
									'more'     => __( '"Show more" button', 'ultra-max-core' ),
									'infinite' => __( 'Load while scrolling', 'ultra-max-core' ),
									'pages'    => __( 'Page links', 'ultra-max-core' ),
								)
							),
						),
						array(
							'id'      => 'shop_view',
							'type'    => 'segmented',
							'label'   => __( 'Show products as', 'ultra-max-core' ),
							'default' => 'grid',
							'options' => self::options(
								array(
									'grid' => __( 'Grid', 'ultra-max-core' ),
									'list' => __( 'Rows', 'ultra-max-core' ),
								)
							),
						),
						array(
							'id'      => 'shop_view_toggle',
							'type'    => 'toggle',
							'label'   => __( 'Let shoppers switch between grid and rows', 'ultra-max-core' ),
							'default' => true,
						),
						array(
							'id'      => 'shop_subcategories',
							'type'    => 'toggle',
							'label'   => __( 'Show subcategories with pictures above the list', 'ultra-max-core' ),
							'default' => true,
						),
					),
				),
				array(
					'id'          => 'shop_filters_group',
					'title'       => __( 'Filters', 'ultra-max-core' ),
					'icon'        => 'filter',
					'features'    => array( 'PLP-01', 'PLP-02', 'PLP-06', 'PLP-12' ),
					'module'      => 'shop-archive',
					'description' => __( 'A category can choose its own filters on its edit screen; its subcategories follow it.', 'ultra-max-core' ),
					'fields'      => array(
						array(
							'id'       => 'shop_filters',
							'type'     => 'repeater',
							'label'    => __( 'Filters and their order', 'ultra-max-core' ),
							'help'     => __( 'Drag to change the order. Filters with nothing to choose in a list are hidden there.', 'ultra-max-core' ),
							'max_rows' => 30,
							'default'  => array_map( static fn ( string $key ): array => array( 'filter' => $key ), Filters::defaults() ),
							'fields'   => array(
								array(
									'id'      => 'filter',
									'type'    => 'select',
									'label'   => __( 'Filter', 'ultra-max-core' ),
									'default' => 'brand',
									'options' => self::options( Filters::choices() ),
								),
							),
						),
						array(
							'id'      => 'shop_index_single',
							'type'    => 'toggle',
							'label'   => __( 'Let search engines index a list with one chosen value', 'ultra-max-core' ),
							'help'    => __( 'Such as "headphones, brand Lumo". Other filter combinations are never indexed.', 'ultra-max-core' ),
							'default' => true,
						),
					),
				),
				array(
					'id'       => 'live_search',
					'title'    => __( 'Search', 'ultra-max-core' ),
					'icon'     => 'search',
					'features' => array( 'SRCH-01', 'SRCH-02', 'SRCH-03', 'SRCH-04' ),
					'module'   => 'live-search',
					'fields'   => array(
						$number( 'search_products', __( 'Products in suggestions', 'ultra-max-core' ), 6, 3, 10 ),
						$number( 'search_min', __( 'Letters before suggestions show', 'ultra-max-core' ), 2, 1, 4 ),
						array(
							'id'      => 'search_posts',
							'type'    => 'toggle',
							'label'   => __( 'Suggest blog posts too', 'ultra-max-core' ),
							'default' => true,
						),
						array(
							'id'          => 'search_popular',
							'type'        => 'textarea',
							'label'       => __( 'Popular searches', 'ultra-max-core' ),
							'help'        => __( 'One per line. The phone search shows them before the shopper types.', 'ultra-max-core' ),
							'placeholder' => __( "Headphones\nSmart watch", 'ultra-max-core' ),
						),
					),
				),
			),
		);
	}

	/**
	 * Adds the core modules.
	 *
	 * @param mixed $modules Modules keyed by id.
	 * @return array<mixed>
	 */
	public static function modules( mixed $modules ): array {
		$modules = is_array( $modules ) ? $modules : array();

		$modules['custom-code']    = array(
			'title'       => __( 'Custom code', 'ultra-max-core' ),
			'description' => __( 'Your own CSS, JavaScript and head or footer code, for the whole site and for each page.', 'ultra-max-core' ),
			'icon'        => 'code',
			'features'    => array( 'ADM-08' ),
			'default'     => true,
			'section'     => 'code',
			'product'     => 'ultra-max-core',
		);
		$modules['layout-builder'] = array(
			'title'       => __( 'Layout builder', 'ultra-max-core' ),
			'description' => __( 'Design the header, footer and reusable blocks with Elementor, and choose where each one shows.', 'ultra-max-core' ),
			'icon'        => 'layers',
			'features'    => array( 'BLD-01', 'BLD-02', 'BLD-03', 'BLD-09', 'HDR-01', 'HDR-04' ),
			'default'     => true,
			'product'     => 'ultra-max-core',
		);
		$modules['widgets']        = array(
			'title'       => __( 'Store widgets', 'ultra-max-core' ),
			'description' => __( 'Header, home page and general widgets for Elementor, also usable as shortcodes. Each widget loads its files only on pages that use it.', 'ultra-max-core' ),
			'icon'        => 'grid',
			'features'    => array( 'HDR-02', 'HOME-01', 'HOME-12', 'BLD-08', 'BLD-10', 'BLD-12' ),
			'default'     => true,
			'product'     => 'ultra-max-core',
		);
		$modules['shop-archive']   = array(
			'title'       => __( 'Product lists', 'ultra-max-core' ),
			'description' => __( 'Shop, category, brand and search pages with instant filters, sorting, a grid or rows, "show more" and phone sheets. Off, WooCommerce\'s own list shows.', 'ultra-max-core' ),
			'icon'        => 'filter',
			'features'    => array( 'PLP-01', 'PLP-02', 'PLP-03', 'PLP-04', 'PLP-05', 'PLP-06', 'PLP-07', 'PLP-08', 'PLP-09', 'PLP-10', 'PLP-11', 'PLP-12', 'PLP-13', 'BLD-05' ),
			'default'     => true,
			'section'     => 'shop',
			'assets'      => array( 'shop' ),
			'product'     => 'ultra-max-core',
		);
		$modules['live-search']    = array(
			'title'       => __( 'Live search', 'ultra-max-core' ),
			'description' => __( 'Suggestions while typing, search by product code and attributes, Persian letter and digit matching, and a full screen phone search.', 'ultra-max-core' ),
			'icon'        => 'search',
			'features'    => array( 'SRCH-01', 'SRCH-02', 'SRCH-03', 'SRCH-04' ),
			'default'     => true,
			'section'     => 'shop',
			'assets'      => array( 'search' ),
			'product'     => 'ultra-max-core',
		);
		$modules['duplicate']      = array(
			'title'       => __( 'Duplicate posts and pages', 'ultra-max-core' ),
			'description' => __( 'A "Duplicate" link in the lists of posts and pages makes a draft copy with all its settings.', 'ultra-max-core' ),
			'icon'        => 'copy',
			'features'    => array( 'ADM-09' ),
			'default'     => true,
			'product'     => 'ultra-max-core',
		);
		return $modules;
	}
}
