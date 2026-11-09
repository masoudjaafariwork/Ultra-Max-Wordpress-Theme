/**
 * The library boundary rule (CORE-20, roadmap section 4).
 *
 * Outside packages/ui, code may not mention Flowbite, use its data
 * attributes, import shadcn/ui or Radix directly, or use Tailwind classes
 * that change how something looks. Pages, templates, widgets and shortcodes
 * call component APIs and use layout classes only. This keeps every UI
 * library replaceable by editing packages/ui alone.
 */

/** Tailwind utility families that set looks, not layout. */
const LOOK = new RegExp(
	'^(' +
		[
			'bg', 'border', 'rounded', 'shadow', 'font', 'ring', 'outline',
			'opacity', 'fill', 'stroke', 'decoration', 'underline', 'overline', 'line-through', 'no-underline', 'leading', 'tracking',
			'italic', 'not-italic', 'uppercase', 'lowercase', 'capitalize', 'from', 'via', 'to', 'accent', 'caret', 'placeholder',
			'divide', 'transition', 'animate', 'duration', 'ease', 'delay', 'blur', 'brightness', 'contrast', 'grayscale', 'backdrop',
			'drop-shadow', 'sepia', 'saturate', 'hue-rotate', 'invert', 'mix-blend', 'bg-blend', 'scale', 'rotate', 'skew', 'antialiased',
		].join( '|' ) +
		')(-|$)'
);

/** `text-*` sets colour or size, except these alignment and wrapping utilities. */
const TEXT_LAYOUT = /^text-(start|end|center|left|right|justify|wrap|nowrap|balance|pretty|ellipsis|clip)$/;

const FLOWBITE_ATTR = /\bdata-(modal|drawer|dropdown|tabs|accordion|tooltip|popover|collapse|dismiss|carousel|dial|copy-to-clipboard|input-counter|datepicker)(-[\w-]+)?\b|\bdata-popper-[\w-]+/;
const FLOWBITE_NAME = /\bflowbite\b/i;
const DIRECT_IMPORT = /from\s+['"](radix-ui|@radix-ui\/[^'"]+|@\/components\/ui\/[^'"]+|[^'"]*admin\/components\/ui\/[^'"]+)['"]/;
const CLASS_ATTR = /\b(?:class|className)\s*=\s*(["'])(.*?)\1|'class'\s*=>\s*'([^']*)'/g;

/**
 * Returns the look classes in a class list.
 *
 * @param {string} list Space-separated classes.
 * @return {string[]} Offending classes.
 */
export function lookClasses( list ) {
	return list
		.split( /\s+/ )
		.filter( Boolean )
		.filter( ( name ) => ! name.includes( '<?' ) && ! name.includes( '$' ) && ! name.includes( '{' ) )
		.filter( ( name ) => {
			const utility = name.split( ':' ).pop().replace( /^!|^-/, '' );
			return LOOK.test( utility ) || ( utility.startsWith( 'text-' ) && ! TEXT_LAYOUT.test( utility ) );
		} );
}

/**
 * Checks one file's source.
 *
 * @param {string} file    Path from the repository root, with forward slashes.
 * @param {string} content File content.
 * @return {{file: string, line: number, message: string}[]} Violations.
 */
export function checkSource( file, content ) {
	if ( file.startsWith( 'packages/ui/' ) ) {
		return [];
	}
	const violations = [];
	const lines = content.split( '\n' );
	lines.forEach( ( text, index ) => {
		const line = index + 1;
		if ( FLOWBITE_NAME.test( text ) ) {
			violations.push( { file, line, message: 'Flowbite is only used inside packages/ui.' } );
		}
		const attr = text.match( FLOWBITE_ATTR );
		if ( attr ) {
			violations.push( { file, line, message: `Library attribute "${ attr[ 0 ] }" is only used inside packages/ui.` } );
		}
		const direct = text.match( DIRECT_IMPORT );
		if ( direct ) {
			violations.push( { file, line, message: `Import "${ direct[ 1 ] }" through @ultra-max/ui/admin instead.` } );
		}
		for ( const match of text.matchAll( CLASS_ATTR ) ) {
			const found = lookClasses( match[ 2 ] ?? match[ 3 ] ?? '' );
			if ( found.length ) {
				violations.push( { file, line, message: `Look classes "${ found.join( ' ' ) }" belong inside a component; use layout classes only.` } );
			}
		}
	} );
	return violations;
}
