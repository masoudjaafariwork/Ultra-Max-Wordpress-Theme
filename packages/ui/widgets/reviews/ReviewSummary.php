<?php
/**
 * ReviewSummary component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The left column of a product's reviews (PDP-07, PDP-08, PDP-09): the
 * average and its stars, how many shoppers gave each star (each a link that
 * filters the list), the average of each criterion, the share of buyers
 * who recommend the product, the shoppers' photos and the way to write a
 * review.
 *
 * @phpstan-type ReviewSummaryProps array{
 *     score: string,
 *     value: float,
 *     out_of: string,
 *     label: string,
 *     count: string,
 *     bars?: list<array{label: string, share: int, count: string, href?: string}>,
 *     criteria?: list<array{label: string, value: string, share: int}>,
 *     recommend?: string,
 *     photos?: list<array{thumb: Html, full: string}>,
 *     photos_label?: string,
 *     more_photos?: string,
 *     write?: array{label: string, href: string, note?: string},
 *     class?: string,
 * }
 */
final class ReviewSummary {

	/**
	 * Renders the summary.
	 *
	 * @param array $props Props.
	 * @phpstan-param ReviewSummaryProps $props
	 */
	public static function render( array $props ): Html {
		$bars = '';
		foreach ( $props['bars'] ?? array() as $bar ) {
			$row   = sprintf(
				'<span class="w-14 shrink-0 text-xs text-fg-2">%1$s</span><span class="h-1.5 flex-1 overflow-hidden rounded-pill bg-surface-3"><i class="block h-full rounded-pill bg-rating" style="width:%2$d%%"></i></span><span class="w-8 shrink-0 text-end font-num text-xs text-fg-3">%3$s</span>',
				esc_html( $bar['label'] ),
				max( 0, min( 100, $bar['share'] ) ),
				esc_html( $bar['count'] )
			);
			$bars .= isset( $bar['href'] )
				? sprintf( '<li><a href="%1$s" class="flex items-center gap-2 rounded-xs no-underline hover:bg-surface-2" rel="nofollow" data-um-reviews-link>%2$s</a></li>', esc_url( $bar['href'] ), $row )
				: sprintf( '<li class="flex items-center gap-2">%s</li>', $row );
		}
		$criteria = '';
		foreach ( $props['criteria'] ?? array() as $item ) {
			$criteria .= sprintf(
				'<li class="grid grid-cols-[88px_minmax(0,1fr)_28px] items-center gap-2 text-xs text-fg-2"><span>%1$s</span><span class="h-1.5 overflow-hidden rounded-pill bg-surface-3"><i class="block h-full rounded-pill bg-primary" style="width:%2$d%%"></i></span><b class="font-num">%3$s</b></li>',
				esc_html( $item['label'] ),
				max( 0, min( 100, $item['share'] ) ),
				esc_html( $item['value'] )
			);
		}
		$photos = '';
		foreach ( $props['photos'] ?? array() as $photo ) {
			$photos .= sprintf(
				'<a href="%1$s" class="grid size-16 place-items-center overflow-hidden rounded-md bg-surface-3 [&_img]:size-full [&_img]:object-cover" target="_blank" rel="noopener">%2$s</a>',
				esc_url( $photo['full'] ),
				$photo['thumb']
			);
		}
		if ( '' !== $photos && isset( $props['more_photos'] ) && '' !== $props['more_photos'] ) {
			$photos .= sprintf( '<span class="grid size-16 place-items-center rounded-md bg-surface-3 font-num font-bold text-fg-2">%s</span>', esc_html( $props['more_photos'] ) );
		}
		$write = isset( $props['write'] ) ? sprintf(
			'%1$s<a href="%2$s" class="inline-flex min-h-10 w-full items-center justify-center rounded-md border border-primary px-4 text-sm font-bold text-primary no-underline hover:bg-primary-soft" data-um-review-write>%3$s</a>',
			isset( $props['write']['note'] ) ? sprintf( '<p class="m-0 text-sm text-fg-2">%s</p>', esc_html( $props['write']['note'] ) ) : '',
			esc_url( $props['write']['href'] ),
			esc_html( $props['write']['label'] )
		) : '';
		return Html::trusted(
			sprintf(
				'<div class="%1$s"><div class="flex items-baseline gap-1.5"><b class="font-num text-4xl leading-none font-heavy">%2$s</b><span class="text-sm text-fg-3">%3$s</span></div>%4$s<small class="font-num text-xs text-fg-3">%5$s</small>%6$s%7$s%8$s%9$s%10$s</div>',
				esc_attr( Attributes::classes( 'um-review-summary grid content-start gap-2 lg:sticky lg:top-[calc(var(--wp-admin--admin-bar--height,0px)+var(--um-header-offset,0px)+80px)]', $props['class'] ?? null ) ),
				esc_html( $props['score'] ),
				esc_html( $props['out_of'] ),
				Stars::show( $props['value'], $props['label'] ),
				esc_html( $props['count'] ),
				'' === $bars ? '' : '<ul class="m-0 mt-2 grid list-none gap-1 p-0">' . $bars . '</ul>',
				'' === $criteria ? '' : '<ul class="m-0 my-2 grid list-none gap-1.5 p-0">' . $criteria . '</ul>',
				isset( $props['recommend'] ) && '' !== $props['recommend'] ? sprintf( '<p class="m-0 inline-flex items-center gap-1 text-sm font-medium text-success-text">%1$s%2$s</p>', Icon::render( 'thumb-up', 'size-4' ), esc_html( $props['recommend'] ) ) : '',
				$write,
				'' === $photos ? '' : sprintf( '<div class="mt-3 grid gap-2"><span class="text-sm font-bold">%1$s</span><div class="flex flex-wrap gap-2">%2$s</div></div>', esc_html( $props['photos_label'] ?? '' ), $photos )
			)
		);
	}
}
