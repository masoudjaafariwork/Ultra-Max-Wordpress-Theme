/**
 * The products in this monorepo and the shared packages each one bundles.
 */

/**
 * @typedef {object} Product
 * @property {string} slug      Folder name and text domain.
 * @property {string} dir       Path from the repository root.
 * @property {string} namespace Root PHP namespace of the product.
 * @property {'theme'|'plugin'} type WordPress package type.
 * @property {boolean} shipped  Whether the product goes into release packages.
 */

/** @type {Product[]} */
export const products = [
	{ slug: 'ultra-max', dir: 'themes/ultra-max', namespace: 'UltraMax\\Theme', type: 'theme', shipped: true },
	{ slug: 'ultra-max-core', dir: 'plugins/ultra-max-core', namespace: 'UltraMax\\Core', type: 'plugin', shipped: true },
	{ slug: 'ultra-max-auth', dir: 'plugins/ultra-max-auth', namespace: 'UltraMax\\Auth', type: 'plugin', shipped: true },
	{ slug: 'ultra-max-invoice', dir: 'plugins/ultra-max-invoice', namespace: 'UltraMax\\Invoice', type: 'plugin', shipped: true },
	{ slug: 'ultra-max-seo', dir: 'plugins/ultra-max-seo', namespace: 'UltraMax\\Seo', type: 'plugin', shipped: true },
];

/** The child theme ships too, but has no build step. */
export const childTheme = { slug: 'ultra-max-child', dir: 'themes/ultra-max-child', type: 'theme' };

/**
 * Shared PHP packages copied into every product under its own namespace,
 * so two products of different versions never load each other's classes.
 */
export const phpPackages = [
	{ name: 'UI', domain: 'ultra-max-ui', sources: [ 'packages/ui/src', 'packages/ui/components', 'packages/ui/widgets' ], target: 'ui' },
	{ name: 'Foundation', domain: 'ultra-max-foundation', sources: [ 'packages/foundation/src' ], target: 'foundation' },
	{ name: 'License', domain: 'ultra-max-license', sources: [ 'packages/license/src' ], target: 'license' },
	{ name: 'Settings', domain: 'ultra-max-settings', sources: [ 'packages/settings/src' ], target: 'settings' },
];

export const markets = [ 'rtl-theme', 'zhaket' ];
