<?php
/**
 * ReviewList component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Reviews of a product (PDP-08, PDP-09): filter chips (all, buyers, with
 * photos), the sort order, the reviews, and "show more".
 *
 * Every chip, sort choice and "show more" is a link, so the list works
 * without JavaScript; the reviews behaviour loads them in place from
 * `endpoint` and sends "helpful" votes.
 *
 * A review shows its author and Jalali date, the buyer badge, its rating,
 * title, text, strengths and weaknesses, photos, whether the author
 * recommends the product, the store's reply and the vote buttons.
 *
 * @phpstan-type ReviewItem array{
 *     id: int,
 *     author: string,
 *     date: string,
 *     datetime: string,
 *     rating: int,
 *     rating_text: string,
 *     rating_label: string,
 *     text: Html,
 *     title?: string,
 *     buyer?: string,
 *     note?: string,
 *     pros?: list<string>,
 *     cons?: list<string>,
 *     recommend?: array{label: string, tone: 'yes'|'no'|'unsure'},
 *     images?: list<array{thumb: Html, full: string}>,
 *     replies?: list<array{author: string, text: Html}>,
 *     votes?: array{up: string, down: string},
 *     pending?: bool,
 * }
 * @phpstan-type ReviewChoice array{label: string, href: string, value: string, current: bool}
 * @phpstan-type ReviewListProps array{
 *     id: string,
 *     product: int,
 *     items: list<ReviewItem>,
 *     labels: array{filters: string, sort: string, helpful: string, up: string, down: string, pros: string, cons: string, reply: string, empty: string, pending: string},
 *     filters?: list<ReviewChoice>,
 *     sorts?: list<ReviewChoice>,
 *     more?: array{label: string, href: string},
 *     endpoint?: string,
 *     vote?: string,
 *     class?: string,
 * }
 */
final class ReviewList {

	/**
	 * Renders the list.
	 *
	 * @param array $props Props.
	 * @phpstan-param ReviewListProps $props
	 */
	public static function render( array $props ): Html {
		$id      = Props::id( 'ReviewList', $props );
		$filters = '';
		foreach ( $props['filters'] ?? array() as $choice ) {
			$filters .= sprintf(
				'<a href="%1$s" class="inline-flex h-8 items-center rounded-pill border border-border bg-surface px-3 text-xs font-medium text-fg-2 no-underline hover:border-border-strong aria-current:border-primary aria-current:bg-primary-soft aria-current:text-primary" rel="nofollow" data-um-reviews-link%2$s>%3$s</a>',
				esc_url( $choice['href'] ),
				$choice['current'] ? ' aria-current="true"' : '',
				esc_html( $choice['label'] )
			);
		}
		$sorts = '';
		foreach ( $props['sorts'] ?? array() as $choice ) {
			$sorts .= sprintf(
				'<a href="%1$s" class="text-xs font-medium text-fg-2 no-underline hover:text-fg aria-current:font-bold aria-current:text-primary" rel="nofollow" data-um-reviews-link%2$s>%3$s</a>',
				esc_url( $choice['href'] ),
				$choice['current'] ? ' aria-current="true"' : '',
				esc_html( $choice['label'] )
			);
		}
		$bar   = '' === $filters && '' === $sorts ? '' : sprintf(
			'<div class="flex flex-wrap items-center justify-between gap-3 pb-3"><nav class="flex flex-wrap gap-2" aria-label="%1$s">%2$s</nav><nav class="flex flex-wrap items-center gap-3" aria-label="%3$s">%4$s%5$s</nav></div>',
			esc_attr( $props['labels']['filters'] ),
			$filters,
			esc_attr( $props['labels']['sort'] ),
			Icon::render( 'sort', 'size-4 text-fg-3' ),
			$sorts
		);
		$items = self::items( $props['items'], $props['labels'] );
		if ( '' === $items ) {
			$items = sprintf( '<p class="m-0 border-t border-border py-6 text-center text-sm text-fg-3">%s</p>', esc_html( $props['labels']['empty'] ) );
		}
		return Html::trusted(
			sprintf(
				'<div%1$s>%2$s<div data-um-reviews-items>%3$s</div>%4$s</div>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes( 'um-review-list grid min-w-0 content-start', $props['class'] ?? null ),
						'data-um-component' => 'reviews',
						'data-product'      => (string) $props['product'],
						'data-endpoint'     => isset( $props['endpoint'] ) ? esc_url_raw( $props['endpoint'] ) : null,
						'data-vote'         => isset( $props['vote'] ) ? esc_url_raw( $props['vote'] ) : null,
					)
				),
				$bar,
				$items,
				self::more( $props['more'] ?? null )
			)
		);
	}

	/**
	 * The "show more" link.
	 *
	 * @param array{label: string, href: string}|null $more Link.
	 */
	public static function more( ?array $more ): string {
		if ( null === $more ) {
			return '';
		}
		return sprintf(
			'<a href="%1$s" class="mt-3 inline-flex items-center gap-1 justify-self-start text-sm font-bold text-link no-underline hover:underline" rel="nofollow" data-um-reviews-more>%2$s%3$s</a>',
			esc_url( $more['href'] ),
			esc_html( $more['label'] ),
			Icon::render( 'chevron-down', 'size-4' )
		);
	}

	/**
	 * The reviews only, for "show more".
	 *
	 * @param array $items  Reviews.
	 * @param array $labels Texts.
	 * @phpstan-param list<ReviewItem> $items
	 * @phpstan-param array{filters: string, sort: string, helpful: string, up: string, down: string, pros: string, cons: string, reply: string, empty: string, pending: string} $labels
	 */
	public static function items( array $items, array $labels ): string {
		$out = '';
		foreach ( $items as $item ) {
			$out .= self::item( $item, $labels );
		}
		return $out;
	}

	/**
	 * One review.
	 *
	 * @param array $item   Review.
	 * @param array $labels Texts.
	 * @phpstan-param ReviewItem $item
	 * @phpstan-param array{filters: string, sort: string, helpful: string, up: string, down: string, pros: string, cons: string, reply: string, empty: string, pending: string} $labels
	 */
	private static function item( array $item, array $labels ): string {
		$tone   = $item['rating'] >= 4 ? 'bg-success-text text-white' : ( $item['rating'] >= 3 ? 'bg-warning-soft text-warning-text' : 'bg-danger-soft text-danger-text' );
		$points = '';
		foreach ( $item['pros'] ?? array() as $text ) {
			$points .= sprintf( '<li class="flex items-start gap-2"><span class="w-4 shrink-0 text-center font-heavy text-success-text" aria-hidden="true">+</span><span><span class="sr-only">%1$s: </span>%2$s</span></li>', esc_html( $labels['pros'] ), esc_html( $text ) );
		}
		foreach ( $item['cons'] ?? array() as $text ) {
			$points .= sprintf( '<li class="flex items-start gap-2"><span class="w-4 shrink-0 text-center font-heavy text-danger-text" aria-hidden="true">−</span><span><span class="sr-only">%1$s: </span>%2$s</span></li>', esc_html( $labels['cons'] ), esc_html( $text ) );
		}
		$images = '';
		foreach ( $item['images'] ?? array() as $image ) {
			$images .= sprintf( '<a href="%1$s" class="grid size-18 place-items-center overflow-hidden rounded-md bg-surface-3 [&_img]:size-full [&_img]:object-cover" target="_blank" rel="noopener">%2$s</a>', esc_url( $image['full'] ), $image['thumb'] );
		}
		$replies = '';
		foreach ( $item['replies'] ?? array() as $reply ) {
			$replies .= sprintf(
				'<div class="rounded-md bg-surface-2 p-3 text-sm"><b class="mb-1 block text-xs text-primary">%1$s: %2$s</b><div class="text-fg-2 [&_p]:m-0">%3$s</div></div>',
				esc_html( $labels['reply'] ),
				esc_html( $reply['author'] ),
				$reply['text']
			);
		}
		$recommend = '';
		if ( isset( $item['recommend'] ) ) {
			$recommend = sprintf(
				'<span class="inline-flex items-center gap-1 text-xs %1$s">%2$s%3$s</span>',
				'yes' === $item['recommend']['tone'] ? 'text-success-text' : ( 'no' === $item['recommend']['tone'] ? 'text-danger-text' : 'text-fg-3' ),
				Icon::render( 'yes' === $item['recommend']['tone'] ? 'thumb-up' : 'info', 'size-4' ),
				esc_html( $item['recommend']['label'] )
			);
		}
		$votes = '';
		if ( isset( $item['votes'] ) ) {
			$button = 'inline-flex h-8 cursor-pointer items-center gap-1 rounded-sm border-0 bg-transparent px-2 font-num text-xs text-fg-2 hover:bg-surface-3 aria-pressed:bg-primary-soft aria-pressed:text-primary disabled:cursor-default';
			$votes  = sprintf(
				'<span class="text-xs text-fg-3">%1$s</span><button type="button" class="%2$s" data-um-vote="up" aria-pressed="false" aria-label="%3$s">%4$s<span data-um-vote-count>%5$s</span></button><button type="button" class="%2$s" data-um-vote="down" aria-pressed="false" aria-label="%6$s">%7$s<span data-um-vote-count>%8$s</span></button>',
				esc_html( $labels['helpful'] ),
				esc_attr( $button ),
				esc_attr( $labels['up'] ),
				Icon::render( 'thumb-up', 'size-4' ),
				esc_html( $item['votes']['up'] ),
				esc_attr( $labels['down'] ),
				Icon::render( 'thumb-up', 'size-4 rotate-180' ),
				esc_html( $item['votes']['down'] )
			);
		}
		$initial = function_exists( 'mb_substr' ) ? mb_substr( $item['author'], 0, 1 ) : substr( $item['author'], 0, 1 );
		return sprintf(
			'<article id="review-%1$d" class="grid gap-2 border-t border-border py-4" data-review="%1$d"><div class="flex items-center gap-3 text-sm"><span class="grid size-9 shrink-0 place-items-center rounded-full bg-primary-soft font-bold text-primary" aria-hidden="true">%2$s</span><div class="min-w-0 flex-1"><b class="block">%3$s</b><span class="text-xs text-fg-3"><time datetime="%4$s">%5$s</time>%6$s</span></div>%7$s<span class="inline-grid h-6 min-w-9 place-items-center rounded-sm px-1.5 font-num text-xs font-bold %8$s" aria-label="%9$s">%10$s</span></div>%11$s%12$s<div class="text-sm text-fg-2 [&_p]:m-0 [&_p+p]:mt-2">%13$s</div>%14$s%15$s%16$s<div class="flex flex-wrap items-center gap-2">%17$s<span class="flex-1"></span>%18$s</div></article>',
			$item['id'],
			esc_html( $initial ),
			esc_html( $item['author'] ),
			esc_attr( $item['datetime'] ),
			esc_html( $item['date'] ),
			isset( $item['note'] ) && '' !== $item['note'] ? ' · ' . esc_html( $item['note'] ) : '',
			isset( $item['buyer'] ) ? sprintf( '<span class="inline-flex h-6 items-center gap-1 rounded-pill bg-success-soft px-2 text-xs font-bold text-success-text">%1$s%2$s</span>', Icon::render( 'verified', 'size-3.5' ), esc_html( $item['buyer'] ) ) : '',
			esc_attr( $tone ),
			esc_attr( $item['rating_label'] ),
			esc_html( $item['rating_text'] ),
			( $item['pending'] ?? false ) ? sprintf( '<p class="m-0 text-xs font-medium text-warning-text">%s</p>', esc_html( $labels['pending'] ) ) : '',
			isset( $item['title'] ) && '' !== $item['title'] ? sprintf( '<h3 class="m-0 text-md font-bold">%s</h3>', esc_html( $item['title'] ) ) : '',
			$item['text'],
			'' === $images ? '' : '<div class="flex flex-wrap gap-2">' . $images . '</div>',
			'' === $points ? '' : '<ul class="m-0 grid list-none gap-0.5 p-0 text-sm">' . $points . '</ul>',
			$replies,
			$recommend,
			$votes
		);
	}
}
