<?php
/**
 * ArchiveIntro component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The top of a product list: breadcrumbs, the title, the short top
 * description written for the category (PLP-10) and, on a brand page,
 * the brand's logo in a framed header (PLP-11).
 *
 * @phpstan-type ArchiveIntroProps array{
 *     title: string,
 *     breadcrumbs?: Html,
 *     description?: Html,
 *     logo?: Html,
 *     meta?: string,
 *     variant?: 'plain'|'brand',
 *     class?: string,
 * }
 */
final class ArchiveIntro {

	/**
	 * Renders the intro.
	 *
	 * @param array $props Props.
	 * @phpstan-param ArchiveIntroProps $props
	 */
	public static function render( array $props ): Html {
		$variant     = Props::one_of( 'ArchiveIntro', $props, 'variant', array( 'plain', 'brand' ) );
		$description = isset( $props['description'] ) && ! $props['description']->is_empty()
			? sprintf( '<div class="max-w-3xl text-sm leading-body text-fg-2 [&_p]:m-0 [&_p+p]:mt-2">%s</div>', $props['description'] )
			: '';
		$meta        = isset( $props['meta'] ) && '' !== $props['meta'] ? sprintf( '<span class="font-num text-sm text-fg-3">%s</span>', esc_html( $props['meta'] ) ) : '';
		$crumbs      = $props['breadcrumbs'] ?? '';
		if ( 'brand' === $variant ) {
			$logo = isset( $props['logo'] ) && ! $props['logo']->is_empty()
				? sprintf( '<span class="grid size-24 shrink-0 place-items-center overflow-hidden rounded-lg border border-border bg-surface p-2 max-md:size-16 [&_img]:max-h-full [&_img]:w-auto [&_img]:object-contain">%s</span>', $props['logo'] )
				: '';
			return Html::trusted(
				sprintf(
					'<div class="%1$s">%2$s<div class="flex items-center gap-5 rounded-lg border border-border bg-surface p-5 max-md:gap-3 max-md:p-4">%3$s<div class="grid min-w-0 gap-1.5"><h1 class="m-0 text-2xl font-heavy max-md:text-lg">%4$s</h1>%5$s%6$s</div></div></div>',
					esc_attr( Attributes::classes( 'um-archive-intro grid gap-3', $props['class'] ?? null ) ),
					$crumbs,
					$logo,
					esc_html( $props['title'] ),
					$meta,
					$description
				)
			);
		}
		return Html::trusted(
			sprintf(
				'<div class="%1$s">%2$s<div class="flex flex-wrap items-baseline gap-x-3 gap-y-1"><h1 class="m-0 text-2xl font-heavy max-md:text-lg">%3$s</h1>%4$s</div>%5$s</div>',
				esc_attr( Attributes::classes( 'um-archive-intro grid gap-3', $props['class'] ?? null ) ),
				$crumbs,
				esc_html( $props['title'] ),
				$meta,
				$description
			)
		);
	}
}
