/**
 * Filter chips (HOME-09): pressing a chip shows only the items of the
 * target list whose `data-keys` contain its key; the empty key shows all.
 * Announces the number of items shown to screen readers.
 */
import type { Behavior } from '../../storefront/runtime';

/**
 * Whether an item matches a key.
 *
 * @param keys Space-padded keys of the item.
 * @param key  Chip key; empty matches all.
 */
export function matches( keys: string | undefined, key: string ): boolean {
	return key === '' || ( keys ?? '' ).includes( ` ${ key } ` );
}

export const filterChips: Behavior = ( element ) => {
	const target = document.getElementById( element.getAttribute( 'aria-controls' ) ?? '' );
	if ( ! target ) {
		return {};
	}
	element.setAttribute( 'data-ready', '' );
	const status = document.createElement( 'span' );
	status.className = 'sr-only';
	status.setAttribute( 'role', 'status' );
	element.after( status );

	const apply = ( key: string ): void => {
		let shown = 0;
		target.querySelectorAll<HTMLElement>( '[data-keys]' ).forEach( ( item ) => {
			const visible = matches( item.dataset.keys, key );
			item.hidden = ! visible;
			shown += visible ? 1 : 0;
		} );
		status.textContent = String( shown );
	};

	const onClick = ( event: MouseEvent ): void => {
		const chip = event.target instanceof Element ? event.target.closest<HTMLButtonElement>( 'button[data-key]' ) : null;
		if ( ! chip ) {
			return;
		}
		element.querySelectorAll( 'button[data-key]' ).forEach( ( other ) => other.setAttribute( 'aria-pressed', String( other === chip ) ) );
		apply( chip.dataset.key ?? '' );
	};
	element.addEventListener( 'click', onClick );
	return {
		destroy(): void {
			element.removeEventListener( 'click', onClick );
		},
	};
};
