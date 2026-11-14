/**
 * Script of the saved asset (SAVE-01 to SAVE-05): wishlist and compare
 * buttons and counters on every page while those modules are on, the
 * compare table and the quick view window.
 */
import { startSaved } from './saved.behavior';
import { compare } from './compare.behavior';
import { quickView } from './quick-view.behavior';

window.UltraMaxUI?.define( 'compare', compare );
window.UltraMaxUI?.define( 'quick-view', quickView );

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', startSaved, { once: true } );
} else {
	startSaved();
}
