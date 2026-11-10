/**
 * Animated text: shows the words in turn, with a short fade. Pauses while
 * the tab is hidden; does nothing for visitors who prefer reduced motion.
 */
import type { Behavior } from '../../storefront/runtime';

export const animatedText: Behavior = ( element ) => {
	const holder = element.querySelector<HTMLElement>( '[data-words]' );
	const words = Array.from( element.querySelectorAll<HTMLElement>( '[data-word]' ) );
	if ( ! holder || words.length < 2 || window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		return {};
	}
	const interval = Math.max( 1000, Number( element.dataset.interval ?? 2500 ) );
	let index = 0;
	let fade = 0;
	const next = (): void => {
		if ( document.hidden ) {
			return;
		}
		holder.setAttribute( 'data-fading', '' );
		fade = window.setTimeout( () => {
			words[ index ]?.classList.replace( 'inline-block', 'hidden' );
			index = ( index + 1 ) % words.length;
			words[ index ]?.classList.replace( 'hidden', 'inline-block' );
			holder.removeAttribute( 'data-fading' );
		}, 300 );
	};
	const timer = window.setInterval( next, interval );
	return {
		destroy(): void {
			window.clearInterval( timer );
			window.clearTimeout( fade );
		},
	};
};
