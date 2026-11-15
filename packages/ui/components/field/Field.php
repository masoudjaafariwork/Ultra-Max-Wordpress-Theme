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
 * comment form, the login forms and every store form.
 *
 * `icon` draws an icon at the start of the input. `reveal` adds a button
 * that shows the typed password (behaviour: field.behavior.ts). `link`
 * puts a small link at the end of the label row, such as "Forgot your
 * password?".
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
 *     icon?: string,
 *     reveal?: bool,
 *     link?: array{label: string, href: string},
 *     class?: string,
 * }
 */
final class Field {

	public const TYPES = array( 'text', 'email', 'url', 'tel', 'password', 'search', 'number', 'textarea' );

	private const CONTROL = 'w-full appearance-none rounded-md border border-border-strong bg-surface px-3.5 text-md text-fg outline-none transition-colors duration-150 placeholder:text-fg-3 hover:border-fg-3 focus:border-primary focus:shadow-[var(--um-focus)] aria-invalid:border-danger';

	private const REVEAL = 'absolute inset-y-0 end-0 flex w-11 items-center justify-center rounded-e-md border-0 bg-transparent p-0 text-fg-3 cursor-pointer hover:text-fg focus-visible:outline-3 focus-visible:-outline-offset-3 focus-visible:outline-primary/40';

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
		$icon     = $props['icon'] ?? '';
		$reveal   = 'password' === $type && ( $props['reveal'] ?? false );

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
			// Physical sides: an input with its own dir="ltr" inside a right-to-left page would flip start and end.
			$rtl                 = is_rtl();
			$attributes['class'] = Attributes::classes(
				self::CONTROL,
				'h-11',
				'' !== $icon ? ( $rtl ? 'pr-10' : 'pl-10' ) : null,
				$reveal ? ( $rtl ? 'pl-11' : 'pr-11' ) . ' [&::-ms-reveal]:hidden' : null
			);
			$control             = sprintf( '<input%s>', Attributes::render( $attributes ) );
			if ( '' !== $icon || $reveal ) {
				$control = sprintf(
					'<div%1$s>%2$s%3$s%4$s</div>',
					Attributes::render(
						array(
							'class'             => 'relative block',
							'data-um-component' => $reveal ? 'password-reveal' : null,
						)
					),
					'' === $icon ? '' : '<span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-fg-3">' . Icon::render( $icon, 'size-5' ) . '</span>',
					$control,
					$reveal ? self::reveal( $id ) : ''
				);
			}
		}

		$label = sprintf(
			'<label for="%1$s" class="text-sm font-medium text-fg">%2$s%3$s</label>',
			esc_attr( $id ),
			esc_html( $props['label'] ),
			$required ? ' <span class="text-danger" aria-hidden="true">*</span>' : ''
		);
		if ( isset( $props['link'] ) ) {
			$label = sprintf(
				'<span class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">%1$s<a href="%2$s" class="text-sm font-medium text-primary no-underline hover:underline">%3$s</a></span>',
				$label,
				esc_url( $props['link']['href'] ),
				esc_html( $props['link']['label'] )
			);
		}

		return Html::trusted(
			sprintf(
				'<div class="%1$s">%2$s%3$s%4$s</div>',
				esc_attr( Attributes::classes( 'm-0 grid gap-1.5', $props['class'] ?? null ) ),
				$label,
				$control,
				null === $help_id ? '' : sprintf( '<span id="%s" class="text-xs text-fg-3">%s</span>', esc_attr( $help_id ), esc_html( $props['help'] ?? '' ) )
			)
		);
	}

	/**
	 * The button that shows or hides the typed password. Its name stays the
	 * same; aria-pressed says whether the password is shown.
	 *
	 * @param string $id Input id.
	 */
	private static function reveal( string $id ): string {
		return sprintf(
			'<button type="button" class="%1$s" aria-label="%2$s" aria-pressed="false" aria-controls="%3$s" data-um-reveal><span data-um-reveal-show>%4$s</span><span data-um-reveal-hide hidden>%5$s</span></button>',
			esc_attr( self::REVEAL ),
			esc_attr__( 'Show password', 'ultra-max-ui' ),
			esc_attr( $id ),
			Icon::render( 'eye', 'size-5' ),
			Icon::render( 'eye-off', 'size-5' )
		);
	}
}
