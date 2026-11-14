<?php
/**
 * RecentProducts component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * "Your recent views" (PDP-23). The list lives only in the shopper's
 * browser, so pages stay cacheable and nothing is stored about visitors:
 * the behaviour remembers the products a shopper opens and asks the
 * `endpoint` for their cards. Products that are no longer public are never
 * returned. Hidden until there is something to show.
 *
 * With `record` set, the current product is added to the list; with an
 * empty `endpoint` the component only records.
 *
 * @phpstan-type RecentProductsProps array{
 *     title: string,
 *     endpoint: string,
 *     record?: int,
 *     limit?: int,
 *     class?: string,
 * }
 */
final class RecentProducts {

	/**
	 * Renders the empty section.
	 *
	 * @param array $props Props.
	 * @phpstan-param RecentProductsProps $props
	 */
	public static function render( array $props ): Html {
		return Html::trusted(
			sprintf(
				'<section%1$s hidden><h2 class="m-0 mb-4 text-lg font-bold">%2$s</h2><div data-um-recent-list></div></section>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-scope um-recent-products rounded-lg border border-border bg-surface p-5 max-md:p-4', $props['class'] ?? null ),
						'aria-label'        => $props['title'],
						'data-um-component' => 'recent-products',
						'data-endpoint'     => '' === $props['endpoint'] ? null : esc_url_raw( $props['endpoint'] ),
						'data-record'       => isset( $props['record'] ) ? (string) $props['record'] : null,
						'data-limit'        => (string) max( 1, min( 12, $props['limit'] ?? 10 ) ),
					)
				),
				esc_html( $props['title'] )
			)
		);
	}
}
