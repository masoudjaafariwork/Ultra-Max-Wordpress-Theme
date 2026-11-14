/**
 * Script of the reviews widget asset (BLD-12): reviews and questions of
 * product pages and of the product widgets.
 */
import { reviews } from './reviews.behavior';
import { questions } from './questions.behavior';

window.UltraMaxUI?.define( 'reviews', reviews );
window.UltraMaxUI?.define( 'questions', questions );

// "Write a review" links open the folded form.
document.addEventListener( 'click', ( event ) => {
	const link = event.target instanceof Element ? event.target.closest<HTMLAnchorElement>( '[data-um-review-write]' ) : null;
	const form = link ? document.getElementById( link.hash.slice( 1 ) ) : null;
	if ( form instanceof HTMLDetailsElement ) {
		form.open = true;
	}
} );
