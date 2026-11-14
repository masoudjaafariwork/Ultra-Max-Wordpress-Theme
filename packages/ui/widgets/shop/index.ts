/**
 * Script of the shop widget asset (BLD-12): loaded only on product lists
 * and on pages that show a shop widget.
 */
import { shop } from './shop.behavior';
import { priceRange } from './price-range.behavior';
import { seoText } from './seo-text.behavior';

window.UltraMaxUI?.define( 'price-range', priceRange );
window.UltraMaxUI?.define( 'seo-text', seoText );
window.UltraMaxUI?.define( 'shop', shop );
