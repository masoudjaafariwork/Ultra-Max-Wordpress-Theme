/**
 * Cart behaviour (CART-01 to CART-05): every change of the cart page is
 * sent in place, the cart area is drawn again part by part, and the
 * header counters follow. Without JavaScript the same buttons are links
 * and forms handled by WooCommerce.
 *
 * The root carries `data-endpoint` and `data-nonce`. A change that leaves
 * the cart empty, or a store answer that asks for it, loads the page again.
 */
import type { Behavior } from '../../storefront/runtime';
import { announce, applyParts, bodyOf, changeOf, partsOf, send, type CartChange } from './cart-actions';

export const cart: Behavior = ( element ) => {
	const endpoint = element.dataset.endpoint ?? '';
	const context = element.dataset.context ?? 'cart';
	let busy = false;

	const status = ( text: string, tone: 'danger' | 'success' ): void => {
		const region = element.querySelector<HTMLElement>( '[data-um-cart-status]' );
		if ( region ) {
			region.dataset.tone = tone;
			region.textContent = text;
		}
	};

	const run = async ( change: CartChange ): Promise<void> => {
		if ( busy || endpoint === '' ) {
			return;
		}
		busy = true;
		element.setAttribute( 'aria-busy', 'true' );
		try {
			const reply = await send( endpoint, bodyOf( change, element.dataset.nonce ?? '', context, partsOf( element ) ) );
			if ( reply.reload ) {
				window.location.reload();
				return;
			}
			if ( reply.parts ) {
				applyParts( element, reply.parts );
			}
			announce( reply, element );
			if ( reply.message ) {
				status( reply.message, reply.success === false ? 'danger' : 'success' );
			}
		} catch {
			window.location.reload();
		} finally {
			busy = false;
			element.removeAttribute( 'aria-busy' );
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
		const form = event.target instanceof HTMLFormElement ? event.target : null;
		if ( ! form ) {
			return;
		}
		const code = form.querySelector<HTMLInputElement>( '[data-um-coupon-code]' );
		if ( code ) {
			event.preventDefault();
			if ( code.value.trim() !== '' ) {
				void run( { action: 'coupon', code: code.value.trim() } );
			}
			return;
		}
		// The quantity form: the stepper already sent each change.
		if ( form.querySelector( '[data-um-cart-qty]' ) ) {
			event.preventDefault();
		}
	};

	const onKey = ( event: KeyboardEvent ): void => {
		const input = event.target instanceof HTMLInputElement ? event.target : null;
		if ( event.key === 'Enter' && input?.closest( '[data-um-cart-qty]' ) ) {
			event.preventDefault();
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
	};

	element.addEventListener( 'click', onClick );
	element.addEventListener( 'change', onChange );
	element.addEventListener( 'submit', onSubmit );
	element.addEventListener( 'keydown', onKey );
	return {
		destroy(): void {
			element.removeEventListener( 'click', onClick );
			element.removeEventListener( 'change', onChange );
			element.removeEventListener( 'submit', onSubmit );
			element.removeEventListener( 'keydown', onKey );
		},
	};
};
