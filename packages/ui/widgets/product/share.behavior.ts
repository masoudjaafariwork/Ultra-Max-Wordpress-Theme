/**
 * ShareLinks behaviour (PDP-24): copies the address to the clipboard and
 * shows the device's own share sheet when the browser offers one.
 */
import type { Behavior } from '../../storefront/runtime';

export const share: Behavior = ( element ) => {
	const url = element.dataset.url ?? window.location.href;
	const title = element.dataset.title ?? document.title;
	const copy = element.querySelector<HTMLButtonElement>( '[data-um-share-copy]' );
	const native = element.querySelector<HTMLButtonElement>( '[data-um-share-native]' );
	const label = copy?.querySelector( 'span' );
	const original = label?.textContent ?? '';

	const onCopy = async (): Promise<void> => {
		try {
			await navigator.clipboard.writeText( url );
		} catch {
			const field = document.createElement( 'textarea' );
			field.value = url;
			field.setAttribute( 'readonly', '' );
			field.className = 'fixed -start-full';
			document.body.append( field );
			field.select();
			document.execCommand( 'copy' );
			field.remove();
		}
		if ( label && copy ) {
			label.textContent = copy.dataset.copied ?? original;
			setTimeout( () => ( label.textContent = original ), 2000 );
		}
	};

	const onNative = (): void => {
		void navigator.share?.( { url, title } ).catch( () => undefined );
	};

	const copyListener = (): void => {
		void onCopy();
	};
	copy?.addEventListener( 'click', copyListener );
	if ( native && typeof navigator.share === 'function' ) {
		native.hidden = false;
		native.addEventListener( 'click', onNative );
	}
	return {
		destroy(): void {
			copy?.removeEventListener( 'click', copyListener );
			native?.removeEventListener( 'click', onNative );
		},
	};
};
