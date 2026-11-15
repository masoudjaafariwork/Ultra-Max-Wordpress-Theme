/**
 * Field behaviour: the button of a password field shows or hides what was
 * typed. The button keeps its name; aria-pressed says whether the password
 * is shown, and the eye icon changes with it.
 */
import type { Behavior } from '../../storefront/runtime';

export const passwordReveal: Behavior = ( element ) => {
	const input = element.querySelector<HTMLInputElement>( 'input' );
	const button = element.querySelector<HTMLButtonElement>( '[data-um-reveal]' );
	const show = element.querySelector<HTMLElement>( '[data-um-reveal-show]' );
	const hide = element.querySelector<HTMLElement>( '[data-um-reveal-hide]' );

	const onClick = (): void => {
		if ( ! input || ! button ) {
			return;
		}
		const shown = input.type === 'password';
		input.type = shown ? 'text' : 'password';
		button.setAttribute( 'aria-pressed', shown ? 'true' : 'false' );
		if ( show && hide ) {
			show.hidden = shown;
			hide.hidden = ! shown;
		}
	};

	// The browser must never remember a password field as plain text.
	const onSubmit = (): void => {
		if ( input && input.type === 'text' ) {
			onClick();
		}
	};
	const form = input?.form ?? null;

	button?.addEventListener( 'click', onClick );
	form?.addEventListener( 'submit', onSubmit );
	return {
		destroy(): void {
			button?.removeEventListener( 'click', onClick );
			form?.removeEventListener( 'submit', onSubmit );
		},
	};
};
