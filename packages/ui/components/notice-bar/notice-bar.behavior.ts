/**
 * NoticeBar behaviour: hides the bar outside its dates and remembers when
 * the visitor closed this version of it.
 */
import type { Controller } from '../../storefront/runtime';

const STORAGE_KEY = 'um-notice-bar-closed';

/**
 * Reads the closed versions from local storage; private modes may block it.
 */
function closedVersions(): string[] {
	try {
		const value: unknown = JSON.parse( window.localStorage.getItem( STORAGE_KEY ) ?? '[]' );
		return Array.isArray( value ) ? value.filter( ( item ): item is string => typeof item === 'string' ) : [];
	} catch {
		return [];
	}
}

/**
 * Whether now is inside the bar's dates.
 *
 * @param element Bar element.
 * @param now     Current Unix time in seconds.
 */
export function isScheduled( element: HTMLElement, now: number = Date.now() / 1000 ): boolean {
	const start = Number( element.dataset.start ?? '' );
	const end = Number( element.dataset.end ?? '' );
	if ( element.dataset.start && now < start ) {
		return false;
	}
	return ! ( element.dataset.end && now > end );
}

/**
 * Mounts the behaviour.
 *
 * @param element The bar element.
 */
export function noticeBar( element: HTMLElement ): Controller {
	const version = element.id + ':' + ( element.dataset.version ?? '' );
	if ( ! isScheduled( element ) || ( element.dataset.dismissible && closedVersions().includes( version ) ) ) {
		element.hidden = true;
	}
	return {
		close() {
			element.hidden = true;
			try {
				const versions = closedVersions().filter( ( item ) => ! item.startsWith( element.id + ':' ) );
				window.localStorage.setItem( STORAGE_KEY, JSON.stringify( [ ...versions, version ] ) );
			} catch {
				// Storage is blocked: the bar stays closed until the next page.
			}
			element.dispatchEvent( new CustomEvent( 'um:close', { bubbles: true } ) );
		},
	};
}
