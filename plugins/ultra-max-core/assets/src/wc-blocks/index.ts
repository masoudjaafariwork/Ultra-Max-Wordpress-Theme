/**
 * Our additions to WooCommerce's block cart and block checkout (CART-03,
 * CART-04, CART-10, CHK-02, CHK-03, CHK-10).
 *
 * - Under the totals of both blocks: the saving, the free shipping bar and
 *   the order limit. The store draws them (Store API data `ultra-max`), so
 *   they look and read the same as on our own cart; this script only puts
 *   that markup in WooCommerce's order summary slot.
 * - The city fields suggest the cities of the chosen province.
 * - A saved address (`[data-um-address]`) fills the block checkout's
 *   shipping and billing address.
 *
 * It reads WordPress's and WooCommerce's globals only, so nothing of
 * theirs is bundled, and draws no looks of its own.
 */

interface WpElement {
	createElement: ( type: unknown, props?: Record<string, unknown> | null, ...children: unknown[] ) => unknown;
}

interface Store {
	getCustomerData?: () => { shippingAddress?: { state?: string }; billingAddress?: { state?: string } };
}

interface Dispatch {
	setShippingAddress?: ( address: Record<string, string> ) => void;
	setBillingAddress?: ( address: Record<string, string> ) => void;
	updateCustomerData?: ( data: Record<string, unknown>, editing?: boolean ) => unknown;
}

/** WordPress's and WooCommerce's globals, read without declaring them for every script. */
const scope = window as unknown as {
	wp?: {
		element?: WpElement;
		plugins?: { registerPlugin: ( name: string, settings: Record<string, unknown> ) => void };
		data?: { select: ( store: string ) => Store; dispatch: ( store: string ) => Dispatch; subscribe: ( listener: () => void ) => () => void };
	};
	wc?: { blocksCheckout?: { ExperimentalOrderMeta?: unknown } };
	ultraMaxBlocks?: { cities?: Record<string, string[]> };
};

const CART = 'wc/store/cart';

/**
 * The summary rows the store drew for the cart, in WooCommerce's slot.
 */
function registerSummary(): void {
	const element = scope.wp?.element;
	const plugins = scope.wp?.plugins;
	const slot = scope.wc?.blocksCheckout?.ExperimentalOrderMeta;
	if ( ! element || ! plugins || ! slot ) {
		return;
	}
	const extras = ( props: { extensions?: Record<string, { html?: string }> } ) => {
		const html = props.extensions?.[ 'ultra-max' ]?.html ?? '';
		return html === '' ? null : element.createElement( 'div', { className: 'um-block-extras', dangerouslySetInnerHTML: { __html: html } } );
	};
	plugins.registerPlugin( 'ultra-max-summary', {
		scope: 'woocommerce-checkout',
		render: () => element.createElement( slot, null, element.createElement( extras ) ),
	} );
}

/**
 * Suggests the cities of the chosen province in the city fields.
 */
function cityLists(): void {
	const cities = scope.ultraMaxBlocks?.cities;
	const data = scope.wp?.data;
	if ( ! cities || ! data ) {
		return;
	}
	const fill = (): void => {
		const customer = data.select( CART ).getCustomerData?.();
		( [ 'shipping', 'billing' ] as const ).forEach( ( type ) => {
			const input = document.getElementById( `${ type }-city` );
			if ( ! ( input instanceof HTMLInputElement ) ) {
				return;
			}
			const state = ( type === 'shipping' ? customer?.shippingAddress?.state : customer?.billingAddress?.state ) ?? '';
			const id = `um-cities-${ type }`;
			let list = document.getElementById( id );
			if ( ! list ) {
				list = document.createElement( 'datalist' );
				list.id = id;
				document.body.append( list );
			}
			if ( list.dataset.state !== state ) {
				list.dataset.state = state;
				list.replaceChildren(
					...( cities[ state ] ?? [] ).map( ( city ) => {
						const option = document.createElement( 'option' );
						option.value = city;
						return option;
					} )
				);
			}
			if ( input.getAttribute( 'list' ) !== id ) {
				input.setAttribute( 'list', id );
			}
		} );
	};
	data.subscribe( fill );
	new MutationObserver( fill ).observe( document.body, { childList: true, subtree: true } );
	fill();
}

/**
 * A saved address fills the checkout's addresses.
 */
function addressBook(): void {
	document.addEventListener( 'click', ( event ) => {
		const card = event.target instanceof Element ? event.target.closest<HTMLElement>( '[data-um-address]' ) : null;
		const dispatch = scope.wp?.data?.dispatch( CART );
		if ( ! card || ! dispatch?.setShippingAddress || ! card.closest( '[data-um-blocks-addresses]' ) ) {
			return;
		}
		event.preventDefault();
		const address = JSON.parse( card.dataset.umAddress ?? '{}' ) as Record<string, string>;
		dispatch.setShippingAddress( address );
		dispatch.setBillingAddress?.( address );
		card.closest( '[data-um-blocks-addresses]' )?.querySelectorAll( '[data-um-address]' ).forEach( ( other ) => other.setAttribute( 'aria-pressed', String( other === card ) ) );
	} );
}

registerSummary();
cityLists();
addressBook();

export {};
