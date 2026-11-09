<?php
/**
 * Field component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A labelled text input or text area with optional help text. Used by the
 * comment form now and by every store form later.
 *
 * @phpstan-type FieldProps array{
 *     id: string,
 *     name: string,
 *     label: string,
 *     type?: 'text'|'email'|'url'|'tel'|'password'|'search'|'number'|'textarea',
 *     value?: string,
 *     required?: bool,
 *     placeholder?: string,
 *     autocomplete?: string,
 *     help?: string,
 *     rows?: int,
 *     dir?: 'ltr'|'rtl'|'auto',
 *     maxlength?: int,
 *     class?: string,
 * }
 */
final class Field {

	public const TYPES = array( 'text', 'email', 'url', 'tel', 'password', 'search', 'number', 'textarea' );

	private const CONTROL = 'w-full appearance-none rounded-md border border-border-strong bg-surface px-3.5 text-md text-fg outline-none transition-colors duration-150 placeholder:text-fg-3 hover:border-fg-3 focus:border-primary focus:shadow-[var(--um-focus)] aria-invalid:border-danger';

	/**
	 * Renders the field.
	 *
	 * @param array $props Props.
	 * @phpstan-param FieldProps $props
	 */
	public static function render( array $props ): Html {
		$id       = Props::id( 'Field', $props );
		$type     = Props::one_of( 'Field', $props, 'type', self::TYPES );
		$required = $props['required'] ?? false;
		$help_id  = isset( $props['help'] ) ? $id . '-help' : null;

		$attributes = array(
			'id'               => $id,
			'name'             => $props['name'],
			'required'         => $required,
			'aria-required'    => $required ? 'true' : null,
			'placeholder'      => $props['placeholder'] ?? null,
			'autocomplete'     => $props['autocomplete'] ?? null,
			'aria-describedby' => $help_id,
			'dir'              => $props['dir'] ?? null,
			'maxlength'        => $props['maxlength'] ?? null,
		);
		if ( 'textarea' === $type ) {
			$attributes['rows']  = $props['rows'] ?? 5;
			$attributes['class'] = self::CONTROL . ' py-3 leading-body';
			$control             = sprintf( '<textarea%s>%s</textarea>', Attributes::render( $attributes ), esc_textarea( $props['value'] ?? '' ) );
		} else {
			$attributes['type']  = $type;
			$attributes['value'] = $props['value'] ?? '';
			$attributes['class'] = self::CONTROL . ' h-11';
			$control             = sprintf( '<input%s>', Attributes::render( $attributes ) );
		}

		return Html::trusted(
			sprintf(
				'<p class="%1$s"><label for="%2$s" class="text-sm font-medium text-fg">%3$s%4$s</label>%5$s%6$s</p>',
				esc_attr( Attributes::classes( 'm-0 grid gap-1.5', $props['class'] ?? null ) ),
				esc_attr( $id ),
				esc_html( $props['label'] ),
				$required ? ' <span class="text-danger" aria-hidden="true">*</span>' : '',
				$control,
				null === $help_id ? '' : sprintf( '<span id="%s" class="text-xs text-fg-3">%s</span>', esc_attr( $help_id ), esc_html( $props['help'] ?? '' ) )
			)
		);
	}
}
