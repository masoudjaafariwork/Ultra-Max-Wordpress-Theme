<?php
/**
 * BundleBox component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * "Frequently bought together" (PDP-22): the product and up to three
 * others with a tick each, the total, the bundle saving, and one button
 * that adds the ticked products.
 *
 * `totals` holds the texts for every combination of ticks, keyed by the
 * ticked ids joined with commas, so the behaviour shows the store's own
 * numbers without counting money in the browser.
 *
 * @phpstan-type BundleItem array{id: int, title: string, href: string, image: Html, price: string, fixed?: bool}
 * @phpstan-type BundleTotal array{old: string, now: string, save: string}
 * @phpstan-type BundleBoxProps array{
 *     id: string,
 *     title: string,
 *     items: list<BundleItem>,
 *     totals: array<string, BundleTotal>,
 *     currency: string,
 *     labels: array{total: string, add: string, include: string},
 *     endpoint: string,
 *     messages: array{added: string, cart: string, cart_url: string, error: string},
 *     class?: string,
 * }
 */
final class BundleBox {

	/**
	 * Renders the box, or nothing with fewer than two products.
	 *
	 * @param array $props Props.
	 * @phpstan-param BundleBoxProps $props
	 */
	public static function render( array $props ): Html {
		if ( count( $props['items'] ) < 2 ) {
			return Html::none();
		}
		$id    = Props::id( 'BundleBox', $props );
		$items = '';
		foreach ( $props['items'] as $index => $item ) {
			$items .= sprintf(
				'%1$s<label class="relative flex min-w-36 flex-1 cursor-pointer flex-col gap-1.5 has-disabled:cursor-default"><input type="checkbox" class="peer absolute start-0 top-0 z-1 size-5.5 cursor-pointer accent-primary" value="%2$d" checked%3$s aria-label="%4$s" data-um-bundle-item><span class="mx-auto grid size-28 place-items-center overflow-hidden rounded-md bg-surface-2 transition-opacity peer-not-checked:opacity-45 [&_img]:size-full [&_img]:object-contain">%5$s</span><a href="%6$s" class="line-clamp-2 text-sm text-fg no-underline hover:text-primary">%7$s</a><b class="font-num text-sm">%8$s <small class="font-regular text-fg-3">%9$s</small></b></label>',
				$index > 0 ? '<span class="shrink-0 self-center text-2xl text-fg-3" aria-hidden="true">+</span>' : '',
				$item['id'],
				( $item['fixed'] ?? false ) ? ' disabled' : '',
				esc_attr( sprintf( $props['labels']['include'], $item['title'] ) ),
				$item['image'],
				esc_url( $item['href'] ),
				esc_html( $item['title'] ),
				esc_html( $item['price'] ),
				esc_html( $props['currency'] )
			);
		}
		$all   = implode( ',', array_map( static fn ( array $item ): string => (string) $item['id'], $props['items'] ) );
		$total = $props['totals'][ $all ] ?? array(
			'old'  => '',
			'now'  => '',
			'save' => '',
		);
		return Html::trusted(
			sprintf(
				'<section%1$s><h2 class="m-0 mb-4 text-lg font-bold">%2$s</h2><div class="flex items-center gap-6 max-lg:flex-col max-lg:items-stretch"><div class="flex flex-1 items-start gap-3 overflow-x-auto pb-1">%3$s</div><div class="grid w-60 shrink-0 gap-1.5 rounded-lg border border-border bg-surface-2 p-4 max-lg:w-auto"><span class="text-sm text-fg-3" data-um-bundle-label>%4$s</span><del class="font-num text-sm text-fg-3" data-um-bundle-old>%5$s</del><b class="font-num text-xl font-heavy"><span data-um-bundle-now>%6$s</span> <small class="text-xs font-regular text-fg-3">%7$s</small></b><span class="inline-flex justify-self-start rounded-pill bg-success-soft px-2.5 py-0.5 font-num text-xs font-bold text-success-text data-[empty]:hidden" data-um-bundle-save>%8$s</span><button type="button" class="mt-1 inline-flex min-h-11 cursor-pointer items-center justify-center rounded-md border-0 bg-primary px-4 text-md font-bold text-on-primary hover:bg-primary-hover focus-visible:outline-3 focus-visible:outline-primary/40 aria-busy:opacity-60" data-um-bundle-add>%9$s</button><p class="m-0 hidden text-sm data-shown:block data-[tone=danger]:text-danger-text data-[tone=success]:text-success-text" role="status" data-um-bundle-status></p></div></div><script type="application/json" data-um-bundle-totals>%10$s</script></section>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes( 'um-scope um-bundle rounded-lg border border-border bg-surface p-5 max-md:p-4', $props['class'] ?? null ),
						'data-um-component' => 'bundle',
						'data-endpoint'     => esc_url_raw( $props['endpoint'] ),
						'data-messages'     => (string) wp_json_encode( $props['messages'] ),
					)
				),
				esc_html( $props['title'] ),
				$items,
				esc_html( $props['labels']['total'] ),
				esc_html( $total['old'] ),
				esc_html( $total['now'] ),
				esc_html( $props['currency'] ),
				esc_html( $total['save'] ),
				esc_html( $props['labels']['add'] ),
				wp_json_encode( $props['totals'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE )
			)
		);
	}
}
