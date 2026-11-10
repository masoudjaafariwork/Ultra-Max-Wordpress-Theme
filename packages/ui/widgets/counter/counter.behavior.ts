/**
 * Counter behaviour: counts up to the number once it scrolls into view.
 * The number stays in the page for visitors who prefer reduced motion and
 * for screen readers, which read the final value.
 */
import type { Behavior } from '../../storefront/runtime';

const PERSIAN = '۰۱۲۳۴۵۶۷۸۹';

/**
 * Formats a whole number with separators.
 *
 * @param value  Number.
 * @param digits fa or en.
 */
export function formatNumber( value: number, digits: string ): string {
	const text = Math.round( value ).toLocaleString( 'en-US' );
	return digits === 'fa' ? text.replace( /\d/g, ( digit ) => PERSIAN[ Number( digit ) ] ?? digit ).replace( /,/g, '٬' ) : text;
}

export const counter: Behavior = ( element ) => {
	const output = element.querySelector<HTMLElement>( '[data-value]' );
	const target = Number( element.dataset.target ?? 0 );
	const digits = element.dataset.digits ?? 'fa';
	const duration = Number( element.dataset.duration ?? 1600 );
	if ( ! output || ! Number.isFinite( target ) || window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches || ! ( 'IntersectionObserver' in window ) ) {
		return {};
	}
	let frame = 0;
	const run = (): void => {
		const start = performance.now();
		const step = ( now: number ): void => {
			const progress = Math.min( 1, ( now - start ) / duration );
			const eased = 1 - Math.pow( 1 - progress, 3 );
			output.textContent = formatNumber( target * eased, digits );
			if ( progress < 1 ) {
				frame = window.requestAnimationFrame( step );
			}
		};
		frame = window.requestAnimationFrame( step );
	};
	const observer = new IntersectionObserver( ( entries ) => {
		if ( entries.some( ( entry ) => entry.isIntersecting ) ) {
			observer.disconnect();
			run();
		}
	} );
	observer.observe( element );
	return {
		destroy(): void {
			observer.disconnect();
			window.cancelAnimationFrame( frame );
		},
	};
};
