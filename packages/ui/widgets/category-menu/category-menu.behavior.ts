/**
 * Categories menu (HDR-07): pointing at or focusing a main category shows
 * its pane; the button toggles the panel for touch and keyboard users;
 * Escape closes it and returns focus to the button.
 */
import type { Behavior } from '../../storefront/runtime';

export const categoryMenu: Behavior = ( element ) => {
	const button = element.querySelector<HTMLButtonElement>( ':scope > button' );
	const links = Array.from( element.querySelectorAll<HTMLAnchorElement>( '[data-pane]' ) );
	let timer = 0;

	const show = ( link: HTMLAnchorElement ): void => {
		links.forEach( ( other ) => {
			const active = other === link;
			other.setAttribute( 'aria-current', String( active ) );
			const pane = document.getElementById( other.dataset.pane ?? '' );
			if ( pane ) {
				pane.hidden = ! active;
			}
		} );
	};

	const set = ( open: boolean ): void => {
		element.toggleAttribute( 'data-open', open );
		button?.setAttribute( 'aria-expanded', String( open ) );
	};

	const onOver = ( event: Event ): void => {
		const link = event.target instanceof Element ? event.target.closest<HTMLAnchorElement>( '[data-pane]' ) : null;
		if ( link ) {
			window.clearTimeout( timer );
			// A short delay keeps the pane when the pointer crosses other categories on its way.
			timer = window.setTimeout( () => show( link ), event.type === 'focusin' ? 0 : 70 );
		}
	};
	const onClick = (): void => set( ! element.hasAttribute( 'data-open' ) );
	const onEnter = (): void => set( true );
	const onLeave = (): void => set( false );
	const onKey = ( event: KeyboardEvent ): void => {
		if ( event.key === 'Escape' ) {
			set( false );
			button?.focus();
		}
	};
	const onFocusOut = ( event: FocusEvent ): void => {
		if ( ! element.contains( event.relatedTarget as Node | null ) ) {
			set( false );
		}
	};

	element.addEventListener( 'mouseover', onOver );
	element.addEventListener( 'focusin', onOver );
	element.addEventListener( 'mouseenter', onEnter );
	element.addEventListener( 'mouseleave', onLeave );
	element.addEventListener( 'keydown', onKey );
	element.addEventListener( 'focusout', onFocusOut );
	button?.addEventListener( 'click', onClick );

	return {
		open: () => set( true ),
		close: () => set( false ),
		destroy(): void {
			window.clearTimeout( timer );
			element.removeEventListener( 'mouseover', onOver );
			element.removeEventListener( 'focusin', onOver );
			element.removeEventListener( 'mouseenter', onEnter );
			element.removeEventListener( 'mouseleave', onLeave );
			element.removeEventListener( 'keydown', onKey );
			element.removeEventListener( 'focusout', onFocusOut );
			button?.removeEventListener( 'click', onClick );
		},
	};
};
