/**
 * Video behaviour: replaces the cover with the player on the first click
 * and starts it. Embedded players load only then.
 */
import type { Behavior } from '../../storefront/runtime';

/**
 * Whether an address may be embedded: https only.
 *
 * @param src Address.
 */
export function embeddable( src: string ): boolean {
	try {
		return new URL( src, window.location.href ).protocol === 'https:';
	} catch {
		return false;
	}
}

export const video: Behavior = ( element ) => {
	const button = element.querySelector<HTMLButtonElement>( 'button' );
	const src = element.dataset.src ?? '';
	const play = (): void => {
		if ( ! src ) {
			return;
		}
		let player: HTMLElement;
		if ( element.dataset.kind === 'file' ) {
			const media = document.createElement( 'video' );
			media.src = src;
			media.controls = true;
			media.autoplay = true;
			media.playsInline = true;
			player = media;
		} else {
			if ( ! embeddable( src ) ) {
				return;
			}
			const frame = document.createElement( 'iframe' );
			frame.src = src;
			frame.title = element.dataset.title ?? '';
			frame.allow = 'autoplay; fullscreen; picture-in-picture';
			frame.allowFullscreen = true;
			player = frame;
		}
		player.className = 'absolute inset-0 size-full border-0';
		element.replaceChildren( player );
		player.focus();
	};
	button?.addEventListener( 'click', play, { once: true } );
	return {};
};
