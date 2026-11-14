/**
 * Shared part of the cart and side cart behaviours: sends one change of
 * the cart to the store's endpoint and puts the parts it sends back in
 * place.
 *
 * A part is any element with `data-um-cart-part="<name>"`; the request
 * names the parts on the page (with their `data-args`, such as a widget's
 * options), and the answer brings each one drawn again. Focus returns to
 * the element with the same `data-um-focus` key, so keyboard users stay
 * where they were.
 */
import { applyFragments } from '../product-card/product-card.behavior';

export interface CartReply {
	success?: boolean;
	message?: string;
	parts?: Record<string, string>;
	fragments?: Record<string, string>;
	reload?: boolean;
	count?: number;
}

export interface CartChange {
	action: string;
	key?: string;
	qty?: number;
	code?: string;
}

/**
 * The parts below a root, with their options.
 *
 * @param root Where to look.
 */
export function partsOf( root: ParentNode ): Record<string, string> {
	const parts: Record<string, string> = {};
	root.querySelectorAll<HTMLElement>( '[data-um-cart-part]' ).forEach( ( node ) => {
		const name = node.dataset.umCartPart ?? '';
		if ( name !== '' ) {
			parts[ name ] = node.dataset.args ?? '';
		}
	} );
	return parts;
}

/**
 * The form body of a change.
 *
 * @param change  Change.
 * @param nonce   Security token.
 * @param context Where the change comes from: cart, side or blocks.
 * @param parts   Parts to draw again.
 */
export function bodyOf( change: CartChange, nonce: string, context: string, parts: Record<string, string> ): URLSearchParams {
	const body = new URLSearchParams( { um_action: change.action, nonce, context } );
	if ( change.key !== undefined ) {
		body.set( 'key', change.key );
	}
	if ( change.qty !== undefined ) {
		body.set( 'qty', String( change.qty ) );
	}
	if ( change.code !== undefined ) {
		body.set( 'code', change.code );
	}
	Object.entries( parts ).forEach( ( [ name, args ] ) => body.append( `parts[${ name }]`, args ) );
	return body;
}

/**
 * Replaces the parts below a root and gives focus back.
 *
 * @param root  Root.
 * @param parts Markup by part name.
 */
export function applyParts( root: ParentNode, parts: Record<string, string> ): void {
	const active = document.activeElement instanceof HTMLElement ? document.activeElement.closest<HTMLElement>( '[data-um-focus]' )?.dataset.umFocus : undefined;
	Object.entries( parts ).forEach( ( [ name, markup ] ) => {
		root.querySelectorAll<HTMLElement>( `[data-um-cart-part="${ CSS.escape( name ) }"]` ).forEach( ( node ) => {
			const template = document.createElement( 'template' );
			template.innerHTML = markup.trim();
			const next = template.content.firstElementChild;
			if ( next ) {
				node.replaceWith( next );
				window.UltraMaxUI?.mount( next );
			} else {
				node.replaceChildren();
			}
		} );
	} );
	if ( active ) {
		const again = root.querySelector<HTMLElement>( `[data-um-focus="${ CSS.escape( active ) }"]` );
		again?.focus();
	}
}

/**
 * Sends a change.
 *
 * @param endpoint Endpoint address.
 * @param body     Form body.
 */
export async function send( endpoint: string, body: URLSearchParams ): Promise<CartReply> {
	const response = await fetch( endpoint, { method: 'POST', credentials: 'same-origin', body } );
	if ( ! response.ok ) {
		throw new Error( String( response.status ) );
	}
	return ( await response.json() ) as CartReply;
}

/**
 * Puts the header counters in place and tells the page the cart changed.
 *
 * @param reply  Answer.
 * @param source Root that sent the change.
 */
export function announce( reply: CartReply, source: HTMLElement ): void {
	if ( reply.fragments ) {
		applyFragments( reply.fragments );
	}
	document.dispatchEvent( new CustomEvent( 'um:cart-changed', { detail: { source, count: reply.count ?? null } } ) );
}

/**
 * The change a click asks for, or null.
 *
 * @param target Clicked element.
 */
export function changeOf( target: Element ): CartChange | null {
	const actor = target.closest<HTMLElement>( '[data-um-cart-action]' );
	if ( actor ) {
		return { action: actor.dataset.umCartAction ?? '', key: actor.dataset.key };
	}
	const remove = target.closest<HTMLElement>( '[data-um-coupon-remove]' );
	if ( remove ) {
		return { action: 'uncoupon', code: remove.dataset.umCouponRemove ?? '' };
	}
	const step = target.closest<HTMLButtonElement>( '[data-um-cart-step]' );
	if ( step && ! step.disabled ) {
		const box = step.closest<HTMLElement>( '[data-um-cart-qty]' );
		const input = box?.querySelector<HTMLInputElement>( 'input[type="number"]' );
		if ( ! box || ! input ) {
			return null;
		}
		const size = Math.max( 1, Number( input.step || '1' ) );
		const next = Number( input.value || '0' ) + Number( step.dataset.umCartStep ) * size;
		return { action: 'set', key: box.dataset.umCartQty ?? '', qty: next };
	}
	return null;
}
