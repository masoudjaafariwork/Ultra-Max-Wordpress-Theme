/**
 * Product card behaviour: the add button adds the product to the cart in
 * place through WooCommerce's own add-to-cart endpoint, then updates every
 * cart counter on the page (`[data-um-cart-count]`) and fires
 * `um:cart-updated` on the document. Without JavaScript the button is a
 * normal add-to-cart link.
 */
import type { Behavior } from '../../storefront/runtime';

interface Fragments {
	fragments?: Record<string, string>;
	error?: boolean;
	product_url?: string;
}

/**
 * The address of WooCommerce's add-to-cart endpoint.
 */
function endpoint(): string {
	const url = new URL( window.location.href );
	url.search = '';
	url.hash = '';
	url.searchParams.set( 'wc-ajax', 'add_to_cart' );
	return url.toString();
}

/**
 * Puts the new cart counters on the page.
 *
 * @param fragments Markup by selector, from WooCommerce.
 */
export function applyFragments( fragments: Record<string, string> ): void {
	Object.entries( fragments ).forEach( ( [ selector, markup ] ) => {
		let nodes: NodeListOf<Element>;
		try {
			nodes = document.querySelectorAll( selector );
		} catch {
			return;
		}
		nodes.forEach( ( node ) => {
			const template = document.createElement( 'template' );
			template.innerHTML = markup.trim();
			const next = template.content.firstElementChild;
			if ( next ) {
				node.replaceWith( next );
			}
		} );
	} );
}

/**
 * Shows the picture of a colour on its card (swatch card, PLP-07).
 *
 * @param card   Card element.
 * @param swatch The pressed colour button.
 */
export function showSwatch( card: HTMLElement, swatch: HTMLElement ): void {
	card.querySelectorAll( '[data-um-swatch]' ).forEach( ( other ) => other.setAttribute( 'aria-pressed', String( other === swatch ) ) );
	const image = card.querySelector<HTMLImageElement>( '[data-um-card-image] img' );
	const src = swatch.dataset.src;
	if ( ! image || ! src ) {
		return;
	}
	image.src = src;
	if ( swatch.dataset.srcset ) {
		image.srcset = swatch.dataset.srcset;
	} else {
		image.removeAttribute( 'srcset' );
	}
}

export const productCard: Behavior = ( element ) => {
	const onClick = async ( event: MouseEvent ): Promise<void> => {
		const swatch = event.target instanceof Element ? event.target.closest<HTMLElement>( '[data-um-swatch]' ) : null;
		if ( swatch ) {
			showSwatch( element, swatch );
			return;
		}
		const button = event.target instanceof Element ? event.target.closest<HTMLAnchorElement>( '[data-um-add]' ) : null;
		if ( ! button || event.ctrlKey || event.metaKey || event.shiftKey ) {
			return;
		}
		event.preventDefault();
		if ( button.getAttribute( 'aria-busy' ) === 'true' ) {
			return;
		}
		button.setAttribute( 'aria-busy', 'true' );
		try {
			const body = new URLSearchParams( { product_id: button.dataset.umAdd ?? '', quantity: '1' } );
			const response = await fetch( endpoint(), { method: 'POST', credentials: 'same-origin', body } );
			const data = ( await response.json() ) as Fragments;
			if ( data.error && data.product_url ) {
				window.location.href = data.product_url;
				return;
			}
			if ( data.fragments ) {
				applyFragments( data.fragments );
			}
			button.dataset.added = 'true';
			document.dispatchEvent( new CustomEvent( 'um:cart-updated', { detail: { id: Number( button.dataset.umAdd ) } } ) );
		} catch {
			window.location.href = button.href;
		} finally {
			button.removeAttribute( 'aria-busy' );
		}
	};
	const listener = ( event: MouseEvent ): void => {
		void onClick( event );
	};
	element.addEventListener( 'click', listener );
	return {
		destroy(): void {
			element.removeEventListener( 'click', listener );
		},
	};
};
