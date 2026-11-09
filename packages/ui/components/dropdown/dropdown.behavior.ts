/**
 * Dropdown behaviour. Flowbite positions the list; this module adds what
 * the acceptance test found missing: aria-expanded, Escape with focus
 * return, and placement on the start side in both writing directions.
 */
import Dropdown from 'flowbite/lib/esm/components/dropdown';
import type { Controller } from '../../storefront/runtime';

export interface DropdownController extends Controller {
	open(): void;
	close(): void;
	isOpen(): boolean;
}

/**
 * Mounts the behaviour.
 *
 * @param element The dropdown root.
 */
export function dropdown( element: HTMLElement ): DropdownController {
	const trigger = element.querySelector<HTMLElement>( '[aria-controls]' ) as HTMLElement;
	const menu = element.querySelector<HTMLElement>( `#${ trigger.getAttribute( 'aria-controls' ) }` ) as HTMLElement;
	const rtl = getComputedStyle( element ).direction === 'rtl';

	const instance = new Dropdown( menu, trigger, {
		// Popper placements are physical: the start side is the right edge in RTL.
		placement: rtl ? 'bottom-end' : 'bottom-start',
		offsetDistance: 6,
		onShow: () => {
			trigger.setAttribute( 'aria-expanded', 'true' );
			element.dispatchEvent( new CustomEvent( 'um:open', { bubbles: true } ) );
		},
		onHide: () => {
			trigger.setAttribute( 'aria-expanded', 'false' );
			element.dispatchEvent( new CustomEvent( 'um:close', { bubbles: true } ) );
		},
	}, { id: element.id, override: true } );

	const onKeydown = ( event: KeyboardEvent ): void => {
		if ( event.key === 'Escape' && instance.isVisible() ) {
			event.preventDefault();
			instance.hide();
			trigger.focus();
		}
	};
	element.addEventListener( 'keydown', onKeydown );

	return {
		open: () => instance.show(),
		close: () => instance.hide(),
		isOpen: () => instance.isVisible(),
		destroy() {
			element.removeEventListener( 'keydown', onKeydown );
			instance.destroy();
		},
	};
}