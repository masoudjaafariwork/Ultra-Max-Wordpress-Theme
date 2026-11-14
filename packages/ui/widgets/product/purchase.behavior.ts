/**
 * PurchaseForm behaviour (PDP-04, PDP-05, PDP-21, PDP-25).
 *
 * - The stepper keeps the quantity inside the product's minimum, maximum
 *   and step.
 * - For a variable product it matches the chosen values to a variation,
 *   fills `variation_id`, writes the variation's price, stock and limits
 *   into every part of the page that shows them, greys out values with
 *   nothing in stock, and fires `um:variation` on the document.
 * - It sends the form in place through the plugin's endpoint and updates
 *   the cart counters; "buy now" then goes to the checkout. Without the
 *   endpoint, or when the request fails, the form is sent normally.
 */
import type { Behavior } from '../../storefront/runtime';
import { applyFragments } from '../product-card/product-card.behavior';

export interface Variation {
	id: number;
	attributes: Record<string, string>;
	price: string;
	regular: string;
	discount: string;
	in_stock: boolean;
	stock: string;
	stock_tone: string;
	min: number;
	max: number;
	step: number;
	gallery: string;
	sku: string;
	summary: string;
}

interface Messages {
	choose: string;
	unavailable: string;
	out: string;
	added: string;
	cart: string;
	cart_url: string;
	error: string;
}

interface Reply {
	success?: boolean;
	message?: string;
	redirect?: string;
	fragments?: Record<string, string>;
}

/**
 * Whether a variation fits the chosen values. An empty value of the
 * variation means "any".
 *
 * @param variation Variation.
 * @param chosen    Chosen values by field name; missing means not chosen.
 */
export function fits( variation: Variation, chosen: Record<string, string> ): boolean {
	return Object.entries( chosen ).every( ( [ name, value ] ) => {
		if ( value === '' ) {
			return true;
		}
		const own = variation.attributes[ name ] ?? '';
		return own === '' || own === value;
	} );
}

/**
 * The variation of a complete choice, or null.
 *
 * @param variations Variations.
 * @param names      Every attribute field name.
 * @param chosen     Chosen values.
 */
export function match( variations: Variation[], names: string[], chosen: Record<string, string> ): Variation | null {
	if ( names.some( ( name ) => ( chosen[ name ] ?? '' ) === '' ) ) {
		return null;
	}
	return variations.find( ( variation ) => fits( variation, chosen ) ) ?? null;
}

/**
 * Keeps a quantity inside its limits.
 *
 * @param value Wanted quantity.
 * @param min   Minimum, at least 1.
 * @param max   Maximum, 0 for none.
 * @param step  Step, at least 1.
 */
export function clamp( value: number, min: number, max: number, step: number ): number {
	const low = Math.max( 1, min );
	const size = Math.max( 1, step );
	let next = Number.isFinite( value ) ? Math.round( value ) : low;
	next = Math.max( low, next );
	if ( max > 0 ) {
		next = Math.min( max, next );
	}
	// Quantities go up from the minimum in steps.
	const offset = ( next - low ) % size;
	if ( offset !== 0 ) {
		next -= offset;
	}
	return Math.max( low, next );
}

export const purchase: Behavior = ( element ) => {
	const form = element as HTMLFormElement;
	const product = Number( form.dataset.product ?? '0' );
	const messages = JSON.parse( form.dataset.messages ?? '{}' ) as Messages;
	const raw = form.querySelector<HTMLScriptElement>( 'script[data-um-variations]' );
	const variations = raw ? ( JSON.parse( raw.textContent ?? '[]' ) as Variation[] ) : null;
	const quantity = form.querySelector<HTMLInputElement>( 'input[name="quantity"]' );
	const status = form.querySelector<HTMLElement>( '[data-um-purchase-status]' );
	const variationField = form.querySelector<HTMLInputElement>( '[data-um-variation-id]' );
	const limits = {
		min: Number( quantity?.min || '1' ),
		max: Number( quantity?.max || '0' ),
		step: Number( quantity?.step || '1' ),
	};
	const scope = `[data-um-price-for="${ product }"]`;
	const prices = Array.from( document.querySelectorAll<HTMLElement>( scope ) ).map( ( node ) => ( { node, html: node.innerHTML } ) );
	const stocks = Array.from( document.querySelectorAll<HTMLElement>( `[data-um-stock-for="${ product }"]` ) ).map( ( node ) => ( {
		node,
		html: node.innerHTML,
		tone: node.dataset.tone ?? '',
		empty: ( node.querySelector( '[data-um-stock-text]' )?.textContent ?? '' ).trim() === '',
	} ) );
	let current: Variation | null = null;

	const say = ( text: string, tone: 'danger' | 'success' | '' = '', link?: { href: string; label: string } ): void => {
		if ( ! status ) {
			return;
		}
		status.replaceChildren( document.createTextNode( text ) );
		if ( link ) {
			const anchor = document.createElement( 'a' );
			anchor.href = link.href;
			anchor.textContent = link.label;
			anchor.className = 'ms-2 font-bold text-link';
			status.append( anchor );
		}
		status.dataset.tone = tone;
		status.toggleAttribute( 'data-shown', text !== '' );
	};

	/* ---------------------------------------------------------- quantity */

	const setLimits = ( min: number, max: number, step: number ): void => {
		if ( ! quantity ) {
			return;
		}
		quantity.min = String( Math.max( 1, min ) );
		quantity.step = String( Math.max( 1, step ) );
		if ( max > 0 ) {
			quantity.max = String( max );
		} else {
			quantity.removeAttribute( 'max' );
		}
		quantity.value = String( clamp( Number( quantity.value ), min, max, step ) );
		syncSteppers();
	};

	const syncSteppers = (): void => {
		if ( ! quantity ) {
			return;
		}
		const value = Number( quantity.value );
		const max = Number( quantity.max || '0' );
		form.querySelectorAll<HTMLButtonElement>( '[data-um-step]' ).forEach( ( button ) => {
			const up = Number( button.dataset.umStep ) > 0;
			button.disabled = up ? max > 0 && value >= max : value <= Number( quantity.min || '1' );
		} );
	};

	const onStep = ( event: MouseEvent ): void => {
		const button = event.target instanceof Element ? event.target.closest<HTMLElement>( '[data-um-step]' ) : null;
		if ( ! button || ! quantity ) {
			return;
		}
		const step = Number( quantity.step || '1' );
		quantity.value = String( clamp( Number( quantity.value ) + Number( button.dataset.umStep ) * step, Number( quantity.min || '1' ), Number( quantity.max || '0' ), step ) );
		quantity.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		syncSteppers();
	};

	/* ---------------------------------------------------------- variations */

	const fields = (): Array<HTMLInputElement | HTMLSelectElement> =>
		Array.from( form.elements ).filter( ( field ): field is HTMLInputElement | HTMLSelectElement => ( field instanceof HTMLInputElement || field instanceof HTMLSelectElement ) && field.hasAttribute( 'data-um-attribute' ) );

	const names = (): string[] => [ ...new Set( fields().map( ( field ) => field.name ) ) ];

	const chosen = (): Record<string, string> => {
		const out: Record<string, string> = {};
		names().forEach( ( name ) => ( out[ name ] = '' ) );
		fields().forEach( ( field ) => {
			if ( field instanceof HTMLSelectElement ) {
				out[ field.name ] = field.value;
			} else if ( field.checked ) {
				out[ field.name ] = field.value;
			}
		} );
		return out;
	};

	const showPrice = ( variation: Variation | null ): void => {
		prices.forEach( ( { node, html } ) => {
			if ( ! variation ) {
				node.innerHTML = html;
				return;
			}
			const set = ( selector: string, text: string ): void => {
				const slot = node.querySelector<HTMLElement>( selector );
				if ( slot ) {
					slot.textContent = text;
					slot.hidden = text === '';
				}
			};
			node.querySelector<HTMLElement>( '[data-um-price-call]' )?.setAttribute( 'hidden', '' );
			node.querySelector<HTMLElement>( '[data-um-price-call]' )?.nextElementSibling?.removeAttribute( 'hidden' );
			set( '[data-um-price-now]', variation.price );
			set( '[data-um-price-old]', variation.regular );
			set( '[data-um-price-off]', variation.discount );
			set( '[data-um-price-prefix]', '' );
			const now = node.querySelector<HTMLElement>( '[data-um-price-now]' );
			if ( now ) {
				now.hidden = false;
			}
		} );
	};

	const showStock = ( variation: Variation | null ): void => {
		stocks.forEach( ( { node, html, tone, empty } ) => {
			if ( ! variation ) {
				node.innerHTML = html;
				node.dataset.tone = tone;
				node.hidden = empty;
				return;
			}
			const text = node.querySelector<HTMLElement>( '[data-um-stock-text]' );
			if ( text ) {
				text.textContent = variation.stock;
			}
			node.dataset.tone = variation.stock_tone;
			node.hidden = variation.stock === '';
		} );
	};

	const markAvailable = ( values: Record<string, string> ): void => {
		if ( ! variations ) {
			return;
		}
		fields().forEach( ( field ) => {
			const options = field instanceof HTMLSelectElement ? Array.from( field.options ).filter( ( option ) => option.value !== '' ) : [ field ];
			options.forEach( ( option ) => {
				const trial = { ...values, [ field.name ]: option.value };
				const open = variations.some( ( variation ) => variation.in_stock && fits( variation, trial ) );
				const exists = variations.some( ( variation ) => fits( variation, trial ) );
				const label = option instanceof HTMLInputElement ? option.closest( 'label' ) : null;
				label?.toggleAttribute( 'data-out', ! open );
				if ( option instanceof HTMLOptionElement ) {
					option.disabled = ! exists;
				}
			} );
		} );
	};

	const update = ( announce = false ): void => {
		if ( ! variations ) {
			return;
		}
		const values = chosen();
		document.querySelectorAll<HTMLElement>( `[data-um-picker="${ CSS.escape( form.id ) }"] [data-um-picker-value]` ).forEach( ( slot ) => {
			const name = slot.dataset.umPickerValue ?? '';
			const field = fields().find( ( item ) => item.name === name && ( item instanceof HTMLSelectElement || item.checked ) );
			let label = '';
			if ( field instanceof HTMLSelectElement ) {
				label = field.value === '' ? '' : field.selectedOptions[ 0 ]?.textContent ?? '';
			} else if ( field ) {
				label = field.closest( 'label' )?.textContent?.trim() ?? '';
			}
			slot.textContent = label === '' ? slot.dataset.empty ?? '' : label;
		} );
		document.querySelectorAll<HTMLElement>( `[data-um-picker="${ CSS.escape( form.id ) }"] [data-um-picker-reset]` ).forEach( ( reset ) => {
			reset.hidden = Object.values( values ).every( ( value ) => value === '' );
		} );
		markAvailable( values );
		current = match( variations, names(), values );
		if ( variationField ) {
			variationField.value = current ? String( current.id ) : '0';
		}
		showPrice( current );
		showStock( current );
		document.querySelectorAll<HTMLElement>( `[data-um-summary-for="${ product }"]` ).forEach( ( node ) => {
			node.textContent = current?.summary ?? node.dataset.empty ?? '';
		} );
		if ( current ) {
			setLimits( current.min, current.max, current.step );
		} else {
			setLimits( limits.min, limits.max, limits.step );
		}
		const complete = names().every( ( name ) => values[ name ] !== '' );
		const blocked = complete && ( ! current || ! current.in_stock );
		form.querySelectorAll<HTMLButtonElement>( '[data-um-add-button], [data-um-quick-button]' ).forEach( ( button ) => ( button.disabled = blocked ) );
		document.querySelectorAll<HTMLButtonElement>( `button[form="${ CSS.escape( form.id ) }"][type="submit"]` ).forEach( ( button ) => ( button.disabled = blocked ) );
		if ( announce ) {
			if ( complete && ! current ) {
				say( messages.unavailable, 'danger' );
			} else if ( current && ! current.in_stock ) {
				say( messages.out, 'danger' );
			} else {
				say( '' );
			}
		}
		document.dispatchEvent( new CustomEvent( 'um:variation', { detail: { product, variation: current?.id ?? 0, gallery: current?.gallery ?? '', sku: current?.sku ?? '' } } ) );
	};

	const onChange = ( event: Event ): void => {
		const target = event.target;
		if ( ! ( target instanceof HTMLInputElement || target instanceof HTMLSelectElement ) || target.form !== form ) {
			return;
		}
		if ( target.hasAttribute( 'data-um-attribute' ) ) {
			update( true );
		}
		if ( target === quantity && quantity ) {
			quantity.value = String( clamp( Number( quantity.value ), Number( quantity.min || '1' ), Number( quantity.max || '0' ), Number( quantity.step || '1' ) ) );
			syncSteppers();
		}
	};

	const onReset = (): void => {
		setTimeout( () => {
			update();
			say( '' );
		} );
	};

	/* ---------------------------------------------------------- sending */

	const onSubmit = async ( event: SubmitEvent ): Promise<void> => {
		if ( variations && ! current ) {
			event.preventDefault();
			say( messages.choose, 'danger' );
			const first = fields().find( ( field ) => ( chosen()[ field.name ] ?? '' ) === '' );
			first?.closest( 'fieldset, div' )?.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			first?.focus( { preventScroll: true } );
			return;
		}
		const endpoint = form.dataset.endpoint ?? '';
		if ( endpoint === '' || typeof fetch !== 'function' ) {
			return;
		}
		event.preventDefault();
		const submitter = event.submitter instanceof HTMLButtonElement ? event.submitter : null;
		const buttons = Array.from( form.querySelectorAll<HTMLButtonElement>( 'button[type="submit"]' ) );
		buttons.forEach( ( button ) => button.setAttribute( 'aria-busy', 'true' ) );
		const body = new FormData( form );
		if ( submitter?.name ) {
			body.set( submitter.name, submitter.value );
		}
		// WooCommerce adds anything posted as "add-to-cart" by itself, before
		// the endpoint runs; the endpoint reads the product from its own field.
		body.delete( 'add-to-cart' );
		body.set( 'um_product', String( product ) );
		try {
			const response = await fetch( endpoint, { method: 'POST', credentials: 'same-origin', body } );
			const reply = ( await response.json() ) as Reply;
			if ( reply.redirect ) {
				window.location.href = reply.redirect;
				return;
			}
			if ( reply.fragments ) {
				applyFragments( reply.fragments );
			}
			if ( reply.success ) {
				say( reply.message || messages.added, 'success', { href: messages.cart_url, label: messages.cart } );
				document.dispatchEvent( new CustomEvent( 'um:cart-updated', { detail: { id: product, variation: current?.id ?? 0 } } ) );
			} else {
				say( reply.message || messages.error, 'danger' );
			}
		} catch {
			// The page sends the form itself, with WooCommerce's own messages.
			form.removeAttribute( 'data-endpoint' );
			if ( submitter ) {
				form.requestSubmit( submitter );
			} else {
				form.submit();
			}
		} finally {
			buttons.forEach( ( button ) => button.removeAttribute( 'aria-busy' ) );
		}
	};

	const submitListener = ( event: SubmitEvent ): void => {
		void onSubmit( event );
	};

	form.addEventListener( 'click', onStep );
	form.addEventListener( 'reset', onReset );
	form.addEventListener( 'submit', submitListener );
	// Choices outside the form (the picker) send their events to the document.
	document.addEventListener( 'change', onChange );
	syncSteppers();
	if ( variations ) {
		update();
	}

	return {
		destroy(): void {
			form.removeEventListener( 'click', onStep );
			form.removeEventListener( 'reset', onReset );
			form.removeEventListener( 'submit', submitListener );
			document.removeEventListener( 'change', onChange );
		},
	};
};
