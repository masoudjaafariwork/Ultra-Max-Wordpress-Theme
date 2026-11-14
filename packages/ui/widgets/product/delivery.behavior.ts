/**
 * DeliveryEstimate behaviour (PDP-18): loads the delivery dates of the
 * chosen province and remembers the province in the browser, so every
 * product page opens with it.
 */
import type { Behavior } from '../../storefront/runtime';

const KEY = 'um-delivery-state';

export const delivery: Behavior = ( element ) => {
	const select = element.querySelector<HTMLSelectElement>( '[data-um-delivery-state]' );
	const rows = element.querySelector<HTMLElement>( '[data-um-delivery-rows]' );
	const endpoint = element.dataset.endpoint ?? '';
	if ( ! select || ! rows || endpoint === '' ) {
		return {};
	}

	const load = async ( state: string ): Promise<void> => {
		const url = new URL( endpoint, window.location.href );
		url.searchParams.set( 'product', element.dataset.product ?? '' );
		url.searchParams.set( 'state', state );
		element.setAttribute( 'aria-busy', 'true' );
		try {
			const response = await fetch( url.toString(), { credentials: 'same-origin' } );
			const reply = ( await response.json() ) as { rows?: string };
			if ( typeof reply.rows === 'string' ) {
				rows.innerHTML = reply.rows;
			}
		} catch {
			// The rows of the store's province stay.
		} finally {
			element.removeAttribute( 'aria-busy' );
		}
	};

	const onChange = (): void => {
		try {
			window.localStorage.setItem( KEY, select.value );
		} catch {
			// Not remembered; the choice still applies on this page.
		}
		void load( select.value );
	};

	let saved = '';
	try {
		saved = window.localStorage.getItem( KEY ) ?? '';
	} catch {
		saved = '';
	}
	if ( saved !== '' && saved !== select.value && Array.from( select.options ).some( ( option ) => option.value === saved ) ) {
		select.value = saved;
		void load( saved );
	}
	select.addEventListener( 'change', onChange );
	return {
		destroy(): void {
			select.removeEventListener( 'change', onChange );
		},
	};
};
