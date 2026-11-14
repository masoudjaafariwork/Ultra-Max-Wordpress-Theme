<?php
/**
 * DeliveryEstimate component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * When a product arrives (PDP-18): a province chooser and, for each way of
 * shipping, the first and last delivery dates in working days.
 *
 * The chooser is a plain select; the delivery behaviour asks `endpoint` for
 * the rows of the chosen province and remembers the choice in the browser.
 * Without JavaScript the rows of the store's own province show.
 *
 * @phpstan-type DeliveryRow array{title: string, text: string}
 * @phpstan-type DeliveryEstimateProps array{
 *     id: string,
 *     product: int,
 *     label: string,
 *     rows: list<DeliveryRow>,
 *     states?: array<string, string>,
 *     state?: string,
 *     endpoint?: string,
 *     class?: string,
 * }
 */
final class DeliveryEstimate {

	/**
	 * Renders the estimate.
	 *
	 * @param array $props Props.
	 * @phpstan-param DeliveryEstimateProps $props
	 */
	public static function render( array $props ): Html {
		$id      = Props::id( 'DeliveryEstimate', $props );
		$options = '';
		foreach ( $props['states'] ?? array() as $code => $name ) {
			$options .= sprintf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $code ), selected( $code, $props['state'] ?? '', false ), esc_html( $name ) );
		}
		$select = '' === $options ? '' : sprintf(
			'<label class="sr-only" for="%1$s-state">%2$s</label><select id="%1$s-state" class="max-w-40 cursor-pointer rounded-sm border-0 bg-transparent p-0 text-sm font-bold text-link focus-visible:outline-3 focus-visible:outline-primary/40" data-um-delivery-state>%3$s</select>',
			esc_attr( $id ),
			esc_html( $props['label'] ),
			$options
		);
		return Html::trusted(
			sprintf(
				'<div%1$s><div class="flex flex-wrap items-center gap-1 text-sm"><b class="font-bold">%2$s</b>%3$s</div><ul class="m-0 grid list-none gap-1 p-0" data-um-delivery-rows aria-live="polite">%4$s</ul></div>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes( 'um-delivery grid gap-1', $props['class'] ?? null ),
						'data-um-component' => 'delivery',
						'data-product'      => (string) $props['product'],
						'data-endpoint'     => isset( $props['endpoint'] ) ? esc_url_raw( $props['endpoint'] ) : null,
					)
				),
				esc_html( $props['label'] ),
				$select,
				self::rows( $props['rows'] )
			)
		);
	}

	/**
	 * The rows only, for the behaviour.
	 *
	 * @param array $rows Rows.
	 * @phpstan-param list<DeliveryRow> $rows
	 */
	public static function rows( array $rows ): string {
		$out = '';
		foreach ( $rows as $row ) {
			$out .= sprintf(
				'<li class="text-xs text-fg-3">%1$s: <b class="text-sm font-medium text-fg">%2$s</b></li>',
				esc_html( $row['title'] ),
				esc_html( $row['text'] )
			);
		}
		return $out;
	}
}
