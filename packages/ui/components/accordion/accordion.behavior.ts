/**
 * Accordion behaviour: Flowbite's Accordion, which passed the acceptance test.
 */
import Accordion from 'flowbite/lib/esm/components/accordion';
import type { Controller } from '../../storefront/runtime';

/**
 * Mounts the behaviour.
 *
 * @param element The accordion root.
 */
export function accordion( element: HTMLElement ): Controller {
	const items = Array.from( element.querySelectorAll<HTMLElement>( 'button[aria-controls]' ) ).map( ( triggerEl ) => ( {
		id: triggerEl.id,
		triggerEl,
		targetEl: element.querySelector<HTMLElement>( `#${ triggerEl.getAttribute( 'aria-controls' ) }` ) as HTMLElement,
		iconEl: null,
		active: triggerEl.getAttribute( 'aria-expanded' ) === 'true',
	} ) );
	const instance = new Accordion( element, items, {
		alwaysOpen: element.dataset.umMultiple === 'true',
		activeClasses: 'um-accordion-active',
		inactiveClasses: 'um-accordion-inactive',
	}, { id: element.id, override: true } );
	return { destroy: () => instance.destroy() };
}
