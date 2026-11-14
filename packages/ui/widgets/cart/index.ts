/**
 * Script of the cart widget asset (BLD-06, BLD-12): the cart page, the
 * cart widgets and the side cart of every page.
 */
import { cart } from './cart.behavior';
import { sideCart } from './side-cart.behavior';

window.UltraMaxUI?.define( 'cart', cart );
window.UltraMaxUI?.define( 'side-cart', sideCart );
