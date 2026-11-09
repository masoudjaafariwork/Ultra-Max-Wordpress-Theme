/**
 * Mounts component behaviours on server-rendered markup.
 *
 * Every interactive component renders `data-um-component="<name>"`. The
 * runtime finds those elements, mounts the matching behaviour once and
 * wires our own triggers: `data-um-open="<id>"` opens a dialog or drawer,
 * `data-um-close` inside one closes it. Behaviour modules are the only
 * place where Flowbite is used, so swapping the library touches nothing else.
 */

/** What every behaviour returns. */
export interface Controller {
	open?(): void;
	close?(): void;
	destroy?(): void;
}

export type Behavior = ( element: HTMLElement ) => Controller;

const controllers = new WeakMap<HTMLElement, Controller>();

/**
 * Creates a runtime for a set of behaviours.
 *
 * @param behaviors Behaviour by component name.
 */
export function createRuntime( behaviors: Record<string, Behavior> ) {
	/**
	 * Mounts every component below a root that is not mounted yet.
	 *
	 * @param root Where to look; defaults to the whole document.
	 */
	const mount = ( root: ParentNode = document ): void => {
		root.querySelectorAll<HTMLElement>( '[data-um-component]' ).forEach( ( element ) => {
			if ( controllers.has( element ) ) {
				return;
			}
			const behavior = behaviors[ element.dataset.umComponent ?? '' ];
			if ( behavior ) {
				controllers.set( element, behavior( element ) );
			}
		} );
	};

	/**
	 * Returns the controller of a mounted component.
	 *
	 * @param element Component root element.
	 */
	const get = ( element: HTMLElement | null ): Controller | undefined => ( element ? controllers.get( element ) : undefined );

	const onClick = ( event: MouseEvent ): void => {
		const target = event.target instanceof Element ? event.target : null;
		const opener = target?.closest<HTMLElement>( '[data-um-open]' );
		if ( opener ) {
			event.preventDefault();
			get( document.getElementById( opener.dataset.umOpen ?? '' ) )?.open?.();
			return;
		}
		const closer = target?.closest<HTMLElement>( '[data-um-close]' );
		if ( closer ) {
			get( closer.closest<HTMLElement>( '[data-um-component]' ) )?.close?.();
		}
	};

	/**
	 * Adds a behaviour loaded later, by a module's own script, and mounts it.
	 *
	 * @param name     Component name.
	 * @param behavior Behaviour.
	 */
	const define = ( name: string, behavior: Behavior ): void => {
		behaviors[ name ] = behavior;
		if ( started ) {
			mount();
		}
	};

	let started = false;
	const start = (): void => {
		started = true;
		document.addEventListener( 'click', onClick );
		mount();
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start, { once: true } );
	} else {
		start();
	}

	return { mount, get, define };
}
