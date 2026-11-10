<?php
/**
 * Gallery component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A grid of images that open large in a window, one after another with
 * arrows, swipes or arrow keys (HOME-12). Each thumbnail is a link to the
 * large image, so it works without JavaScript too.
 *
 * @phpstan-type GalleryImage array{thumb: Html, full: string, caption?: string}
 * @phpstan-type GalleryProps array{
 *     images: list<GalleryImage>,
 *     label: string,
 *     columns?: 2|3|4|5|6,
 *     close: string,
 *     previous: string,
 *     next: string,
 *     class?: string,
 * }
 */
final class Gallery {

	/**
	 * Renders the gallery.
	 *
	 * @param array $props Props.
	 * @phpstan-param GalleryProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['images'] ) {
			return Html::none();
		}
		$columns = match ( $props['columns'] ?? 4 ) {
			2       => 'sm:grid-cols-2',
			3       => 'sm:grid-cols-3',
			5       => 'sm:grid-cols-3 lg:grid-cols-5',
			6       => 'sm:grid-cols-3 lg:grid-cols-6',
			default => 'sm:grid-cols-3 lg:grid-cols-4',
		};
		$items = '';
		foreach ( $props['images'] as $index => $image ) {
			$items .= sprintf(
				'<li><a href="%1$s" class="group block aspect-square overflow-hidden rounded-md bg-surface-3 [&_img]:size-full [&_img]:object-cover [&_img]:transition-transform [&_img]:duration-500 hover:[&_img]:scale-105" data-index="%2$d" data-caption="%3$s">%4$s</a></li>',
				esc_url( $image['full'] ),
				$index,
				esc_attr( $image['caption'] ?? '' ),
				$image['thumb']
			);
		}
		$viewer = sprintf(
			'<dialog class="m-auto max-h-none max-w-none bg-transparent p-0 backdrop:bg-black/85" aria-label="%1$s" data-viewer><div class="relative grid h-[100dvh] w-screen place-items-center p-4"><figure class="m-0 grid max-h-full place-items-center gap-2"><img src="" alt="" class="max-h-[85dvh] max-w-full rounded-md object-contain" data-image><figcaption class="text-center text-sm text-white" data-caption></figcaption></figure><button type="button" class="absolute end-4 top-4 grid size-11 place-items-center rounded-full bg-white/15 text-white hover:bg-white/25" data-close aria-label="%2$s">%3$s</button><button type="button" class="absolute start-4 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/15 text-white hover:bg-white/25" data-step="-1" aria-label="%4$s">%5$s</button><button type="button" class="absolute end-4 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/15 text-white hover:bg-white/25" data-step="1" aria-label="%6$s">%7$s</button></div></dialog>',
			esc_attr( $props['label'] ),
			esc_attr( $props['close'] ),
			Icon::render( 'close', 'size-5' ),
			esc_attr( $props['previous'] ),
			Icon::render( 'chevron-right', 'size-6 ltr:rotate-180' ),
			esc_attr( $props['next'] ),
			Icon::render( 'chevron-left', 'size-6 ltr:rotate-180' )
		);
		return Html::trusted(
			sprintf(
				'<div%1$s><ul class="m-0 grid list-none grid-cols-2 gap-2 p-0 %2$s" aria-label="%3$s">%4$s</ul>%5$s</div>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-gallery', $props['class'] ?? null ),
						'data-um-component' => 'gallery',
					)
				),
				esc_attr( $columns ),
				esc_attr( $props['label'] ),
				$items,
				$viewer
			)
		);
	}
}
