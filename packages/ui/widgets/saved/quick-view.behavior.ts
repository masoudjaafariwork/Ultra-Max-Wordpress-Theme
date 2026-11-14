/**
 * QuickView behaviour (SAVE-04, SAVE-05): a button with
 * `data-um-quick="<id>"` opens the window, which asks the store for the
 * product and shows its pictures, choices and add button. The product
 * page's files load the first time only, so lists stay light.
 */
import type { Behavior } from '../../storefront/runtime';

interface Assets {
	css?: string[];
	js?: string[];
}

const loaded = new Map<string, Promise<void>>();

/**
 * Loads a stylesheet or script once.
 *
 * @param url  Address.
 * @param kind css or js.
 */
export function load( url: string, kind: 'css' | 'js' ): Promise<void> {
	const known = loaded.get( url );
	if ( known ) {
		return known;
	}
	const selector = kind === 'css' ? `link[href="${ CSS.escape( url ) }"]` : `script[src="${ CSS.escape( url ) }"]`;
	if ( document.querySelector( selector ) ) {
		loaded.set( url, Promise.resolve() );
		return Promise.resolve();
	}
	const promise = new Promise<void>( ( resolve ) => {
		const node = kind === 'css' ? document.createElement( 'link' ) : document.createElement( 'script' );
		if ( node instanceof HTMLLinkElement ) {
			node.rel = 'stylesheet';
			node.href = url;
		} else if ( node instanceof HTMLScriptElement ) {
			node.src = url;
			node.async = false;
		}
		node.addEventListener( 'load', () => resolve(), { once: true } );
		node.addEventListener( 'error', () => resolve(), { once: true } );
		document.head.append( node );
	} );
	loaded.set( url, promise );
	return promise;
}

/**
 * Loads the product page's files.
 *
 * @param assets Their addresses.
 */
export async function loadAssets( assets: Assets ): Promise<void> {
	await Promise.all( ( assets.css ?? [] ).map( ( url ) => load( url, 'css' ) ) );
	for ( const url of assets.js ?? [] ) {
		await load( url, 'js' );
	}
}

export const quickView: Behavior = ( element ) => {
	const dialog = element.querySelector<HTMLElement>( '[data-um-component="dialog"]' );
	const body = element.querySelector<HTMLElement>( '[data-um-quick-body]' );
	const waiting = body?.innerHTML ?? '';
	let assets: Assets = {};
	try {
		assets = JSON.parse( element.dataset.assets ?? '{}' ) as Assets;
	} catch {
		assets = {};
	}
	let request = 0;

	const show = async ( id: string ): Promise<void> => {
		if ( ! dialog || ! body ) {
			return;
		}
		const mine = ++request;
		body.innerHTML = waiting;
		body.setAttribute( 'aria-busy', 'true' );
		window.UltraMaxUI?.get( dialog )?.open?.();
		const url = new URL( element.dataset.endpoint ?? '', window.location.href );
		url.searchParams.set( 'id', id );
		try {
			const [ reply ] = await Promise.all( [
				fetch( url.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json' } } ).then( ( response ) => response.json() as Promise<{ html?: string }> ),
				loadAssets( assets ),
			] );
			if ( mine !== request ) {
				return;
			}
			if ( ! reply.html ) {
				throw new Error( 'empty' );
			}
			body.innerHTML = reply.html;
			window.UltraMaxUI?.mount( body );
		} catch {
			if ( mine === request ) {
				body.textContent = element.dataset.error ?? '';
			}
		} finally {
			if ( mine === request ) {
				body.removeAttribute( 'aria-busy' );
			}
		}
	};

	const onClick = ( event: MouseEvent ): void => {
		const trigger = event.target instanceof Element ? event.target.closest<HTMLElement>( '[data-um-quick]' ) : null;
		if ( ! trigger || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0 ) {
			return;
		}
		event.preventDefault();
		void show( trigger.dataset.umQuick ?? '' );
	};
	document.addEventListener( 'click', onClick );
	// After adding from the window, it closes; the cart counters were already updated.
	const onAdded = (): void => {
		if ( dialog?.hasAttribute( 'open' ) ) {
			window.setTimeout( () => window.UltraMaxUI?.get( dialog )?.close?.(), 900 );
		}
	};
	document.addEventListener( 'um:cart-updated', onAdded );
	return {
		destroy(): void {
			document.removeEventListener( 'click', onClick );
			document.removeEventListener( 'um:cart-updated', onAdded );
		},
	};
};
