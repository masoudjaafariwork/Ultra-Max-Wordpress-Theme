/**
 * Tooltip behaviour. Flowbite shows, hides and positions the hint; this
 * module adds aria-describedby (missing in the acceptance test) and maps
 * our logical placements to Popper's physical ones.
 */
import Tooltip from 'flowbite/lib/esm/components/tooltip';
import type { Controller } from '../../storefront/runtime';

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])';

/**
 * Mounts the behaviour.
 *
 * @param element The tooltip root.
 */
export function tooltip( element: HTMLElement ): Controller {
	const tip = element.querySelector<HTMLElement>( `#${ element.dataset.umTooltip }` ) as HTMLElement;
	const trigger = element.querySelector<HTMLElement>( FOCUSABLE ) ?? element;
	const rtl = getComputedStyle( element ).direction === 'rtl';
	const logical = element.dataset.umPlacement ?? 'top';
	const placement = ( {
		top: 'top',
		bottom: 'bottom',
		start: rtl ? 'right' : 'left',
		end: rtl ? 'left' : 'right',
	} as const )[ logical as 'top' ] ?? 'top';

	const describedBy = new Set( ( trigger.getAttribute( 'aria-describedby' ) ?? '' ).split( /\s+/ ).filter( Boolean ) );
	describedBy.add( tip.id );
	trigger.setAttribute( 'aria-describedby', [ ...describedBy ].join( ' ' ) );

	const instance = new Tooltip( tip, trigger, { placement, triggerType: 'hover' }, { id: tip.id, override: true } );
	return { destroy: () => instance.destroy() };
}
