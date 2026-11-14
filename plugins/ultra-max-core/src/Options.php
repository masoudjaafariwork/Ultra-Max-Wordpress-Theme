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

		$sections['shop']    = self::shop();
		$sections['product'] = self::product();

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
	 * A range field.
	 *
	 * @param string $id      Field id.
	 * @param string $label   Label.
	 * @param int    $initial Default.
	 * @param int    $min     Lowest.
	 * @param int    $max     Highest.
	 * @param string $help    Help.
	 * @return array<string, mixed>
	 */
	private static function range( string $id, string $label, int $initial, int $min, int $max, string $help = '' ): array {
		$field = array(
			'id'      => $id,
			'type'    => 'range',
			'label'   => $label,
			'default' => $initial,
			'min'     => $min,
			'max'     => $max,
			'step'    => 1,
		);
		if ( '' !== $help ) {
			$field['help'] = $help;
		}
		return $field;
	}

	/**
	 * A switch.
	 *
	 * @param string $id      Field id.
	 * @param string $label   Label.
	 * @param bool   $initial Default.
	 * @param string $help    Help.
	 * @return array<string, mixed>
	 */
	private static function toggle( string $id, string $label, bool $initial = true, string $help = '' ): array {
		$field = array(
			'id'      => $id,
			'type'    => 'toggle',
			'label'   => $label,
			'default' => $initial,
		);
		if ( '' !== $help ) {
			$field['help'] = $help;
		}
		return $field;
	}

	/**
	 * The product page section (PDP-01 to PDP-26).
	 *
	 * @return array<string, mixed>
	 */
	private static function product(): array {
		$icons = array();
		foreach ( array( 'truck', 'cash', 'headset', 'return', 'verified', 'shield', 'gift', 'card', 'store', 'clock', 'box', 'percent', 'check' ) as $icon ) {
			$icons[ $icon ] = $icon;
		}
		$perks = array(
			array(
				'icon'  => 'truck',
				'label' => __( 'Express delivery', 'ultra-max-core' ),
			),
			array(
				'icon'  => 'cash',
				'label' => __( 'Pay on delivery', 'ultra-max-core' ),
			),
			array(
				'icon'  => 'headset',
				'label' => __( 'Support every day', 'ultra-max-core' ),
			),
			array(
				'icon'  => 'return',
				'label' => __( 'Seven days to return', 'ultra-max-core' ),
			),
			array(
				'icon'  => 'verified',
				'label' => __( 'Genuine products', 'ultra-max-core' ),
			),
		);
		/**
		 * Filters the groups of the product page section; later parts of the
		 * page add theirs.
		 *
		 * @param list<array<string, mixed>> $groups Groups.
		 */
		$groups = apply_filters(
			'ultra_max_product_settings',
			array(
				array(
					'id'       => 'product_layout',
					'title'    => __( 'Product page', 'ultra-max-core' ),
					'icon'     => 'layers',
					'features' => array( 'PDP-01', 'PDP-06', 'PDP-20', 'PDP-23', 'PDP-24' ),
					'module'   => Product\Product::MODULE,
					'fields'   => array(
						array(
							'id'      => 'product_gallery',
							'type'    => 'segmented',
							'label'   => __( 'Gallery', 'ultra-max-core' ),
							'help'    => __( 'Phones always swipe through the pictures.', 'ultra-max-core' ),
							'default' => 'side',
							'options' => self::options(
								array(
									'side'   => __( 'Thumbnails beside', 'ultra-max-core' ),
									'bottom' => __( 'Thumbnails under', 'ultra-max-core' ),
									'grid'   => __( 'All in a grid', 'ultra-max-core' ),
									'slider' => __( 'Slider', 'ultra-max-core' ),
								)
							),
						),
						self::toggle( 'product_zoom', __( 'Zoom pictures under the mouse', 'ultra-max-core' ) ),
						array(
							'id'      => 'product_sections',
							'type'    => 'segmented',
							'label'   => __( 'Introduction, specifications and reviews', 'ultra-max-core' ),
							'default' => 'sections',
							'options' => self::options(
								array(
									'sections'  => __( 'One page with sticky links', 'ultra-max-core' ),
									'tabs'      => __( 'Tabs', 'ultra-max-core' ),
									'accordion' => __( 'Folding panels', 'ultra-max-core' ),
								)
							),
						),
						array(
							'id'      => 'product_picker',
							'type'    => 'segmented',
							'label'   => __( 'Show options as', 'ultra-max-core' ),
							'help'    => __( 'Colour and picture attributes always show as swatches.', 'ultra-max-core' ),
							'default' => 'button',
							'options' => self::options(
								array(
									'button' => __( 'Buttons', 'ultra-max-core' ),
									'select' => __( 'Dropdowns', 'ultra-max-core' ),
								)
							),
						),
						self::range( 'product_key_count', __( 'Key features at the top', 'ultra-max-core' ), 6, 0, 12, __( 'Attributes marked as key features show first; without any, the first attributes show.', 'ultra-max-core' ) ),
						self::toggle( 'product_dimensions', __( 'Show weight and size in the specifications', 'ultra-max-core' ) ),
						array(
							'id'          => 'product_notice',
							'type'        => 'textarea',
							'label'       => __( 'Note under the key features', 'ultra-max-core' ),
							'help'        => __( 'Such as your return rules. Leave empty to show none.', 'ultra-max-core' ),
							'placeholder' => __( 'Opened products can be returned only when faulty.', 'ultra-max-core' ),
						),
						self::toggle( 'product_sticky', __( 'Keep the buy button in reach', 'ultra-max-core' ), true, __( 'A bar at the bottom of phones and a small box beside the sections on desktop.', 'ultra-max-core' ) ),
						self::toggle( 'product_share', __( 'Share button', 'ultra-max-core' ) ),
						self::toggle( 'product_similar', __( 'Similar and complementary products', 'ultra-max-core' ) ),
						self::range( 'product_related_count', __( 'Products in each slider', 'ultra-max-core' ), 10, 4, 24 ),
						self::toggle( 'product_recent', __( 'The shopper\'s recent views', 'ultra-max-core' ), true, __( 'Kept only in the shopper\'s browser.', 'ultra-max-core' ) ),
					),
				),
				array(
					'id'       => 'product_buy',
					'title'    => __( 'Buy box', 'ultra-max-core' ),
					'icon'     => 'cart',
					'features' => array( 'PDP-05', 'PDP-21', 'PDP-25' ),
					'module'   => Product\Product::MODULE,
					'fields'   => array(
						self::toggle( 'product_seller', __( 'Show the store as the seller', 'ultra-max-core' ) ),
						array(
							'id'          => 'product_seller_note',
							'type'        => 'text',
							'label'       => __( 'Line under the store name', 'ultra-max-core' ),
							'placeholder' => __( 'Sold and shipped by us', 'ultra-max-core' ),
						),
						array(
							'id'          => 'product_warranty',
							'type'        => 'text',
							'label'       => __( 'Default warranty', 'ultra-max-core' ),
							'help'        => __( 'A product can set its own on its edit screen.', 'ultra-max-core' ),
							'placeholder' => __( 'Seven day guarantee of genuine goods', 'ultra-max-core' ),
						),
						self::range( 'product_low_stock', __( 'Warn "only a few left" from', 'ultra-max-core' ), 5, 0, 50 ),
						self::toggle( 'product_quick_buy', __( '"Buy now" button that goes straight to checkout', 'ultra-max-core' ) ),
						self::range( 'product_qty_max', __( 'Most of one product in an order', 'ultra-max-core' ), 0, 0, 1000, __( '0 for no limit. A product can set its own minimum, maximum and step.', 'ultra-max-core' ) ),
						array(
							'id'       => 'product_perks',
							'type'     => 'repeater',
							'label'    => __( 'Promises under the buy box', 'ultra-max-core' ),
							'max_rows' => 6,
							'default'  => $perks,
							'fields'   => array(
								array(
									'id'      => 'icon',
									'type'    => 'select',
									'label'   => __( 'Icon', 'ultra-max-core' ),
									'default' => 'check',
									'options' => self::options( $icons ),
								),
								array(
									'id'    => 'label',
									'type'  => 'text',
									'label' => __( 'Text', 'ultra-max-core' ),
								),
							),
						),
					),
				),
			)
		);
		return array(
			'title'  => __( 'Product page', 'ultra-max-core' ),
			'icon'   => 'box',
			'order'  => 56,
			'groups' => is_array( $groups ) ? array_values( $groups ) : array(),
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
		$modules['single-product'] = array(
			'title'       => __( 'Product page', 'ultra-max-core' ),
			'description' => __( 'Gallery with zoom and video, colour and size choices, a buy box with delivery time, specifications, reviews with photos, questions, and a buy button that stays in reach. Off, WooCommerce\'s own product page shows.', 'ultra-max-core' ),
			'icon'        => 'box',
			'features'    => array( 'PDP-01', 'PDP-02', 'PDP-03', 'PDP-04', 'PDP-05', 'PDP-06', 'PDP-07', 'PDP-08', 'PDP-09', 'PDP-10', 'PDP-13', 'PDP-14', 'PDP-15', 'PDP-16', 'PDP-17', 'PDP-18', 'PDP-19', 'PDP-20', 'PDP-21', 'PDP-22', 'PDP-23', 'PDP-24', 'PDP-25', 'PDP-26', 'BLD-04' ),
			'default'     => true,
			'section'     => 'product',
			'assets'      => array( 'product', 'reviews' ),
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
