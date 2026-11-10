/**
 * Menu bar behaviour (HDR-06): keeps `aria-expanded` in step with the open
 * panel, opens a panel from the keyboard with Enter or arrow down on an
 * item that has one, and closes it with Escape, returning focus.
 * Hover and focus open panels through CSS alone.
 */
import type { Behavior } from '../../storefront/runtime';

export const navBar: Behavior = ( element ) => {
	const items = Array.from( element.querySelectorAll<HTMLElement>( ':scope > ul > li' ) );

	const set = ( item: HTMLElement, open: boolean ): void => {
		item.toggleAttribute( 'data-open', open );
		item.querySelector( ':scope > a' )?.setAttribute( 'aria-expanded', String( open ) );
	};

	const cleanups = items
		.filter( ( item ) => item.querySelector( ':scope > [data-panel]' ) )
		.map( ( item ) => {
			const link = item.querySelector<HTMLAnchorElement>( ':scope > a' );
			const enter = (): void => set( item, true );
			const leave = (): void => set( item, false );
			const focusOut = ( event: FocusEvent ): void => {
				if ( ! item.contains( event.relatedTarget as Node | null ) ) {
					set( item, false );
				}
			};
			const key = ( event: KeyboardEvent ): void => {
				if ( event.key === 'Escape' && item.hasAttribute( 'data-open' ) ) {
					set( item, false );
					link?.focus();
				} else if ( event.key === 'ArrowDown' && event.target === link ) {
					event.preventDefault();
					set( item, true );
					item.querySelector<HTMLElement>( '[data-panel] a, [data-panel] button' )?.focus();
				}
			};
			item.addEventListener( 'mouseenter', enter );
			item.addEventListener( 'mouseleave', leave );
			item.addEventListener( 'focusin', enter );
			item.addEventListener( 'focusout', focusOut );
			item.addEventListener( 'keydown', key );
			return (): void => {
				item.removeEventListener( 'mouseenter', enter );
				item.removeEventListener( 'mouseleave', leave );
				item.removeEventListener( 'focusin', enter );
				item.removeEventListener( 'focusout', focusOut );
				item.removeEventListener( 'keydown', key );
			};
		} );

	return {
		destroy(): void {
			cleanups.forEach( ( cleanup ) => cleanup() );
		},
	};
};
