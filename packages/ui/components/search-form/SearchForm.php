<?php
/**
 * SearchForm component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Site search: a labelled search field with a submit button. Themes print it
 * through get_search_form(), so other plugins can still filter the form.
 *
 * `soft` is the filled field of the header; `outline` fits page content,
 * such as the 404 page or an empty search result.
 *
 * With `live`, suggestions appear under the field while the visitor types
 * (SRCH-01): products, categories, brands and posts from the given address.
 * The field becomes a combobox that arrow keys move through. `inline`
 * shows the suggestions in the page flow, as the phone search window does,
 * instead of a floating panel. The live search behaviour lives in the
 * `search` widget asset.
 *
 * @phpstan-type SearchFormProps array{
 *     id: string,
 *     action: string,
 *     label: string,
 *     submit: string,
 *     value?: string,
 *     placeholder?: string,
 *     variant?: 'soft'|'outline',
 *     hidden?: array<string, string>,
 *     live?: array{endpoint: string, label: string, min?: int, inline?: bool},
 *     class?: string,
 * }
 */
final class SearchForm {

	/**
	 * Renders the form.
	 *
	 * @param array $props Props.
	 * @phpstan-param SearchFormProps $props
	 */
	public static function render( array $props ): Html {
		$id      = Props::id( 'SearchForm', $props );
		$variant = Props::one_of( 'SearchForm', $props, 'variant', array( 'soft', 'outline' ) );
		$hidden  = '';
		foreach ( $props['hidden'] ?? array() as $name => $value ) {
			$hidden .= sprintf( '<input type="hidden" name="%s" value="%s">', esc_attr( $name ), esc_attr( $value ) );
		}
		$field = 'soft' === $variant
			? 'bg-surface-3 border-transparent hover:bg-[color-mix(in_srgb,var(--um-gray-100)_70%,var(--um-surface-3))]'
			: 'bg-surface border-border-strong';

		$live    = $props['live'] ?? null;
		$combo   = '';
		$results = '';
		if ( null !== $live ) {
			$combo   = sprintf( ' role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="%s-results"', esc_attr( $id ) );
			$results = sprintf(
				'<div id="%1$s-results" role="listbox" aria-label="%2$s" class="%3$s" hidden data-um-search-results></div>',
				esc_attr( $id ),
				esc_attr( $live['label'] ),
				( $live['inline'] ?? false )
					? 'mt-3 w-full'
					: 'absolute inset-x-0 top-full z-(--um-z-sticky) mt-1.5 max-h-[min(70vh,560px)] overflow-y-auto rounded-lg border border-border bg-surface p-2 text-fg shadow-lg'
			);
		}

		return Html::trusted(
			sprintf(
				'<form%1$s><div class="relative flex w-full items-center"><label for="%2$s" class="sr-only">%3$s</label><span class="pointer-events-none absolute inset-y-0 start-4 flex items-center text-fg-3">%4$s</span><input id="%2$s" type="search" name="s" value="%5$s" placeholder="%6$s" autocomplete="off" class="%7$s"%8$s><button type="submit" class="%9$s">%10$s<span class="sr-only">%11$s</span></button></div>%12$s%13$s</form>',
				Attributes::render(
					array(
						'role'              => 'search',
						'method'            => 'get',
						'action'            => esc_url_raw( $props['action'] ),
						'class'             => Attributes::classes( 'relative w-full', $props['class'] ?? null ),
						'data-um-component' => null !== $live ? 'live-search' : null,
						'data-endpoint'     => null !== $live ? esc_url_raw( $live['endpoint'] ) : null,
						'data-min'          => null !== $live ? (string) max( 1, $live['min'] ?? 2 ) : null,
					)
				),
				esc_attr( $id ),
				esc_html( $props['label'] ),
				Icon::render( 'search', 'size-5' ),
				esc_attr( $props['value'] ?? '' ),
				esc_attr( $props['placeholder'] ?? '' ),
				esc_attr( Attributes::classes( 'h-11 w-full appearance-none rounded-md border ps-12 pe-24 text-md text-fg outline-none transition-colors duration-150 placeholder:text-fg-3 focus:border-primary focus:bg-surface focus:shadow-[var(--um-focus)] [&::-webkit-search-cancel-button]:hidden', $field ) ),
				$combo,
				'absolute inset-y-1 end-1 inline-flex items-center gap-1 rounded-sm bg-primary px-4 text-sm font-bold text-on-primary hover:bg-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-primary/40',
				esc_html( $props['submit'] ),
				esc_html( $props['label'] ),
				$hidden,
				$results
			)
		);
	}
}
