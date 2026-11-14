<?php
/**
 * CheckoutSection component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * One white box of the checkout: a title, an optional action beside it,
 * and the content. `step` names the checkout step it belongs to, so the
 * multi-step checkout shows it only in that step (CHK-01); without a step
 * it shows in every step.
 *
 * WooCommerce's own fields inside it get the store's field look from this
 * asset's styles (`.um-checkout-section .form-row`).
 *
 * @phpstan-type CheckoutSectionProps array{
 *     title: string,
 *     children: string|Html|array<int, string|Html>,
 *     step?: string,
 *     action?: Html,
 *     id?: string,
 *     class?: string,
 * }
 */
final class CheckoutSection {

	/**
	 * Renders the section.
	 *
	 * @param array $props Props.
	 * @phpstan-param CheckoutSectionProps $props
	 */
	public static function render( array $props ): Html {
		$id = $props['id'] ?? null;
		return Html::trusted(
			sprintf(
				'<section%1$s><div class="mb-3 flex flex-wrap items-center justify-between gap-2"><h2 class="m-0 text-md font-bold"%2$s>%3$s</h2>%4$s</div>%5$s</section>',
				Attributes::render(
					array(
						'id'           => $id,
						'class'        => Attributes::classes( 'um-scope um-checkout-section min-w-0 rounded-lg border border-border bg-surface p-5 max-md:p-4', $props['class'] ?? null ),
						'data-um-step' => $props['step'] ?? null,
					)
				),
				null === $id ? '' : sprintf( ' id="%s-title"', esc_attr( $id ) ),
				esc_html( $props['title'] ),
				$props['action'] ?? '',
				Html::from( $props['children'] )
			)
		);
	}
}
