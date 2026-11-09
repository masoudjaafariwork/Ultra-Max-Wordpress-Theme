/**
 * ContactButtons behaviour: the round button shows and hides the list.
 * Escape and a click outside close it; focus returns to the button.
 */
import type { Controller } from '../../storefront/runtime';

/**
 * Mounts the behaviour.
 *
 * @param element The component root.
 */
export function contactButtons( element: HTMLElement ): Controller {
	const toggle = element.querySelector<HTMLButtonElement>( 'button[aria-controls]' );
	const list = toggle ? document.getElementById( toggle.getAttribute( 'aria-controls' ) ?? '' ) : null;
	if ( ! toggle || ! list ) {
		return {};
	}

	const set = ( open: boolean ): void => {
		list.hidden = ! open;
		toggle.setAttribute( 'aria-expanded', String( open ) );
		element.dispatchEvent( new CustomEvent( open ? 'um:open' : 'um:close', { bubbles: true } ) );
	};
	const onToggle = (): void => set( Boolean( list.hidden ) );
	const onKey = ( event: KeyboardEvent ): void => {
		if ( event.key === 'Escape' && ! list.hidden ) {
			set( false );
			toggle.focus();
		}
	};
	const onOutside = ( event: MouseEvent ): void => {
		if ( ! list.hidden && event.target instanceof Node && ! element.contains( event.target ) ) {
			set( false );
		}
	};

	toggle.addEventListener( 'click', onToggle );
	element.addEventListener( 'keydown', onKey );
	document.addEventListener( 'click', onOutside );

	return {
		open: () => set( true ),
		close: () => set( false ),
		destroy() {
			toggle.removeEventListener( 'click', onToggle );
			element.removeEventListener( 'keydown', onKey );
			document.removeEventListener( 'click', onOutside );
		},
	};
}
