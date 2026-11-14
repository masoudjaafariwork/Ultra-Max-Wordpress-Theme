/**
 * BundleBox behaviour (PDP-22): shows the store's totals for the ticked
 * products and adds them together through the purchase endpoint.
 */
import type { Behavior } from '../../storefront/runtime';
import { applyFragments } from '../product-card/product-card.behavior';

interface Total {
	old: string;
	now: string;
	save: string;
}

export const bundle: Behavior = ( element ) => {
	const totals = JSON.parse( element.querySelector( 'script[data-um-bundle-totals]' )?.textContent ?? '{}' ) as Record<string, Total>;
	const messages = JSON.parse( element.dataset.messages ?? '{}' ) as Record<string, string>;
	const boxes = Array.from( element.querySelectorAll<HTMLInputElement>( '[data-um-bundle-item]' ) );
	const button = element.querySelector<HTMLButtonElement>( '[data-um-bundle-add]' );
	const status = element.querySelector<HTMLElement>( '[data-um-bundle-status]' );
	const chosen = (): string[] => boxes.filter( ( box ) => box.checked ).map( ( box ) => box.value );

	const show = (): void => {
		const total = totals[ chosen().join( ',' ) ];
		const set = ( selector: string, text: string ): void => {
			const slot = element.querySelector<HTMLElement>( selector );
			if ( slot ) {
				slot.textContent = text;
				slot.toggleAttribute( 'data-empty', text === '' );
			}
		};
		set( '[data-um-bundle-old]', total?.old ?? '' );
		set( '[data-um-bundle-now]', total?.now ?? '' );
		set( '[data-um-bundle-save]', total?.save ?? '' );
	};

	const say = ( text: string, tone: string ): void => {
		if ( status ) {
			status.textContent = text;
			status.dataset.tone = tone;
			status.toggleAttribute( 'data-shown', text !== '' );
		}
	};

	const add = async (): Promise<void> => {
		if ( ! button ) {
			return;
		}
		button.setAttribute( 'aria-busy', 'true' );
		try {
			const body = new URLSearchParams( { um_items: chosen().join( ',' ) } );
			const response = await fetch( element.dataset.endpoint ?? '', { method: 'POST', credentials: 'same-origin', body } );
			const reply = ( await response.json() ) as { success?: boolean; message?: string; fragments?: Record<string, string> };
			if ( reply.fragments ) {
				applyFragments( reply.fragments );
			}
			say( reply.message || ( reply.success ? messages.added ?? '' : messages.error ?? '' ), reply.success ? 'success' : 'danger' );
			if ( reply.success ) {
				document.dispatchEvent( new CustomEvent( 'um:cart-updated', { detail: { bundle: chosen() } } ) );
			}
		} catch {
			say( messages.error ?? '', 'danger' );
		} finally {
			button.removeAttribute( 'aria-busy' );
		}
	};

	const onAdd = (): void => {
		void add();
	};
	boxes.forEach( ( box ) => box.addEventListener( 'change', show ) );
	button?.addEventListener( 'click', onAdd );
	show();
	return {
		destroy(): void {
			boxes.forEach( ( box ) => box.removeEventListener( 'change', show ) );
			button?.removeEventListener( 'click', onAdd );
		},
	};
};
