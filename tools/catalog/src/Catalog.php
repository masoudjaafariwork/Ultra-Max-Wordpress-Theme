<?php
/**
 * Live component catalog (development only, never shipped).
 *
 * @package UltraMax\Catalog
 */

declare(strict_types=1);

namespace UltraMax\Catalog;

use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\UI;

/**
 * Renders every component with the same code the products use.
 *
 * - Storefront catalog: /?um-catalog inside the active theme's header and
 *   footer, so it also proves the components work next to WooCommerce and
 *   Elementor.
 * - Admin catalog: Tools > Ultra Max components (shadcn/ui based admin set).
 *
 * Open to everyone on a local site, to administrators elsewhere.
 */
final class Catalog {

	/**
	 * Main plugin file.
	 *
	 * @var string
	 */
	private static string $file = '';

	/**
	 * Registers the catalog hooks.
	 *
	 * @param string $file Main plugin file.
	 */
	public static function boot( string $file ): void {
		self::$file = $file;
		add_action( 'template_redirect', array( self::class, 'maybe_render' ) );
		add_action( 'admin_menu', array( self::class, 'admin_menu' ) );
	}

	/**
	 * Whether the current visitor may see the catalog.
	 */
	private static function allowed(): bool {
		return 'local' === wp_get_environment_type() || current_user_can( 'manage_options' );
	}

	/**
	 * Renders the storefront catalog when requested.
	 */
	public static function maybe_render(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page switch.
		if ( ! isset( $_GET['um-catalog'] ) || ! self::allowed() || ! class_exists( UI::class ) ) {
			return;
		}
		add_filter( 'pre_get_document_title', static fn (): string => 'Ultra Max components' );
		add_action( 'wp_enqueue_scripts', array( Assets::class, 'enqueue' ), 20 );
		status_header( 200 );
		get_header();
		self::storefront()->print();
		get_footer();
		exit;
	}

	/**
	 * Wraps one catalog section.
	 *
	 * @param string $slug     Section id.
	 * @param string $title    Heading.
	 * @param array  $examples Rendered examples.
	 * @phpstan-param array<int, Html> $examples
	 */
	private static function section( string $slug, string $title, array $examples ): Html {
		return Html::trusted(
			sprintf(
				'<section id="c-%1$s" data-testid="%1$s" class="mb-8"><h2 class="mb-4">%2$s</h2><div class="flex flex-wrap items-start gap-4">%3$s</div></section>',
				esc_attr( $slug ),
				esc_html( $title ),
				Html::from( $examples )
			)
		);
	}

	/**
	 * Every button variant and size.
	 */
	private static function buttons(): Html {
		$buttons = array();
		foreach ( array( 'primary', 'outline', 'soft', 'ghost', 'neutral', 'accent', 'danger' ) as $variant ) {
			$buttons[] = UI::button(
				array(
					'variant'  => $variant,
					'children' => $variant,
					'attrs'    => array( 'data-testid' => 'button-' . $variant ),
				)
			);
		}
		$buttons[] = UI::button(
			array(
				'size'     => 'sm',
				'children' => 'کوچک',
			)
		);
		$buttons[] = UI::button(
			array(
				'size'     => 'lg',
				'children' => 'بزرگ',
			)
		);
		$buttons[] = UI::button(
			array(
				'disabled' => true,
				'children' => 'غیرفعال',
			)
		);
		$buttons[] = UI::button(
			array(
				'href'     => home_url( '/' ),
				'variant'  => 'outline',
				'children' => 'پیوند',
			)
		);
		return self::section( 'button', 'دکمه', $buttons );
	}

	/**
	 * Dialog with its trigger.
	 */
	private static function dialog(): Html {
		return self::section(
			'dialog',
			'پنجره',
			array(
				UI::button(
					array(
						'id'       => 'dialog-trigger',
						'opens'    => 'demo-dialog',
						'children' => 'باز کردن پنجره',
					)
				),
				UI::dialog(
					array(
						'id'       => 'demo-dialog',
						'title'    => 'ثبت دیدگاه',
						'children' => Html::trusted( '<p>دیدگاه شما پس از بررسی نمایش داده می‌شود.</p>' ),
						'footer'   => array(
							UI::button(
								array(
									'variant'  => 'neutral',
									'children' => 'انصراف',
									'attrs'    => array( 'data-um-close' => true ),
								)
							),
							UI::button(
								array(
									'children' => 'ثبت',
									'attrs'    => array( 'data-um-close' => true ),
								)
							),
						),
					)
				),
			)
		);
	}

	/**
	 * Drawer with its trigger.
	 */
	private static function drawer(): Html {
		return self::section(
			'drawer',
			'کشو',
			array(
				UI::button(
					array(
						'id'       => 'drawer-trigger',
						'opens'    => 'demo-drawer',
						'children' => 'سبد خرید',
					)
				),
				UI::drawer(
					array(
						'id'       => 'demo-drawer',
						'title'    => 'سبد خرید',
						'children' => Html::trusted( '<p>سبد شما خالی است.</p><a id="drawer-link" href="#c-drawer">ادامه خرید</a>' ),
						'footer'   => UI::button(
							array(
								'block'    => true,
								'children' => 'تسویه حساب',
							)
						),
					)
				),
			)
		);
	}

	/**
	 * Tabs, accordion, dropdown and tooltip.
	 */
	private static function disclosures(): Html {
		return Html::from(
			array(
				self::section(
					'tabs',
					'زبانه',
					array(
						UI::tabs(
							array(
								'id'    => 'demo-tabs',
								'label' => 'اطلاعات محصول',
								'class' => 'w-full',
								'items' => array(
									self::item( 'معرفی', 'متن معرفی محصول.' ),
									self::item( 'مشخصات', 'جدول مشخصات فنی.' ),
									self::item( 'دیدگاه‌ها', 'دیدگاه خریداران.' ),
								),
							)
						),
					)
				),
				self::section(
					'accordion',
					'آکاردئون',
					array(
						UI::accordion(
							array(
								'id'    => 'demo-accordion',
								'class' => 'w-full',
								'items' => array(
									array(
										'title'   => 'ارسال چقدر طول می‌کشد؟',
										'content' => 'دو تا چهار روز کاری.',
									),
									array(
										'title'   => 'بازگشت کالا چطور است؟',
										'content' => 'تا هفت روز پس از تحویل.',
									),
								),
							)
						),
					)
				),
				self::section(
					'dropdown',
					'منوی بازشو',
					array(
						UI::dropdown(
							array(
								'id'    => 'demo-dropdown',
								'label' => 'حساب من',
								'items' => array(
									array(
										'label'   => 'سفارش‌ها',
										'href'    => '#c-dropdown',
										'current' => true,
									),
									array(
										'label' => 'نشانی‌ها',
										'href'  => '#c-dropdown',
									),
									array(
										'label' => 'خروج',
										'href'  => '#c-dropdown',
									),
								),
							)
						),
					)
				),
				self::section(
					'tooltip',
					'راهنمای کوچک',
					array(
						UI::tooltip(
							array(
								'id'       => 'demo-tooltip',
								'text'     => 'افزودن به فهرست مقایسه',
								'children' => UI::button(
									array(
										'id'       => 'tooltip-trigger',
										'variant'  => 'neutral',
										'children' => 'مقایسه',
									)
								),
							)
						),
					)
				),
			)
		);
	}

	/**
	 * One tab item.
	 *
	 * @param string $label   Tab label.
	 * @param string $content Panel content.
	 * @return array{label: string, content: string}
	 */
	private static function item( string $label, string $content ): array {
		return array(
			'label'   => $label,
			'content' => $content,
		);
	}

	/**
	 * The storefront catalog.
	 */
	private static function storefront(): Html {
		return Html::trusted(
			sprintf(
				'<div class="mx-auto max-w-(--um-container) px-4 py-6" data-testid="catalog"><h1 class="mb-6">کاتالوگ اجزا</h1>%s</div>',
				Html::from( array( self::buttons(), self::dialog(), self::drawer(), self::disclosures() ) )
			)
		);
	}

	/**
	 * Adds the admin catalog page.
	 */
	public static function admin_menu(): void {
		$hook = add_management_page( 'Ultra Max components', 'Ultra Max components', 'manage_options', 'ultra-max-catalog', array( self::class, 'admin_page' ) );
		if ( false === $hook ) {
			return;
		}
		add_action(
			'admin_enqueue_scripts',
			static function ( string $current ) use ( $hook ): void {
				if ( $current !== $hook ) {
					return;
				}
				$manifest = self::manifest( 'catalog/admin' );
				if ( null === $manifest ) {
					return;
				}
				Assets::enqueue_admin();
				wp_enqueue_script(
					'ultra-max-catalog-admin',
					plugins_url( 'assets/build/catalog/admin.js', self::$file ),
					$manifest['dependencies'],
					$manifest['version'],
					true
				);
			},
			20
		);
	}

	/**
	 * Reads the build manifest of one of the catalog's own entries.
	 *
	 * @param string $entry Entry inside assets/build.
	 * @return array{dependencies: list<non-empty-string>, version: string}|null
	 */
	private static function manifest( string $entry ): ?array {
		$file = plugin_dir_path( self::$file ) . 'assets/build/' . $entry . '.asset.php';
		if ( ! is_readable( $file ) ) {
			return null;
		}
		$data = require $file;
		if ( ! is_array( $data ) || ! is_string( $data['version'] ?? null ) || ! is_array( $data['dependencies'] ?? null ) ) {
			return null;
		}
		return array(
			'dependencies' => \UltraMax\Core\Lib\Foundation\Assets::handles( $data['dependencies'] ),
			'version'      => $data['version'],
		);
	}

	/**
	 * Admin catalog mount point.
	 */
	public static function admin_page(): void {
		echo '<div class="wrap"><h1>Ultra Max components</h1><div id="um-admin-catalog" class="um-admin um-scope"></div></div>';
	}
}
