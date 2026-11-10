<?php
/**
 * Testimonial component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A customer's words with their name, role, photo and star rating
 * (HOME-12). Several of them go in a Carousel.
 *
 * @phpstan-type TestimonialProps array{
 *     quote: string,
 *     name: string,
 *     role?: string,
 *     photo?: Html,
 *     rating?: int,
 *     rating_label?: string,
 *     class?: string,
 * }
 */
final class Testimonial {

	/**
	 * Renders the testimonial.
	 *
	 * @param array $props Props.
	 * @phpstan-param TestimonialProps $props
	 */
	public static function render( array $props ): Html {
		$stars  = '';
		$rating = max( 0, min( 5, $props['rating'] ?? 0 ) );
		if ( $rating > 0 ) {
			for ( $i = 1; $i <= 5; $i++ ) {
				$stars .= sprintf( '<svg class="size-4 %s" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/></svg>', $i <= $rating ? 'text-rating' : 'text-border-strong' );
			}
			$stars = sprintf( '<span class="flex gap-0.5" role="img" aria-label="%1$s">%2$s</span>', esc_attr( $props['rating_label'] ?? '' ), $stars );
		}
		$photo = isset( $props['photo'] ) && ! $props['photo']->is_empty()
			? sprintf( '<span class="size-12 shrink-0 overflow-hidden rounded-full bg-surface-3 [&_img]:size-full [&_img]:object-cover">%s</span>', $props['photo'] )
			: sprintf( '<span class="grid size-12 shrink-0 place-items-center rounded-full bg-primary-soft text-primary">%s</span>', Icon::render( 'user', 'size-6' ) );
		return Html::trusted(
			sprintf(
				'<figure class="%1$s">%2$s%3$s<blockquote class="m-0 flex-1 text-sm leading-body text-fg-2">%4$s</blockquote><figcaption class="flex items-center gap-3">%5$s<span class="grid"><b class="text-sm">%6$s</b>%7$s</span></figcaption></figure>',
				esc_attr( Attributes::classes( 'um-testimonial m-0 flex h-full flex-col gap-4 rounded-xl border border-border bg-surface p-5', $props['class'] ?? null ) ),
				Icon::render( 'quote', 'size-8 text-primary-soft-2' ),
				$stars,
				esc_html( $props['quote'] ),
				$photo,
				esc_html( $props['name'] ),
				isset( $props['role'] ) && '' !== $props['role'] ? sprintf( '<small class="text-xs text-fg-3">%s</small>', esc_html( $props['role'] ) ) : ''
			)
		);
	}
}
