<?php
/**
 * TrustMarks component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Trust badges and payment marks (HOME-11, HDR-11): a heading and text on
 * one side, framed marks on the other. A mark is an image with an optional
 * link, or the HTML code a trust service gives the store (for example the
 * e-Namad badge), which the store owner pastes in the settings.
 *
 * Also the store's advantages ("fast shipping", "cash on delivery"), as
 * round icons with two lines each.
 *
 * @phpstan-type TrustMark array{label: string, image?: Html, href?: string, code?: Html}
 * @phpstan-type Perk array{icon: string, title: string, text?: string}
 * @phpstan-type TrustMarksProps array{
 *     marks?: list<TrustMark>,
 *     perks?: list<Perk>,
 *     title?: string,
 *     text?: string,
 *     layout?: 'band'|'marks',
 *     class?: string,
 * }
 */
final class TrustMarks {

	/**
	 * Renders the marks.
	 *
	 * @param array $props Props.
	 * @phpstan-param TrustMarksProps $props
	 */
	public static function render( array $props ): Html {
		$marks = '';
		foreach ( $props['marks'] ?? array() as $mark ) {
			if ( isset( $mark['code'] ) && ! $mark['code']->is_empty() ) {
				$inner = (string) $mark['code'];
			} elseif ( isset( $mark['image'] ) && ! $mark['image']->is_empty() ) {
				$inner = (string) $mark['image'];
			} else {
				$inner = sprintf( '<span class="text-2xs leading-relaxed text-fg-3">%s</span>', esc_html( $mark['label'] ) );
			}
			if ( isset( $mark['href'] ) && '' !== $mark['href'] && ! isset( $mark['code'] ) ) {
				$inner = sprintf( '<a href="%1$s" class="grid size-full place-items-center no-underline" target="_blank" rel="noopener" aria-label="%2$s">%3$s</a>', esc_url( $mark['href'] ), esc_attr( $mark['label'] ), $inner );
			}
			$marks .= sprintf( '<li class="grid h-24 w-22 place-items-center overflow-hidden rounded-md border-[1.5px] border-dashed border-border-strong bg-surface p-1.5 text-center [&_img]:max-h-full [&_img]:max-w-full max-md:h-20 max-md:w-18" title="%1$s">%2$s</li>', esc_attr( $mark['label'] ), $inner );
		}
		$perks = '';
		foreach ( $props['perks'] ?? array() as $perk ) {
			$perks .= sprintf(
				'<li class="flex items-center gap-3 max-md:flex-col max-md:gap-1.5 max-md:text-center"><span class="grid size-13 shrink-0 place-items-center rounded-full bg-primary-soft text-primary">%1$s</span><span><b class="block text-sm">%2$s</b>%3$s</span></li>',
				Icon::render( $perk['icon'], 'size-6' ),
				esc_html( $perk['title'] ),
				isset( $perk['text'] ) && '' !== $perk['text'] ? sprintf( '<small class="text-xs text-fg-3">%s</small>', esc_html( $perk['text'] ) ) : ''
			);
		}
		$out = '';
		if ( '' !== $perks ) {
			$out .= sprintf( '<ul class="m-0 grid list-none grid-cols-2 gap-4 p-0 md:grid-cols-3 lg:grid-cols-5">%s</ul>', $perks );
		}
		if ( '' !== $marks ) {
			$head = '';
			if ( isset( $props['title'] ) && '' !== $props['title'] ) {
				$head = sprintf(
					'<div class="min-w-0"><h2 class="m-0 text-lg font-heavy">%1$s</h2>%2$s</div>',
					esc_html( $props['title'] ),
					isset( $props['text'] ) && '' !== $props['text'] ? sprintf( '<p class="m-0 mt-1 text-sm text-fg-2">%s</p>', esc_html( $props['text'] ) ) : ''
				);
			}
			$out .= sprintf( '<div class="flex flex-wrap items-center justify-between gap-6">%1$s<ul class="m-0 flex list-none flex-wrap gap-2 p-0">%2$s</ul></div>', $head, $marks );
		}
		if ( '' === $out ) {
			return Html::none();
		}
		return Html::trusted( sprintf( '<div class="%1$s">%2$s</div>', esc_attr( Attributes::classes( 'um-trust-marks grid gap-6', $props['class'] ?? null ) ), $out ) );
	}
}
