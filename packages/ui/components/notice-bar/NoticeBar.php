<?php
/**
 * NoticeBar component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The announcement bar above the header (CORE-10): a short text, an
 * optional button and a close button. Closing hides this version of the
 * text for the visitor; a new text shows again.
 *
 * `start` and `end` are Unix timestamps. The server already skips a bar
 * outside its dates; the behaviour checks again in the browser, so a page
 * served from a cache never shows an expired bar.
 *
 * @phpstan-type NoticeBarProps array{
 *     id: string,
 *     text: string,
 *     close_label: string,
 *     link?: array{label: string, href: string},
 *     tone?: 'brand'|'accent'|'dark',
 *     dismissible?: bool,
 *     version?: string,
 *     start?: int,
 *     end?: int,
 *     class?: string,
 * }
 */
final class NoticeBar {

	public const TONES = array( 'brand', 'accent', 'dark' );

	private const TONE_CLASSES = array(
		'brand'  => 'bg-(image:--um-deal-gradient) text-white',
		'accent' => 'bg-accent text-on-accent',
		'dark'   => 'bg-[var(--um-gray-900)] text-white',
	);

	/**
	 * Renders the bar.
	 *
	 * @param array $props Props.
	 * @phpstan-param NoticeBarProps $props
	 */
	public static function render( array $props ): Html {
		$id          = Props::id( 'NoticeBar', $props );
		$tone        = Props::one_of( 'NoticeBar', $props, 'tone', self::TONES );
		$dismissible = $props['dismissible'] ?? true;
		$link        = '';
		if ( isset( $props['link'] ) && '' !== $props['link']['label'] && '' !== $props['link']['href'] ) {
			$link = sprintf(
				'<a href="%1$s" class="%2$s">%3$s</a>',
				esc_url( $props['link']['href'] ),
				'inline-flex shrink-0 items-center rounded-pill bg-white/20 px-3 py-0.5 text-xs font-bold text-inherit no-underline hover:bg-white/30 focus-visible:outline-2 focus-visible:outline-current',
				esc_html( $props['link']['label'] )
			);
		}
		$close = $dismissible ? sprintf(
			'<button type="button" class="%1$s" data-um-close aria-label="%2$s">%3$s</button>',
			'absolute end-(--um-gutter) top-1/2 grid size-8 -translate-y-1/2 place-items-center rounded-sm text-inherit opacity-80 hover:opacity-100 focus-visible:outline-2 focus-visible:outline-current',
			esc_attr( $props['close_label'] ),
			Icon::render( 'close', 'size-4' )
		) : '';

		return Html::trusted(
			sprintf(
				'<div%1$s><div class="%2$s"><p class="m-0 inline-flex min-w-0 items-center gap-2">%3$s<span>%4$s</span></p>%5$s</div>%6$s</div>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes( 'um-scope um-notice-bar relative text-sm max-md:text-xs', self::TONE_CLASSES[ $tone ], $props['class'] ?? null ),
						'role'              => 'region',
						'aria-label'        => $props['text'],
						'data-um-component' => 'notice-bar',
						'data-version'      => $props['version'] ?? substr( md5( $props['text'] ), 0, 8 ),
						'data-start'        => isset( $props['start'] ) ? (string) $props['start'] : null,
						'data-end'          => isset( $props['end'] ) ? (string) $props['end'] : null,
						'data-dismissible'  => $dismissible ? 'true' : null,
					)
				),
				esc_attr( Attributes::classes( 'mx-auto flex min-h-10 max-w-(--um-container) items-center justify-center gap-3 px-(--um-gutter) py-1.5 max-md:justify-start', $dismissible ? 'pe-12' : null ) ),
				Icon::render( 'gift', 'size-4 max-md:hidden' ),
				esc_html( $props['text'] ),
				$link,
				$close
			)
		);
	}
}
