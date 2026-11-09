/**
 * Builds everything the products need at runtime.
 *
 * 1. Copies the shared PHP packages into each product under its own
 *    namespace (scripts/lib/prefix.mjs) and refreshes Composer autoloaders.
 * 2. Bundles TypeScript with esbuild and CSS with Tailwind CSS 4 (PostCSS).
 *    CSS is authored right to left; rtlcss writes the left-to-right copy.
 * 3. Writes `<entry>.asset.php` with dependencies and a content hash.
 * 4. Copies the shared UI build into every product's assets/build/ui.
 *
 * Usage: node scripts/build.mjs [--watch] [--dev] [--market=dev|rtl-theme|zhaket] [--skip-php]
 */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import * as esbuild from 'esbuild';
import postcss from 'postcss';
import tailwind from '@tailwindcss/postcss';
import rtlcss from 'rtlcss';
import { root } from './lib/run.mjs';
import { products } from './lib/products.mjs';
import { prefixPackages } from './lib/prefix.mjs';
import { dumpAutoload } from './lib/composer.mjs';
import { wpGlobalsPlugin } from './lib/wp-globals.mjs';
import { writeScopedPreflight } from './lib/scoped-preflight.mjs';

const args = process.argv.slice( 2 );
const watch = args.includes( '--watch' );
const dev = watch || args.includes( '--dev' );
const skipPhp = args.includes( '--skip-php' );
const market = ( args.find( ( arg ) => arg.startsWith( '--market=' ) ) ?? '--market=dev' ).slice( 9 );

const buildDir = path.join( root, 'build' );

/**
 * Build entries. `shared` entries are copied into every shipped product.
 *
 * @type {{name: string, js?: string, css?: string, shared?: boolean, product?: string, react?: boolean}[]}
 */
export const entries = [
	{ name: 'ui/storefront', js: 'packages/ui/storefront/index.ts', css: 'packages/ui/storefront/storefront.css', shared: true },
	{ name: 'ui/admin', css: 'packages/ui/admin/admin.css', shared: true },
	{ name: 'catalog/admin', js: 'tools/catalog/assets/src/admin.tsx', product: 'tools/catalog', react: true },
	{ name: 'acceptance/flowbite', js: 'packages/ui/acceptance/flowbite/fixture.ts', css: 'packages/ui/acceptance/flowbite/fixture.css' },
];

/**
 * Writes a file, creating its folder.
 *
 * @param {string} file    Absolute path.
 * @param {string|Buffer} content Content.
 */
function write( file, content ) {
	fs.mkdirSync( path.dirname( file ), { recursive: true } );
	fs.writeFileSync( file, content );
}

/**
 * Compiles one CSS entry to RTL and LTR files.
 *
 * @param {string} source Path from the root.
 * @param {string} out    Output path without extension.
 * @return {Promise<string>} The RTL CSS.
 */
async function buildCss( source, out ) {
	const from = path.join( root, source );
	const input = fs.readFileSync( from, 'utf8' );
	const result = await postcss( [ tailwind( { base: root, optimize: ! dev } ) ] ).process( input, { from, to: out + '.css' } );
	const ltr = rtlcss.process( result.css );
	write( out + '.css', result.css );
	write( out + '-ltr.css', ltr );
	return result.css;
}

/**
 * Bundles one script entry.
 *
 * @param {string}  source Path from the root.
 * @param {string}  out    Output path without extension.
 * @param {boolean} react  Whether React comes from WordPress globals.
 * @return {Promise<{code: string, dependencies: string[]}>} Bundle and WordPress script dependencies.
 */
async function buildJs( source, out, react ) {
	const dependencies = new Set();
	const result = await esbuild.build( {
		entryPoints: [ path.join( root, source ) ],
		bundle: true,
		format: 'iife',
		target: 'es2020',
		minify: ! dev,
		sourcemap: dev ? 'inline' : false,
		write: false,
		jsx: 'automatic',
		legalComments: 'none',
		logLevel: 'warning',
		define: { 'process.env.NODE_ENV': JSON.stringify( dev ? 'development' : 'production' ) },
		plugins: react ? [ wpGlobalsPlugin( dependencies ) ] : [],
	} );
	const code = result.outputFiles[ 0 ].text;
	write( out + '.js', code );
	return { code, dependencies: [ ...dependencies ].sort() };
}

/**
 * Builds one entry into the build folder.
 *
 * @param {typeof entries[number]} entry Entry.
 */
async function buildEntry( entry ) {
	const missing = [ entry.js, entry.css ].filter( ( file ) => file && ! fs.existsSync( path.join( root, file ) ) );
	if ( missing.length ) {
		return;
	}
	const out = path.join( buildDir, entry.name );
	const hash = crypto.createHash( 'sha256' );
	let dependencies = [];
	if ( entry.js ) {
		const js = await buildJs( entry.js, out, Boolean( entry.react ) );
		hash.update( js.code );
		dependencies = js.dependencies;
	}
	if ( entry.css ) {
		hash.update( await buildCss( entry.css, out ) );
	}
	const deps = dependencies.map( ( dep ) => `'${ dep }'` ).join( ', ' );
	write(
		out + '.asset.php',
		`<?php return array( 'dependencies' => array( ${ deps } ), 'version' => '${ hash.digest( 'hex' ).slice( 0, 12 ) }' );\n`
	);
}

/** Static files copied as they are: [source folder, folder inside build]. */
// Fonts sit next to the ui/ folder because Tailwind rebases url() from
// packages/ui/<entry>/ to the output, which lives one level down too.
const copies = [ [ 'packages/ui/fonts', 'fonts' ] ];

/**
 * Copies built files into the products that serve them.
 */
function copyToProducts() {
	for ( const [ source, target ] of copies ) {
		fs.cpSync( path.join( root, source ), path.join( buildDir, target ), { recursive: true } );
		for ( const product of products ) {
			fs.cpSync( path.join( root, source ), path.join( root, product.dir, 'assets', 'build', target ), { recursive: true } );
		}
	}
	for ( const entry of entries ) {
		const files = [ '.js', '.css', '-ltr.css', '.asset.php' ]
			.map( ( ext ) => path.join( buildDir, entry.name + ext ) )
			.filter( ( file ) => fs.existsSync( file ) );
		const targets = entry.shared ? products.map( ( product ) => product.dir ) : entry.product ? [ entry.product ] : [];
		for ( const target of targets ) {
			for ( const file of files ) {
				const dest = path.join( root, target, 'assets', 'build', path.relative( buildDir, file ) );
				fs.mkdirSync( path.dirname( dest ), { recursive: true } );
				fs.copyFileSync( file, dest );
			}
		}
	}
}

/**
 * Prefixes the shared PHP packages and refreshes autoloaders.
 */
function buildPhp() {
	for ( const product of products ) {
		prefixPackages( product, market );
	}
	dumpAutoload( products.map( ( product ) => product.dir ), dev ? [] : [ '--classmap-authoritative' ] );
}

/**
 * Builds everything once.
 *
 * @param {boolean} withPhp Whether to rebuild the PHP copies too.
 */
async function buildAll( withPhp ) {
	const started = Date.now();
	if ( withPhp ) {
		buildPhp();
	}
	writeScopedPreflight();
	await Promise.all( entries.map( buildEntry ) );
	copyToProducts();
	console.log( `Built in ${ ( ( Date.now() - started ) / 1000 ).toFixed( 1 ) }s${ dev ? ' (development)' : '' }.` );
}

await buildAll( ! skipPhp );

if ( watch ) {
	const watched = [ 'packages', 'themes', 'plugins', 'tools' ];
	const ignored = /(node_modules|vendor|vendor-prefixed|assets[\\/]build)/;
	let timer;
	let phpChanged = false;
	console.log( 'Watching for changes. Press Ctrl+C to stop.' );
	for ( const dir of watched ) {
		fs.watch( path.join( root, dir ), { recursive: true }, ( event, file ) => {
			if ( ! file || ignored.test( file ) ) {
				return;
			}
			phpChanged ||= dir === 'packages' && file.endsWith( '.php' );
			clearTimeout( timer );
			timer = setTimeout( () => {
				const withPhp = phpChanged;
				phpChanged = false;
				buildAll( withPhp ).catch( ( error ) => console.error( error.message ) );
			}, 150 );
		} );
	}
}
