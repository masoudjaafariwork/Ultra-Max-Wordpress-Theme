<?php
/**
 * AnimatedText component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A heading whose highlighted words change in turn, such as "Fast /
 * genuine / affordable shopping" (HOME-12). Screen readers and search
 * engines get the whole sentence with every word; the turning words are
 * shown only to sighted visitors, and stay still for those who prefer
 * reduced motion.
 *
 * @phpstan-type AnimatedTextProps array{before?: string, words: list<string>, after?: string, tag?: 'h2'|'h3'|'p', interval?: int, class?: string}
 */
final class AnimatedText {

	/**
	 * Renders the heading.
	 *
	 * @param array $props Props.
	 * @phpstan-param AnimatedTextProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['words'] ) {
			return Html::none();
		}
		$tag      = $props['tag'] ?? 'h2';
		$before   = $props['before'] ?? '';
		$after    = $props['after'] ?? '';
		$readable = trim( $before . ' ' . implode( wp_get_list_item_separator(), $props['words'] ) . ' ' . $after );
		$words    = '';
		foreach ( $props['words'] as $index => $word ) {
			$words .= sprintf( '<span class="%1$s" data-word>%2$s</span>', 0 === $index ? 'inline-block' : 'hidden', esc_html( $word ) );
		}
		return Html::trusted(
			sprintf(
				'<%1$s%2$s><span class="sr-only">%3$s</span><span aria-hidden="true">%4$s <span class="inline-block text-primary transition-opacity duration-300 data-fading:opacity-0" data-words>%5$s</span> %6$s</span></%1$s>',
				$tag,
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-animated-text m-0 text-3xl leading-tight font-heavy max-md:text-2xl', $props['class'] ?? null ),
						'data-um-component' => 'animated-text',
						'data-interval'     => (string) ( $props['interval'] ?? 2500 ),
					)
				),
				esc_html( $readable ),
				esc_html( $before ),
				$words,
				esc_html( $after )
			)
		);
	}
}
