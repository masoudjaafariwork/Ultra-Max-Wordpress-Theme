<?php
/**
 * ShopFilters component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The filter panel of a product list (PLP-01, PLP-02): switches such as
 * "only available", a category tree, check lists with optional colour
 * swatches and a search box, single choices such as the rating, and a
 * price range with two handles.
 *
 * It is a plain GET form, so it works without JavaScript: the "apply"
 * button sends it and the server tidies the address. With JavaScript the
 * shop behaviour applies every change at once and the button hides.
 *
 * Every field id starts with the panel id, so the sidebar panel and the
 * phone sheet can both be on the page.
 *
 * @phpstan-type FilterItem array{value: string, label: string, count?: string, hint?: string, checked?: bool, color?: string}
 * @phpstan-type FilterLink array{label: string, href: string, current?: bool, count?: string, depth?: int}
 * @phpstan-type FilterGroup array{
 *     type: 'toggle'|'checks'|'choice'|'links'|'range',
 *     label: string,
 *     key: string,
 *     name?: string,
 *     checked?: bool,
 *     count?: string,
 *     open?: bool,
 *     items?: list<FilterItem>,
 *     links?: list<FilterLink>,
 *     swatch?: bool,
 *     search?: string,
 *     range?: array{min: int, max: int, from: int, to: int, step: int, unit: string, from_label: string, to_label: string, min_name: string, max_name: string},
 * }
 * @phpstan-type ShopFiltersProps array{
 *     id: string,
 *     action: string,
 *     title: string,
 *     groups: list<FilterGroup>,
 *     apply: string,
 *     hidden?: array<string, string>,
 *     clear?: array{label: string, href: string},
 *     active?: Html,
 *     variant?: 'sidebar'|'sheet',
 *     class?: string,
 * }
 */
final class ShopFilters {

	/**
	 * Renders the panel.
	 *
	 * @param array $props Props.
	 * @phpstan-param ShopFiltersProps $props
	 */
	public static function render( array $props ): Html {
		$id      = Props::id( 'ShopFilters', $props );
		$variant = Props::one_of( 'ShopFilters', $props, 'variant', array( 'sidebar', 'sheet' ) );
		$hidden  = '';
		foreach ( $props['hidden'] ?? array() as $name => $value ) {
			$hidden .= sprintf( '<input type="hidden" name="%s" value="%s">', esc_attr( $name ), esc_attr( $value ) );
		}
		$groups = '';
		foreach ( $props['groups'] as $group ) {
			$groups .= self::group( $id, $group );
		}
		$clear = isset( $props['clear'] ) ? sprintf(
			'<a href="%1$s" class="text-sm font-medium text-link no-underline hover:underline" rel="nofollow" data-um-shop-link>%2$s</a>',
			esc_url( $props['clear']['href'] ),
			esc_html( $props['clear']['label'] )
		) : '';
		$head  = 'sheet' === $variant ? '' : sprintf(
			'<div class="mb-3 flex items-center justify-between gap-2"><h2 class="m-0 text-lg font-bold">%1$s</h2>%2$s</div>',
			esc_html( $props['title'] ),
			$clear
		);

		return Html::trusted(
			sprintf(
				'<form%1$s>%2$s%3$s%4$s<div>%5$s</div><button type="submit" class="mt-4 inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 text-sm font-bold text-on-primary hover:bg-primary-hover" data-um-shop-apply>%6$s</button></form>',
				Attributes::render(
					array(
						'id'                => $id,
						'method'            => 'get',
						'action'            => esc_url_raw( $props['action'] ),
						'class'             => Attributes::classes( 'um-shop-filters m-0', $props['class'] ?? null ),
						'aria-label'        => $props['title'],
						'data-um-shop-form' => '',
					)
				),
				$hidden,
				$head,
				isset( $props['active'] ) && ! $props['active']->is_empty() ? '<div class="border-b border-border pb-3">' . $props['active'] . '</div>' : '',
				$groups,
				esc_html( $props['apply'] )
			)
		);
	}

	/**
	 * One group of the panel.
	 *
	 * @param string $id    Panel id.
	 * @param array  $group Group.
	 * @phpstan-param FilterGroup $group
	 */
	private static function group( string $id, array $group ): string {
		$field = $id . '-' . preg_replace( '/[^a-z0-9_-]/i', '', $group['key'] );
		if ( 'toggle' === $group['type'] ) {
			return sprintf(
				'<label for="%1$s" class="flex cursor-pointer items-center gap-3 border-b border-border py-3 text-sm font-medium"><span class="grow">%2$s</span>%3$s<input id="%1$s" type="checkbox" role="switch" name="%4$s" value="1" class="peer sr-only"%5$s><span class="relative h-6 w-10 shrink-0 rounded-pill bg-border-strong transition-colors duration-150 after:absolute after:top-0.5 after:start-0.5 after:size-5 after:rounded-full after:bg-surface after:shadow-sm after:transition-[inset-inline-start] after:duration-150 peer-checked:bg-primary peer-checked:after:start-4.5 peer-focus-visible:outline-3 peer-focus-visible:outline-primary/40" aria-hidden="true"></span></label>',
				esc_attr( $field ),
				esc_html( $group['label'] ),
				isset( $group['count'] ) ? sprintf( '<span class="font-num text-xs text-fg-3">%s</span>', esc_html( $group['count'] ) ) : '',
				esc_attr( $group['name'] ?? $group['key'] ),
				( $group['checked'] ?? false ) ? ' checked' : ''
			);
		}

		$body = match ( $group['type'] ) {
			'links'  => self::links( $group['links'] ?? array() ),
			'range'  => isset( $group['range'] ) ? self::range( $field, $group['range'] ) : '',
			default  => self::checks( $field, $group ),
		};
		if ( '' === $body ) {
			return '';
		}
		return sprintf(
			'<details class="group/filter border-b border-border"%1$s><summary class="flex cursor-pointer list-none items-center justify-between gap-2 py-3 text-sm font-bold [&::-webkit-details-marker]:hidden">%2$s%3$s</summary><div class="grid gap-1 pb-3">%4$s</div></details>',
			( $group['open'] ?? false ) ? ' open' : '',
			esc_html( $group['label'] ),
			Icon::render( 'chevron-down', 'size-4 text-fg-3 transition-transform duration-150 group-open/filter:rotate-180' ),
			$body
		);
	}

	/**
	 * A list of check boxes or single choices.
	 *
	 * @param string $field Field id prefix.
	 * @param array  $group Group.
	 * @phpstan-param FilterGroup $group
	 */
	private static function checks( string $field, array $group ): string {
		$items = $group['items'] ?? array();
		if ( array() === $items ) {
			return '';
		}
		$single = 'choice' === $group['type'];
		$swatch = $group['swatch'] ?? false;
		$name   = ( $group['name'] ?? $group['key'] ) . ( $single ? '' : '[]' );
		$out    = '';
		foreach ( $items as $index => $item ) {
			$input = $field . '-' . $index;
			$mark  = $swatch && isset( $item['color'] )
				? sprintf( '<span class="size-6 shrink-0 rounded-full border border-border shadow-xs peer-checked:outline-2 peer-checked:outline-offset-2 peer-checked:outline-primary" style="background:%s" aria-hidden="true"></span>', esc_attr( $item['color'] ) )
				: sprintf( '<span class="grid size-5 shrink-0 place-items-center %1$s border-2 border-border-strong bg-surface text-transparent peer-checked:border-primary peer-checked:bg-primary peer-checked:text-on-primary peer-focus-visible:outline-3 peer-focus-visible:outline-primary/40" aria-hidden="true">%2$s</span>', $single ? 'rounded-full' : 'rounded-xs', Icon::render( 'check', 'size-3.5' ) );
			$out  .= sprintf(
				'<label for="%1$s" class="flex min-h-9 cursor-pointer items-center gap-2.5 text-sm" data-um-filter-item="%2$s"><input id="%1$s" type="%3$s" name="%4$s" value="%5$s" class="peer sr-only"%6$s>%7$s<span class="grow">%8$s</span>%9$s%10$s</label>',
				esc_attr( $input ),
				esc_attr( strtolower( $item['label'] . ' ' . ( $item['hint'] ?? '' ) . ' ' . $item['value'] ) ),
				$single ? 'radio' : 'checkbox',
				esc_attr( $name ),
				esc_attr( $item['value'] ),
				( $item['checked'] ?? false ) ? ' checked' : '',
				$mark,
				esc_html( $item['label'] ),
				isset( $item['hint'] ) && '' !== $item['hint'] ? sprintf( '<span class="text-xs text-fg-3" dir="ltr">%s</span>', esc_html( $item['hint'] ) ) : '',
				isset( $item['count'] ) ? sprintf( '<span class="min-w-6 font-num text-xs text-fg-3 text-end">%s</span>', esc_html( $item['count'] ) ) : ''
			);
		}
		$search = '';
		if ( isset( $group['search'] ) && count( $items ) > 6 ) {
			$search = sprintf(
				'<div class="relative mb-1"><label for="%1$s-q" class="sr-only">%2$s</label><span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-fg-3">%3$s</span><input id="%1$s-q" type="search" placeholder="%2$s" autocomplete="off" class="h-9 w-full rounded-md border border-border bg-surface ps-9 pe-3 text-sm text-fg outline-none placeholder:text-fg-3 focus:border-primary focus:shadow-[var(--um-focus)]" data-um-filter-search></div>',
				esc_attr( $field ),
				esc_attr( $group['search'] ),
				Icon::render( 'search', 'size-4' )
			);
		}
		$list = $swatch
			? sprintf( '<div class="grid grid-cols-2 gap-x-3 gap-y-1">%s</div>', $out )
			: sprintf( '<div class="grid max-h-64 gap-0.5 overflow-y-auto pe-1">%s</div>', $out );
		return $search . ( $single ? sprintf( '<div role="radiogroup" aria-label="%1$s">%2$s</div>', esc_attr( $group['label'] ), $list ) : $list );
	}

	/**
	 * A tree of category links.
	 *
	 * @param array $links Links.
	 * @phpstan-param list<FilterLink> $links
	 */
	private static function links( array $links ): string {
		if ( array() === $links ) {
			return '';
		}
		$out = '';
		foreach ( $links as $link ) {
			$depth = max( 0, min( 3, $link['depth'] ?? 0 ) );
			$out  .= sprintf(
				'<li class="%1$s"><a href="%2$s" class="flex min-h-8 items-center justify-between gap-2 text-sm no-underline %3$s"%4$s data-um-shop-link><span>%5$s</span>%6$s</a></li>',
				array( '', 'ps-3', 'ps-6', 'ps-9' )[ $depth ],
				esc_url( $link['href'] ),
				( $link['current'] ?? false ) ? 'font-bold text-primary' : 'text-fg-2 hover:text-primary',
				( $link['current'] ?? false ) ? ' aria-current="page"' : '',
				esc_html( $link['label'] ),
				isset( $link['count'] ) ? sprintf( '<span class="font-num text-xs text-fg-3">%s</span>', esc_html( $link['count'] ) ) : ''
			);
		}
		return sprintf( '<ul class="m-0 grid list-none gap-0.5 p-0">%s</ul>', $out );
	}

	/**
	 * The price range: two sliders and two number fields.
	 *
	 * @param string $field Field id prefix.
	 * @param array  $range Range.
	 * @phpstan-param array{min: int, max: int, from: int, to: int, step: int, unit: string, from_label: string, to_label: string, min_name: string, max_name: string} $range
	 */
	private static function range( string $field, array $range ): string {
		if ( $range['max'] <= $range['min'] ) {
			return '';
		}
		$slider = static fn ( string $which, int $value, string $label ): string => sprintf(
			'<input type="range" min="%1$d" max="%2$d" step="%3$d" value="%4$d" aria-label="%5$s" class="pointer-events-none absolute inset-x-0 top-0 m-0 h-5 w-full appearance-none bg-transparent [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:size-5 [&::-moz-range-thumb]:cursor-pointer [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-primary [&::-moz-range-thumb]:bg-surface [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:size-5 [&::-webkit-slider-thumb]:cursor-pointer [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-primary [&::-webkit-slider-thumb]:bg-surface [&::-webkit-slider-thumb]:shadow-sm" data-um-range="%6$s">',
			$range['min'],
			$range['max'],
			max( 1, $range['step'] ),
			$value,
			esc_attr( $label ),
			esc_attr( $which )
		);
		$number = static fn ( string $which, string $name, int $value, string $label ): string => sprintf(
			'<label for="%1$s-%2$s" class="grid gap-1 text-xs text-fg-3">%3$s<input id="%1$s-%2$s" type="number" inputmode="numeric" name="%4$s" min="%5$d" max="%6$d" step="%7$d" value="%8$d" dir="ltr" class="h-9 w-full rounded-md border border-border bg-surface px-2 font-num text-sm text-fg outline-none focus:border-primary focus:shadow-[var(--um-focus)]" data-um-range-field="%2$s" data-bound="%9$d"></label>',
			esc_attr( $field ),
			esc_attr( $which ),
			esc_html( $label ),
			esc_attr( $name ),
			$range['min'],
			$range['max'],
			max( 1, $range['step'] ),
			$value,
			'from' === $which ? $range['min'] : $range['max']
		);
		return sprintf(
			'<div class="grid gap-3" data-um-component="price-range"><div class="relative mx-1 h-5"><span class="absolute inset-x-0 top-2 h-1 rounded-pill bg-border"></span><span class="absolute top-2 h-1 rounded-pill bg-primary" data-um-range-fill></span>%1$s%2$s</div><div class="grid grid-cols-2 gap-2">%3$s%4$s</div><span class="text-xs text-fg-3">%5$s</span></div>',
			$slider( 'from', $range['from'], $range['from_label'] ),
			$slider( 'to', $range['to'], $range['to_label'] ),
			$number( 'from', $range['min_name'], $range['from'], $range['from_label'] ),
			$number( 'to', $range['max_name'], $range['to'], $range['to_label'] ),
			esc_html( $range['unit'] )
		);
	}
}
