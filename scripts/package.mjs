/**
 * Builds the release packages, one per marketplace (REL-01, REL-04).
 *
 * For each market:
 * 1. Copies every product into dist/<market>/stage, without development
 *    files (sources, tests, Composer and npm metadata).
 * 2. Writes the shared packages into each product with that market's
 *    license module, and an optimised, classmap-only Composer autoloader.
 * 3. Checks the staged files: nothing from the forbidden list, the right
 *    license module, an autoloader, no ionCube or other encoding.
 * 4. Zips each product, then the standard bundle: themes, plugins, demos,
 *    help.pdf and the license text.
 *
 * Usage: node scripts/package.mjs [--market=rtl-theme|zhaket] (default: both)
 */
import fs from 'node:fs';
import path from 'node:path';
import yazl from 'yazl';
import { root, run } from './lib/run.mjs';
import { products, childTheme, markets } from './lib/products.mjs';
import { prefixPackages } from './lib/prefix.mjs';
import { dumpAutoload } from './lib/composer.mjs';

const chosen = process.argv.filter( ( a ) => a.startsWith( '--market=' ) ).map( ( a ) => a.slice( 9 ) );
const targets = chosen.length ? chosen : markets;
for ( const market of targets ) {
	if ( ! markets.includes( market ) ) {
		console.error( `Unknown market "${ market }". Use one of: ${ markets.join( ', ' ) }.` );
		process.exit( 1 );
	}
}

/** Paths left out of every product, relative to the product folder. */
const EXCLUDED = /^(vendor|vendor-prefixed|node_modules|assets\/src|tests)(\/|$)|(^|\/)(composer\.lock|package(-lock)?\.json|phpcs\.xml(\.dist)?|\.[^/]+)$|\.map$/;

/** Patterns that must never appear in a shipped file. */
const FORBIDDEN_CONTENT = [ /ionCube/i, /sg_load\(/, /eval\(\s*base64_decode/i ];

const version = fs.readFileSync( path.join( root, 'themes', 'ultra-max', 'style.css' ), 'utf8' ).match( /Version:\s*(\S+)/ )?.[ 1 ] ?? '0.0.0';

/**
 * Lists files below a folder, relative to it, with forward slashes.
 *
 * @param {string} dir Absolute folder.
 * @return {string[]} Relative paths.
 */
function listFiles( dir ) {
	return fs
		.readdirSync( dir, { recursive: true, withFileTypes: true } )
		.filter( ( entry ) => entry.isFile() )
		.map( ( entry ) => path.relative( dir, path.join( entry.parentPath, entry.name ) ).split( path.sep ).join( '/' ) );
}

/**
 * Copies a product into the stage without development files.
 *
 * @param {string} from Product folder.
 * @param {string} to   Stage folder.
 */
function stageProduct( from, to ) {
	fs.rmSync( to, { recursive: true, force: true } );
	for ( const file of listFiles( from ) ) {
		if ( EXCLUDED.test( file ) ) {
			continue;
		}
		fs.mkdirSync( path.dirname( path.join( to, file ) ), { recursive: true } );
		fs.copyFileSync( path.join( from, file ), path.join( to, file ) );
	}
}

/**
 * Zips a folder.
 *
 * @param {string} dir    Folder to zip.
 * @param {string} prefix Folder name inside the zip.
 * @param {string} out    Zip path.
 * @return {Promise<void>}
 */
function zip( dir, prefix, out ) {
	return new Promise( ( resolve, reject ) => {
		const archive = new yazl.ZipFile();
		for ( const file of listFiles( dir ).sort() ) {
			archive.addFile( path.join( dir, file ), prefix ? `${ prefix }/${ file }` : file, { mtime: new Date( '2026-01-01T00:00:00Z' ) } );
		}
		archive.end();
		fs.mkdirSync( path.dirname( out ), { recursive: true } );
		archive.outputStream.pipe( fs.createWriteStream( out ) ).on( 'close', resolve ).on( 'error', reject );
	} );
}

/**
 * Checks a staged product and returns the problems found.
 *
 * @param {string} dir    Staged product.
 * @param {string} market Market.
 * @param {boolean} php   Whether the product has a PHP autoloader.
 * @return {string[]} Problems.
 */
function verify( dir, market, php ) {
	const problems = [];
	const files = listFiles( dir );
	if ( php ) {
		if ( files.includes( 'composer.json' ) ) {
			problems.push( 'composer.json shipped' );
		}
		if ( ! files.includes( 'vendor/autoload.php' ) ) {
			problems.push( 'missing vendor/autoload.php' );
		}
		const license = path.join( dir, 'vendor-prefixed', 'license', market, 'MarketLicense.php' );
		if ( ! fs.existsSync( license ) || ! fs.readFileSync( license, 'utf8' ).includes( `'${ market }'` ) ) {
			problems.push( `missing the ${ market } license module` );
		}
		if ( files.some( ( f ) => f.startsWith( 'vendor-prefixed/license/' ) && ! f.includes( `/${ market }/` ) && f.includes( 'MarketLicense' ) ) ) {
			problems.push( 'contains another market\'s license module' );
		}
	}
	for ( const file of files ) {
		if ( /\.(ts|tsx|scss|md)$/.test( file ) && ! file.startsWith( 'vendor/' ) ) {
			problems.push( `development file shipped: ${ file }` );
		}
		if ( /\.(php|js)$/.test( file ) ) {
			const content = fs.readFileSync( path.join( dir, file ), 'utf8' );
			if ( FORBIDDEN_CONTENT.some( ( pattern ) => pattern.test( content ) ) ) {
				problems.push( `encoded or obfuscated code in ${ file }` );
			}
		}
	}
	return problems.map( ( p ) => `${ path.basename( dir ) }: ${ p }` );
}

// Production assets and the help file, shared by every market.
run( 'node', [ 'scripts/build.mjs', '--skip-php' ] );
run( 'node', [ 'scripts/help-pdf.mjs', '--out=dist/help.pdf' ] );

for ( const market of targets ) {
	const base = path.join( root, 'dist', market );
	const stage = path.join( base, 'stage' );
	fs.rmSync( base, { recursive: true, force: true } );
	const problems = [];

	for ( const product of products ) {
		const to = path.join( stage, product.slug );
		stageProduct( path.join( root, product.dir ), to );
		prefixPackages( product, market, to );
	}
	dumpAutoload( products.map( ( product ) => path.relative( root, path.join( stage, product.slug ) ).split( path.sep ).join( '/' ) ), [ '--classmap-authoritative', '--no-dev' ] );
	// composer.json was only needed to write the autoloader.
	for ( const product of products ) {
		fs.rmSync( path.join( stage, product.slug, 'composer.json' ) );
	}
	stageProduct( path.join( root, childTheme.dir ), path.join( stage, childTheme.slug ) );

	for ( const product of products ) {
		problems.push( ...verify( path.join( stage, product.slug ), market, true ) );
	}
	problems.push( ...verify( path.join( stage, childTheme.slug ), market, false ) );
	if ( problems.length ) {
		problems.forEach( ( p ) => console.error( `  ${ p }` ) );
		console.error( `\nPackage for ${ market } failed its checks.` );
		process.exit( 1 );
	}

	const bundle = path.join( base, 'bundle' );
	for ( const item of [ ...products, childTheme ] ) {
		const folder = item.type === 'theme' ? 'themes' : 'plugins';
		await zip( path.join( stage, item.slug ), item.slug, path.join( bundle, folder, `${ item.slug }.zip` ) );
	}
	fs.copyFileSync( path.join( root, 'dist', 'help.pdf' ), path.join( bundle, 'help.pdf' ) );
	fs.mkdirSync( path.join( bundle, 'demos' ), { recursive: true } );
	fs.writeFileSync( path.join( bundle, 'demos', 'README.txt' ), 'Demo packages (Duplicator) are added in phase 9.\nبسته‌های دمو (Duplicator) در فاز ۹ اضافه می‌شوند.\n' );
	fs.writeFileSync(
		path.join( bundle, 'license.txt' ),
		'Ultra Max theme and plugins are released under the GNU General Public License, version 2 or later.\nhttps://www.gnu.org/licenses/gpl-2.0.html\n\nBundled third-party resources and their licences are listed in each readme.txt.\n'
	);
	const bundleZip = path.join( base, `ultra-max-${ version }-${ market }.zip` );
	await zip( bundle, '', bundleZip );

	const size = ( fs.statSync( bundleZip ).size / 1024 ).toFixed( 0 );
	console.log( `\n${ market }: ${ path.relative( root, bundleZip ) } (${ size } KB)` );
	for ( const file of listFiles( bundle ).sort() ) {
		console.log( `  ${ file }` );
	}
}
