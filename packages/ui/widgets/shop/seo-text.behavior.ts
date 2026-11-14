/**
 * SeoText behaviour (PLP-10): folds a long text to a few lines and shows
 * a "show more" button that opens and closes it. A short text that fits
 * stays open with no button. Without JavaScript the whole text shows.
 */
import type { Behavior } from '../../storefront/runtime';

export const seoText: Behavior = ( element ) => {
	const text = element.querySelector<HTMLElement>( '[data-um-fold]' );
	const button = element.querySelector<HTMLButtonElement>( 'button[aria-controls]' );
	if ( ! text || ! button ) {
		return {};
	}
	text.setAttribute( 'data-folded', '' );
	if ( text.scrollHeight <= text.clientHeight + 8 ) {
		text.removeAttribute( 'data-folded' );
		return {};
	}
	button.setAttribute( 'data-ready', '' );

	const onClick = (): void => {
		const open = button.getAttribute( 'aria-expanded' ) !== 'true';
		button.setAttribute( 'aria-expanded', String( open ) );
		text.toggleAttribute( 'data-folded', ! open );
		button.textContent = ( open ? button.dataset.less : button.dataset.more ) ?? '';
	};
	button.addEventListener( 'click', onClick );
	return {
		destroy(): void {
			button.removeEventListener( 'click', onClick );
		},
	};
};
