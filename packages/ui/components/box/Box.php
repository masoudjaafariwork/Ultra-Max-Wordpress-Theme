<?php
/**
 * Box component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A surface with a border and corners: content panels and sidebar widgets.
 *
 * `widget()` returns the markup WordPress wraps around each widget of a
 * sidebar, so widgets look like boxes without templates writing classes.
 *
 * @phpstan-type BoxProps array{
 *     children: string|Html|array<int, string|Html>,
 *     title?: string,
 *     tag?: 'div'|'section'|'aside'|'article',
 *     padding?: 'md'|'lg'|'none',
 *     id?: string,
 *     class?: string,
 * }
 */
final class Box {

	private const PADDING = array(
		'md'   => 'p-4',
		'lg'   => 'p-6 max-md:p-4',
		'none' => '',
	);

	/**
	 * Renders the box.
	 *
	 * @param array $props Props.
	 * @phpstan-param BoxProps $props
	 */
	public static function render( array $props ): Html {
		$tag     = Props::one_of( 'Box', $props, 'tag', array( 'div', 'section', 'aside', 'article' ) );
		$padding = Props::one_of( 'Box', $props, 'padding', array( 'md', 'lg', 'none' ) );
		$title   = isset( $props['title'] ) ? sprintf( '<h2 class="m-0 mb-3 text-md font-bold">%s</h2>', esc_html( $props['title'] ) ) : '';
		return Html::trusted(
			sprintf(
				'<%1$s%2$s>%3$s%4$s</%1$s>',
				$tag,
				Attributes::render(
					array(
						'id'    => $props['id'] ?? null,
						'class' => Attributes::classes( 'um-box min-w-0 rounded-lg border border-border bg-surface', self::PADDING[ $padding ], $props['class'] ?? null ),
					)
				),
				$title,
				Html::from( $props['children'] )
			)
		);
	}

	/**
	 * Wrapper markup for `register_sidebar()`.
	 *
	 * @return array{before_widget: string, after_widget: string, before_title: string, after_title: string}
	 */
	public static function widget(): array {
		return array(
			'before_widget' => '<section id="%1$s" class="um-box um-widget %2$s min-w-0 rounded-lg border border-border bg-surface p-4">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="um-widget__title m-0 mb-3 text-md font-bold">',
			'after_title'   => '</h2>',
		);
	}

	/**
	 * Wrapper markup for footer widgets: no box, just a column title.
	 *
	 * @return array{before_widget: string, after_widget: string, before_title: string, after_title: string}
	 */
	public static function footer_widget(): array {
		return array(
			'before_widget' => '<section id="%1$s" class="um-widget um-widget--footer %2$s mb-6 min-w-0 text-sm text-fg-2">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="um-widget__title m-0 mb-3 text-md font-bold text-fg">',
			'after_title'   => '</h2>',
		);
	}
}
