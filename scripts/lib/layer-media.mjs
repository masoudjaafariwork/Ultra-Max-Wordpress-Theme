/**
 * Keeps responsive utilities working across separate stylesheets.
 *
 * Every widget has its own stylesheet (BLD-12), and each one repeats the
 * utilities it uses, such as `grid-cols-2`. Inside one file Tailwind puts
 * `lg:grid-cols-4` after `grid-cols-2`, so the wide-screen rule wins; but
 * the plain `grid-cols-2` of a stylesheet loaded later would win over it.
 *
 * This PostCSS plugin moves every media query of the utilities layer into
 * its own cascade layer, ordered as Tailwind orders them (hover, then
 * max-width from wide to narrow, then min-width from narrow to wide, then
 * reduced motion), and declares the same layer order at the top of every
 * file. Cascade layers beat source order, so a wide-screen rule from any
 * file wins over a plain rule from any other file.
 */

const WIDTHS = [ 40, 48, 64, 80, 96 ];

/** The full layer order, the same in every stylesheet. */
export const LAYER_ORDER = [
	'properties',
	'theme',
	'base',
	'components',
	'utilities',
	'utilities-hover',
	...[ ...WIDTHS ].reverse().map( ( width ) => `utilities-max-${ width }` ),
	...WIDTHS.map( ( width ) => `utilities-min-${ width }` ),
	'utilities-motion',
];

/**
 * Width in rem of a media query, or null.
 *
 * @param {string} params Media query.
 * @return {number|null} Width.
 */
function width( params ) {
	const match = params.match( /(\d+(?:\.\d+)?)(rem|px)/ );
	if ( ! match ) {
		return null;
	}
	const rem = match[ 2 ] === 'px' ? ( Number( match[ 1 ] ) + ( params.includes( 'max-width' ) ? 1 : 0 ) ) / 16 : Number( match[ 1 ] );
	return WIDTHS.includes( rem ) ? rem : null;
}

/**
 * The layer a media query belongs to, or null to leave it in place.
 *
 * @param {string} params Media query.
 * @return {string|null} Layer name.
 */
export function layerFor( params ) {
	const text = params.replace( /\s+/g, '' );
	if ( text === '(hover:hover)' ) {
		return 'utilities-hover';
	}
	if ( text.includes( 'prefers-reduced-motion' ) ) {
		return 'utilities-motion';
	}
	const size = width( params );
	if ( null === size ) {
		return null;
	}
	const max = text.startsWith( 'notall' ) || text.includes( 'max-width' ) || text.includes( '<' );
	return `utilities-${ max ? 'max' : 'min' }-${ size }`;
}

/**
 * The PostCSS plugin.
 *
 * @return {import('postcss').Plugin} Plugin.
 */
export function layerMedia() {
	return {
		postcssPlugin: 'um-layer-media',
		Once( root, { AtRule } ) {
			root.walkAtRules( 'layer', ( layer ) => {
				if ( layer.params !== 'utilities' || ! layer.nodes ) {
					return;
				}
				const moved = [];
				layer.each( ( node ) => {
					if ( node.type === 'atrule' && node.name === 'media' ) {
						const name = layerFor( node.params );
						if ( name ) {
							moved.push( [ name, node ] );
						}
					}
				} );
				let anchor = layer;
				for ( const [ name, node ] of moved ) {
					node.remove();
					const target = new AtRule( { name: 'layer', params: name } );
					target.append( node );
					anchor.after( target );
					anchor = target;
				}
			} );
			// Replace Tailwind's short layer statement with the full order.
			root.walkAtRules( 'layer', ( rule ) => {
				if ( ! rule.nodes && rule.params.includes( ',' ) ) {
					rule.remove();
				}
			} );
			root.prepend( new AtRule( { name: 'layer', params: LAYER_ORDER.join( ', ' ) } ) );
		},
	};
}
layerMedia.postcss = true;
