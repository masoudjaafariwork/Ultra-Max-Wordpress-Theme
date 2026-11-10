<?php
/**
 * Stories component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A row of round story covers (HOME-02). Tapping one opens a full-screen
 * viewer that plays the stories one after another: images for a few
 * seconds, videos until they end, each with an optional link, for example
 * to a product. Viewed stories lose their coloured ring; the browser
 * remembers them.
 *
 * Without JavaScript each cover is a plain link to its target.
 *
 * @phpstan-type Story array{title: string, cover: string, media: string, type: 'image'|'video', href?: string, link_label?: string, id: string}
 * @phpstan-type StoriesProps array{
 *     label: string,
 *     stories: list<Story>,
 *     close: string,
 *     previous: string,
 *     next: string,
 *     duration?: int,
 *     class?: string,
 * }
 */
final class Stories {

	/**
	 * Renders the stories.
	 *
	 * @param array $props Props.
	 * @phpstan-param StoriesProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['stories'] ) {
			return Html::none();
		}
		$items = '';
		$data  = array();
		foreach ( $props['stories'] as $index => $story ) {
			$data[] = array(
				'title' => $story['title'],
				'media' => $story['media'],
				'type'  => $story['type'],
				'href'  => $story['href'] ?? '',
				'link'  => $story['link_label'] ?? '',
				'id'    => $story['id'],
			);
			$items .= sprintf(
				'<li class="shrink-0"><a href="%1$s" class="group flex w-21 flex-col items-center gap-1.5 text-center text-xs leading-tight text-fg-2 no-underline max-md:w-17" data-story="%2$d" data-id="%3$s"><span class="block size-19 rounded-full bg-[conic-gradient(from_200deg,var(--um-brand-500),var(--um-accent-500),var(--um-brand-700),var(--um-brand-500))] p-0.75 group-data-seen:bg-border-strong max-md:size-16"><img src="%4$s" alt="" class="size-full rounded-full border-3 border-surface bg-surface object-cover" loading="lazy" decoding="async" width="76" height="76"></span><span class="line-clamp-2">%5$s</span></a></li>',
				esc_url( '' !== ( $story['href'] ?? '' ) ? $story['href'] : $story['media'] ),
				$index,
				esc_attr( $story['id'] ),
				esc_url( $story['cover'] ),
				esc_html( $story['title'] )
			);
		}
		$viewer = sprintf(
			'<dialog class="um-story-viewer m-0 size-full max-h-none max-w-none border-0 bg-black p-0 text-white backdrop:bg-black/80 md:m-auto md:h-[min(92vh,840px)] md:w-[min(92vw,472px)] md:rounded-xl" aria-label="%1$s" data-viewer><div class="relative flex size-full flex-col"><div class="absolute inset-x-3 top-3 z-2 flex gap-1" data-bars></div><div class="absolute inset-x-3 top-6 z-2 flex items-center justify-between gap-2"><b class="text-sm" data-title></b><button type="button" class="grid size-10 place-items-center rounded-full bg-black/40 text-white" data-close aria-label="%2$s">%3$s</button></div><div class="relative grid flex-1 place-items-center overflow-hidden" data-stage></div><button type="button" class="absolute inset-y-0 start-0 z-1 w-1/3" data-step="-1" aria-label="%4$s"></button><button type="button" class="absolute inset-y-0 end-0 z-1 w-1/3" data-step="1" aria-label="%5$s"></button><a href="#" class="absolute inset-x-6 bottom-6 z-2 inline-flex h-11 items-center justify-center rounded-md bg-white text-sm font-bold text-fg no-underline" data-link hidden></a></div></dialog>',
			esc_attr( $props['label'] ),
			esc_attr( $props['close'] ),
			Icon::render( 'close', 'size-5' ),
			esc_attr( $props['previous'] ),
			esc_attr( $props['next'] )
		);
		return Html::trusted(
			sprintf(
				'<div%1$s><ul class="m-0 flex list-none gap-4 overflow-x-auto px-0 py-4 [scrollbar-width:none] max-md:gap-3 max-md:py-3" aria-label="%2$s">%3$s</ul>%4$s<script type="application/json" data-stories>%5$s</script></div>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-stories', $props['class'] ?? null ),
						'data-um-component' => 'stories',
						'data-duration'     => (string) ( $props['duration'] ?? 5000 ),
					)
				),
				esc_attr( $props['label'] ),
				$items,
				$viewer,
				wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			)
		);
	}
}
