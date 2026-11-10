<?php
/**
 * CategoryMenu component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * "Product categories" in the header (HDR-07): a button that opens a large
 * panel. On the start side, the main categories with their images; on the
 * other side, the pointed category's subcategories in columns, each with
 * its own children, and a promotional image.
 *
 * Every pane is printed by the server, so search engines and screen
 * readers see all links; pointing or focusing a category shows its pane
 * (category-menu.behavior.ts). Without JavaScript the first pane shows.
 *
 * @phpstan-type CategoryColumn array{label: string, href: string, children: list<array{label: string, href: string}>}
 * @phpstan-type CategoryEntry array{
 *     label: string,
 *     href: string,
 *     image?: Html,
 *     icon?: string,
 *     columns: list<CategoryColumn>,
 *     promo?: array{image: Html, title: string, text?: string, href: string},
 * }
 * @phpstan-type CategoryMenuProps array{
 *     id: string,
 *     label: string,
 *     all_label: string,
 *     categories: list<CategoryEntry>,
 *     open_on?: 'hover'|'click',
 *     class?: string,
 * }
 */
final class CategoryMenu {

	/**
	 * Renders the menu.
	 *
	 * @param array $props Props.
	 * @phpstan-param CategoryMenuProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['categories'] ) {
			return Html::none();
		}
		$id    = Props::id( 'CategoryMenu', $props );
		$list  = '';
		$panes = '';
		foreach ( $props['categories'] as $index => $category ) {
			$pane_id = $id . '-pane-' . $index;
			$visual  = '';
			if ( isset( $category['image'] ) && ! $category['image']->is_empty() ) {
				$visual = sprintf( '<span class="size-7 shrink-0 [&_img]:size-full [&_img]:object-contain">%s</span>', $category['image'] );
			} elseif ( isset( $category['icon'] ) ) {
				$visual = Icon::render( $category['icon'], 'size-5 text-fg-3' );
			}
			$list  .= sprintf(
				'<li><a href="%1$s" class="flex items-center gap-2.5 px-4 py-2 font-medium text-fg-2 no-underline hover:text-primary aria-[current=true]:bg-surface aria-[current=true]:text-primary rtl:aria-[current=true]:shadow-[inset_-3px_0_0_var(--um-primary)] ltr:aria-[current=true]:shadow-[inset_3px_0_0_var(--um-primary)]" data-pane="%2$s" aria-current="%3$s">%4$s<span class="flex-1">%5$s</span>%6$s</a></li>',
				esc_url( $category['href'] ),
				esc_attr( $pane_id ),
				0 === $index ? 'true' : 'false',
				$visual,
				esc_html( $category['label'] ),
				Icon::render( 'chevron-left', 'size-4 opacity-60 ltr:rotate-180' )
			);
			$panes .= self::pane( $category, $pane_id, $props['all_label'], 0 === $index );
		}
		return Html::trusted(
			sprintf(
				'<div%1$s><button type="button" class="inline-flex h-11 items-center gap-1.5 border-0 bg-transparent px-0 font-bold text-fg cursor-pointer focus-visible:outline-3 focus-visible:outline-primary/40" aria-expanded="false" aria-controls="%2$s-panel">%3$s%4$s</button><div id="%2$s-panel" class="invisible absolute start-0 top-full z-60 flex h-115 w-[min(1080px,calc(100vw-32px))] translate-y-1.5 overflow-hidden rounded-b-lg border border-border bg-surface opacity-0 shadow-lg transition delay-75 duration-200 ease-um group-hover/cats:visible group-hover/cats:translate-y-0 group-hover/cats:opacity-100 group-focus-within/cats:visible group-focus-within/cats:translate-y-0 group-focus-within/cats:opacity-100 group-data-open/cats:visible group-data-open/cats:translate-y-0 group-data-open/cats:opacity-100"><ul class="m-0 w-57.5 shrink-0 list-none overflow-y-auto border-e border-border bg-surface-2 py-2">%5$s</ul><div class="relative flex-1 overflow-hidden">%6$s</div></div></div>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes( 'um-category-menu group/cats relative flex h-full items-center text-sm', $props['class'] ?? null ),
						'data-um-component' => 'category-menu',
						'data-open-on'      => $props['open_on'] ?? 'hover',
					)
				),
				esc_attr( $id ),
				Icon::render( 'menu', 'size-5' ),
				esc_html( $props['label'] ),
				$list,
				$panes
			)
		);
	}

	/**
	 * The pane of one category.
	 *
	 * @param array  $category  Category.
	 * @param string $id        Pane id.
	 * @param string $all_label "All products of %s" pattern.
	 * @param bool   $shown     Whether it shows first.
	 * @phpstan-param CategoryEntry $category
	 */
	private static function pane( array $category, string $id, string $all_label, bool $shown ): string {
		$columns = '';
		foreach ( $category['columns'] as $column ) {
			$links = '';
			foreach ( $column['children'] as $child ) {
				$links .= sprintf( '<a href="%1$s" class="text-fg-2 no-underline hover:text-primary">%2$s</a>', esc_url( $child['href'] ), esc_html( $child['label'] ) );
			}
			$columns .= sprintf(
				'<div class="mb-4 flex break-inside-avoid flex-col gap-1.5 text-sm"><a href="%1$s" class="inline-flex items-center gap-1 border-s-2 border-primary ps-2 font-bold text-fg no-underline hover:text-primary">%2$s%3$s</a>%4$s</div>',
				esc_url( $column['href'] ),
				esc_html( $column['label'] ),
				Icon::render( 'chevron-left', 'size-4 ltr:rotate-180' ),
				$links
			);
		}
		$promo = '';
		if ( isset( $category['promo'] ) ) {
			$promo = sprintf(
				'<a href="%1$s" class="absolute end-5 top-5 flex w-50 flex-col gap-2 rounded-lg bg-primary-soft p-4 no-underline max-xl:hidden"><span class="mx-auto block w-35 [&_img]:h-auto [&_img]:w-full">%2$s</span><b class="text-lg text-primary">%3$s</b>%4$s</a>',
				esc_url( $category['promo']['href'] ),
				$category['promo']['image'],
				esc_html( $category['promo']['title'] ),
				isset( $category['promo']['text'] ) ? sprintf( '<small class="text-fg-2">%s</small>', esc_html( $category['promo']['text'] ) ) : ''
			);
		}
		return sprintf(
			'<div id="%1$s" class="absolute inset-0 overflow-y-auto p-5 %2$s" data-pane-body><a href="%3$s" class="mb-4 inline-flex items-center gap-1 text-sm font-bold text-primary no-underline hover:underline">%4$s%5$s</a><div class="max-w-155 columns-3 gap-6">%6$s</div>%7$s</div>',
			esc_attr( $id ),
			$shown ? '' : 'hidden',
			esc_url( $category['href'] ),
			esc_html( sprintf( $all_label, $category['label'] ) ),
			Icon::render( 'chevron-left', 'size-4 ltr:rotate-180' ),
			$columns,
			$promo
		);
	}
}
