/**
 * Bottom bar (HDR-10): an item that names a drawer opens it instead of
 * following its link, when the page has that drawer.
 */
import type { Behavior } from '../../storefront/runtime';

export const bottomNav: Behavior = ( element ) => {
	const onClick = ( event: MouseEvent ): void => {
		const link = event.target instanceof Element ? event.target.closest<HTMLAnchorElement>( '[data-opens]' ) : null;
		if ( ! link ) {
			return;
		}
		const drawer = document.querySelector<HTMLElement>( link.dataset.opens ?? '' );
		const controller = drawer ? window.UltraMaxUI?.get( drawer ) : undefined;
		if ( controller?.open ) {
			event.preventDefault();
			controller.open();
		}
	};
	element.addEventListener( 'click', onClick );
	return {
		destroy(): void {
			element.removeEventListener( 'click', onClick );
		},
	};
};
