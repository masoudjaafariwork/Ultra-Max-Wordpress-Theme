<?php
/**
 * The live search module (SRCH-01 to SRCH-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Search;

use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Lib\UI\Dialog;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\SearchSheet;
use UltraMax\Core\Lib\UI\UI;

/**
 * Off, search is WordPress's own: no file, no route, no change to queries.
 * On, every search field of the store (the theme's, the header widgets')
 * suggests while typing, product searches understand Persian spellings,
 * product codes, attributes and brand names, and phones get a full screen
 * search window with recent and popular searches.
 */
final class Search {

	public const MODULE = 'live-search';
	public const DIALOG = 'um-search-window';

	/**
	 * Whether the window was printed already on this page.
	 *
	 * @var bool
	 */
	private static bool $window = false;

	/**
	 * Whether the module is on.
	 */
	public static function enabled(): bool {
		return Modules::enabled( self::MODULE );
	}

	/**
	 * Registers the module.
	 */
	public static function register(): void {
		if ( ! self::enabled() ) {
			return;
		}
		Matcher::register();
		Live::register();
		add_filter( 'ultra_max_search_form', array( self::class, 'form' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'assets' ), 20 );
		add_action( 'wp_footer', array( self::class, 'footer' ), 5 );
	}

	/**
	 * The live search props of a search form.
	 *
	 * @param bool $inline Whether suggestions show in the page flow.
	 * @return array{endpoint: string, label: string, min: int, inline: bool}
	 */
	public static function live( bool $inline = false ): array {
		return array(
			'endpoint' => Live::endpoint(),
			'label'    => __( 'Search suggestions', 'ultra-max-core' ),
			'min'      => max( 1, Store::int( 'search_min' ) ),
			'inline'   => $inline,
		);
	}

	/**
	 * Adds live search to the theme's search form; it then searches products.
	 *
	 * @param mixed $props Search form props.
	 * @return mixed
	 */
	public static function form( mixed $props ): mixed {
		if ( ! is_array( $props ) ) {
			return $props;
		}
		$props['live'] = self::live();
		if ( post_type_exists( 'product' ) ) {
			$props['hidden'] = array( 'post_type' => 'product' );
		}
		return $props;
	}

	/**
	 * The live search script and styles, on every storefront page.
	 */
	public static function assets(): void {
		Assets::enqueue_widget( 'search' );
	}

	/**
	 * The phone search window: the field, recent and popular searches
	 * (SRCH-04). Printed once per page.
	 *
	 * @param string $placeholder Placeholder of the field.
	 */
	public static function window( string $placeholder = '' ): Html {
		if ( self::$window ) {
			return Html::none();
		}
		self::$window = true;
		$base         = add_query_arg(
			array(
				'post_type' => post_type_exists( 'product' ) ? 'product' : null,
				's'         => '',
			),
			home_url( '/' )
		);
		$terms        = array();
		$lines        = preg_split( '/\R/u', Store::string( 'search_popular' ) );
		foreach ( false === $lines ? array() : $lines as $line ) {
			$line = trim( (string) $line );
			if ( '' !== $line ) {
				$terms[] = array(
					'label' => $line,
					'href'  => $base . rawurlencode( $line ),
				);
			}
		}
		$form = array(
			'id'          => self::DIALOG . '-field',
			'action'      => home_url( '/' ),
			'label'       => __( 'Search', 'ultra-max-core' ),
			'submit'      => __( 'Search', 'ultra-max-core' ),
			'value'       => get_search_query(),
			/* translators: %s: store name. */
			'placeholder' => '' === $placeholder ? sprintf( __( 'Search in %s', 'ultra-max-core' ), get_bloginfo( 'name' ) ) : $placeholder,
			'variant'     => 'outline',
			'live'        => self::live( true ),
		);
		if ( post_type_exists( 'product' ) ) {
			$form['hidden'] = array( 'post_type' => 'product' );
		}
		return Dialog::render(
			array(
				'id'       => self::DIALOG,
				'title'    => __( 'Search', 'ultra-max-core' ),
				'size'     => 'full',
				'class'    => 'um-search-dialog',
				'children' => array(
					UI::search_form( $form ),
					SearchSheet::render(
						array(
							'recent'     => __( 'Your recent searches', 'ultra-max-core' ),
							'clear'      => __( 'Clear', 'ultra-max-core' ),
							'popular'    => __( 'Popular searches', 'ultra-max-core' ),
							'terms'      => $terms,
							'search_url' => $base,
						)
					),
				),
			)
		);
	}

	/**
	 * Prints the window at the end of pages that have none yet, for the
	 * phone bar's search button.
	 */
	public static function footer(): void {
		self::window()->print();
	}

	/**
	 * Forgets that the window was printed, for tests.
	 */
	public static function reset(): void {
		self::$window = false;
	}
}
