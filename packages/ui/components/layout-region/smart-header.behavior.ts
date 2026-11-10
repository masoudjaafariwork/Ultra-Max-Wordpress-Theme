/**
 * Smart sticky header (HDR-05): hides the header while the visitor scrolls
 * down and shows it again on the first scroll up. Nothing hides while the
 * keyboard focus is inside the header or a menu in it is open.
 */
import type { Behavior } from '../../storefront/runtime';

/** Pixels the page must move before the header reacts. */
const THRESHOLD = 8;

export const smartHeader: Behavior = ( element ) => {
	let last = window.scrollY;
	let frame = 0;

	const update = (): void => {
		frame = 0;
		const y = window.scrollY;
		const delta = y - last;
		if ( Math.abs( delta ) < THRESHOLD ) {
			return;
		}
		const busy = element.contains( document.activeElement ) || element.querySelector( '[aria-expanded="true"]' ) !== null;
		const hide = delta > 0 && y > element.offsetHeight && ! busy;
		element.toggleAttribute( 'data-hidden', hide );
		document.documentElement.style.setProperty( '--um-header-offset', hide ? '0px' : `${ element.offsetHeight }px` );
		last = y;
	};

	const onScroll = (): void => {
		if ( ! frame ) {
			frame = window.requestAnimationFrame( update );
		}
	};

	const onFocus = (): void => element.removeAttribute( 'data-hidden' );

	window.addEventListener( 'scroll', onScroll, { passive: true } );
	element.addEventListener( 'focusin', onFocus );

	return {
		destroy(): void {
			window.removeEventListener( 'scroll', onScroll );
			element.removeEventListener( 'focusin', onFocus );
			window.cancelAnimationFrame( frame );
		},
	};
};
