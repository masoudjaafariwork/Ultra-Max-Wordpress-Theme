<?php
/**
 * QuickView component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A product in a window over a list (SAVE-04, SAVE-05): its pictures,
 * price, choices and add button, so a shopper can pick a colour and buy
 * without leaving the list.
 *
 * `shell()` prints the empty window once per page. Buttons with
 * `data-um-quick="<product id>"` open it; the behaviour asks `endpoint`
 * for the product, loads the product page's files from `assets` the first
 * time, and shows `content()`.
 *
 * @phpstan-type QuickViewShellProps array{
 *     title: string,
 *     endpoint: string,
 *     loading: string,
 *     error: string,
 *     assets: array{css: list<string>, js: list<string>},
 * }
 * @phpstan-type QuickViewContentProps array{
 *     gallery: Html,
 *     heading: Html,
 *     price: Html,
 *     picker: Html,
 *     form: Html,
 *     more: array{label: string, href: string},
 * }
 */
final class QuickView {

	public const ID = 'um-quick-view';

	/**
	 * The empty window.
	 *
	 * @param array $props Props.
	 * @phpstan-param QuickViewShellProps $props
	 */
	public static function shell( array $props ): Html {
		$assets = wp_json_encode( $props['assets'] );
		return Html::trusted(
			sprintf(
				'<div%1$s>%2$s</div>',
				Attributes::render(
					array(
						'class'             => 'um-scope um-quick-view',
						'data-um-component' => 'quick-view',
						'data-endpoint'     => esc_url_raw( $props['endpoint'] ),
						'data-assets'       => is_string( $assets ) ? $assets : '{}',
						'data-error'        => $props['error'],
					)
				),
				Dialog::render(
					array(
						'id'       => self::ID,
						'title'    => $props['title'],
						'size'     => 'lg',
						'children' => Html::trusted(
							sprintf(
								'<div class="min-h-60" data-um-quick-body aria-busy="true"><p class="m-0 grid min-h-60 place-items-center text-sm text-fg-3" data-um-quick-loading>%s</p></div>',
								esc_html( $props['loading'] )
							)
						),
					)
				)
			)
		);
	}

	/**
	 * What the window shows for one product.
	 *
	 * @param array $props Props.
	 * @phpstan-param QuickViewContentProps $props
	 */
	public static function content( array $props ): Html {
		return Html::trusted(
			sprintf(
				'<div class="grid items-start gap-6 md:grid-cols-2 max-md:gap-4"><div class="min-w-0">%1$s</div><div class="grid min-w-0 content-start gap-4">%2$s%3$s%4$s%5$s<a href="%6$s" class="inline-flex items-center gap-1 text-sm font-medium text-primary no-underline hover:underline">%7$s%8$s</a></div></div>',
				$props['gallery'],
				$props['heading'],
				$props['price'],
				$props['picker'],
				$props['form'],
				esc_url( $props['more']['href'] ),
				esc_html( $props['more']['label'] ),
				Icon::render( is_rtl() ? 'chevron-left' : 'chevron-right', 'size-4' )
			)
		);
	}
}
