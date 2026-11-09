<?php
/**
 * The theme's settings and modules.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

namespace UltraMax\Theme;

use UltraMax\Theme\Lib\Settings\Schema;
use UltraMax\Theme\Lib\Settings\Modules;

/**
 * Declares the theme's sections of the settings panel and its modules.
 *
 * The panel lives in the core plugin. Without the plugin the theme still
 * reads these defaults, so a fresh theme works on its own.
 */
final class Options {

	/**
	 * Registers the declarations.
	 */
	public static function register(): void {
		add_filter( Schema::FILTER, array( self::class, 'schema' ), 10 );
		add_filter( Modules::FILTER, array( self::class, 'modules' ), 10 );
	}

	/**
	 * Start and end labels that follow the writing direction.
	 *
	 * @return array{start: string, end: string}
	 */
	private static function sides(): array {
		return is_rtl()
			? array(
				'start' => __( 'Right', 'ultra-max' ),
				'end'   => __( 'Left', 'ultra-max' ),
			)
			: array(
				'start' => __( 'Left', 'ultra-max' ),
				'end'   => __( 'Right', 'ultra-max' ),
			);
	}

	/**
	 * Options of a sidebar position.
	 *
	 * @return list<array{value: string, label: string}>
	 */
	private static function sidebar_options(): array {
		$sides = self::sides();
		return array(
			array(
				'value' => 'end',
				'label' => $sides['end'],
			),
			array(
				'value' => 'start',
				'label' => $sides['start'],
			),
			array(
				'value' => 'none',
				'label' => __( 'No sidebar', 'ultra-max' ),
			),
		);
	}

	/**
	 * Options of a font choice.
	 *
	 * @param bool $with_body Whether "same as body text" is offered.
	 * @return list<array{value: string, label: string}>
	 */
	private static function font_options( bool $with_body ): array {
		$options = array();
		if ( $with_body ) {
			$options[] = array(
				'value' => 'body',
				'label' => __( 'Same as body text', 'ultra-max' ),
			);
		}
		foreach ( Tokens::fonts() as $value => $font ) {
			$options[] = array(
				'value' => $value,
				'label' => $font['label'],
			);
		}
		return $options;
	}

	/**
	 * Adds the theme's sections.
	 *
	 * @param mixed $sections Sections keyed by id.
	 * @return array<mixed>
	 */
	public static function schema( mixed $sections ): array {
		$sections = is_array( $sections ) ? $sections : array();
		$sides    = self::sides();

		$presets = array();
		foreach ( Tokens::presets() as $value => $preset ) {
			$presets[] = array(
				'value' => $value,
				'label' => $preset['label'],
				'color' => $preset['ramp'][600],
				'ramp'  => $preset['ramp'],
			);
		}
		$presets[] = array(
			'value' => 'custom',
			'label' => __( 'Custom colour', 'ultra-max' ),
		);

		$sections['general'] = array(
			'title'  => __( 'General', 'ultra-max' ),
			'icon'   => 'sliders',
			'order'  => 10,
			'groups' => array(
				array(
					'id'       => 'numbers',
					'title'    => __( 'Dates and digits', 'ultra-max' ),
					'icon'     => 'calendar',
					'features' => array( 'CORE-03', 'CORE-04' ),
					'fields'   => array(
						array(
							'id'       => 'digits',
							'type'     => 'segmented',
							'label'    => __( 'Digits', 'ultra-max' ),
							'help'     => __( 'Digits of prices, dates and order numbers on the storefront. Dates always use the Jalali calendar.', 'ultra-max' ),
							'default'  => 'fa',
							'keywords' => __( 'numbers Persian English Jalali calendar date', 'ultra-max' ),
							'options'  => array(
								array(
									'value' => 'fa',
									'label' => __( 'Persian ۱۲۳', 'ultra-max' ),
								),
								array(
									'value' => 'en',
									'label' => __( 'Latin 123', 'ultra-max' ),
								),
							),
						),
					),
				),
				array(
					'id'       => 'maintenance',
					'title'    => __( 'Maintenance and coming soon', 'ultra-max' ),
					'icon'     => 'wrench',
					'features' => array( 'CORE-09' ),
					'fields'   => array(
						array(
							'id'      => 'maintenance_mode',
							'type'    => 'segmented',
							'label'   => __( 'Site mode', 'ultra-max' ),
							'help'    => __( 'Visitors see a notice page; you and other editors still see the site. Maintenance answers search engines with "come back later" (503).', 'ultra-max' ),
							'default' => 'off',
							'options' => array(
								array(
									'value' => 'off',
									'label' => __( 'Open', 'ultra-max' ),
								),
								array(
									'value' => 'maintenance',
									'label' => __( 'Maintenance', 'ultra-max' ),
								),
								array(
									'value' => 'coming_soon',
									'label' => __( 'Coming soon', 'ultra-max' ),
								),
							),
						),
						array(
							'id'          => 'maintenance_title',
							'type'        => 'text',
							'label'       => __( 'Title', 'ultra-max' ),
							'placeholder' => __( 'We will be back soon', 'ultra-max' ),
							'show_if'     => array(
								'field'  => 'maintenance_mode',
								'equals' => array( 'maintenance', 'coming_soon' ),
							),
						),
						array(
							'id'      => 'maintenance_text',
							'type'    => 'textarea',
							'label'   => __( 'Message', 'ultra-max' ),
							'show_if' => array(
								'field'  => 'maintenance_mode',
								'equals' => array( 'maintenance', 'coming_soon' ),
							),
						),
						array(
							'id'          => 'maintenance_until',
							'type'        => 'jdate',
							'label'       => __( 'Back on', 'ultra-max' ),
							'help'        => __( 'Optional. Shown on the page, for example 1405/09/01.', 'ultra-max' ),
							'placeholder' => '1405/09/01',
							'show_if'     => array(
								'field'  => 'maintenance_mode',
								'equals' => array( 'maintenance', 'coming_soon' ),
							),
						),
					),
				),
				array(
					'id'       => 'not_found',
					'title'    => __( 'Page not found (404)', 'ultra-max' ),
					'icon'     => 'search',
					'features' => array( 'CORE-09' ),
					'fields'   => array(
						array(
							'id'          => 'error_title',
							'type'        => 'text',
							'label'       => __( 'Title', 'ultra-max' ),
							'placeholder' => __( 'This page could not be found', 'ultra-max' ),
						),
						array(
							'id'          => 'error_text',
							'type'        => 'textarea',
							'label'       => __( 'Message', 'ultra-max' ),
							'placeholder' => __( 'The link may be broken or the page may have moved. Try searching or go back to the home page.', 'ultra-max' ),
						),
						array(
							'id'      => 'error_search',
							'type'    => 'toggle',
							'label'   => __( 'Show the search form', 'ultra-max' ),
							'default' => true,
						),
					),
				),
			),
		);

		$sections['appearance'] = array(
			'title'  => __( 'Colours and typography', 'ultra-max' ),
			'icon'   => 'palette',
			'order'  => 20,
			'groups' => array(
				array(
					'id'       => 'colors',
					'title'    => __( 'Colours', 'ultra-max' ),
					'icon'     => 'palette',
					'features' => array( 'CORE-06' ),
					'fields'   => array(
						array(
							'id'       => 'color_preset',
							'type'     => 'preset',
							'label'    => __( 'Main colour', 'ultra-max' ),
							'help'     => __( 'Buttons, links, discount badges and progress bars use this colour. Lighter and darker shades are made automatically.', 'ultra-max' ),
							'default'  => 'indigo',
							'options'  => $presets,
							'keywords' => __( 'brand primary color theme', 'ultra-max' ),
						),
						array(
							'id'      => 'color_primary',
							'type'    => 'color',
							'label'   => __( 'Your brand colour', 'ultra-max' ),
							'default' => '#4c42d3',
							'token'   => '--um-brand-600',
							'show_if' => array(
								'field'  => 'color_preset',
								'equals' => 'custom',
							),
						),
						array(
							'id'      => 'color_accent',
							'type'    => 'color',
							'label'   => __( 'Accent colour', 'ultra-max' ),
							'help'    => __( 'For special offers, countdowns and highlighted buttons.', 'ultra-max' ),
							'default' => '#ff9a1f',
							'token'   => '--um-accent-500',
						),
					),
				),
				array(
					'id'       => 'fonts',
					'title'    => __( 'Fonts', 'ultra-max' ),
					'icon'     => 'file',
					'features' => array( 'CORE-05', 'PERF-05' ),
					'fields'   => array(
						array(
							'id'      => 'font_body',
							'type'    => 'select',
							'label'   => __( 'Body text', 'ultra-max' ),
							'help'    => __( 'Fonts load from your own site, never from an outside service.', 'ultra-max' ),
							'default' => 'vazirmatn',
							'options' => self::font_options( false ),
							'token'   => '--um-font-body',
						),
						array(
							'id'      => 'font_heading',
							'type'    => 'select',
							'label'   => __( 'Headings', 'ultra-max' ),
							'default' => 'body',
							'options' => self::font_options( true ),
							'token'   => '--um-font-heading',
						),
						array(
							'id'      => 'font_numbers',
							'type'    => 'select',
							'label'   => __( 'Numbers and prices', 'ultra-max' ),
							'default' => 'body',
							'options' => self::font_options( true ),
							'token'   => '--um-font-num',
						),
						array(
							'id'     => 'font_custom',
							'type'   => 'media',
							'label'  => __( 'Your own font', 'ultra-max' ),
							'help'   => __( 'Upload a WOFF2 file, then choose "Uploaded font" above. Make sure its licence allows use on websites.', 'ultra-max' ),
							'accept' => 'font/woff2',
						),
					),
				),
				array(
					'id'       => 'shape',
					'title'    => __( 'Corners, shadows and spacing', 'ultra-max' ),
					'icon'     => 'sliders',
					'features' => array( 'CORE-06' ),
					'fields'   => array(
						array(
							'id'      => 'radius',
							'type'    => 'range',
							'label'   => __( 'Corner radius', 'ultra-max' ),
							'help'    => __( 'Applies to cards, buttons and windows.', 'ultra-max' ),
							'default' => 14,
							'min'     => 0,
							'max'     => 24,
							'step'    => 1,
							'unit'    => 'px',
							'token'   => '--um-radius-lg',
						),
						array(
							'id'      => 'shadow',
							'type'    => 'segmented',
							'label'   => __( 'Shadows', 'ultra-max' ),
							'default' => 'soft',
							'options' => array(
								array(
									'value' => 'none',
									'label' => __( 'None', 'ultra-max' ),
								),
								array(
									'value' => 'soft',
									'label' => __( 'Soft', 'ultra-max' ),
								),
								array(
									'value' => 'strong',
									'label' => __( 'Strong', 'ultra-max' ),
								),
							),
						),
						array(
							'id'      => 'gutter',
							'type'    => 'range',
							'label'   => __( 'Page side spacing', 'ultra-max' ),
							'default' => 16,
							'min'     => 8,
							'max'     => 32,
							'step'    => 4,
							'unit'    => 'px',
							'token'   => '--um-gutter',
						),
						array(
							'id'    => 'appearance_preview',
							'type'  => 'preview',
							'label' => __( 'Live preview', 'ultra-max' ),
							'help'  => __( 'See your changes here before saving.', 'ultra-max' ),
						),
					),
				),
			),
		);

		$sections['layout'] = array(
			'title'  => __( 'Layout and header', 'ultra-max' ),
			'icon'   => 'layers',
			'order'  => 30,
			'groups' => array(
				array(
					'id'       => 'page',
					'title'    => __( 'Page layout', 'ultra-max' ),
					'icon'     => 'layers',
					'features' => array( 'CORE-07' ),
					'fields'   => array(
						array(
							'id'      => 'site_layout',
							'type'    => 'choice',
							'label'   => __( 'Site width', 'ultra-max' ),
							'default' => 'full',
							'options' => array(
								array(
									'value' => 'full',
									'label' => __( 'Full width', 'ultra-max' ),
								),
								array(
									'value' => 'boxed',
									'label' => __( 'Boxed', 'ultra-max' ),
								),
							),
						),
						array(
							'id'      => 'container_width',
							'type'    => 'range',
							'label'   => __( 'Content width', 'ultra-max' ),
							'default' => 1336,
							'min'     => 960,
							'max'     => 1600,
							'step'    => 8,
							'unit'    => 'px',
							'token'   => '--um-container',
						),
						array(
							'id'      => 'sidebar_blog',
							'type'    => 'choice',
							'label'   => __( 'Sidebar on blog and archives', 'ultra-max' ),
							'default' => 'end',
							'options' => self::sidebar_options(),
						),
						array(
							'id'      => 'sidebar_post',
							'type'    => 'choice',
							'label'   => __( 'Sidebar on posts', 'ultra-max' ),
							'default' => 'end',
							'options' => self::sidebar_options(),
						),
						array(
							'id'      => 'sidebar_page',
							'type'    => 'choice',
							'label'   => __( 'Sidebar on pages', 'ultra-max' ),
							'default' => 'none',
							'options' => self::sidebar_options(),
						),
					),
				),
				array(
					'id'       => 'header',
					'title'    => __( 'Header and scrolling', 'ultra-max' ),
					'icon'     => 'menu',
					'features' => array( 'CORE-12' ),
					'fields'   => array(
						array(
							'id'      => 'sticky_header',
							'type'    => 'toggle',
							'label'   => __( 'Sticky header', 'ultra-max' ),
							'help'    => __( 'The header stays at the top while visitors scroll.', 'ultra-max' ),
							'default' => true,
						),
						array(
							'id'      => 'header_search',
							'type'    => 'toggle',
							'label'   => __( 'Search field in the header', 'ultra-max' ),
							'default' => true,
						),
						array(
							'id'      => 'sticky_sidebar',
							'type'    => 'toggle',
							'label'   => __( 'Sticky sidebar', 'ultra-max' ),
							'help'    => __( 'On wide screens the sidebar follows the page while it fits on screen.', 'ultra-max' ),
							'default' => true,
						),
						array(
							'id'      => 'back_to_top_devices',
							'type'    => 'devices',
							'label'   => __( 'Back to top button', 'ultra-max' ),
							'default' => array(
								'desktop' => true,
								'tablet'  => false,
								'mobile'  => false,
							),
						),
					),
				),
				array(
					'id'       => 'title',
					'title'    => __( 'Page title and breadcrumbs', 'ultra-max' ),
					'icon'     => 'home',
					'features' => array( 'CORE-08', 'CORE-13' ),
					'fields'   => array(
						array(
							'id'      => 'page_title',
							'type'    => 'toggle',
							'label'   => __( 'Show the page title', 'ultra-max' ),
							'default' => true,
						),
						array(
							'id'      => 'page_title_style',
							'type'    => 'choice',
							'label'   => __( 'Title style', 'ultra-max' ),
							'default' => 'plain',
							'options' => array(
								array(
									'value' => 'plain',
									'label' => __( 'Plain', 'ultra-max' ),
								),
								array(
									'value' => 'soft',
									'label' => __( 'Light band', 'ultra-max' ),
								),
								array(
									'value' => 'brand',
									'label' => __( 'Brand colour', 'ultra-max' ),
								),
							),
						),
						array(
							'id'      => 'page_title_align',
							'type'    => 'segmented',
							'label'   => __( 'Title alignment', 'ultra-max' ),
							'default' => 'start',
							'options' => array(
								array(
									'value' => 'start',
									'label' => $sides['start'],
								),
								array(
									'value' => 'center',
									'label' => __( 'Centre', 'ultra-max' ),
								),
							),
						),
						array(
							'id'      => 'breadcrumbs',
							'type'    => 'devices',
							'label'   => __( 'Breadcrumbs', 'ultra-max' ),
							'help'    => __( 'The path from the home page to the current page. Untick every device to hide it.', 'ultra-max' ),
							'default' => array(
								'desktop' => true,
								'tablet'  => true,
								'mobile'  => true,
							),
						),
					),
				),
			),
		);

		$sections['notice'] = array(
			'title'  => __( 'Announcement bar', 'ultra-max' ),
			'icon'   => 'gift',
			'order'  => 40,
			'groups' => array(
				array(
					'id'       => 'notice_bar',
					'title'    => __( 'Announcement bar above the site', 'ultra-max' ),
					'icon'     => 'gift',
					'features' => array( 'CORE-10', 'CORE-13' ),
					'module'   => 'notice-bar',
					'fields'   => array(
						array(
							'id'          => 'notice_text',
							'type'        => 'text',
							'label'       => __( 'Text', 'ultra-max' ),
							'help'        => __( 'Leave empty to hide the bar.', 'ultra-max' ),
							'placeholder' => __( 'Free shipping on orders over 500,000 toman', 'ultra-max' ),
						),
						array(
							'id'    => 'notice_link_label',
							'type'  => 'text',
							'label' => __( 'Button text', 'ultra-max' ),
						),
						array(
							'id'          => 'notice_link_url',
							'type'        => 'url',
							'label'       => __( 'Button link', 'ultra-max' ),
							'placeholder' => 'https://',
						),
						array(
							'id'      => 'notice_tone',
							'type'    => 'segmented',
							'label'   => __( 'Colour', 'ultra-max' ),
							'default' => 'brand',
							'options' => array(
								array(
									'value' => 'brand',
									'label' => __( 'Brand', 'ultra-max' ),
								),
								array(
									'value' => 'accent',
									'label' => __( 'Accent', 'ultra-max' ),
								),
								array(
									'value' => 'dark',
									'label' => __( 'Dark', 'ultra-max' ),
								),
							),
						),
						array(
							'id'          => 'notice_start',
							'type'        => 'jdate',
							'label'       => __( 'Show from', 'ultra-max' ),
							'help'        => __( 'Optional Jalali date, for example 1405/08/18.', 'ultra-max' ),
							'placeholder' => '1405/08/18',
						),
						array(
							'id'          => 'notice_end',
							'type'        => 'jdate',
							'label'       => __( 'Show until', 'ultra-max' ),
							'help'        => __( 'Optional. The bar hides after the end of this day.', 'ultra-max' ),
							'placeholder' => '1405/08/25',
						),
						array(
							'id'      => 'notice_dismissible',
							'type'    => 'toggle',
							'label'   => __( 'Visitors can close it', 'ultra-max' ),
							'default' => true,
						),
						array(
							'id'    => 'notice_devices',
							'type'  => 'devices',
							'label' => __( 'Show on', 'ultra-max' ),
						),
					),
				),
			),
		);

		$sections['contact'] = array(
			'title'  => __( 'Contact buttons', 'ultra-max' ),
			'icon'   => 'headset',
			'order'  => 45,
			'groups' => array(
				array(
					'id'       => 'contact_buttons',
					'title'    => __( 'Floating contact buttons', 'ultra-max' ),
					'icon'     => 'headset',
					'features' => array( 'CORE-11', 'CORE-13' ),
					'module'   => 'contact-buttons',
					'fields'   => array(
						array(
							'id'       => 'contact_items',
							'type'     => 'repeater',
							'label'    => __( 'Buttons', 'ultra-max' ),
							'help'     => __( 'Add as many as you need. Drag to change the order.', 'ultra-max' ),
							'max_rows' => 20,
							'fields'   => array(
								array(
									'id'      => 'type',
									'type'    => 'select',
									'label'   => __( 'Service', 'ultra-max' ),
									'default' => 'whatsapp',
									'options' => ContactLinks::services(),
								),
								array(
									'id'          => 'value',
									'type'        => 'text',
									'label'       => __( 'Number, username or link', 'ultra-max' ),
									'placeholder' => '09120000000',
								),
								array(
									'id'    => 'label',
									'type'  => 'text',
									'label' => __( 'Label (optional)', 'ultra-max' ),
								),
							),
						),
						array(
							'id'      => 'contact_side',
							'type'    => 'segmented',
							'label'   => __( 'Position', 'ultra-max' ),
							'default' => 'end',
							'options' => array(
								array(
									'value' => 'end',
									'label' => $sides['end'],
								),
								array(
									'value' => 'start',
									'label' => $sides['start'],
								),
							),
						),
						array(
							'id'    => 'contact_devices',
							'type'  => 'devices',
							'label' => __( 'Show on', 'ultra-max' ),
						),
					),
				),
			),
		);

		$sections['performance'] = array(
			'title'  => __( 'Performance', 'ultra-max' ),
			'icon'   => 'zap',
			'order'  => 80,
			'groups' => array(
				array(
					'id'       => 'loading',
					'title'    => __( 'Loading', 'ultra-max' ),
					'icon'     => 'zap',
					'features' => array( 'PERF-02', 'PERF-03', 'PERF-05' ),
					'fields'   => array(
						array(
							'id'      => 'perf_lazy',
							'type'    => 'toggle',
							'label'   => __( 'Lazy-load images and embeds', 'ultra-max' ),
							'help'    => __( 'Images and videos below the fold load only when visitors scroll to them.', 'ultra-max' ),
							'default' => true,
						),
						array(
							'id'      => 'perf_preload_lcp',
							'type'    => 'toggle',
							'label'   => __( 'Preload the main image', 'ultra-max' ),
							'help'    => __( 'The featured image at the top of a post loads first, so the page looks ready sooner.', 'ultra-max' ),
							'default' => true,
						),
						array(
							'id'      => 'perf_preload_font',
							'type'    => 'toggle',
							'label'   => __( 'Preload the body font', 'ultra-max' ),
							'default' => true,
						),
						array(
							'id'      => 'perf_critical_css',
							'type'    => 'toggle',
							'label'   => __( 'Inline critical CSS', 'ultra-max' ),
							'help'    => __( 'Puts the styles the top of the page needs into the page itself, so it draws without waiting for a file.', 'ultra-max' ),
							'default' => true,
						),
					),
				),
			),
		);

		return $sections;
	}

	/**
	 * Adds the theme's modules.
	 *
	 * @param mixed $modules Modules keyed by id.
	 * @return array<mixed>
	 */
	public static function modules( mixed $modules ): array {
		$modules = is_array( $modules ) ? $modules : array();

		$modules['notice-bar']      = array(
			'title'       => __( 'Announcement bar', 'ultra-max' ),
			'description' => __( 'A bar above the header with a message, a button and optional dates.', 'ultra-max' ),
			'icon'        => 'gift',
			'features'    => array( 'CORE-10' ),
			'default'     => true,
			'section'     => 'notice',
			'assets'      => array( 'ui/modules/notice-bar' ),
			'product'     => 'ultra-max',
		);
		$modules['contact-buttons'] = array(
			'title'       => __( 'Floating contact buttons', 'ultra-max' ),
			'description' => __( 'WhatsApp, Telegram, Eitaa, Bale and phone buttons in a corner of every page.', 'ultra-max' ),
			'icon'        => 'headset',
			'features'    => array( 'CORE-11' ),
			'default'     => true,
			'section'     => 'contact',
			'assets'      => array( 'ui/modules/contact-buttons' ),
			'product'     => 'ultra-max',
		);
		$modules['back-to-top']     = array(
			'title'       => __( 'Back to top button', 'ultra-max' ),
			'description' => __( 'A round button that appears after scrolling and returns to the top.', 'ultra-max' ),
			'icon'        => 'arrow-up',
			'features'    => array( 'CORE-12' ),
			'default'     => true,
			'section'     => 'layout',
			'assets'      => array( 'ui/modules/back-to-top' ),
			'product'     => 'ultra-max',
		);
		return $modules;
	}
}
