<?php
/**
 * ProductHeading component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The top of a product's info column (PDP-07): brand and category links,
 * the name, its Latin name, and a line with the rating, the numbers of
 * reviews and questions (links to their sections) and the share of buyers
 * who recommend it.
 *
 * @phpstan-type ProductHeadingProps array{
 *     title: string,
 *     latin?: string,
 *     links?: list<array{label: string, href: string}>,
 *     rating?: array{value: string, label: string, count: string},
 *     reviews?: array{label: string, href: string},
 *     questions?: array{label: string, href: string},
 *     recommend?: string,
 *     badges?: list<array{label: string, tone: 'hot'|'new'|'brand'|'neutral', icon?: string}>,
 *     tag?: 'h1'|'h2',
 *     class?: string,
 * }
 */
final class ProductHeading {

	private const BADGE = array(
		'hot'     => 'bg-accent text-on-accent',
		'new'     => 'bg-success-text text-white',
		'brand'   => 'bg-primary-soft text-primary',
		'neutral' => 'bg-surface-3 text-fg-2',
	);

	/**
	 * Renders the heading.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductHeadingProps $props
	 */
	public static function render( array $props ): Html {
		$tag   = in_array( $props['tag'] ?? 'h1', array( 'h1', 'h2' ), true ) ? ( $props['tag'] ?? 'h1' ) : 'h1';
		$links = array();
		foreach ( $props['links'] ?? array() as $link ) {
			$links[] = sprintf( '<a href="%1$s" class="text-sm text-link no-underline hover:underline">%2$s</a>', esc_url( $link['href'] ), esc_html( $link['label'] ) );
		}
		$badges = '';
		foreach ( $props['badges'] ?? array() as $badge ) {
			$badges .= sprintf(
				'<span class="inline-flex h-6 items-center gap-1 rounded-pill px-2.5 text-xs font-bold %1$s">%2$s%3$s</span>',
				esc_attr( self::BADGE[ $badge['tone'] ] ),
				isset( $badge['icon'] ) ? Icon::render( $badge['icon'], 'size-3.5' ) : '',
				esc_html( $badge['label'] )
			);
		}
		$stats = array();
		if ( isset( $props['rating'] ) ) {
			$stats[] = sprintf(
				'<span class="inline-flex items-center gap-1 text-sm" aria-label="%1$s">%2$s<b class="font-num">%3$s</b><span class="font-num text-fg-3">(%4$s)</span></span>',
				esc_attr( $props['rating']['label'] ),
				'<svg class="size-4.5 shrink-0 text-rating" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/></svg>',
				esc_html( $props['rating']['value'] ),
				esc_html( $props['rating']['count'] )
			);
		}
		foreach ( array( 'reviews', 'questions' ) as $key ) {
			if ( isset( $props[ $key ] ) ) {
				$stats[] = sprintf( '<a href="%1$s" class="font-num text-sm text-link no-underline hover:underline">%2$s</a>', esc_url( $props[ $key ]['href'] ), esc_html( $props[ $key ]['label'] ) );
			}
		}
		if ( isset( $props['recommend'] ) && '' !== $props['recommend'] ) {
			$stats[] = sprintf( '<span class="inline-flex h-6 items-center gap-1 rounded-pill bg-success-soft px-2.5 font-num text-xs font-bold text-success-text">%1$s%2$s</span>', Icon::render( 'thumb-up', 'size-3.5' ), esc_html( $props['recommend'] ) );
		}
		return Html::trusted(
			sprintf(
				'<div class="%1$s">%2$s%3$s<%4$s class="m-0 text-xl leading-[1.75] font-bold max-md:text-lg">%5$s</%4$s>%6$s%7$s</div>',
				esc_attr( Attributes::classes( 'um-product-heading grid gap-2', $props['class'] ?? null ) ),
				array() === $links ? '' : '<div class="flex flex-wrap items-center gap-1.5">' . implode( '<span class="text-fg-3" aria-hidden="true">/</span>', $links ) . '</div>',
				'' === $badges ? '' : '<div class="flex flex-wrap gap-1.5">' . $badges . '</div>',
				$tag,
				esc_html( $props['title'] ),
				isset( $props['latin'] ) && '' !== $props['latin'] ? sprintf( '<p class="m-0 text-xs text-fg-3 rtl:text-right" dir="ltr" lang="en">%s</p>', esc_html( $props['latin'] ) ) : '',
				array() === $stats ? '' : '<div class="flex flex-wrap items-center gap-4 border-b border-border pb-4">' . implode( '', $stats ) . '</div>'
			)
		);
	}
}
