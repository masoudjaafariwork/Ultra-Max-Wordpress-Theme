<?php
/**
 * SiteHeader component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The default site header: logo, search, actions and the menu bar.
 *
 * The layout builder of phase 3 replaces it with a header designed in
 * Elementor; this one is what a fresh install and a site without the core
 * plugin show. On phones and tablets the menu bar moves into a drawer
 * opened by the menu button.
 *
 * @phpstan-type SiteHeaderProps array{
 *     logo: Html,
 *     menu_label: string,
 *     search?: Html,
 *     nav?: Html,
 *     actions?: Html|list<Html>,
 *     drawer?: string,
 *     sticky?: bool,
 *     class?: string,
 * }
 */
final class SiteHeader {

	/**
	 * Renders the header.
	 *
	 * @param array $props Props.
	 * @phpstan-param SiteHeaderProps $props
	 */
	public static function render( array $props ): Html {
		$sticky = $props['sticky'] ?? true;
		$burger = '';
		if ( isset( $props['drawer'] ) ) {
			$burger = sprintf(
				'<button type="button" class="%1$s" data-um-open="%2$s" aria-haspopup="dialog" aria-label="%3$s">%4$s</button>',
				'inline-flex size-11 shrink-0 items-center justify-center rounded-md text-fg hover:bg-surface-3 focus-visible:outline-3 focus-visible:outline-primary/40 lg:hidden',
				esc_attr( $props['drawer'] ),
				esc_attr( $props['menu_label'] ),
				Icon::render( 'menu', 'size-6' )
			);
		}
		$search = isset( $props['search'] ) ? sprintf( '<div class="order-last w-full md:order-none md:max-w-150 md:flex-1">%s</div>', $props['search'] ) : '';
		$nav    = isset( $props['nav'] ) && ! $props['nav']->is_empty()
			? sprintf( '<div class="max-lg:hidden"><div class="mx-auto max-w-(--um-container) px-(--um-gutter)">%s</div></div>', $props['nav'] )
			: '';

		return Html::trusted(
			sprintf(
				'<header%1$s><div class="mx-auto flex max-w-(--um-container) flex-wrap items-center gap-x-6 gap-y-2 px-(--um-gutter) py-3 md:min-h-18 md:flex-nowrap max-md:gap-x-2">%2$s%3$s%4$s<div class="ms-auto flex items-center gap-2">%5$s</div></div>%6$s</header>',
				Attributes::render(
					array(
						'class'             => Attributes::classes(
							'um-scope um-site-header z-(--um-z-header) bg-surface shadow-[0_1px_0_var(--um-border)]',
							$sticky ? 'sticky top-[var(--wp-admin--admin-bar--height,0px)]' : 'relative',
							$props['class'] ?? null
						),
						'data-um-component' => 'site-header',
					)
				),
				$burger,
				$props['logo'],
				$search,
				Html::from( $props['actions'] ?? null ),
				$nav
			)
		);
	}

	/**
	 * A round icon link for the actions area, such as account or cart.
	 *
	 * @param string   $href  Link.
	 * @param string   $label Accessible name.
	 * @param string   $icon  Icon name.
	 * @param int|null $count Badge number, or null for none.
	 */
	public static function action( string $href, string $label, string $icon, ?int $count = null ): Html {
		$badge = null === $count ? '' : sprintf(
			'<span class="absolute -top-1 -start-1 grid h-4.5 min-w-4.5 place-items-center rounded-pill border-2 border-surface bg-primary px-1 text-2xs font-bold text-on-primary">%s</span>',
			esc_html( number_format_i18n( $count ) )
		);
		return Html::trusted(
			sprintf(
				'<a href="%1$s" class="relative inline-flex size-11 items-center justify-center rounded-md text-fg no-underline hover:bg-surface-3 focus-visible:outline-3 focus-visible:outline-primary/40" aria-label="%2$s">%3$s%4$s</a>',
				esc_url( $href ),
				esc_attr( $label ),
				Icon::render( $icon, 'size-6' ),
				$badge
			)
		);
	}
}
