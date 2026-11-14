/**
 * Price range (PLP-01): two sliders on one track and two number fields
 * that follow each other. The handles never cross. A change of a slider
 * is passed on as a change of its number field, which the shop
 * behaviour turns into a new list.
 */
import type { Behavior } from '../../storefront/runtime';

/**
 * Keeps two values in order and inside the bounds.
 *
 * @param from Lower value.
 * @param to   Upper value.
 * @param min  Lowest bound.
 * @param max  Highest bound.
 * @param moved Which value the visitor moved.
 */
export function clampRange( from: number, to: number, min: number, max: number, moved: 'from' | 'to' ): [ number, number ] {
	let low = Math.min( Math.max( from, min ), max );
	let high = Math.min( Math.max( to, min ), max );
	if ( low > high ) {
		if ( moved === 'from' ) {
			low = high;
		} else {
			high = low;
		}
	}
	return [ low, high ];
}

export const priceRange: Behavior = ( element ) => {
	const slider = ( which: string ): HTMLInputElement | null => element.querySelector( `input[data-um-range="${ which }"]` );
	const field = ( which: string ): HTMLInputElement | null => element.querySelector( `input[data-um-range-field="${ which }"]` );
	const fill = element.querySelector<HTMLElement>( '[data-um-range-fill]' );
	const from = slider( 'from' );
	const to = slider( 'to' );
	const fromField = field( 'from' );
	const toField = field( 'to' );
	if ( ! from || ! to || ! fromField || ! toField ) {
		return {};
	}
	const min = Number( from.min );
	const max = Number( from.max );

	const paint = (): void => {
		const span = max - min || 1;
		if ( fill ) {
			fill.style.insetInlineStart = `${ ( ( Number( from.value ) - min ) / span ) * 100 }%`;
			fill.style.insetInlineEnd = `${ 100 - ( ( Number( to.value ) - min ) / span ) * 100 }%`;
		}
	};

	const sync = ( moved: 'from' | 'to', source: 'slider' | 'field' ): void => {
		const low = source === 'slider' ? Number( from.value ) : Number( fromField.value || min );
		const high = source === 'slider' ? Number( to.value ) : Number( toField.value || max );
		const [ a, b ] = clampRange( low, high, min, max, moved );
		from.value = String( a );
		to.value = String( b );
		fromField.value = String( a );
		toField.value = String( b );
		paint();
	};

	const onSlide = ( event: Event ): void => {
		sync( event.target === from ? 'from' : 'to', 'slider' );
	};
	const onRelease = ( event: Event ): void => {
		const moved = event.target === from ? fromField : toField;
		moved.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	};
	const onField = ( event: Event ): void => {
		sync( event.target === fromField ? 'from' : 'to', 'field' );
	};

	from.addEventListener( 'input', onSlide );
	to.addEventListener( 'input', onSlide );
	from.addEventListener( 'change', onRelease );
	to.addEventListener( 'change', onRelease );
	fromField.addEventListener( 'change', onField );
	toField.addEventListener( 'change', onField );
	paint();

	return {
		destroy(): void {
			from.removeEventListener( 'input', onSlide );
			to.removeEventListener( 'input', onSlide );
			from.removeEventListener( 'change', onRelease );
			to.removeEventListener( 'change', onRelease );
			fromField.removeEventListener( 'change', onField );
			toField.removeEventListener( 'change', onField );
		},
	};
};
