/**
 * Tabs behaviour. Flowbite switches the panels; this module adds what the
 * acceptance test found missing: roving tabindex, arrow keys mirrored in
 * RTL, Home and End.
 */
import Tabs from 'flowbite/lib/esm/components/tabs';
import type { Controller } from '../../storefront/runtime';

export interface TabsController extends Controller {
	select( index: number ): void;
	selected(): number;
}

/**
 * Mounts the behaviour.
 *
 * @param element The tabs root.
 */
export function tabs( element: HTMLElement ): TabsController {
	const triggers = Array.from( element.querySelectorAll<HTMLElement>( '[role="tab"]' ) );
	const items = triggers.map( ( triggerEl ) => ( {
		id: triggerEl.id,
		triggerEl,
		targetEl: element.querySelector<HTMLElement>( `#${ triggerEl.getAttribute( 'aria-controls' ) }` ) as HTMLElement,
	} ) );
	const initial = Math.max( 0, triggers.findIndex( ( t ) => t.getAttribute( 'aria-selected' ) === 'true' ) );
	const tablist = element.querySelector<HTMLElement>( '[role="tablist"]' ) as HTMLElement;

	const instance = new Tabs( tablist, items, {
		defaultTabId: items[ initial ]?.id,
		activeClasses: 'um-tab-active',
		inactiveClasses: 'um-tab-inactive',
	}, { id: element.id, override: true } );

	let current = initial;

	const select = ( index: number, focus = false ): void => {
		const target = triggers[ index ];
		if ( ! target ) {
			return;
		}
		instance.show( target.id );
		triggers.forEach( ( t, i ) => t.setAttribute( 'tabindex', i === index ? '0' : '-1' ) );
		current = index;
		if ( focus ) {
			target.focus();
		}
	};

	const onKeydown = ( event: KeyboardEvent ): void => {
		const index = triggers.indexOf( event.target as HTMLElement );
		if ( index < 0 ) {
			return;
		}
		const rtl = getComputedStyle( element ).direction === 'rtl';
		const forward = rtl ? 'ArrowLeft' : 'ArrowRight';
		const back = rtl ? 'ArrowRight' : 'ArrowLeft';
		const last = triggers.length - 1;
		const next = {
			[ forward ]: index === last ? 0 : index + 1,
			[ back ]: index === 0 ? last : index - 1,
			Home: 0,
			End: last,
		}[ event.key ];
		if ( next === undefined ) {
			return;
		}
		event.preventDefault();
		select( next, true );
	};

	const onClick = ( event: MouseEvent ): void => {
		const index = triggers.indexOf( ( event.target as HTMLElement ).closest( '[role="tab"]' ) as HTMLElement );
		if ( index >= 0 ) {
			select( index );
		}
	};

	tablist.addEventListener( 'keydown', onKeydown );
	tablist.addEventListener( 'click', onClick );
	select( initial );

	return {
		select: ( index ) => select( index ),
		selected: () => current,
		destroy() {
			tablist.removeEventListener( 'keydown', onKeydown );
			tablist.removeEventListener( 'click', onClick );
			instance.destroy();
		},
	};
}
