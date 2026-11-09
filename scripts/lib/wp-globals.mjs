/**
 * esbuild plugin: takes React from the copy WordPress already loads in the
 * admin, instead of bundling a second React into every product.
 */

const globals = {
	react: { global: 'React', handle: 'react' },
	'react-dom': { global: 'ReactDOM', handle: 'react-dom' },
	'react-dom/client': { global: 'ReactDOM', handle: 'react-dom' },
	'react/jsx-runtime': { global: 'ReactJSXRuntime', handle: 'react-jsx-runtime' },
	'react/jsx-dev-runtime': { global: 'ReactJSXRuntime', handle: 'react-jsx-runtime' },
};

/**
 * Creates the plugin.
 *
 * @param {Set<string>} dependencies Collects the WordPress script handles the bundle needs.
 * @return {import('esbuild').Plugin} Plugin.
 */
export function wpGlobalsPlugin( dependencies ) {
	const filter = new RegExp( `^(${ Object.keys( globals ).map( ( name ) => name.replace( /[/]/g, '\\/' ) ).join( '|' ) })$` );
	return {
		name: 'wp-globals',
		setup( build ) {
			build.onResolve( { filter }, ( args ) => ( { path: args.path, namespace: 'wp-global' } ) );
			build.onLoad( { filter: /.*/, namespace: 'wp-global' }, ( args ) => {
				const { global, handle } = globals[ args.path ];
				dependencies.add( handle );
				return { contents: `module.exports = window.${ global };`, loader: 'js' };
			} );
		},
	};
}
