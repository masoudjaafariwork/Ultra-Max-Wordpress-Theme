<?php
/**
 * SearchForm component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Site search: a labelled search field with a submit button.
 *
 * `soft` is the filled field of the header; `outline` fits page content,
 * such as the 404 page or an empty search result.
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

		return Html::trusted(
			sprintf(
				'<form role="search" method="get" action="%1$s" class="%2$s"><label for="%3$s" class="sr-only">%4$s</label><span class="pointer-events-none absolute inset-y-0 start-4 flex items-center text-fg-3">%5$s</span><input id="%3$s" type="search" name="s" value="%6$s" placeholder="%7$s" autocomplete="off" class="%8$s"><button type="submit" class="%9$s">%10$s<span class="sr-only">%11$s</span></button>%12$s</form>',
				esc_url( $props['action'] ),
				esc_attr( Attributes::classes( 'relative flex w-full items-center', $props['class'] ?? null ) ),
				esc_attr( $id ),
				esc_html( $props['label'] ),
				Icon::render( 'search', 'size-5' ),
				esc_attr( $props['value'] ?? '' ),
				esc_attr( $props['placeholder'] ?? '' ),
				esc_attr( Attributes::classes( 'h-11 w-full appearance-none rounded-md border ps-12 pe-24 text-md text-fg outline-none transition-colors duration-150 placeholder:text-fg-3 focus:border-primary focus:bg-surface focus:shadow-[var(--um-focus)] [&::-webkit-search-cancel-button]:hidden', $field ) ),
				'absolute inset-y-1 end-1 inline-flex items-center gap-1 rounded-sm bg-primary px-4 text-sm font-bold text-on-primary hover:bg-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-primary/40',
				esc_html( $props['submit'] ),
				esc_html( $props['label'] ),
				$hidden
			)
		);
	}
}
