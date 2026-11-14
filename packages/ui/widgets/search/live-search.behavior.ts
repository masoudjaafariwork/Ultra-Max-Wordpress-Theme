/**
 * Live search (SRCH-01, SRCH-04): suggestions under the search field while
 * the visitor types, a list box that arrow keys move through, and in the
 * phone search window the visitor's recent searches, kept in this
 * browser only.
 *
 * The server renders the suggestions; this module only asks for them,
 * shows them and handles the keyboard.
 */
import type { Behavior } from '../../storefront/runtime';

/** Milliseconds to wait for more typing before asking. */
const DELAY = 220;

/** Storage key and length of the recent searches. */
const RECENT_KEY = 'um-recent-searches';
const RECENT_MAX = 8;

/**
 * Adds a search to the recent list: newest first, no repeats, a short list.
 *
 * @param list  Earlier searches.
 * @param query New search.
 */
export function remember( list: string[], query: string ): string[] {
	const clean = query.trim().replace( /\s+/g, ' ' );
	if ( clean === '' ) {
		return list;
	}
	return [ clean, ...list.filter( ( item ) => item.toLowerCase() !== clean.toLowerCase() ) ].slice( 0, RECENT_MAX );
}

/** Reads the recent searches. */
function readRecent(): string[] {
	try {
		const value: unknown = JSON.parse( window.localStorage.getItem( RECENT_KEY ) ?? '[]' );
		return Array.isArray( value ) ? value.filter( ( item ): item is string => typeof item === 'string' ) : [];
	} catch {
		return [];
	}
}

/**
 * Stores the recent searches.
 *
 * @param list Searches.
 */
function writeRecent( list: string[] ): void {
	try {
		window.localStorage.setItem( RECENT_KEY, JSON.stringify( list ) );
	} catch {
		// Private windows may refuse storage.
	}
}

export const liveSearch: Behavior = ( form ) => {
	const input = form.querySelector<HTMLInputElement>( 'input[type="search"]' );
	const panel = form.querySelector<HTMLElement>( '[data-um-search-results]' );
	if ( ! input || ! panel ) {
		return {};
	}
	const endpoint = form.dataset.endpoint ?? '';
	const min = Number( form.dataset.min ?? '2' );
	const sheet = form.closest( 'dialog' )?.querySelector<HTMLElement>( '[data-um-search-sheet]' ) ?? null;
	let timer = 0;
	let controller: AbortController | null = null;
	let asked = '';

	const options = (): HTMLElement[] => Array.from( panel.querySelectorAll<HTMLElement>( '[role="option"]' ) );

	const close = (): void => {
		panel.hidden = true;
		input.setAttribute( 'aria-expanded', 'false' );
		input.removeAttribute( 'aria-activedescendant' );
		if ( sheet ) {
			sheet.hidden = false;
		}
	};

	const open = (): void => {
		panel.hidden = false;
		input.setAttribute( 'aria-expanded', 'true' );
		if ( sheet ) {
			sheet.hidden = true;
		}
	};

	const select = ( option: HTMLElement | undefined ): void => {
		options().forEach( ( item ) => item.setAttribute( 'aria-selected', String( item === option ) ) );
		if ( option ) {
			input.setAttribute( 'aria-activedescendant', option.id );
			option.scrollIntoView( { block: 'nearest' } );
		} else {
			input.removeAttribute( 'aria-activedescendant' );
		}
	};

	const ask = async ( query: string ): Promise<void> => {
		controller?.abort();
		controller = new AbortController();
		asked = query;
		try {
			const url = new URL( endpoint, window.location.href );
			url.searchParams.set( 'q', query );
			const response = await fetch( url.toString(), { signal: controller.signal, credentials: 'same-origin' } );
			const data = ( await response.json() ) as { html?: string };
			if ( input.value.trim() !== query ) {
				return;
			}
			panel.innerHTML = data.html ?? '';
			open();
		} catch ( error ) {
			if ( ( error as Error ).name !== 'AbortError' ) {
				close();
			}
		}
	};

	const onInput = (): void => {
		window.clearTimeout( timer );
		const query = input.value.trim();
		if ( query.length < min ) {
			controller?.abort();
			asked = '';
			close();
			return;
		}
		if ( query === asked && panel.innerHTML !== '' ) {
			open();
			return;
		}
		timer = window.setTimeout( () => void ask( query ), DELAY );
	};

	const onKey = ( event: KeyboardEvent ): void => {
		const list = options();
		const current = list.findIndex( ( item ) => item.getAttribute( 'aria-selected' ) === 'true' );
		if ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) {
			if ( panel.hidden || list.length === 0 ) {
				return;
			}
			event.preventDefault();
			const step = event.key === 'ArrowDown' ? 1 : -1;
			select( list[ ( current + step + list.length ) % list.length ] ?? list[ 0 ] );
		} else if ( event.key === 'Enter' && current >= 0 && ! panel.hidden ) {
			event.preventDefault();
			writeRecent( remember( readRecent(), input.value ) );
			window.location.href = ( list[ current ] as HTMLAnchorElement ).href;
		} else if ( event.key === 'Escape' && ! panel.hidden ) {
			event.preventDefault();
			event.stopPropagation();
			close();
		}
	};

	const onFocusOut = ( event: FocusEvent ): void => {
		if ( ! ( event.relatedTarget instanceof Node ) || ! form.contains( event.relatedTarget ) ) {
			window.setTimeout( () => {
				if ( ! form.contains( document.activeElement ) && ! sheet ) {
					close();
				}
			}, 120 );
		}
	};

	const onSubmit = (): void => {
		writeRecent( remember( readRecent(), input.value ) );
	};

	const onPanelClick = (): void => {
		writeRecent( remember( readRecent(), input.value ) );
	};

	/** Fills the recent searches of the phone window. */
	const paintRecent = (): void => {
		const box = sheet?.querySelector<HTMLElement>( '[data-um-recent]' );
		const list = box?.querySelector<HTMLElement>( '[data-um-recent-list]' );
		const template = box?.querySelector<HTMLTemplateElement>( 'template[data-um-recent-item]' );
		if ( ! box || ! list || ! template || ! sheet ) {
			return;
		}
		const recent = readRecent();
		list.replaceChildren(
			...recent.map( ( query ) => {
				const item = template.content.firstElementChild?.cloneNode( true ) as HTMLElement;
				const link = item.querySelector( 'a' );
				if ( link ) {
					link.href = ( sheet.dataset.searchUrl ?? '?s=' ) + encodeURIComponent( query );
					const label = link.querySelector( 'span:last-child' );
					if ( label ) {
						label.textContent = query;
					}
				}
				return item;
			} )
		);
		box.hidden = recent.length === 0;
	};

	const onSheetClick = ( event: MouseEvent ): void => {
		if ( event.target instanceof Element && event.target.closest( '[data-um-recent-clear]' ) ) {
			writeRecent( [] );
			paintRecent();
		}
	};

	input.addEventListener( 'input', onInput );
	input.addEventListener( 'keydown', onKey );
	form.addEventListener( 'focusout', onFocusOut );
	form.addEventListener( 'submit', onSubmit );
	panel.addEventListener( 'click', onPanelClick );
	sheet?.addEventListener( 'click', onSheetClick );
	paintRecent();

	return {
		destroy(): void {
			input.removeEventListener( 'input', onInput );
			input.removeEventListener( 'keydown', onKey );
			form.removeEventListener( 'focusout', onFocusOut );
			form.removeEventListener( 'submit', onSubmit );
			panel.removeEventListener( 'click', onPanelClick );
			sheet?.removeEventListener( 'click', onSheetClick );
			controller?.abort();
		},
	};
};
