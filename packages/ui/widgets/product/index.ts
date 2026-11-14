/**
 * Script of the product widget asset (BLD-12): loaded only on product pages
 * and on pages that show a product widget.
 */
import { productGallery } from './gallery.behavior';
import { purchase } from './purchase.behavior';
import { productSections } from './sections.behavior';
import { stickyBuy } from './sticky-buy.behavior';
import { share } from './share.behavior';
import { recentProducts } from './recent.behavior';
import { seoText } from '../shop/seo-text.behavior';

window.UltraMaxUI?.define( 'product-gallery', productGallery );
window.UltraMaxUI?.define( 'purchase', purchase );
window.UltraMaxUI?.define( 'product-sections', productSections );
window.UltraMaxUI?.define( 'sticky-buy', stickyBuy );
window.UltraMaxUI?.define( 'share', share );
window.UltraMaxUI?.define( 'recent-products', recentProducts );
window.UltraMaxUI?.define( 'seo-text', seoText );
