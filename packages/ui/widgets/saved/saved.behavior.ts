/**
 * Saved lists behaviour (SAVE-01, SAVE-02): marks the products of the
 * shopper's wishlist and compare list, fills the header and bottom bar
 * counters, and saves a product when its button is pressed.
 *
 * Pages are the same for every visitor, so nothing about the shopper is in
 * the markup. A guest's lists are read from their cookies; a logged in
 * shopper's are asked from the store once per page, together with the
 * security token for changes.
 */

/** The lists, newest first. */
export interface Lists {
	wishlist: number[];
	compare: number[];
}

/** A button or link in the toast. */
export interface ToastAction {
	label: string;
	href?: string;
	onClick?: () => void;
}

/** What the store answers. */
export interface Reply {
	success?: boolean;
	lists?: Partial<Lists>;
	nonce?: string;
	message?: string;
	link?: { label: string; href: string };
	conflict?: { label: string };
}

export const COOKIES: Record<keyof Lists, string> = { wishlist: 'um_wishlist', compare: 'um_compare' };

/**
 * Ids in a cookie value: numbers joined with dots.
 *
 * @param value Cookie value.
 */
export function parseIds( value: string ): number[] {
	const ids: number[] = [];
	value.split( '.' ).forEach( ( part ) => {
		const id = Number( part );
		if ( Number.isInteger( id ) && id > 0 && ! ids.includes( id ) ) {
			ids.push( id );
		}
	} );
	return ids;
}

/**
 * One cookie's value.
 *
 * @param name   Cookie name.
 * @param source Cookie string; the document's by default.
 */
export function readCookie( name: string, source: string = document.cookie ): string {
	for ( const part of source.split( ';' ) ) {
		const [ key, ...rest ] = part.trim().split( '=' );
		if ( key === name ) {
			try {
				return decodeURIComponent( rest.join( '=' ) );
			} catch {
				return '';
			}
		}
	}
	return '';
}

/**
 * A count in the page's language, or nothing for zero (the badge hides).
 *
 * @param count Number.
 * @param lang  Language tag.
 */
export function formatCount( count: number, lang: string = document.documentElement.lang ): string {
	if ( count <= 0 ) {
		return '';
	}
	try {
		return count.toLocaleString( lang || undefined );
	} catch {
		return String( count );
	}
}

/**
 * Marks buttons and fills counters.
 *
 * @param lists The lists.
 * @param root  Where to look.
 */
export function apply( lists: Lists, root: ParentNode = document ): void {
	root.querySelectorAll<HTMLElement>( '[data-um-save]' ).forEach( ( button ) => {
		const list = button.dataset.umSave as keyof Lists;
		const id = Number( button.dataset.id ?? '0' );
		button.setAttribute( 'aria-pressed', String( ( lists[ list ] ?? [] ).includes( id ) ) );
	} );
	( Object.keys( COOKIES ) as Array<keyof Lists> ).forEach( ( list ) => {
		const text = formatCount( lists[ list ].length );
		document.querySelectorAll<HTMLElement>( `[data-um-count="${ list }"]` ).forEach( ( badge ) => {
			// Only real changes: writing the same text would wake the page observer again.
			if ( badge.textContent !== text ) {
				badge.textContent = text;
			}
		} );
	} );
}

let toastTimer = 0;

/**
 * Hides the toast.
 */
export function hideToast(): void {
	const toast = document.getElementById( 'um-toast' );
	if ( toast ) {
		toast.hidden = true;
	}
	window.clearTimeout( toastTimer );
}

/**
 * Shows a message at the bottom of the screen for a few seconds.
 *
 * @param text    Message.
 * @param actions Links or buttons beside it.
 */
export function showToast( text: string, actions: ToastAction[] = [] ): void {
	const toast = document.getElementById( 'um-toast' );
	const target = toast?.querySelector<HTMLElement>( '[data-um-toast-text]' );
	const place = toast?.querySelector<HTMLElement>( '[data-um-toast-actions]' );
	if ( ! toast || ! target || ! place || text === '' ) {
		return;
	}
	target.textContent = text;
	place.replaceChildren(
		...actions.map( ( action ) => {
			const node = action.href ? document.createElement( 'a' ) : document.createElement( 'button' );
			node.className = toast.dataset.actionClass ?? '';
			node.textContent = action.label;
			if ( node instanceof HTMLAnchorElement && action.href ) {
				node.href = action.href;
			} else if ( node instanceof HTMLButtonElement ) {
				node.type = 'button';
				node.addEventListener( 'click', () => {
					hideToast();
					action.onClick?.();
				} );
			}
			return node;
		} )
	);
	toast.hidden = false;
	window.clearTimeout( toastTimer );
	const wait = (): void => {
		toastTimer = window.setTimeout( () => {
			if ( toast.matches( ':hover, :focus-within' ) ) {
				wait();
				return;
			}
			hideToast();
		}, 5000 );
	};
	wait();
}

/**
 * The address of the store's lists endpoint.
 */
export function endpoint(): string {
	const url = new URL( window.location.href );
	url.search = '';
	url.hash = '';
	url.searchParams.set( 'wc-ajax', 'um_lists' );
	return url.toString();
}

/**
 * Starts the saved lists on the page.
 */
export function startSaved(): void {
	let lists: Lists = { wishlist: [], compare: [] };
	let nonce = '';
	const on = ( document.getElementById( 'um-toast' )?.dataset.lists ?? '' ).split( ',' ).filter( Boolean );
	if ( on.length === 0 ) {
		// Only the quick view is on: the toast is all this script is needed for.
		document.addEventListener( 'click', ( event ) => {
			if ( event.target instanceof Element && event.target.closest( '[data-um-toast-close]' ) ) {
				hideToast();
			}
		} );
		return;
	}
	const loggedIn = document.body.classList.contains( 'logged-in' );

	const take = ( reply: Reply ): void => {
		if ( reply.lists ) {
			lists = { wishlist: reply.lists.wishlist ?? lists.wishlist, compare: reply.lists.compare ?? lists.compare };
		}
		if ( reply.nonce ) {
			nonce = reply.nonce;
		}
		apply( lists );
	};

	const fromCookies = (): void => {
		take( { lists: { wishlist: parseIds( readCookie( COOKIES.wishlist ) ), compare: parseIds( readCookie( COOKIES.compare ) ) } } );
	};

	const loading: Promise<void> = loggedIn
		? fetch( endpoint(), { credentials: 'same-origin', headers: { Accept: 'application/json' } } )
			.then( ( response ) => ( response.ok ? ( response.json() as Promise<Reply> ) : {} ) )
			.then( take )
			.catch( fromCookies )
		: Promise.resolve( fromCookies() );

	const send = async ( fields: Record<string, string>, button: HTMLElement | null ): Promise<Reply> => {
		await loading;
		const body = new URLSearchParams( fields );
		if ( nonce !== '' ) {
			body.set( 'nonce', nonce );
		}
		button?.setAttribute( 'aria-busy', 'true' );
		try {
			const response = await fetch( endpoint(), { method: 'POST', credentials: 'same-origin', body } );
			const reply = ( await response.json() ) as Reply;
			take( reply );
			return reply;
		} catch {
			return {};
		} finally {
			button?.removeAttribute( 'aria-busy' );
		}
	};

	const press = async ( button: HTMLElement, replace = false ): Promise<void> => {
		const list = button.dataset.umSave ?? '';
		const id = button.dataset.id ?? '';
		const removing = button.getAttribute( 'aria-pressed' ) === 'true';
		const fields: Record<string, string> = { op: removing ? 'remove' : 'add', list, id };
		if ( replace ) {
			fields.replace = '1';
		}
		const reply = await send( fields, button );
		const actions: ToastAction[] = [];
		if ( reply.link ) {
			actions.push( reply.link );
		}
		if ( reply.conflict ) {
			actions.push( { label: reply.conflict.label, onClick: () => void press( button, true ) } );
		}
		showToast( reply.message ?? '', actions );
		if ( ! reply.success ) {
			return;
		}
		document.dispatchEvent( new CustomEvent( 'um:saved', { detail: { list, id: Number( id ), saved: ! removing } } ) );
		// The wishlist page drops a removed card; the compare table is drawn again.
		if ( removing && button.closest( '[data-um-component="compare"]' ) ) {
			window.location.reload();
			return;
		}
		const page = button.closest<HTMLElement>( '[data-um-saved-list]' );
		if ( removing && page ) {
			const card = button.closest( '.um-product-card' );
			( card?.parentElement?.closest( 'li' ) ?? card )?.remove();
			if ( ! page.querySelector( '.um-product-card' ) ) {
				window.location.reload();
			}
		}
	};

	document.addEventListener( 'click', ( event ) => {
		const target = event.target instanceof Element ? event.target : null;
		if ( target?.closest( '[data-um-toast-close]' ) ) {
			hideToast();
			return;
		}
		const button = target?.closest<HTMLElement>( '[data-um-save]' );
		if ( ! button ) {
			return;
		}
		event.preventDefault();
		if ( button.getAttribute( 'aria-busy' ) !== 'true' ) {
			void press( button );
		}
	} );

	// Cards drawn later, such as "show more" in a list or the quick view, get their state too.
	let queued = false;
	new MutationObserver( () => {
		if ( queued ) {
			return;
		}
		queued = true;
		window.requestAnimationFrame( () => {
			queued = false;
			apply( lists );
		} );
	} ).observe( document.body, { childList: true, subtree: true } );
}
