/**
 * TrackingBox behaviour (ACC-02): the copy button puts the tracking code
 * on the clipboard and says so to screen readers and on the button.
 */
import type { Behavior } from '../../storefront/runtime';

/**
 * Copies text, with the old way as a fallback for pages without a secure
 * context.
 *
 * @param text Text.
 */
export async function copyText( text: string ): Promise<boolean> {
	try {
		await navigator.clipboard.writeText( text );
		return true;
	} catch {
		const field = document.createElement( 'textarea' );
		field.value = text;
		field.setAttribute( 'readonly', '' );
		field.style.position = 'fixed';
		field.style.opacity = '0';
		document.body.append( field );
		field.select();
		let done: boolean;
		try {
			done = document.execCommand( 'copy' );
		} catch {
			done = false;
		}
		field.remove();
		return done;
	}
}

export const copyCode: Behavior = ( element ) => {
	const button = element.querySelector<HTMLButtonElement>( '[data-um-copy]' );
	const code = element.querySelector<HTMLElement>( '[data-um-code]' )?.textContent?.trim() ?? '';
	const status = element.querySelector<HTMLElement>( '[data-um-copy-status]' );
	const label = button?.querySelector( 'span' );
	const original = label?.textContent ?? '';
	const onClick = async (): Promise<void> => {
		if ( ! ( await copyText( code ) ) ) {
			return;
		}
		const copied = button?.dataset.copied ?? '';
		if ( status ) {
			status.textContent = copied;
		}
		if ( label ) {
			label.textContent = copied;
			window.setTimeout( () => {
				label.textContent = original;
			}, 2000 );
		}
	};
	const listener = (): void => {
		void onClick();
	};
	button?.addEventListener( 'click', listener );
	return {
		destroy(): void {
			button?.removeEventListener( 'click', listener );
		},
	};
};
