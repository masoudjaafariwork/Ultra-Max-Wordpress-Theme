<?php
/**
 * VariationPicker component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The choices of a variable product (PDP-04): colour swatches, pictures,
 * buttons or a dropdown for each attribute.
 *
 * Every choice is a real radio button (or select) of the purchase form,
 * tied to it with the `form` attribute, so the picker can sit in the
 * product's info column while the form is in the buy box, and the form
 * works without JavaScript: WooCommerce finds the variation from the
 * chosen values. The purchase behaviour greys out values with no
 * variation in stock for the other choices.
 *
 * @phpstan-type PickerOption array{value: string, label: string, color?: string, image?: Html, out?: bool}
 * @phpstan-type PickerAttribute array{
 *     name: string,
 *     label: string,
 *     display: 'color'|'image'|'button'|'select',
 *     options: list<PickerOption>,
 *     selected?: string,
 *     choose: string,
 * }
 * @phpstan-type VariationPickerProps array{
 *     form: string,
 *     attributes: list<PickerAttribute>,
 *     reset?: string,
 *     class?: string,
 * }
 */
final class VariationPicker {

	public const DISPLAYS = array( 'button', 'color', 'image', 'select' );

	/**
	 * Renders the picker.
	 *
	 * @param array $props Props.
	 * @phpstan-param VariationPickerProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['attributes'] ) {
			return Html::none();
		}
		$form = Props::id( 'VariationPicker', $props, 'form' );
		$out  = '';
		foreach ( $props['attributes'] as $index => $attribute ) {
			$out .= self::attribute( $form, $index, $attribute );
		}
		$reset = isset( $props['reset'] ) && '' !== $props['reset'] ? sprintf(
			'<button type="reset" form="%1$s" class="justify-self-start cursor-pointer border-0 bg-transparent p-0 text-xs font-medium text-link hover:underline" hidden data-um-picker-reset>%2$s</button>',
			esc_attr( $form ),
			esc_html( $props['reset'] )
		) : '';
		return Html::trusted(
			sprintf(
				'<div%1$s>%2$s%3$s</div>',
				Attributes::render(
					array(
						'class'          => Attributes::classes( 'um-variation-picker grid gap-4', $props['class'] ?? null ),
						'data-um-picker' => $form,
					)
				),
				$out,
				$reset
			)
		);
	}

	/**
	 * One attribute.
	 *
	 * @param string $form      Form id.
	 * @param int    $index     Position.
	 * @param array  $attribute Attribute.
	 * @phpstan-param PickerAttribute $attribute
	 */
	private static function attribute( string $form, int $index, array $attribute ): string {
		$display  = in_array( $attribute['display'], self::DISPLAYS, true ) ? $attribute['display'] : 'button';
		$selected = $attribute['selected'] ?? '';
		$current  = '';
		foreach ( $attribute['options'] as $option ) {
			if ( $option['value'] === $selected ) {
				$current = $option['label'];
			}
		}
		$field = sprintf( '%s-attr-%d', $form, $index );
		$head  = sprintf(
			'<span class="text-md font-bold">%1$s<span class="font-regular text-fg-3">: </span><span class="font-medium text-fg-2" data-um-picker-value="%2$s" data-empty="%3$s">%4$s</span></span>',
			esc_html( $attribute['label'] ),
			esc_attr( $attribute['name'] ),
			esc_attr( $attribute['choose'] ),
			esc_html( '' === $current ? $attribute['choose'] : $current )
		);
		if ( 'select' === $display ) {
			$options = sprintf( '<option value="">%s</option>', esc_html( $attribute['choose'] ) );
			foreach ( $attribute['options'] as $option ) {
				$options .= sprintf(
					'<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( $option['value'] ),
					selected( $option['value'], $selected, false ),
					esc_html( $option['label'] )
				);
			}
			return sprintf(
				'<div class="grid gap-2"><label for="%1$s">%2$s</label><select id="%1$s" name="%3$s" form="%4$s" class="h-11 w-full max-w-xs rounded-md border border-border-strong bg-surface px-3 text-sm focus-visible:outline-3 focus-visible:outline-primary/40" data-um-attribute>%5$s</select></div>',
				esc_attr( $field ),
				$head,
				esc_attr( $attribute['name'] ),
				esc_attr( $form ),
				$options
			);
		}
		$chips = '';
		foreach ( $attribute['options'] as $position => $option ) {
			$chips .= self::chip( $form, $field . '-' . $position, $attribute['name'], $display, $option, $option['value'] === $selected );
		}
		return sprintf(
			'<fieldset class="m-0 grid gap-3 border-0 p-0"><legend class="mb-3 p-0">%1$s</legend><div class="flex flex-wrap gap-2">%2$s</div></fieldset>',
			$head,
			$chips
		);
	}

	/**
	 * One value as a chip: a hidden radio button and its label.
	 *
	 * @param string $form     Form id.
	 * @param string $id       Field id.
	 * @param string $name     Field name.
	 * @param string $display  Display.
	 * @param array  $option   Value.
	 * @param bool   $selected Whether chosen.
	 * @phpstan-param PickerOption $option
	 */
	private static function chip( string $form, string $id, string $name, string $display, array $option, bool $selected ): string {
		$visual = '';
		if ( 'color' === $display && isset( $option['color'] ) ) {
			$visual = sprintf( '<span class="size-5.5 shrink-0 rounded-full border border-border-strong" style="background:%s" aria-hidden="true"></span>', esc_attr( $option['color'] ) );
		} elseif ( 'image' === $display && isset( $option['image'] ) && ! $option['image']->is_empty() ) {
			$visual = sprintf( '<span class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-sm bg-surface-2 [&_img]:size-full [&_img]:object-contain" aria-hidden="true">%s</span>', $option['image'] );
		}
		return sprintf(
			'<label for="%1$s" class="relative inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-pill border border-border bg-surface px-3 py-1.5 text-sm has-checked:border-primary has-checked:shadow-[0_0_0_1px_var(--um-primary)] has-focus-visible:outline-3 has-focus-visible:outline-primary/40 has-disabled:cursor-not-allowed data-out:bg-surface-3 data-out:text-fg-3 data-out:line-through %2$s"%3$s><input id="%1$s" type="radio" class="peer sr-only" name="%4$s" value="%5$s" form="%6$s"%7$s data-um-attribute>%8$s<span>%9$s</span></label>',
			esc_attr( $id ),
			'' === $visual ? '' : 'ps-1.5',
			( $option['out'] ?? false ) ? ' data-out' : '',
			esc_attr( $name ),
			esc_attr( $option['value'] ),
			esc_attr( $form ),
			$selected ? ' checked' : '',
			$visual,
			esc_html( $option['label'] )
		);
	}
}
