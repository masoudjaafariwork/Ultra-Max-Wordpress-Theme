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
