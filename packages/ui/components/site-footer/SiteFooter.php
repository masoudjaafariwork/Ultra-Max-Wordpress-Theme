<?php
/**
 * SiteFooter component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The default site footer: logo and about text, up to four columns (menus
 * or widgets) and the copyright line. Phase 3 lets the buyer replace it
 * with a footer designed in Elementor.
 *
 * @phpstan-type SiteFooterProps array{
 *     logo: Html,
 *     copyright: string,
 *     about?: string,
 *     columns?: list<Html>,
 *     menu?: Html,
 *     class?: string,
 * }
 */
final class SiteFooter {

	/**
	 * Renders the footer.
	 *
	 * @param array $props Props.
	 * @phpstan-param SiteFooterProps $props
	 */
	public static function render( array $props ): Html {
		$columns = '';
		foreach ( $props['columns'] ?? array() as $column ) {
			if ( ! $column->is_empty() ) {
				$columns .= sprintf( '<div class="min-w-0">%s</div>', $column );
			}
		}
		if ( '' !== $columns ) {
			$columns = sprintf( '<div class="grid gap-8 border-t border-border py-6 sm:grid-cols-2 lg:grid-cols-4">%s</div>', $columns );
		}
		$about = isset( $props['about'] ) && '' !== $props['about'] ? sprintf( '<p class="m-0 max-w-xl text-sm leading-body text-fg-2">%s</p>', esc_html( $props['about'] ) ) : '';

		return Html::trusted(
			sprintf(
				'<footer class="%1$s"><div class="mx-auto max-w-(--um-container) px-(--um-gutter)"><div class="flex flex-wrap items-start justify-between gap-6 pb-6"><div class="grid gap-3">%2$s%3$s</div>%4$s</div>%5$s</div><div class="bg-surface-3 py-3 text-center text-xs text-fg-3"><div class="mx-auto max-w-(--um-container) px-(--um-gutter)">%6$s</div></div></footer>',
				esc_attr( Attributes::classes( 'um-scope um-site-footer mt-12 border-t border-border bg-surface pt-8', $props['class'] ?? null ) ),
				$props['logo'],
				$about,
				Html::from( $props['menu'] ?? null ),
				$columns,
				esc_html( $props['copyright'] )
			)
		);
	}
}
