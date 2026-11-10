/**
 * Countdown behaviour: ticks once a second, keeps the server's layout and
 * fires `um:ended` on the element when time is up.
 */
import type { Behavior } from '../../storefront/runtime';

const PERSIAN = '۰۱۲۳۴۵۶۷۸۹';

/**
 * Two digits in the chosen numerals.
 *
 * @param value  Number.
 * @param digits fa or en.
 */
export function pad( value: number, digits: string ): string {
	const text = String( value ).padStart( 2, '0' );
	return digits === 'fa' ? text.replace( /\d/g, ( digit ) => PERSIAN[ Number( digit ) ] ?? digit ) : text;
}

/**
 * Days, hours, minutes and seconds in a number of seconds.
 *
 * @param seconds Seconds left.
 */
export function split( seconds: number ): number[] {
	const left = Math.max( 0, Math.floor( seconds ) );
	return [ Math.floor( left / 86400 ), Math.floor( ( left % 86400 ) / 3600 ), Math.floor( ( left % 3600 ) / 60 ), left % 60 ];
}

export const countdown: Behavior = ( element ) => {
	const end = Number( element.dataset.end ) * 1000;
	const digits = element.dataset.digits ?? 'fa';
	const cells = Array.from( element.querySelectorAll<HTMLElement>( '[data-part]' ) );
	let timer = 0;

	const tick = (): void => {
		const left = ( end - Date.now() ) / 1000;
		const parts = split( left );
		const shown = cells.length === 4 ? parts : parts.slice( 1 );
		cells.forEach( ( cell, index ) => {
			const text = pad( shown[ index ] ?? 0, digits );
			if ( cell.textContent !== text ) {
				cell.textContent = text;
			}
		} );
		if ( left <= 0 ) {
			window.clearInterval( timer );
			element.dispatchEvent( new CustomEvent( 'um:ended', { bubbles: true } ) );
			if ( element.dataset.ended ) {
				element.setAttribute( 'aria-label', element.dataset.ended );
			}
		}
	};

	if ( Number.isFinite( end ) ) {
		tick();
		timer = window.setInterval( tick, 1000 );
	}
	return {
		destroy(): void {
			window.clearInterval( timer );
		},
	};
};
