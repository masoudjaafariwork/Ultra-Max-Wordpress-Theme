/**
 * Hotspots behaviour: one point open at a time; Escape or a click outside
 * closes the open one.
 */
import type { Behavior } from '../../storefront/runtime';

export const hotspots: Behavior = ( element ) => {
	const spots = Array.from( element.querySelectorAll<HTMLDetailsElement>( 'details' ) );
	const onToggle = ( event: Event ): void => {
		const opened = event.target as HTMLDetailsElement;
		if ( opened.open ) {
			spots.forEach( ( spot ) => {
				if ( spot !== opened ) {
					spot.open = false;
				}
			} );
		}
	};
	const onKey = ( event: KeyboardEvent ): void => {
		if ( event.key === 'Escape' ) {
			const open = spots.find( ( spot ) => spot.open );
			if ( open ) {
				open.open = false;
				open.querySelector( 'summary' )?.focus();
			}
		}
	};
	const onOutside = ( event: MouseEvent ): void => {
		if ( ! ( event.target instanceof Node ) || ! element.contains( event.target ) ) {
			spots.forEach( ( spot ) => ( spot.open = false ) );
		}
	};
	spots.forEach( ( spot ) => spot.addEventListener( 'toggle', onToggle ) );
	element.addEventListener( 'keydown', onKey );
	document.addEventListener( 'click', onOutside );
	return {
		destroy(): void {
			document.removeEventListener( 'click', onOutside );
		},
	};
};
