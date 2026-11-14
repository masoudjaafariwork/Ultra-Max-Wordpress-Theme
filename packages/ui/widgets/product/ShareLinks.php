<?php
/**
 * ShareLinks component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Ways to share a product (PDP-24): copy its address, WhatsApp, Telegram,
 * and the phone's own share sheet where the browser has one. Meant for the
 * inside of a Dialog. The links work without JavaScript; the behaviour adds
 * copying and the share sheet.
 *
 * @phpstan-type ShareLinksProps array{
 *     url: string,
 *     title: string,
 *     intro?: string,
 *     labels: array{copy: string, copied: string, whatsapp: string, telegram: string, native: string},
 *     class?: string,
 * }
 */
final class ShareLinks {

	/**
	 * Renders the links.
	 *
	 * @param array $props Props.
	 * @phpstan-param ShareLinksProps $props
	 */
	public static function render( array $props ): Html {
		$text   = $props['title'] . ' ' . $props['url'];
		$button = 'inline-flex min-h-11 flex-1 cursor-pointer items-center justify-center gap-2 rounded-md border border-border-strong bg-surface px-4 text-sm font-bold text-fg no-underline hover:bg-surface-3 focus-visible:outline-3 focus-visible:outline-primary/40';
		return Html::trusted(
			sprintf(
				'<div%1$s>%2$s<button type="button" class="%3$s w-full" data-um-share-copy data-copied="%4$s">%5$s<span>%6$s</span></button><div class="flex gap-2"><a class="%3$s" href="%7$s" target="_blank" rel="noopener nofollow">%8$s%9$s</a><a class="%3$s" href="%10$s" target="_blank" rel="noopener nofollow">%11$s%12$s</a></div><button type="button" class="%3$s w-full" hidden data-um-share-native>%13$s%14$s</button></div>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-share grid gap-3', $props['class'] ?? null ),
						'data-um-component' => 'share',
						'data-url'          => esc_url_raw( $props['url'] ),
						'data-title'        => $props['title'],
					)
				),
				isset( $props['intro'] ) && '' !== $props['intro'] ? sprintf( '<p class="m-0 text-sm text-fg-2">%s</p>', esc_html( $props['intro'] ) ) : '',
				esc_attr( $button ),
				esc_attr( $props['labels']['copied'] ),
				Icon::render( 'link', 'size-5' ),
				esc_html( $props['labels']['copy'] ),
				esc_url( 'https://wa.me/?text=' . rawurlencode( $text ) ),
				Icon::render( 'chat', 'size-5' ),
				esc_html( $props['labels']['whatsapp'] ),
				esc_url( 'https://t.me/share/url?url=' . rawurlencode( $props['url'] ) . '&text=' . rawurlencode( $props['title'] ) ),
				Icon::render( 'send', 'size-5' ),
				esc_html( $props['labels']['telegram'] ),
				Icon::render( 'mobile', 'size-5' ),
				esc_html( $props['labels']['native'] )
			)
		);
	}
}
