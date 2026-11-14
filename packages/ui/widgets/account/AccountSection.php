<?php
/**
 * AccountSection component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * One box of the account pages: a heading row with an optional "See all"
 * link or other content at its end, then the content.
 *
 * @phpstan-type AccountSectionProps array{
 *     title: string,
 *     children: Html|list<Html>,
 *     more?: array{label: string, href: string},
 *     end?: Html,
 *     heading?: 'h1'|'h2',
 *     id?: string,
 *     class?: string,
 * }
 */
final class AccountSection {

	/**
	 * Renders the section.
	 *
	 * @param array $props Props.
	 * @phpstan-param AccountSectionProps $props
	 */
	public static function render( array $props ): Html {
		$tag  = Props::one_of( 'AccountSection', $props, 'heading', array( 'h2', 'h1' ) );
		$more = isset( $props['more'] )
			? sprintf(
				'<a href="%1$s" class="inline-flex items-center gap-1 text-sm font-medium text-primary no-underline hover:underline">%2$s%3$s</a>',
				esc_url( $props['more']['href'] ),
				esc_html( $props['more']['label'] ),
				Icon::render( is_rtl() ? 'chevron-left' : 'chevron-right', 'size-4' )
			)
			: '';
		return Html::trusted(
			sprintf(
				'<section%1$s><div class="mb-4 flex flex-wrap items-center justify-between gap-3"><%2$s class="m-0 text-lg font-bold">%3$s</%2$s>%4$s%5$s</div>%6$s</section>',
				Attributes::render(
					array(
						'id'    => $props['id'] ?? null,
						'class' => Attributes::classes( 'um-account-section min-w-0 rounded-lg border border-border bg-surface p-5 max-md:p-4', $props['class'] ?? null ),
					)
				),
				$tag,
				esc_html( $props['title'] ),
				$props['end'] ?? '',
				$more,
				Html::from( $props['children'] )
			)
		);
	}
}
