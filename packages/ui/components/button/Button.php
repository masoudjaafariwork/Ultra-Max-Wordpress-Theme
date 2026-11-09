<?php
/**
 * Button component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A button, or a link that looks like one when `href` is given.
 *
 * @phpstan-type ButtonProps array{
 *     children: string|Html|array<int, string|Html>,
 *     variant?: 'primary'|'outline'|'soft'|'ghost'|'neutral'|'accent'|'danger',
 *     size?: 'md'|'sm'|'lg',
 *     href?: string,
 *     type?: 'button'|'submit'|'reset',
 *     disabled?: bool,
 *     block?: bool,
 *     opens?: string,
 *     label?: string,
 *     id?: string,
 *     attrs?: array<string, string|int|bool|null>,
 *     class?: string,
 * }
 */
final class Button {

	public const VARIANTS = array( 'primary', 'outline', 'soft', 'ghost', 'neutral', 'accent', 'danger' );
	public const SIZES    = array( 'md', 'sm', 'lg' );

	private const BASE = 'inline-flex items-center justify-center gap-2 border font-bold whitespace-nowrap no-underline cursor-pointer select-none transition-colors duration-150 ease-um active:translate-y-px focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-primary/40 disabled:opacity-45 disabled:pointer-events-none aria-disabled:opacity-45 aria-disabled:pointer-events-none';

	private const VARIANT_CLASSES = array(
		'primary' => 'bg-primary text-on-primary border-transparent hover:bg-primary-hover hover:text-on-primary',
		'outline' => 'bg-transparent text-primary border-primary hover:bg-primary-soft',
		'soft'    => 'bg-primary-soft text-primary border-transparent hover:bg-primary-soft-2',
		'ghost'   => 'bg-transparent text-fg border-transparent hover:bg-surface-3',
		'neutral' => 'bg-surface text-fg border-border-strong hover:bg-surface-3',
		'accent'  => 'bg-accent text-on-accent border-transparent hover:bg-accent-hover',
		'danger'  => 'bg-transparent text-danger border-danger/40 hover:bg-danger-soft',
	);

	private const SIZE_CLASSES = array(
		'sm' => 'min-h-9 px-3 text-sm rounded-sm',
		'md' => 'min-h-11 px-5 text-md rounded-md',
		'lg' => 'min-h-13 px-6 text-lg rounded-md',
	);

	/**
	 * Renders the button.
	 *
	 * @param ButtonProps $props Props.
	 */
	public static function render( array $props ): Html {
		$variant  = Props::one_of( 'Button', $props, 'variant', self::VARIANTS );
		$size     = Props::one_of( 'Button', $props, 'size', self::SIZES );
		$href     = $props['href'] ?? null;
		$disabled = (bool) ( $props['disabled'] ?? false );

		$attributes = array(
			'id'           => $props['id'] ?? null,
			'class'        => Attributes::classes(
				self::BASE,
				self::VARIANT_CLASSES[ $variant ],
				self::SIZE_CLASSES[ $size ],
				( $props['block'] ?? false ) ? 'w-full' : null,
				$props['class'] ?? null
			),
			'aria-label'   => $props['label'] ?? null,
			'data-um-open' => $props['opens'] ?? null,
		);

		if ( null !== $href ) {
			$tag                         = 'a';
			$attributes['href']          = $disabled ? null : esc_url( $href );
			$attributes['aria-disabled'] = $disabled ? 'true' : null;
		} else {
			$tag                     = 'button';
			$attributes['type']      = Props::one_of( 'Button', $props, 'type', array( 'button', 'submit', 'reset' ) );
			$attributes['disabled']  = $disabled;
			$attributes['aria-haspopup'] = isset( $props['opens'] ) ? 'dialog' : null;
		}

		return Html::trusted(
			sprintf(
				'<%1$s%2$s>%3$s</%1$s>',
				$tag,
				Attributes::render( array_merge( $attributes, $props['attrs'] ?? array() ) ),
				Html::from( $props['children'] )
			)
		);
	}
}