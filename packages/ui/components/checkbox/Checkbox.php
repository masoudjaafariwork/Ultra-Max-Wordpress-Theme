<?php
/**
 * Checkbox component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A checkbox with its label beside it, such as "Remember me".
 *
 * @phpstan-type CheckboxProps array{
 *     id: string,
 *     name: string,
 *     label: string|Html,
 *     value?: string,
 *     checked?: bool,
 *     required?: bool,
 *     class?: string,
 * }
 */
final class Checkbox {

	/**
	 * Renders the checkbox.
	 *
	 * @param array $props Props.
	 * @phpstan-param CheckboxProps $props
	 */
	public static function render( array $props ): Html {
		$id = Props::id( 'Checkbox', $props );
		return Html::trusted(
			sprintf(
				'<label for="%1$s" class="%2$s"><input%3$s><span>%4$s</span></label>',
				esc_attr( $id ),
				esc_attr( Attributes::classes( 'inline-flex w-fit cursor-pointer items-center gap-2 text-sm text-fg-2 select-none', $props['class'] ?? null ) ),
				Attributes::render(
					array(
						'type'     => 'checkbox',
						'id'       => $id,
						'name'     => $props['name'],
						'value'    => $props['value'] ?? '1',
						'checked'  => $props['checked'] ?? false,
						'required' => $props['required'] ?? false,
						'class'    => 'm-0 size-4.5 shrink-0 cursor-pointer rounded-xs accent-primary focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-primary/40',
					)
				),
				Html::from( $props['label'] )
			)
		);
	}
}
