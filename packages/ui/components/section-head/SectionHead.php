<?php
/**
 * SectionHead component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The title row of a home page section: a heading with an optional icon,
 * a "see all" link and room for tabs or filters at the end.
 *
 * @phpstan-type SectionHeadProps array{
 *     title: string,
 *     icon?: string,
 *     more?: array{label: string, href: string},
 *     extra?: Html,
 *     align?: 'start'|'center',
 *     level?: 2|3,
 *     id?: string,
 *     class?: string,
 * }
 */
final class SectionHead {

	/**
	 * A home page section: its head row, then its content.
	 *
	 * @param Html        $head    The head row.
	 * @param Html        $content Content.
	 * @param string|null $classes Extra classes.
	 */
	public static function section( Html $head, Html $content, ?string $classes = null ): Html {
		return Html::trusted( sprintf( '<section class="%1$s">%2$s%3$s</section>', esc_attr( Attributes::classes( 'um-section min-w-0', $classes ) ), $head, $content ) );
	}

	/**
	 * Renders the heading row.
	 *
	 * @param array $props Props.
	 * @phpstan-param SectionHeadProps $props
	 */
	public static function render( array $props ): Html {
		if ( '' === $props['title'] && ! isset( $props['extra'] ) ) {
			return Html::none();
		}
		$center = 'center' === ( $props['align'] ?? 'start' );
		$level  = $props['level'] ?? 2;
		$title  = sprintf(
			'<h%1$d%2$s class="%3$s">%4$s%5$s</h%1$d>',
			$level,
			isset( $props['id'] ) ? sprintf( ' id="%s"', esc_attr( $props['id'] ) ) : '',
			esc_attr( Attributes::classes( 'm-0 inline-flex items-center gap-2 font-heading text-xl font-heavy text-fg max-md:text-lg', $center ? null : 'before:h-5 before:w-1 before:rounded-pill before:bg-primary' ) ),
			isset( $props['icon'] ) ? Icon::render( $props['icon'], 'size-6 text-accent' ) : '',
			esc_html( $props['title'] )
		);
		$more   = isset( $props['more'] ) && '' !== $props['more']['href']
			? sprintf(
				'<a href="%1$s" class="inline-flex shrink-0 items-center gap-0.5 text-sm font-bold text-primary no-underline hover:underline">%2$s%3$s</a>',
				esc_url( $props['more']['href'] ),
				esc_html( $props['more']['label'] ),
				Icon::render( 'chevron-left', 'size-4 ltr:rotate-180' )
			)
			: '';
		return Html::trusted(
			sprintf(
				'<div class="%1$s">%2$s%3$s%4$s</div>',
				esc_attr( Attributes::classes( 'um-section-head mb-4 flex flex-wrap items-center gap-x-4 gap-y-2', $center ? 'justify-center text-center' : 'justify-between', $props['class'] ?? null ) ),
				$title,
				isset( $props['extra'] ) ? sprintf( '<div class="min-w-0 max-md:w-full">%s</div>', $props['extra'] ) : '',
				$more
			)
		);
	}
}
