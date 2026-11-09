/**
 * Flowbite acceptance fixture: loads Flowbite exactly as documented, with
 * its data-attribute auto initialisation, so the test judges Flowbite itself.
 */
import { initFlowbite } from 'flowbite';

document.addEventListener( 'DOMContentLoaded', () => {
	initFlowbite();
	document.documentElement.dataset.ready = 'true';
} );