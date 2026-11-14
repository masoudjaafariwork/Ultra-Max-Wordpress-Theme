/**
 * SideCart behaviour (CART-01). The drawer of every page holds an empty
 * shell; its content comes from the store when the drawer opens, so
 * cached pages never show an old cart. Quantities change and lines go in
 * place. When a product is added anywhere on the page (`um:cart-updated`),
 * the content is drawn again and, if the store wants it, the drawer opens;
 * not on a product page, whose buy box confirms the add itself.
 */
import type { Behavior } from '../../storefront/runtime';
import { announce, applyParts, bodyOf, changeOf, send, type CartChange } from './cart-actions';

export const sideCart: Behavior = ( element ) => {
	const endpoint = element.dataset.endpoint ?? '';
	const drawer = element.closest<HTMLElement>( 'dialog' );
	let fresh = false;
	let busy = false;
	let nonce = '';

	const draw = ( markup: string ): void => {
		element.querySelector( '[data-um-side-loading]' )?.remove();
		const holder = element.querySelector<HTMLElement>( '[data-um-cart-part="side"]' );
		if ( holder ) {
			applyParts( element, { side: markup } );
		} else {
			const template = document.createElement( 'template' );
			template.innerHTML = markup.trim();
			element.append( template.content );
			window.UltraMaxUI?.mount( element );
		}
		nonce = element.querySelector<HTMLElement>( '[data-um-side-content]' )?.dataset.nonce ?? nonce;
	};

	const run = async ( change: CartChange ): Promise<void> => {
		if ( busy || endpoint === '' ) {
			return;
		}
		busy = true;
		element.setAttribute( 'aria-busy', 'true' );
		try {
			const reply = await send( endpoint, bodyOf( change, nonce, 'side', { side: '' } ) );
			if ( reply.parts?.side !== undefined ) {
				draw( reply.parts.side );
			}
			fresh = true;
			if ( change.action !== 'view' ) {
				announce( reply, element );
			}
		} catch {
			element.querySelector( '[data-um-side-loading]' )?.replaceChildren( document.createTextNode( element.dataset.error ?? '' ) );
		} finally {
			busy = false;
			element.setAttribute( 'aria-busy', 'false' );
		}
	};

	const isOpen = (): boolean => drawer?.hasAttribute( 'open' ) ?? false;

	const observer = new MutationObserver( () => {
		if ( isOpen() && ! fresh ) {
			void run( { action: 'view' } );
		}
	} );
	if ( drawer ) {
		observer.observe( drawer, { attributes: true, attributeFilter: [ 'open' ] } );
	}

	const onAdded = (): void => {
		fresh = false;
		// A product page confirms in its own buy box and keeps "buy now" in reach.
		const ownPage = document.querySelector( '[data-um-product] form.um-purchase' ) !== null;
		if ( element.hasAttribute( 'data-open-on-add' ) && ! ownPage && drawer && ! isOpen() ) {
			window.UltraMaxUI?.get( drawer )?.open?.();
		} else if ( isOpen() ) {
			void run( { action: 'view' } );
		}
	};
	const onChanged = ( event: Event ): void => {
		const source = ( event as CustomEvent<{ source?: HTMLElement }> ).detail?.source;
		if ( source !== element ) {
			fresh = false;
			if ( isOpen() ) {
				void run( { action: 'view' } );
			}
		}
	};
	const onClick = ( event: MouseEvent ): void => {
		const target = event.target instanceof Element ? event.target : null;
		const change = target ? changeOf( target ) : null;
		if ( ! change || event.ctrlKey || event.metaKey || event.shiftKey ) {
			return;
		}
		event.preventDefault();
		void run( change );
	};
	const onChange = ( event: Event ): void => {
		const input = event.target instanceof HTMLInputElement ? event.target : null;
		const box = input?.closest<HTMLElement>( '[data-um-cart-qty]' );
		if ( input && box ) {
			void run( { action: 'set', key: box.dataset.umCartQty ?? '', qty: Number( input.value || '0' ) } );
		}
	};
	const onSubmit = ( event: SubmitEvent ): void => {
		event.preventDefault();
	};

	document.addEventListener( 'um:cart-updated', onAdded );
	document.addEventListener( 'um:cart-changed', onChanged );
	element.addEventListener( 'click', onClick );
	element.addEventListener( 'change', onChange );
	element.addEventListener( 'submit', onSubmit );
	return {
		destroy(): void {
			observer.disconnect();
			document.removeEventListener( 'um:cart-updated', onAdded );
			document.removeEventListener( 'um:cart-changed', onChanged );
			element.removeEventListener( 'click', onClick );
			element.removeEventListener( 'change', onChange );
			element.removeEventListener( 'submit', onSubmit );
		},
	};
};
