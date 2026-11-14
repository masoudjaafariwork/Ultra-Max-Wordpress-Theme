/**
 * Script of the search widget asset (BLD-12): the live search, loaded on
 * every page while the live search module is on.
 */
import { liveSearch } from './live-search.behavior';

window.UltraMaxUI?.define( 'live-search', liveSearch );
