<?php
/**
 * ActivityList component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The shopper's reviews or questions (ACC-05): the product, what they
 * wrote, the date, stars for a review, and whether it is published or
 * waiting for approval.
 *
 * @phpstan-type ActivityItem array{product: string, href: string, image: Html, text: string, date: string, status: array{label: string, tone: 'success'|'warning'|'neutral'}, stars?: Html, note?: string}
 * @phpstan-type ActivityListProps array{
 *     label: string,
 *     items: list<ActivityItem>,
 *     class?: string,
 * }
 */
final class ActivityList {

	/**
	 * Renders the list.
	 *
	 * @param array $props Props.
	 * @phpstan-param ActivityListProps $props
	 */
	public static function render( array $props ): Html {
		$items = '';
		foreach ( $props['items'] as $item ) {
			$items .= sprintf(
				'<li class="flex gap-4 border-0 border-t border-solid border-border py-4 first:border-t-0 first:pt-0 max-md:gap-3"><a href="%1$s" class="size-16 shrink-0 overflow-hidden rounded-md bg-surface-2 [&_img]:size-full [&_img]:object-contain" tabindex="-1" aria-hidden="true">%2$s</a><div class="grid min-w-0 flex-1 content-start gap-1.5"><div class="flex flex-wrap items-center gap-2"><a href="%1$s" class="min-w-0 flex-1 truncate text-sm font-bold text-fg no-underline hover:text-primary">%3$s</a>%4$s</div><div class="flex flex-wrap items-center gap-3 text-xs text-fg-3">%5$s<span class="font-num">%6$s</span>%7$s</div><p class="m-0 line-clamp-3 text-sm leading-body text-fg-2">%8$s</p></div></li>',
				esc_url( $item['href'] ),
				$item['image'],
				esc_html( $item['product'] ),
				Badge::render(
					array(
						'tone'     => $item['status']['tone'],
						'children' => $item['status']['label'],
					)
				),
				$item['stars'] ?? '',
				esc_html( $item['date'] ),
				isset( $item['note'] ) && '' !== $item['note'] ? sprintf( '<span>%s</span>', esc_html( $item['note'] ) ) : '',
				esc_html( $item['text'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<ul class="%1$s" aria-label="%2$s">%3$s</ul>',
				esc_attr( Attributes::classes( 'um-activity-list m-0 list-none p-0', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
