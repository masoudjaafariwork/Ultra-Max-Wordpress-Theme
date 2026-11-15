<?php
/**
 * AuthCard component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The narrow, centred card of the login, register and password pages: the
 * logo or an icon, a title with one line under it, the notices, the form
 * (or login and register tabs), links under the form, a small note and a
 * "back to the store" link under the card.
 *
 * @phpstan-type AuthTab array{label: string, content: Html}
 * @phpstan-type AuthCardProps array{
 *     title: string,
 *     lead?: string,
 *     heading?: 'h1'|'h2',
 *     logo?: Html,
 *     icon?: string,
 *     notices?: Html,
 *     children?: Html|list<Html>,
 *     tabs?: array{id: string, label: string, items: non-empty-list<AuthTab>, selected?: int},
 *     links?: list<array{label: string, href: string, text?: string}>,
 *     note?: Html,
 *     back?: array{label: string, href: string},
 *     after?: Html,
 *     class?: string,
 * }
 */
final class AuthCard {

	/**
	 * Renders the card.
	 *
	 * @param array $props Props.
	 * @phpstan-param AuthCardProps $props
	 */
	public static function render( array $props ): Html {
		$tag = Props::one_of( 'AuthCard', $props, 'heading', array( 'h2', 'h1' ) );

		$mark = '';
		if ( isset( $props['logo'] ) ) {
			$mark = (string) $props['logo'];
		} elseif ( isset( $props['icon'] ) ) {
			$mark = sprintf( '<span class="grid size-14 place-items-center rounded-full bg-primary-soft text-primary">%s</span>', Icon::render( $props['icon'], 'size-7' ) );
		}

		$body = '';
		if ( isset( $props['tabs'] ) ) {
			$body = (string) Tabs::render(
				array(
					'id'       => $props['tabs']['id'],
					'label'    => $props['tabs']['label'],
					'items'    => $props['tabs']['items'],
					'selected' => $props['tabs']['selected'] ?? 0,
					'variant'  => 'pill',
					'class'    => '[&_[role=tablist]]:flex [&_[role=tablist]]:w-full [&_[role=tab]]:flex-1 [&_[role=tab]]:justify-center [&_[role=tab]]:py-2 [&_[role=tabpanel]]:pt-5',
				)
			);
		} elseif ( isset( $props['children'] ) ) {
			$body = (string) Html::from( $props['children'] );
		}

		$links = '';
		foreach ( $props['links'] ?? array() as $link ) {
			$links .= sprintf(
				'<p class="m-0 flex flex-wrap items-center justify-center gap-x-1.5 text-sm text-fg-2">%1$s<a href="%2$s" class="font-bold text-primary no-underline hover:underline">%3$s</a></p>',
				isset( $link['text'] ) ? '<span>' . esc_html( $link['text'] ) . '</span>' : '',
				esc_url( $link['href'] ),
				esc_html( $link['label'] )
			);
		}

		$back = '';
		if ( isset( $props['back'] ) ) {
			$back = sprintf(
				'<a href="%1$s" class="inline-flex items-center gap-1.5 justify-self-center text-sm font-medium text-fg-2 no-underline hover:text-primary">%2$s%3$s</a>',
				esc_url( $props['back']['href'] ),
				Icon::render( is_rtl() ? 'arrow-right' : 'arrow-left', 'size-4' ),
				esc_html( $props['back']['label'] )
			);
		}

		return Html::trusted(
			sprintf(
				'<div class="%1$s"><div class="rounded-xl border border-border bg-surface p-6 shadow-sm md:p-8"><div class="mb-6 grid justify-items-center gap-3 text-center">%2$s<%3$s class="m-0 text-xl font-heavy text-fg">%4$s</%3$s>%5$s</div>%6$s%7$s%8$s%9$s</div>%10$s%11$s</div>',
				esc_attr( Attributes::classes( 'um-scope um-auth mx-auto grid w-full max-w-[27rem] gap-4 py-6 md:py-12', $props['class'] ?? null ) ),
				$mark,
				$tag,
				esc_html( $props['title'] ),
				isset( $props['lead'] ) ? '<p class="m-0 text-sm leading-body text-fg-2">' . esc_html( $props['lead'] ) . '</p>' : '',
				isset( $props['notices'] ) && '' !== trim( (string) $props['notices'] ) ? '<div class="um-auth-notices mb-5">' . $props['notices'] . '</div>' : '',
				$body,
				'' === $links ? '' : '<div class="mt-6 grid gap-2">' . $links . '</div>',
				isset( $props['note'] ) && '' !== trim( (string) $props['note'] ) ? '<div class="mt-6 border-t border-border pt-4 text-center text-xs leading-body text-fg-3 [&_a]:text-primary">' . $props['note'] . '</div>' : '',
				$back,
				$props['after'] ?? ''
			)
		);
	}
}
