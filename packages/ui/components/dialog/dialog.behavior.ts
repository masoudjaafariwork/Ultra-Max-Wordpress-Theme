/**
 * Dialog behaviour on the native `dialog` element.
 *
 * Flowbite's Modal failed the acceptance test (no focus management), so the
 * browser's own modal dialog is used: it moves focus in, keeps Tab inside,
 * makes the page behind inert and closes on Escape. This module adds focus
 * return, closing on a backdrop click and our `um:open` / `um:close` events.
 */
import type { Controller } from '../../storefront/runtime';

export interface DialogController extends Controller {
	open(): void;
	close(): void;
	isOpen(): boolean;
}

/**
 * Mounts the behaviour.
 *
 * @param element The `dialog` element.
 */
export function dialog( element: HTMLElement ): DialogController {
	const el = element as HTMLDialogElement;
	let opener: HTMLElement | null = null;

	const onClose = (): void => {
		opener?.focus();
		opener = null;
		el.dispatchEvent( new CustomEvent( 'um:close', { bubbles: true } ) );
	};

	const onClick = ( event: MouseEvent ): void => {
		if ( event.target !== el ) {
			return;
		}
		const rect = el.getBoundingClientRect();
		const inside = event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom;
		if ( ! inside ) {
			el.close();
		}
	};

	el.addEventListener( 'close', onClose );
	el.addEventListener( 'click', onClick );

	return {
		open() {
			if ( el.open ) {
				return;
			}
			opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
			el.showModal();
			el.dispatchEvent( new CustomEvent( 'um:open', { bubbles: true } ) );
		},
		close() {
			if ( el.open ) {
				el.close();
			}
		},
		isOpen: () => el.open,
		destroy() {
			el.removeEventListener( 'close', onClose );
			el.removeEventListener( 'click', onClick );
		},
	};
}