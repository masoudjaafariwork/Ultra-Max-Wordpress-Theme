/**
 * Contact and newsletter forms: sends the form in place with fetch and
 * shows the server's message under it, so the visitor keeps their place.
 * Without JavaScript the form posts normally.
 */
import type { Behavior } from '../../storefront/runtime';

interface Answer {
	success?: boolean;
	data?: { message?: string };
}

export const siteForm: Behavior = ( element ) => {
	if ( ! ( element instanceof HTMLFormElement ) ) {
		return {};
	}
	const form = element;
	const status = form.querySelector<HTMLElement>( '[data-status]' );
	const onSubmit = async ( event: SubmitEvent ): Promise<void> => {
		event.preventDefault();
		const button = form.querySelector<HTMLButtonElement>( 'button[type="submit"]' );
		if ( button ) {
			button.disabled = true;
		}
		const body = new FormData( form );
		body.set( 'um_ajax', '1' );
		try {
			const response = await fetch( form.action, { method: 'POST', body, credentials: 'same-origin' } );
			const answer = ( await response.json() ) as Answer;
			if ( status ) {
				status.textContent = answer.data?.message ?? '';
				status.dataset.tone = answer.success ? 'success' : 'danger';
			}
			if ( answer.success ) {
				form.reset();
			}
		} catch {
			form.submit();
		} finally {
			if ( button ) {
				button.disabled = false;
			}
		}
	};
	const listener = ( event: SubmitEvent ): void => {
		void onSubmit( event );
	};
	form.addEventListener( 'submit', listener );
	return {
		destroy(): void {
			form.removeEventListener( 'submit', listener );
		},
	};
};
