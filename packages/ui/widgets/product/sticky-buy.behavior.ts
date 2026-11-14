/**
 * StickyBuy behaviour (PDP-20): the phone bar slides away while the main
 * buy box is on screen, so the two buttons never show at once, and comes
 * back once it scrolls out of view. Without JavaScript the bar always shows.
 */
import type { Behavior } from '../../storefront/runtime';

export const stickyBuy: Behavior = ( element ) => {
	const watched = element.dataset.watch ? document.getElementById( element.dataset.watch ) : null;
	if ( ! watched || ! ( 'IntersectionObserver' in window ) ) {
		return {};
	}
	const observer = new IntersectionObserver( ( entries ) => {
		const shown = entries.some( ( entry ) => entry.isIntersecting );
		element.toggleAttribute( 'data-away', shown );
		element.setAttribute( 'aria-hidden', String( shown ) );
		element.querySelectorAll<HTMLElement>( 'button, a' ).forEach( ( control ) => ( control.tabIndex = shown ? -1 : 0 ) );
	} );
	observer.observe( watched );
	return {
		destroy(): void {
			observer.disconnect();
		},
	};
};
