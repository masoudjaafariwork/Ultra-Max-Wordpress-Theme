/**
 * Script of the header widget asset (BLD-12): loaded only on pages that show it.
 */
import { navBar } from '../header/nav-bar.behavior';
import { stickyNav } from '../header/sticky-nav.behavior';

window.UltraMaxUI?.define( 'nav-bar', navBar );
window.UltraMaxUI?.define( 'sticky-nav', stickyNav );
