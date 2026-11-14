/**
 * ReviewList behaviour (PDP-08, PDP-09).
 *
 * - Filter chips, star bars and sort links load the list in place from the
 *   endpoint; the address keeps the choice, so the back button works.
 * - "Show more" adds the next reviews under the others.
 * - "Was this helpful?" sends one vote per review and remembers it in the
 *   browser, so the buttons show the shopper's own vote.
 */
import type { Behavior } from '../../storefront/runtime';

const VOTES = 'um-review-votes';

interface ListReply {
	list?: string;
	items?: string;
	more?: string;
}

/**
 * Votes this browser has sent, by review id.
 */
export function readVotes(): Record<string, 'up' | 'down'> {
	try {
		const stored = JSON.parse( window.localStorage.getItem( VOTES ) ?? '{}' ) as unknown;
		return stored && typeof stored === 'object' ? ( stored as Record<string, 'up' | 'down'> ) : {};
	} catch {
		return {};
	}
}

/**
 * The endpoint address for a link of the list: the link's review choices on
 * the endpoint.
 *
 * @param endpoint Endpoint.
 * @param href     Link of the page.
 * @param product  Product id.
 */
export function listUrl( endpoint: string, href: string, product: string ): string {
	const from = new URL( href, window.location.href );
	const url = new URL( endpoint, window.location.href );
	url.searchParams.set( 'product', product );
	[ 'review_sort', 'review_filter', 'review_page' ].forEach( ( key ) => {
		const value = from.searchParams.get( key );
		if ( value ) {
			url.searchParams.set( key, value );
		}
	} );
	return url.toString();
}

export const reviews: Behavior = ( element ) => {
	const endpoint = element.dataset.endpoint ?? '';
	const vote = element.dataset.vote ?? '';
	const product = element.dataset.product ?? '';

	const markVotes = ( root: ParentNode ): void => {
		const votes = readVotes();
		root.querySelectorAll<HTMLElement>( '[data-review]' ).forEach( ( review ) => {
			const mine = votes[ review.dataset.review ?? '' ];
			if ( ! mine ) {
				return;
			}
			review.querySelectorAll<HTMLButtonElement>( '[data-um-vote]' ).forEach( ( button ) => {
				button.setAttribute( 'aria-pressed', String( button.dataset.umVote === mine ) );
				button.disabled = true;
			} );
		} );
	};

	const load = async ( href: string, append: boolean ): Promise<void> => {
		element.setAttribute( 'aria-busy', 'true' );
		try {
			const response = await fetch( listUrl( endpoint, href, product ), { credentials: 'same-origin' } );
			const reply = ( await response.json() ) as ListReply;
			if ( append ) {
				const items = element.querySelector( '[data-um-reviews-items]' );
				items?.insertAdjacentHTML( 'beforeend', reply.items ?? '' );
				const more = element.querySelector( '[data-um-reviews-more]' );
				if ( more ) {
					more.outerHTML = reply.more ?? '';
				}
				markVotes( element );
				return;
			}
			const holder = document.createElement( 'div' );
			holder.innerHTML = reply.list ?? '';
			const next = holder.firstElementChild;
			if ( next instanceof HTMLElement ) {
				element.replaceChildren( ...Array.from( next.childNodes ) );
				markVotes( element );
				window.history.replaceState( null, '', new URL( href, window.location.href ).toString() );
			}
		} catch {
			window.location.href = href;
		} finally {
			element.removeAttribute( 'aria-busy' );
		}
	};

	const sendVote = async ( button: HTMLButtonElement ): Promise<void> => {
		const review = button.closest<HTMLElement>( '[data-review]' );
		const id = review?.dataset.review ?? '';
		const value = button.dataset.umVote === 'down' ? 'down' : 'up';
		if ( ! review || id === '' || vote === '' ) {
			return;
		}
		review.querySelectorAll<HTMLButtonElement>( '[data-um-vote]' ).forEach( ( other ) => ( other.disabled = true ) );
		try {
			const response = await fetch( vote.replace( '%d', id ), {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( { value } ),
			} );
			const reply = ( await response.json() ) as { up?: string; down?: string };
			review.querySelectorAll<HTMLButtonElement>( '[data-um-vote]' ).forEach( ( other ) => {
				const count = other.querySelector( '[data-um-vote-count]' );
				const text = other.dataset.umVote === 'down' ? reply.down : reply.up;
				if ( count && typeof text === 'string' ) {
					count.textContent = text;
				}
				other.setAttribute( 'aria-pressed', String( other === button ) );
			} );
			const votes = readVotes();
			votes[ id ] = value;
			window.localStorage.setItem( VOTES, JSON.stringify( votes ) );
		} catch {
			review.querySelectorAll<HTMLButtonElement>( '[data-um-vote]' ).forEach( ( other ) => ( other.disabled = false ) );
		}
	};

	const onClick = ( event: MouseEvent ): void => {
		const target = event.target instanceof Element ? event.target : null;
		const button = target?.closest<HTMLButtonElement>( '[data-um-vote]' );
		if ( button ) {
			void sendVote( button );
			return;
		}
		const link = target?.closest<HTMLAnchorElement>( '[data-um-reviews-link], [data-um-reviews-more]' );
		if ( ! link || endpoint === '' || event.ctrlKey || event.metaKey ) {
			return;
		}
		event.preventDefault();
		void load( link.href, link.hasAttribute( 'data-um-reviews-more' ) );
	};

	// The summary's star bars sit outside the list; they filter it too.
	const onDocumentClick = ( event: MouseEvent ): void => {
		const link = event.target instanceof Element ? event.target.closest<HTMLAnchorElement>( '.um-review-summary [data-um-reviews-link]' ) : null;
		if ( link && endpoint !== '' ) {
			event.preventDefault();
			void load( link.href, false );
			element.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}
	};

	element.addEventListener( 'click', onClick );
	document.addEventListener( 'click', onDocumentClick );
	markVotes( element );
	return {
		destroy(): void {
			element.removeEventListener( 'click', onClick );
			document.removeEventListener( 'click', onDocumentClick );
		},
	};
};
