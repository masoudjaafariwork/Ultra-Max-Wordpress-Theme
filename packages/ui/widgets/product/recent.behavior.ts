/**
 * RecentProducts behaviour (PDP-23): keeps the last products a shopper
 * opened in the browser's storage and shows their cards. Nothing leaves the
 * browser except the product ids for the cards.
 */
import type { Behavior } from '../../storefront/runtime';

const KEY = 'um-recent-products';

/**
 * The remembered ids, newest first.
 */
export function readRecent(): number[] {
	try {
		const stored = JSON.parse( window.localStorage.getItem( KEY ) ?? '[]' ) as unknown;
		return Array.isArray( stored ) ? stored.map( Number ).filter( ( id ) => Number.isInteger( id ) && id > 0 ) : [];
	} catch {
		return [];
	}
}

/**
 * Puts a product first in the list.
 *
 * @param list  Current ids.
 * @param id    Product id.
 * @param limit Ids to keep.
 */
export function remember( list: number[], id: number, limit = 20 ): number[] {
	return [ id, ...list.filter( ( item ) => item !== id ) ].slice( 0, limit );
}

export const recentProducts: Behavior = ( element ) => {
	const current = Number( element.dataset.record ?? '0' );
	const limit = Number( element.dataset.limit ?? '10' );
	const list = readRecent();
	const others = list.filter( ( id ) => id !== current ).slice( 0, limit );
	if ( current > 0 ) {
		try {
			window.localStorage.setItem( KEY, JSON.stringify( remember( list, current ) ) );
		} catch {
			// Storage is full or blocked; the list just stays as it was.
		}
	}
	const endpoint = element.dataset.endpoint ?? '';
	const target = element.querySelector<HTMLElement>( '[data-um-recent-list]' );
	if ( endpoint === '' || ! target || others.length === 0 ) {
		return {};
	}
	const url = new URL( endpoint, window.location.href );
	url.searchParams.set( 'ids', others.join( ',' ) );
	fetch( url.toString(), { credentials: 'same-origin' } )
		.then( ( response ) => ( response.ok ? ( response.json() as Promise<{ html?: string; count?: number }> ) : null ) )
		.then( ( reply ) => {
			if ( ! reply?.html || ! reply.count ) {
				return;
			}
			target.innerHTML = reply.html;
			element.hidden = false;
			window.UltraMaxUI?.mount( target );
		} )
		.catch( () => undefined );
	return {};
};
