/**
 * Checkout behaviour (CHK-01 to CHK-05, CHK-09). The form is WooCommerce's
 * own checkout form, which WooCommerce's script sends; this adds:
 *
 * - steps: "shipping details" first, then "payment" (`data-mode="steps"`).
 *   Going on checks the first step's fields here; an error the store finds
 *   on placing the order brings the step that holds the field back;
 * - an instant check of mobile, postal code, national code and company
 *   details (`data-um-check`), with the store's messages;
 * - the province's cities in the city list;
 * - saved addresses that fill the address fields;
 * - the discount code, sent in place.
 *
 * WooCommerce's script listens through jQuery, so changes made here are
 * announced through jQuery too when it is on the page.
 */
import type { Behavior } from '../../storefront/runtime';
import { digits, mobile, passes } from './iran';

interface JQueryish {
	( target: unknown ): {
		on: ( events: string, selector: string | ( ( ...args: unknown[] ) => void ), handler?: ( ...args: unknown[] ) => void ) => unknown;
		off: ( events: string, handler?: ( ...args: unknown[] ) => void ) => unknown;
		trigger: ( event: string, data?: unknown ) => unknown;
	};
}

interface CheckoutParams {
	wc_ajax_url?: string;
	apply_coupon_nonce?: string;
	remove_coupon_nonce?: string;
}

interface Messages {
	required?: string;
	mobile?: string;
	postcode?: string;
	national_code?: string;
	company_id?: string;
	economic_code?: string;
	digits?: string;
	choose_city?: string;
	coupon_empty?: string;
}

/** WooCommerce's globals on the checkout page, read without declaring them for every script. */
const scope = window as unknown as { jQuery?: JQueryish; wc_checkout_params?: CheckoutParams };

/**
 * Tells WooCommerce's script that a field changed.
 *
 * @param field Field.
 */
function changed( field: HTMLElement ): void {
	if ( scope.jQuery ) {
		scope.jQuery( field ).trigger( 'change' );
	} else {
		field.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}
}

/**
 * Asks WooCommerce's script to work out the order again.
 */
function refresh(): void {
	scope.jQuery?.( document.body ).trigger( 'update_checkout' );
}

export const checkout: Behavior = ( element ) => {
	const form = element as HTMLFormElement;
	const messages = JSON.parse( form.dataset.messages ?? '{}' ) as Messages;
	const cities = JSON.parse( form.querySelector<HTMLScriptElement>( 'script[data-um-cities]' )?.textContent ?? '{}' ) as Record<string, string[]>;
	const steps = form.dataset.mode === 'steps';

	/* ------------------------------------------------------------ checks */

	const rowOf = ( field: HTMLElement ): HTMLElement | null => field.closest<HTMLElement>( '.form-row' );

	const showError = ( field: HTMLElement, text: string ): void => {
		const row = rowOf( field );
		if ( ! row ) {
			return;
		}
		row.classList.toggle( 'um-invalid', text !== '' );
		field.setAttribute( 'aria-invalid', text !== '' ? 'true' : 'false' );
		let note = row.querySelector<HTMLElement>( '.um-field-error' );
		if ( text === '' ) {
			note?.remove();
			return;
		}
		if ( ! note ) {
			note = document.createElement( 'p' );
			note.className = 'um-field-error';
			note.id = `${ field.id }-error`;
			note.setAttribute( 'role', 'alert' );
			row.append( note );
			field.setAttribute( 'aria-describedby', note.id );
		}
		note.textContent = text;
	};

	const visible = ( field: HTMLElement ): boolean => field.getClientRects().length > 0;

	const problemOf = ( field: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement ): string => {
		const row = rowOf( field );
		const value = field instanceof HTMLInputElement && field.type === 'checkbox' ? ( field.checked ? '1' : '' ) : field.value;
		const required = row?.classList.contains( 'validate-required' ) || row?.querySelector( 'label.um-required' ) !== null;
		if ( required && value.trim() === '' ) {
			return messages.required ?? '';
		}
		const rule = field.dataset.umCheck ?? '';
		if ( rule !== '' && ! passes( rule, value ) ) {
			return ( messages as Record<string, string | undefined> )[ rule ] ?? '';
		}
		return '';
	};

	const fieldsOf = ( root: ParentNode ): Array<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement> =>
		Array.from( root.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>( '.form-row input:not([type="hidden"]):not([type="radio"]), .form-row select, .form-row textarea' ) ).filter( visible );

	/**
	 * Checks the fields below a root; returns the first wrong one.
	 *
	 * @param root Root.
	 */
	const check = ( root: ParentNode ): HTMLElement | null => {
		let first: HTMLElement | null = null;
		fieldsOf( root ).forEach( ( field ) => {
			const problem = problemOf( field );
			showError( field, problem );
			if ( problem !== '' && ! first ) {
				first = field;
			}
		} );
		return first;
	};

	const onBlur = ( event: FocusEvent ): void => {
		const field = event.target;
		if ( ( field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement ) && rowOf( field ) ) {
			if ( field instanceof HTMLInputElement && field.dataset.umCheck === 'mobile' ) {
				const clean = mobile( field.value );
				if ( clean ) {
					field.value = clean;
				}
			} else if ( field instanceof HTMLInputElement && field.dataset.umCheck && field.dataset.umCheck !== '' ) {
				const clean = digits( field.value );
				if ( clean !== '' && /^[\d\s۰-۹٠-٩-]+$/.test( field.value ) ) {
					field.value = clean;
				}
			}
			showError( field, problemOf( field ) );
		}
	};

	/* ------------------------------------------------------------ steps */

	const go = ( step: 'shipping' | 'payment', focus = true ): void => {
		if ( ! steps ) {
			return;
		}
		form.dataset.step = step;
		document.querySelectorAll<HTMLElement>( '[data-um-step-item]' ).forEach( ( item ) => {
			const key = item.dataset.umStepItem;
			let state = 'todo';
			if ( key === 'cart' || ( key === 'shipping' && step === 'payment' ) ) {
				state = 'done';
			} else if ( key === step ) {
				state = 'current';
			}
			item.dataset.state = state;
			if ( state === 'current' ) {
				item.setAttribute( 'aria-current', 'step' );
			} else {
				item.removeAttribute( 'aria-current' );
			}
		} );
		if ( focus ) {
			const target = form.querySelector<HTMLElement>( `[data-um-step="${ step }"] h2` );
			target?.setAttribute( 'tabindex', '-1' );
			window.scrollTo( { top: 0, behavior: 'smooth' } );
			target?.focus( { preventScroll: true } );
		}
	};

	const next = (): void => {
		const pane = form.querySelectorAll<HTMLElement>( '[data-um-step="shipping"]' );
		let wrong: HTMLElement | null = null;
		pane.forEach( ( section ) => {
			wrong = wrong ?? check( section );
		} );
		if ( wrong ) {
			( wrong as HTMLElement ).focus();
			return;
		}
		go( 'payment' );
	};

	/* ------------------------------------------------------------ cities */

	const fillCities = ( group: string, keep = true ): void => {
		const state = form.querySelector<HTMLSelectElement | HTMLInputElement>( `#${ group }_state` );
		const city = form.querySelector<HTMLSelectElement>( `select#${ group }_city` );
		if ( ! state || ! city ) {
			return;
		}
		const list = cities[ state.value ] ?? [];
		const current = keep ? city.value : '';
		const options = [ new Option( messages.choose_city ?? '', '' ) ];
		list.forEach( ( name ) => options.push( new Option( name, name ) ) );
		if ( current !== '' && ! list.includes( current ) ) {
			options.push( new Option( current, current ) );
		}
		city.replaceChildren( ...options );
		city.value = current;
	};

	const onStateChange = ( group: string ): void => {
		fillCities( group, false );
	};

	/* ------------------------------------------------------------ addresses */

	const fillAddress = ( fields: Record<string, string> ): void => {
		[ 'billing', 'shipping' ].forEach( ( group ) => {
			const order = [ 'country', 'state', 'city', 'first_name', 'last_name', 'address_1', 'address_2', 'postcode', 'phone' ];
			order.forEach( ( key ) => {
				const field = form.querySelector<HTMLInputElement | HTMLSelectElement>( `#${ group }_${ key }` );
				const value = fields[ key ];
				if ( ! field || value === undefined || ( key === 'phone' && group === 'shipping' ) ) {
					return;
				}
				if ( key === 'city' ) {
					fillCities( group, false );
					if ( field instanceof HTMLSelectElement && ! Array.from( field.options ).some( ( option ) => option.value === value ) ) {
						field.append( new Option( value, value ) );
					}
				}
				field.value = value;
				if ( key === 'state' || key === 'country' ) {
					changed( field );
				}
			} );
		} );
		refresh();
	};

	const onClick = ( event: MouseEvent ): void => {
		const target = event.target instanceof Element ? event.target : null;
		if ( ! target ) {
			return;
		}
		if ( target.closest( '[data-um-next]' ) ) {
			event.preventDefault();
			next();
			return;
		}
		if ( target.closest( '[data-um-back]' ) ) {
			event.preventDefault();
			go( 'shipping' );
			return;
		}
		const card = target.closest<HTMLElement>( '[data-um-address]' );
		if ( card && form.contains( card ) ) {
			event.preventDefault();
			form.querySelectorAll( '[data-um-address]' ).forEach( ( other ) => other.setAttribute( 'aria-pressed', String( other === card ) ) );
			form.dataset.address = 'saved';
			fillAddress( JSON.parse( card.dataset.umAddress ?? '{}' ) as Record<string, string> );
			return;
		}
		if ( target.closest( '[data-um-address-new]' ) ) {
			event.preventDefault();
			form.querySelectorAll( '[data-um-address]' ).forEach( ( other ) => other.setAttribute( 'aria-pressed', 'false' ) );
			form.dataset.address = 'new';
			[ 'address_1', 'address_2', 'postcode' ].forEach( ( key ) => {
				const field = form.querySelector<HTMLInputElement>( `#billing_${ key }` );
				if ( field ) {
					field.value = '';
				}
			} );
			form.querySelector<HTMLElement>( '.um-address-fields input, .um-address-fields select' )?.focus();
			return;
		}
		if ( target.closest( '[data-um-address-edit]' ) ) {
			event.preventDefault();
			form.dataset.address = 'new';
			return;
		}
		const send = target.closest( '[data-um-coupon-send]' );
		if ( send ) {
			event.preventDefault();
			void coupon( send.closest<HTMLElement>( '[data-um-component="coupon"]' ) );
			return;
		}
		const remove = target.closest<HTMLElement>( '[data-um-coupon-remove]' );
		if ( remove ) {
			event.preventDefault();
			void uncoupon( remove.dataset.umCouponRemove ?? '' );
		}
	};

	/* ------------------------------------------------------------ coupons */

	const endpoint = ( action: string ): string => ( scope.wc_checkout_params?.wc_ajax_url ?? '' ).replace( '%%endpoint%%', action );

	const say = ( box: HTMLElement | null, html: string, tone: string ): void => {
		const status = box?.querySelector<HTMLElement>( '[data-um-coupon-status]' );
		if ( status ) {
			const template = document.createElement( 'template' );
			template.innerHTML = html;
			status.dataset.tone = tone;
			status.textContent = template.content.textContent?.trim() ?? '';
		}
	};

	const coupon = async ( box: HTMLElement | null ): Promise<void> => {
		const input = box?.querySelector<HTMLInputElement>( '[data-um-coupon-code]' );
		if ( ! box || ! input ) {
			return;
		}
		if ( input.value.trim() === '' ) {
			say( box, messages.coupon_empty ?? '', 'danger' );
			return;
		}
		const body = new URLSearchParams( {
			security: scope.wc_checkout_params?.apply_coupon_nonce ?? '',
			coupon_code: input.value.trim(),
			billing_email: form.querySelector<HTMLInputElement>( '#billing_email' )?.value ?? '',
		} );
		const response = await fetch( endpoint( 'apply_coupon' ), { method: 'POST', credentials: 'same-origin', body } );
		const html = await response.text();
		const failed = html.includes( 'woocommerce-error' );
		say( box, html, failed ? 'danger' : 'success' );
		if ( ! failed ) {
			input.value = '';
			refresh();
		}
	};

	const uncoupon = async ( code: string ): Promise<void> => {
		const body = new URLSearchParams( { security: scope.wc_checkout_params?.remove_coupon_nonce ?? '', coupon: code } );
		await fetch( endpoint( 'remove_coupon' ), { method: 'POST', credentials: 'same-origin', body } );
		refresh();
	};

	/* ------------------------------------------------------------ store errors */

	const onError = (): void => {
		if ( ! steps ) {
			return;
		}
		const ids = Array.from( document.querySelectorAll<HTMLElement>( '.woocommerce-NoticeGroup-checkout [data-id]' ) ).map( ( item ) => item.dataset.id ?? '' );
		const inFirst = ids.some( ( id ) => id !== '' && form.querySelector( `[data-um-step="shipping"] #${ CSS.escape( id ) }` ) );
		if ( inFirst || ids.length === 0 ) {
			const first = ids.map( ( id ) => form.querySelector<HTMLElement>( `#${ CSS.escape( id ) }` ) ).find( ( field ) => field !== null );
			if ( inFirst ) {
				go( 'shipping', false );
				first?.focus();
			}
		}
	};

	/* ------------------------------------------------------------ wiring */

	const onNativeChange = ( event: Event ): void => {
		const field = event.target instanceof HTMLElement ? event.target : null;
		if ( field?.id === 'billing_state' || field?.id === 'shipping_state' ) {
			onStateChange( field.id.replace( '_state', '' ) );
		}
	};
	const onJqueryChange = ( event: unknown ): void => {
		const field = ( event as { target?: unknown } ).target;
		if ( field instanceof HTMLElement ) {
			onStateChange( field.id.replace( '_state', '' ) );
		}
	};

	form.addEventListener( 'click', onClick );
	form.addEventListener( 'focusout', onBlur );
	form.addEventListener( 'change', onNativeChange );
	scope.jQuery?.( form ).on( 'change', '#billing_state, #shipping_state', onJqueryChange );
	scope.jQuery?.( document.body ).on( 'checkout_error', onError );
	if ( form.querySelector( '[data-um-address][aria-pressed="true"]' ) ) {
		form.dataset.address = 'saved';
	}
	go( 'shipping', false );

	return {
		destroy(): void {
			form.removeEventListener( 'click', onClick );
			form.removeEventListener( 'focusout', onBlur );
			form.removeEventListener( 'change', onNativeChange );
			scope.jQuery?.( form ).off( 'change', onJqueryChange );
			scope.jQuery?.( document.body ).off( 'checkout_error', onError );
		},
	};
};
