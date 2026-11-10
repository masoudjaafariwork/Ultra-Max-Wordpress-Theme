<?php
/**
 * FilterChips component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A row of chips that filters a list on the page without reloading it, as
 * in "discount picks by category" (HOME-09). Each chip has a key; items of
 * the target list carry their keys in `data-keys`. The empty key shows all.
 *
 * Without JavaScript every item stays visible and the chips are hidden.
 *
 * @phpstan-type Chip array{key: string, label: string}
 * @phpstan-type FilterChipsProps array{target: string, label: string, chips: list<Chip>, class?: string}
 */
final class FilterChips {

	/**
	 * Renders the chips.
	 *
	 * @param array $props Props.
	 * @phpstan-param FilterChipsProps $props
	 */
	public static function render( array $props ): Html {
		if ( count( $props['chips'] ) < 2 ) {
			return Html::none();
		}
		$chips = '';
		foreach ( $props['chips'] as $index => $chip ) {
			$chips .= sprintf(
				'<button type="button" class="inline-flex min-h-8.5 shrink-0 items-center rounded-pill border border-border bg-surface px-3 text-sm whitespace-nowrap text-fg-2 hover:border-border-strong hover:text-fg aria-pressed:border-primary aria-pressed:bg-primary-soft aria-pressed:font-medium aria-pressed:text-primary" aria-pressed="%1$s" data-key="%2$s">%3$s</button>',
				0 === $index ? 'true' : 'false',
				esc_attr( $chip['key'] ),
				esc_html( $chip['label'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<div%1$s>%2$s</div>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-filter-chips flex gap-2 overflow-x-auto [scrollbar-width:none] [&:not([data-ready])]:hidden', $props['class'] ?? null ),
						'role'              => 'group',
						'aria-label'        => $props['label'],
						'aria-controls'     => $props['target'],
						'data-um-component' => 'filter-chips',
					)
				),
				$chips
			)
		);
	}
}
