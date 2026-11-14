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
import { marked } from 'marked';
import { root } from './lib/run.mjs';
import { products } from './lib/products.mjs';
import { prefixPackages } from './lib/prefix.mjs';
import { dumpAutoload } from './lib/composer.mjs';
import { wpGlobalsPlugin } from './lib/wp-globals.mjs';
import { writeScopedPreflight } from './lib/scoped-preflight.mjs';
import { dropKnown, layeredRuleKeys, layerMedia } from './lib/layer-media.mjs';

/** Layered rules of the storefront file, dropped from module and widget files (see lib/layer-media.mjs). */
let storefrontRules = new Set();

const args = process.argv.slice( 2 );
const watch = args.includes( '--watch' );
const dev = watch || args.includes( '--dev' );
const skipPhp = args.includes( '--skip-php' );
const market = ( args.find( ( arg ) => arg.startsWith( '--market=' ) ) ?? '--market=dev' ).slice( 9 );

const buildDir = path.join( root, 'build' );

/**
 * Build entries. `shared` entries are copied into every shipped product.
 *
 * eact: true takes React from WordPress instead of bundling it.
 *
 * @type {{name: string, js?: string, css?: string, shared?: boolean, product?: string, react?: boolean}[]}
 */
export const entries = [
	{ name: 'ui/storefront', js: 'packages/ui/storefront/index.ts', css: 'packages/ui/storefront/storefront.css', shared: true },
	{ name: 'ui/admin', css: 'packages/ui/admin/admin.css', shared: true },
	// Switchable storefront modules (PERF-02): each loads only when it is on.
	...[ 'notice-bar', 'contact-buttons', 'back-to-top', 'smart-header', 'bottom-nav' ].map( ( module ) => optional( {
		name: `ui/modules/${ module }`,
		js: `packages/ui/modules/${ module }.ts`,
		css: `packages/ui/modules/${ module }.css`,
		shared: true,
	} ) ),
	// Widget assets (BLD-12): one per folder of packages/ui/widgets, loaded only where a widget is used.
	...widgetFolders().map( ( widget ) => optional( {
		name: `ui/widgets/${ widget }`,
		js: `packages/ui/widgets/${ widget }/index.ts`,
		css: `packages/ui/widgets/${ widget }/index.css`,
		shared: true,
	} ) ),
	// Elementor editor and preview helpers (HOME-13, search control).
	{ name: 'core/elementor-editor', js: 'plugins/ultra-max-core/assets/src/elementor/editor.ts', css: 'plugins/ultra-max-core/assets/src/elementor/editor.css', product: 'plugins/ultra-max-core' },
	{ name: 'core/elementor-preview', js: 'plugins/ultra-max-core/assets/src/elementor/preview.ts', product: 'plugins/ultra-max-core' },
	// Product screen: picture choosers for variations and attribute values, rows of the product's tabs (PDP-03, PDP-04, PDP-14).
	{ name: 'core/product-admin', js: 'plugins/ultra-max-core/assets/src/product-admin/index.ts', product: 'plugins/ultra-max-core' },
	// Our additions to WooCommerce's block cart and checkout (CART-03, CART-04, CHK-02, CHK-03, CHK-10); uses WordPress's globals only.
	{ name: 'core/wc-blocks', js: 'plugins/ultra-max-core/assets/src/wc-blocks/index.ts', product: 'plugins/ultra-max-core' },
	// Admin screens bundle React 19: shadcn/ui needs it and WordPress 7.1 still ships React 18.
	{ name: 'catalog/admin', js: 'tools/catalog/assets/src/admin.tsx', product: 'tools/catalog' },
	// The Ultra Max panel (ADM-01 to ADM-07): React 19 bundled, like the catalog.
	{ name: 'core/admin', js: 'plugins/ultra-max-core/assets/src/admin/index.tsx', product: 'plugins/ultra-max-core' },
	{ name: 'acceptance/flowbite', js: 'packages/ui/acceptance/flowbite/fixture.ts', css: 'packages/ui/acceptance/flowbite/fixture.css' },
];

/**
 * Folders of packages/ui/widgets: each is one widget asset.
 *
 * @return {string[]} Folder names.
 */
export function widgetFolders() {
	const dir = path.join( root, 'packages', 'ui', 'widgets' );
	if ( ! fs.existsSync( dir ) ) {
		return [];
	}
	return fs
		.readdirSync( dir, { withFileTypes: true } )
		.filter( ( entry ) => entry.isDirectory() && ( fs.existsSync( path.join( dir, entry.name, 'index.css' ) ) || fs.existsSync( path.join( dir, entry.name, 'index.ts' ) ) ) )
		.map( ( entry ) => entry.name )
		.sort();
}

/**
 * Drops the script or stylesheet of an entry when its file does not exist,
 * so an asset may have only one of them.
 *
 * @param {{name: string, js?: string, css?: string, shared?: boolean}} entry Entry.
 * @return {{name: string, js?: string, css?: string, shared?: boolean}} Entry.
 */
function optional( entry ) {
	const out = { ...entry };
	for ( const key of [ 'js', 'css' ] ) {
		if ( out[ key ] && ! fs.existsSync( path.join( root, out[ key ] ) ) ) {
			delete out[ key ];
		}
	}
	return out;
}

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
	const tailwound = await postcss( [ tailwind( { base: root, optimize: ! dev } ) ] ).process( input, { from, to: out + '.css' } );
	// Admin styles are one file; storefront, module and widget files share layers (see lib/layer-media.mjs).
	const loadsAfterStorefront = /packages\/ui\/(modules|widgets)\//.test( source );
	const plugins = loadsAfterStorefront ? [ layerMedia(), dropKnown( storefrontRules ) ] : [ layerMedia() ];
	const result = source.includes( 'admin' ) ? tailwound : await postcss( plugins ).process( tailwound.css, { from: undefined } );
	if ( source === 'packages/ui/storefront/storefront.css' ) {
		storefrontRules = layeredRuleKeys( result.root );
	}
	// One line ending only: Theme Check rejects files that mix them, and new layer rules use LF.
	const css = result.css.replace( /\r\n/g, '\n' );
	const ltr = rtlcss.process( css );
	write( out + '.css', css );
	write( out + '-ltr.css', ltr );
	return css;
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
 * Converts the Persian help (docs/help/fa) into the JSON the dashboard's
 * help page reads (ADM-06). help.pdf is made from the same files.
 */
export function buildHelp() {
	const source = path.join( root, 'docs', 'help', 'fa' );
	const articles = fs
		.readdirSync( source )
		.filter( ( file ) => file.endsWith( '.md' ) )
		.sort()
		.map( ( file ) => {
			const markdown = fs.readFileSync( path.join( source, file ), 'utf8' );
			return {
				id: file.replace( /^\d+-/, '' ).replace( /\.md$/, '' ),
				title: markdown.match( /^#\s+(.+)$/m )?.[ 1 ]?.trim() ?? file,
				html: marked.parse( markdown, { async: false } ),
			};
		} );
	write( path.join( root, 'plugins', 'ultra-max-core', 'assets', 'build', 'help', 'fa.json' ), JSON.stringify( articles ) );
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
	// The storefront file first: module and widget files leave out what it already has.
	await buildEntry( entries[ 0 ] );
	await Promise.all( entries.slice( 1 ).map( buildEntry ) );
	copyToProducts();
	buildHelp();
	console.log( `Built in ${ ( ( Date.now() - started ) / 1000 ).toFixed( 1 ) }s${ dev ? ' (development)' : '' }.` );
}

await buildAll( ! skipPhp );

if ( watch ) {
	const watched = [ 'packages', 'themes', 'plugins', 'tools', 'docs' ];
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
