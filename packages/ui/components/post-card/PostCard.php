<?php
/**
 * PostCard component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A post in a list: image, badge, title, excerpt and meta. The whole card
 * is one link target through the title, so screen readers hear the title
 * once.
 *
 * `priority` marks the first card of a page: its image loads eagerly with
 * high fetch priority, the others load lazily (PERF-03).
 *
 * @phpstan-type PostCardProps array{
 *     title: string,
 *     href: string,
 *     image_id?: int,
 *     excerpt?: string,
 *     meta?: Html,
 *     badge?: string,
 *     layout?: 'grid'|'list',
 *     heading?: 'h2'|'h3',
 *     priority?: bool,
 *     class?: string,
 * }
 */
final class PostCard {

	/**
	 * Renders the card.
	 *
	 * @param array $props Props.
	 * @phpstan-param PostCardProps $props
	 */
	public static function render( array $props ): Html {
		$layout   = Props::one_of( 'PostCard', $props, 'layout', array( 'grid', 'list' ) );
		$heading  = Props::one_of( 'PostCard', $props, 'heading', array( 'h2', 'h3' ) );
		$priority = $props['priority'] ?? false;
		$image_id = (int) ( $props['image_id'] ?? 0 );

		$media = '';
		if ( $image_id > 0 ) {
			$media = wp_get_attachment_image(
				$image_id,
				'list' === $layout ? 'medium' : 'medium_large',
				false,
				array(
					'class'         => 'size-full object-cover transition-transform duration-300 ease-um group-hover:scale-[1.03]',
					'alt'           => '',
					'loading'       => $priority ? 'eager' : 'lazy',
					'fetchpriority' => $priority ? 'high' : 'auto',
					'decoding'      => 'async',
					'sizes'         => 'list' === $layout ? '(max-width: 767px) 100vw, 280px' : '(max-width: 767px) 100vw, (max-width: 1023px) 50vw, 420px',
				)
			);
			$media = sprintf(
				'<div class="%s">%s</div>',
				'list' === $layout ? 'aspect-video overflow-hidden rounded-md bg-surface-3 md:w-70 md:shrink-0' : 'aspect-video overflow-hidden bg-surface-3',
				$media
			);
		}
		$badge   = isset( $props['badge'] ) && '' !== $props['badge'] ? (string) Badge::render(
			array(
				'children' => $props['badge'],
				'tone'     => 'brand',
			)
		) : '';
		$excerpt = isset( $props['excerpt'] ) && '' !== $props['excerpt'] ? sprintf( '<p class="m-0 line-clamp-3 text-sm leading-body text-fg-2">%s</p>', esc_html( $props['excerpt'] ) ) : '';

		return Html::trusted(
			sprintf(
				'<article class="%1$s">%2$s<div class="%3$s">%4$s<%5$s class="m-0 text-lg font-bold leading-tight"><a href="%6$s" class="text-fg no-underline after:absolute after:inset-0 hover:text-primary focus-visible:outline-none">%7$s</a></%5$s>%8$s%9$s</div></article>',
				esc_attr(
					Attributes::classes(
						'group relative flex overflow-hidden rounded-lg border border-border bg-surface transition-shadow duration-150 ease-um hover:shadow-md has-[a:focus-visible]:shadow-[var(--um-focus)]',
						'list' === $layout ? 'flex-col gap-4 p-4 md:flex-row' : 'flex-col',
						$props['class'] ?? null
					)
				),
				$media,
				'list' === $layout ? 'grid content-start gap-2' : 'grid content-start gap-2 p-4',
				$badge,
				$heading,
				esc_url( $props['href'] ),
				esc_html( $props['title'] ),
				$excerpt,
				Html::from( $props['meta'] ?? null )
			)
		);
	}
}
