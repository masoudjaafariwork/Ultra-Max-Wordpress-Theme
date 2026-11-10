<?php
/**
 * ImageCompare component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Two images on top of each other with a handle to slide between them,
 * such as before and after (HOME-12). The handle is a native range input,
 * so it works with the keyboard and screen readers; the behaviour moves
 * the dividing line as it changes.
 *
 * @phpstan-type ImageCompareProps array{before: Html, after: Html, label: string, before_label: string, after_label: string, start?: int, class?: string}
 */
final class ImageCompare {

	/**
	 * Renders the comparison.
	 *
	 * @param array $props Props.
	 * @phpstan-param ImageCompareProps $props
	 */
	public static function render( array $props ): Html {
		$start = max( 0, min( 100, $props['start'] ?? 50 ) );
		return Html::trusted(
			sprintf(
				'<div%1$s style="--pos:%2$d%%"><div class="[&_img]:block [&_img]:h-auto [&_img]:w-full">%3$s</div><div class="absolute inset-0 [clip-path:inset(0_calc(100%%-var(--pos))_0_0)] [&_img]:block [&_img]:size-full [&_img]:object-cover" aria-hidden="true">%4$s</div><span class="pointer-events-none absolute inset-y-0 left-(--pos) w-0.5 -translate-x-1/2 bg-white shadow"><span class="absolute top-1/2 left-1/2 grid size-10 -translate-1/2 place-items-center rounded-full bg-white text-fg shadow-md">%5$s</span></span><span class="absolute top-3 left-3 rounded-sm bg-black/50 px-2 py-0.5 text-xs text-white">%6$s</span><span class="absolute top-3 right-3 rounded-sm bg-black/50 px-2 py-0.5 text-xs text-white">%7$s</span><input type="range" min="0" max="100" value="%2$d" class="absolute inset-0 size-full cursor-ew-resize opacity-0" aria-label="%8$s"></div>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-image-compare relative overflow-hidden rounded-lg select-none', $props['class'] ?? null ),
						'data-um-component' => 'image-compare',
						'dir'               => 'ltr',
					)
				),
				$start,
				$props['after'],
				$props['before'],
				Icon::render( 'compare', 'size-5' ),
				esc_html( $props['before_label'] ),
				esc_html( $props['after_label'] ),
				esc_attr( $props['label'] )
			)
		);
	}
}
