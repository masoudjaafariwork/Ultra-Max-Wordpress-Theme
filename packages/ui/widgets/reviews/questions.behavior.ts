/**
 * QuestionList behaviour (PDP-10): sends a question or an answer in place
 * and shows the store's reply under the form. Without JavaScript the forms
 * post normally and the page comes back with the same message.
 */
import type { Behavior } from '../../storefront/runtime';

interface Reply {
	success?: boolean;
	message?: string;
}

export const questions: Behavior = ( element ) => {
	const onSubmit = async ( event: SubmitEvent ): Promise<void> => {
		const form = event.target instanceof HTMLFormElement && event.target.hasAttribute( 'data-um-qa-form' ) ? event.target : null;
		if ( ! form ) {
			return;
		}
		event.preventDefault();
		const status = form.querySelector<HTMLElement>( '[data-um-qa-status]' );
		const button = form.querySelector<HTMLButtonElement>( 'button[type="submit"]' );
		button?.setAttribute( 'aria-busy', 'true' );
		try {
			const response = await fetch( form.action, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { Accept: 'application/json' },
				body: new FormData( form ),
			} );
			const reply = ( await response.json() ) as Reply;
			if ( status ) {
				status.textContent = reply.message ?? '';
				status.dataset.tone = reply.success ? 'success' : 'danger';
				status.toggleAttribute( 'data-shown', Boolean( reply.message ) );
			}
			const text = form.querySelector( 'textarea' );
			if ( reply.success && text ) {
				text.value = '';
			}
		} catch {
			form.submit();
		} finally {
			button?.removeAttribute( 'aria-busy' );
		}
	};
	const listener = ( event: SubmitEvent ): void => {
		void onSubmit( event );
	};
	element.addEventListener( 'submit', listener );
	return {
		destroy(): void {
			element.removeEventListener( 'submit', listener );
		},
	};
};
