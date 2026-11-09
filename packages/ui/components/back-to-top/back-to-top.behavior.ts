/**
 * BackToTop behaviour: shows the button after 400px of scrolling, scrolls
 * smoothly unless the visitor prefers reduced motion, and moves focus to
 * the target so keyboard users continue from the top.
 */
import type { Controller } from '../../storefront/runtime';

const THRESHOLD = 400;

/**
 * Mounts the behaviour.
 *
 * @param element The link element.
 */
export function backToTop( element: HTMLElement ): Controller {
	let ticking = false;
	const update = (): void => {
		ticking = false;
		const visible = window.scrollY > THRESHOLD;
		element.dataset.visible = String( visible );
		element.tabIndex = visible ? 0 : -1;
	};
	const onScroll = (): void => {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( update );
		}
	};
	const onClick = ( event: MouseEvent ): void => {
		event.preventDefault();
		const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		window.scrollTo( { top: 0, behavior: reduce ? 'auto' : 'smooth' } );
		const id = ( element.getAttribute( 'href' ) ?? '' ).slice( 1 );
		const target = id ? document.getElementById( id ) : null;
		if ( target ) {
			if ( ! target.hasAttribute( 'tabindex' ) ) {
				target.setAttribute( 'tabindex', '-1' );
			}
			target.focus( { preventScroll: true } );
		}
	};

	window.addEventListener( 'scroll', onScroll, { passive: true } );
	element.addEventListener( 'click', onClick );
	update();

	return {
		destroy() {
			window.removeEventListener( 'scroll', onScroll );
			element.removeEventListener( 'click', onClick );
		},
	};
}
