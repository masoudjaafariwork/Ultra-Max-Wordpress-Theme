<?php
/**
 * Every Ultra Max widget (HDR-02, HOME-01 to HOME-12, BLD-08, BLD-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets;

use UltraMax\Core\Elementor\Data;
use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Store\Catalog;
use UltraMax\Core\Store\Categories;
use UltraMax\Core\Store\Forms;
use UltraMax\Core\Store\Menus;

/**
 * The list of widgets, their shortcodes, and the loading of their assets.
 *
 * A widget's CSS and JS load only on pages that show it (BLD-12). For
 * Elementor designs the page's widget list is read before the head is
 * printed, so the styles arrive with the page and nothing jumps.
 */
final class Widgets {

	public const MODULE = 'widgets';

	/**
	 * Instances by id.
	 *
	 * @var array<string, Widget>|null
	 */
	private static ?array $widgets = null;

	/**
	 * Widget classes. Each file in this folder's groups defines one.
	 *
	 * @return list<class-string<Widget>>
	 */
	public static function classes(): array {
		$classes = array(
			Header\Logo::class,
			Header\NavMenu::class,
			Header\CategoryMenu::class,
			Header\Search::class,
			Header\Cart::class,
			Header\Account::class,
			Header\Wishlist::class,
			Header\Compare::class,
			Header\MenuToggle::class,
			Header\Language::class,
			Header\StickyNav::class,
			Header\Divider::class,
			Header\Spacer::class,
			Header\Html::class,
			Store\HeroSlider::class,
			Store\Stories::class,
			Store\Deals::class,
			Store\CategoryCircles::class,
			Store\Banners::class,
			Store\Products::class,
			Store\ProductTabs::class,
			Store\Brands::class,
			Store\Bestsellers::class,
			Store\DiscountPicks::class,
			Store\Posts::class,
			Store\TrustMarks::class,
			Store\QuickLinks::class,
			Store\WooHook::class,
			General\Heading::class,
			General\Button::class,
			General\Text::class,
			General\IconBox::class,
			General\Counter::class,
			General\Countdown::class,
			General\Accordion::class,
			General\Tabs::class,
			General\PricingTable::class,
			General\Testimonials::class,
			General\Team::class,
			General\Timeline::class,
			General\Gallery::class,
			General\Video::class,
			General\Hotspots::class,
			General\ImageCompare::class,
			General\AnimatedText::class,
			General\Table::class,
			General\IconList::class,
			General\Social::class,
			General\ContactForm::class,
			General\Newsletter::class,
			General\Slider::class,
			Footer\Perks::class,
			Footer\AppLinks::class,
			Footer\Copyright::class,
		);
		return array_values( array_filter( $classes, 'class_exists' ) );
	}

	/**
	 * Every widget, by id.
	 *
	 * @return array<string, Widget>
	 */
	public static function all(): array {
		if ( null === self::$widgets ) {
			self::$widgets = array();
			foreach ( self::classes() as $class ) {
				$widget                         = new $class();
				self::$widgets[ $widget->id() ] = $widget;
			}
		}
		return self::$widgets;
	}

	/**
	 * One widget.
	 *
	 * @param string $id Widget id.
	 */
	public static function get( string $id ): ?Widget {
		return self::all()[ $id ] ?? null;
	}

	/**
	 * Whether the widgets module is on.
	 */
	public static function enabled(): bool {
		return Modules::enabled( self::MODULE );
	}

	/**
	 * Registers shortcodes and asset loading.
	 */
	public static function register(): void {
		if ( ! self::enabled() ) {
			return;
		}
		Shortcodes::register();
		Catalog::register();
		Menus::register();
		Header\Cart::register();
		Forms::register();
		Categories::register();
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_page' ), 20 );
	}

	/**
	 * Asset handles of a widget.
	 *
	 * @param Widget $widget Widget.
	 * @return list<string>
	 */
	public static function handles( Widget $widget ): array {
		return array_map( static fn ( string $asset ): string => Assets::widget_handle( $asset ), $widget->assets() );
	}

	/**
	 * Queues a widget's assets.
	 *
	 * @param Widget $widget Widget.
	 */
	public static function enqueue( Widget $widget ): void {
		foreach ( $widget->assets() as $asset ) {
			Assets::enqueue_widget( $asset );
		}
	}

	/**
	 * Queues the assets of the widgets a post's Elementor design uses.
	 *
	 * @param int $id Post id.
	 */
	public static function enqueue_for( int $id ): void {
		foreach ( Data::widget_types( $id ) as $type ) {
			if ( str_starts_with( $type, 'um-' ) ) {
				$widget = self::get( substr( $type, 3 ) );
				if ( null !== $widget ) {
					self::enqueue( $widget );
				}
			}
		}
	}

	/**
	 * Queues the assets of the widgets on the page being shown.
	 */
	public static function enqueue_page(): void {
		if ( is_singular() ) {
			self::enqueue_for( (int) get_queried_object_id() );
		}
	}
}
