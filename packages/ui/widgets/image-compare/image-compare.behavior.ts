/**
 * Image comparison: moves the dividing line with the range input.
 */
import type { Behavior } from '../../storefront/runtime';

export const imageCompare: Behavior = ( element ) => {
	const input = element.querySelector<HTMLInputElement>( 'input[type="range"]' );
	if ( ! input ) {
		return {};
	}
	const update = (): void => element.style.setProperty( '--pos', `${ Number( input.value ) }%` );
	input.addEventListener( 'input', update );
	update();
	return {
		destroy(): void {
			input.removeEventListener( 'input', update );
		},
	};
};
