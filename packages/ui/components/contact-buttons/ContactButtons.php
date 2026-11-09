<?php
/**
 * ContactButtons component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Floating contact buttons (CORE-11): one round button that opens a list
 * of contact links such as WhatsApp, Telegram, Eitaa, Bale or a phone
 * number. With a single link, the round button is that link.
 *
 * `side` is logical: "end" is the left edge in Persian, as in the design.
 *
 * @phpstan-type ContactItem array{label: string, href: string, icon: string, color?: string}
 * @phpstan-type ContactButtonsProps array{
 *     id: string,
 *     label: string,
 *     items: list<ContactItem>,
 *     side?: 'end'|'start',
 *     class?: string,
 * }
 */
final class ContactButtons {

	private const ROUND = 'grid size-13.5 place-items-center rounded-pill bg-primary text-on-primary shadow-lg transition-transform duration-150 ease-um hover:bg-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-primary/40 max-lg:size-12';

	/**
	 * Renders the buttons.
	 *
	 * @param array $props Props.
	 * @phpstan-param ContactButtonsProps $props
	 */
	public static function render( array $props ): Html {
		$items = $props['items'];
		if ( array() === $items ) {
			return Html::none();
		}
		$id    = Props::id( 'ContactButtons', $props );
		$side  = Props::one_of( 'ContactButtons', $props, 'side', array( 'end', 'start' ) );
		$place = Attributes::classes(
			'um-scope um-contact fixed z-(--um-z-sticky) bottom-6 flex flex-col gap-2.5 max-lg:bottom-[calc(var(--um-bottom-nav-h)+16px)]',
			'end' === $side ? 'end-5 items-end max-lg:end-3' : 'start-5 items-start max-lg:start-3',
			$props['class'] ?? null
		);

		if ( 1 === count( $items ) ) {
			$item = $items[0];
			return Html::trusted(
				sprintf(
					'<div id="%1$s" class="%2$s"><a href="%3$s" class="%4$s" aria-label="%5$s"%6$s>%7$s</a></div>',
					esc_attr( $id ),
					esc_attr( $place ),
					esc_url( $item['href'], self::protocols() ),
					esc_attr( self::ROUND ),
					esc_attr( $item['label'] ),
					self::target( $item['href'] ),
					Icon::render( $item['icon'], 'size-6' )
				)
			);
		}

		$list = '';
		foreach ( $items as $item ) {
			$list .= sprintf(
				'<li><a href="%1$s" class="%2$s"%3$s><span class="%4$s"%5$s>%6$s</span><span class="rounded-sm bg-surface px-2 py-1 shadow-sm">%7$s</span></a></li>',
				esc_url( $item['href'], self::protocols() ),
				esc_attr( Attributes::classes( 'flex items-center gap-2 text-xs font-bold text-fg no-underline focus-visible:outline-3 focus-visible:outline-primary/40', 'end' === $side ? 'flex-row-reverse' : null ) ),
				self::target( $item['href'] ),
				'grid size-10.5 place-items-center rounded-pill bg-surface text-primary shadow-md',
				isset( $item['color'] ) && '' !== $item['color'] ? ' style="color:' . esc_attr( $item['color'] ) . '"' : '',
				Icon::render( $item['icon'], 'size-5' ),
				esc_html( $item['label'] )
			);
		}

		return Html::trusted(
			sprintf(
				'<div id="%1$s" class="%2$s" data-um-component="contact-buttons"><ul id="%1$s-list" class="m-0 flex list-none flex-col gap-2 p-0 %3$s" hidden>%4$s</ul><button type="button" class="%5$s" aria-expanded="false" aria-controls="%1$s-list" aria-label="%6$s">%7$s</button></div>',
				esc_attr( $id ),
				esc_attr( $place ),
				'end' === $side ? 'items-end' : 'items-start',
				$list,
				esc_attr( self::ROUND . ' aria-expanded:-rotate-12' ),
				esc_attr( $props['label'] ),
				Icon::render( 'headset', 'size-6' )
			)
		);
	}

	/**
	 * Allowed link protocols, including the apps' own.
	 *
	 * @return list<string>
	 */
	private static function protocols(): array {
		return array( 'https', 'http', 'tel', 'mailto', 'sms', 'tg', 'whatsapp' );
	}

	/**
	 * Opens web links in a new tab.
	 *
	 * @param string $href Link.
	 */
	private static function target( string $href ): string {
		return str_starts_with( $href, 'http' ) ? ' target="_blank" rel="noopener"' : '';
	}
}
