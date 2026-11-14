/**
 * Shop behaviour (PLP-01, PLP-04, PLP-05, PLP-12): applies filters, sort
 * choices and page links without reloading the page.
 *
 * Everything in the shop area that changes with the filters is a part
 * (`data-um-shop-part`). A change loads the new address, then each part is
 * replaced by the part of the same name (and position) from the answer, so
 * any arrangement works, including lists designed with Elementor. The
 * address bar follows, and Back and Forward work.
 *
 * Without JavaScript the same controls are a GET form and links.
 */
import type { Behavior } from '../../storefront/runtime';

/** Milliseconds to wait for more typing or dragging before loading. */
const DELAY = 450;

/** The storage key of the chosen view. */
const VIEW_KEY = 'um-shop-view';

/**
 * Builds the address of a filter form: several values of one name are
 * joined with commas, empty fields and untouched price bounds are left out.
 *
 * @param action Form action, the list's address without filters.
 * @param fields Field names and values in form order.
 * @param bounds Lowest and highest value of number fields, by name.
 */
export function formUrl( action: string, fields: Array<[ string, string ]>, bounds: Record<string, string> = {} ): string {
	const url = new URL( action, 'http://localhost' );
	const values = new Map<string, string[]>();
	for ( const [ raw, value ] of fields ) {
		const name = raw.replace( /\[\]$/, '' );
		const clean = value.trim();
		if ( clean === '' || bounds[ name ] === clean ) {
			continue;
		}
		values.set( name, [ ...( values.get( name ) ?? [] ), clean ] );
	}
	for ( const [ name, list ] of values ) {
		url.searchParams.set( name, list.join( ',' ) );
	}
	url.searchParams.delete( 'paged' );
	// Commas stay readable, as the server writes them.
	return ( /^https?:/.test( action ) ? url.toString() : url.pathname + url.search ).replace( /%2C/gi, ',' );
}

/**
 * Lower case and Persian letters, for matching typed text to item names.
 *
 * @param text Text.
 */
export function fold( text: string ): string {
	return text.toLowerCase().replace( /[يى]/g, 'ی' ).replace( /ك/g, 'ک' ).replace( /‌/g, ' ' ).trim();
}

/**
 * Swaps every part of the shop area for its new version.
 *
 * @param root     The shop area.
 * @param incoming The shop area of the loaded page.
 */
function swapParts( root: HTMLElement, incoming: ParentNode ): void {
	const seen = new Map<string, number>();
	root.querySelectorAll<HTMLElement>( '[data-um-shop-part]' ).forEach( ( part ) => {
		const name = part.dataset.umShopPart ?? '';
		const index = seen.get( name ) ?? 0;
		seen.set( name, index + 1 );
		const next = incoming.querySelectorAll<HTMLElement>( `[data-um-shop-part="${ CSS.escape( name ) }"]` )[ index ];
		part.innerHTML = next ? next.innerHTML : '';
		window.UltraMaxUI?.mount( part );
	} );
}

export const shop: Behavior = ( root ) => {
	let controller: AbortController | null = null;
	let timer = 0;
	let observer: IntersectionObserver | null = null;
	let shown = window.location.pathname + window.location.search;
	const status = root.querySelector<HTMLElement>( '[data-um-shop-status]' );

	/** Applies the remembered view to every grid with a view switch. */
	const applyView = ( view?: string ): void => {
		let chosen = view;
		if ( ! chosen ) {
			try {
				chosen = window.localStorage.getItem( VIEW_KEY ) ?? '';
			} catch {
				chosen = '';
			}
		}
		if ( chosen !== 'grid' && chosen !== 'list' ) {
			return;
		}
		root.querySelectorAll<HTMLElement>( '[data-um-view]' ).forEach( ( button ) => {
			button.setAttribute( 'aria-pressed', String( button.dataset.umView === chosen ) );
			const grid = document.getElementById( button.getAttribute( 'aria-controls' ) ?? '' );
			grid?.setAttribute( 'data-view', chosen as string );
		} );
	};

	/** Hides the "apply" buttons: changes apply at once. */
	const ready = (): void => {
		root.querySelectorAll<HTMLElement>( '[data-um-shop-apply]' ).forEach( ( button ) => {
			button.hidden = true;
		} );
		applyView();
		watchMore();
	};

	/**
	 * Loads an address and puts its parts in place.
	 *
	 * @param href   Address.
	 * @param push   Whether to add a history entry.
	 * @param append Whether to add the products below the current ones (load more).
	 */
	const load = async ( href: string, push: boolean, append = false ): Promise<void> => {
		controller?.abort();
		controller = new AbortController();
		root.setAttribute( 'aria-busy', 'true' );
		const focused = document.activeElement instanceof HTMLElement && root.contains( document.activeElement ) ? document.activeElement.id : '';
		try {
			const response = await fetch( href, { credentials: 'same-origin', signal: controller.signal, headers: { 'X-Requested-With': 'ultra-max-shop' } } );
			const html = await response.text();
			const doc = new DOMParser().parseFromString( html, 'text/html' );
			const incoming = doc.getElementById( root.id ) ?? doc;
			if ( append ) {
				const list = root.querySelector( '[data-um-shop-part="products"] .um-product-grid' );
				const more = incoming.querySelector( '[data-um-shop-part="products"] .um-product-grid' );
				if ( list && more ) {
					const start = list.children.length;
					list.append( ...Array.from( more.children ) );
					window.UltraMaxUI?.mount( list );
					( list.children[ start ]?.querySelector( 'a[href]' ) as HTMLElement | null )?.focus( { preventScroll: true } );
				}
				root.querySelectorAll<HTMLElement>( '[data-um-shop-part="more"]' ).forEach( ( part, index ) => {
					part.innerHTML = incoming.querySelectorAll<HTMLElement>( '[data-um-shop-part="more"]' )[ index ]?.innerHTML ?? '';
				} );
			} else {
				swapParts( root, incoming );
				if ( push ) {
					window.history.pushState( { umShop: true }, '', href );
				}
				shown = window.location.pathname + window.location.search;
				const title = doc.querySelector( 'title' )?.textContent;
				if ( title ) {
					document.title = title;
				}
				if ( focused ) {
					document.getElementById( focused )?.focus( { preventScroll: true } );
				}
				const top = root.querySelector( '[data-um-shop-part="products"]' )?.getBoundingClientRect().top ?? 0;
				if ( top < 0 ) {
					root.querySelector( '[data-um-shop-part="products"]' )?.scrollIntoView( { block: 'start', behavior: 'smooth' } );
				}
			}
			if ( status ) {
				status.textContent = root.querySelector( '[data-um-shop-count]' )?.textContent ?? '';
			}
			ready();
			root.dispatchEvent( new CustomEvent( 'um:shop-updated', { bubbles: true, detail: { href } } ) );
		} catch ( error ) {
			if ( ( error as Error ).name !== 'AbortError' ) {
				window.location.href = href;
			}
		} finally {
			root.removeAttribute( 'aria-busy' );
		}
	};

	/**
	 * The address a filter form asks for now.
	 *
	 * @param form Form.
	 */
	const addressOf = ( form: HTMLFormElement ): string => {
		const bounds: Record<string, string> = {};
		form.querySelectorAll<HTMLInputElement>( 'input[data-bound]' ).forEach( ( input ) => {
			bounds[ input.name ] = input.dataset.bound ?? '';
		} );
		const fields: Array<[ string, string ]> = [];
		new FormData( form ).forEach( ( value, name ) => fields.push( [ name, String( value ) ] ) );
		return formUrl( form.action, fields, bounds );
	};

	/** Loads the next page by itself when its button scrolls into view (infinite mode). */
	const watchMore = (): void => {
		observer?.disconnect();
		const button = root.querySelector<HTMLAnchorElement>( '[data-um-load-more="infinite"]' );
		if ( ! button || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}
		observer = new IntersectionObserver( ( entries ) => {
			if ( entries.some( ( entry ) => entry.isIntersecting ) && button.getAttribute( 'aria-busy' ) !== 'true' ) {
				button.setAttribute( 'aria-busy', 'true' );
				void load( button.href, false, true );
			}
		}, { rootMargin: '400px 0px' } );
		observer.observe( button );
	};

	const onClick = ( event: MouseEvent ): void => {
		if ( event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey ) {
			return;
		}
		const target = event.target instanceof Element ? event.target : null;
		const view = target?.closest<HTMLElement>( '[data-um-view]' );
		if ( view ) {
			const chosen = view.dataset.umView ?? 'grid';
			try {
				window.localStorage.setItem( VIEW_KEY, chosen );
			} catch {
				// Private windows may refuse storage; the view still changes.
			}
			applyView( chosen );
			return;
		}
		const more = target?.closest<HTMLAnchorElement>( 'a[data-um-load-more]' );
		if ( more ) {
			event.preventDefault();
			more.setAttribute( 'aria-busy', 'true' );
			void load( more.href, false, true );
			return;
		}
		const link = target?.closest<HTMLAnchorElement>( 'a[data-um-shop-link], [data-um-shop-part="more"] .um-load-more nav a' );
		if ( link && link.origin === window.location.origin ) {
			event.preventDefault();
			void load( link.href, true );
		}
	};

	const onChange = ( event: Event ): void => {
		const input = event.target instanceof HTMLInputElement ? event.target : null;
		const form = input?.form;
		if ( ! input || ! form || ! form.hasAttribute( 'data-um-shop-form' ) || input.hasAttribute( 'data-um-filter-search' ) ) {
			return;
		}
		window.clearTimeout( timer );
		const wait = input.type === 'number' || input.type === 'range' ? DELAY : 0;
		timer = window.setTimeout( () => void load( addressOf( form ), true ), wait );
	};

	const onSubmit = ( event: SubmitEvent ): void => {
		const form = event.target instanceof HTMLFormElement ? event.target : null;
		if ( form?.hasAttribute( 'data-um-shop-form' ) ) {
			event.preventDefault();
			void load( addressOf( form ), true );
		}
	};

	const onInput = ( event: Event ): void => {
		const field = event.target instanceof HTMLInputElement && event.target.hasAttribute( 'data-um-filter-search' ) ? event.target : null;
		if ( ! field ) {
			return;
		}
		const query = fold( field.value );
		field.closest( 'details' )?.querySelectorAll<HTMLElement>( '[data-um-filter-item]' ).forEach( ( item ) => {
			item.hidden = query !== '' && ! fold( item.dataset.umFilterItem ?? '' ).includes( query );
		} );
	};

	const onPop = (): void => {
		// A change of the hash alone, such as a skip link, keeps the list.
		if ( window.location.pathname + window.location.search !== shown ) {
			void load( window.location.href, false );
		}
	};

	root.addEventListener( 'click', onClick );
	root.addEventListener( 'change', onChange );
	root.addEventListener( 'submit', onSubmit );
	root.addEventListener( 'input', onInput );
	window.addEventListener( 'popstate', onPop );
	ready();

	return {
		destroy(): void {
			root.removeEventListener( 'click', onClick );
			root.removeEventListener( 'change', onChange );
			root.removeEventListener( 'submit', onSubmit );
			root.removeEventListener( 'input', onInput );
			window.removeEventListener( 'popstate', onPop );
			observer?.disconnect();
			controller?.abort();
		},
	};
};
