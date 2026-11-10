/**
 * Elementor preview: the editor redraws a widget after every change, so
 * our component behaviours are mounted again on each redrawn element.
 */

interface ElementorFrontend {
	hooks?: { addAction: ( name: string, callback: ( scope: unknown ) => void ) => void };
}

interface JQueryLike {
	( target: unknown ): { on: ( event: string, callback: () => void ) => void };
}

declare global {
	interface Window {
		elementorFrontend?: ElementorFrontend;
		jQuery?: JQueryLike;
	}
}

/**
 * The DOM element inside a jQuery object or the element itself.
 *
 * @param scope Elementor's scope argument.
 */
function element( scope: unknown ): ParentNode | null {
	if ( scope instanceof Element ) {
		return scope;
	}
	if ( scope && typeof scope === 'object' && 0 in scope ) {
		const first = ( scope as { 0: unknown } )[ 0 ];
		return first instanceof Element ? first : null;
	}
	return null;
}

let hooked = false;

/** Adds the mount hook once Elementor's frontend is ready. */
function hook(): void {
	const hooks = window.elementorFrontend?.hooks;
	if ( hooked || ! hooks ) {
		return;
	}
	hooked = true;
	hooks.addAction( 'frontend/element_ready/global', ( scope ) => {
		const root = element( scope );
		if ( root ) {
			window.UltraMaxUI?.mount( root.parentNode ?? root );
		}
	} );
}

hook();
if ( ! hooked ) {
	window.jQuery?.( window ).on( 'elementor/frontend/init', hook );
	window.addEventListener( 'load', hook );
}

export {};
