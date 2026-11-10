<?php
/**
 * Video component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A video that costs nothing until it is played (HOME-12): a cover image
 * with a play button. Clicking loads the player, either the site's own
 * video file or an embedded player such as Aparat; before that no player,
 * script or iframe loads, which keeps the page fast (PERF-01).
 *
 * @phpstan-type VideoProps array{
 *     label: string,
 *     src: string,
 *     kind: 'file'|'embed',
 *     cover?: Html,
 *     play: string,
 *     ratio?: '16/9'|'4/3'|'1/1'|'9/16',
 *     class?: string,
 * }
 */
final class Video {

	/**
	 * Renders the video.
	 *
	 * @param array $props Props.
	 * @phpstan-param VideoProps $props
	 */
	public static function render( array $props ): Html {
		$ratio = match ( $props['ratio'] ?? '16/9' ) {
			'4/3'   => 'aspect-[4/3]',
			'1/1'   => 'aspect-square',
			'9/16'  => 'aspect-[9/16] max-w-90 mx-auto',
			default => 'aspect-video',
		};
		$cover = isset( $props['cover'] ) && ! $props['cover']->is_empty()
			? sprintf( '<span class="absolute inset-0 [&_img]:size-full [&_img]:object-cover">%s</span>', $props['cover'] )
			: '';
		return Html::trusted(
			sprintf(
				'<div%1$s><button type="button" class="group absolute inset-0 grid size-full cursor-pointer place-items-center border-0 bg-transparent p-0" aria-label="%2$s">%3$s<span class="relative grid size-18 place-items-center rounded-full bg-black/55 text-white transition-transform group-hover:scale-110 group-focus-visible:outline-3 group-focus-visible:outline-white">%4$s</span></button></div>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-video relative overflow-hidden rounded-lg bg-fg', $ratio, $props['class'] ?? null ),
						'data-um-component' => 'video',
						'data-src'          => $props['src'],
						'data-kind'         => $props['kind'],
						'data-title'        => $props['label'],
					)
				),
				esc_attr( $props['play'] ),
				$cover,
				Icon::render( 'play', 'size-8 translate-x-0.5' )
			)
		);
	}
}
