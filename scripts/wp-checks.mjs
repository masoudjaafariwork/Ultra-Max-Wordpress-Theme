/**
 * Runs WordPress's own review tools on the products, on the Docker site:
 * Plugin Check on each plugin and Theme Check on the theme. Fails on any
 * error (Plugin Check) or REQUIRED / WARNING item (Theme Check), which is
 * what marketplace reviewers look at first.
 */
import { spawnSync } from 'node:child_process';
import { root } from './lib/run.mjs';
import { products } from './lib/products.mjs';

/**
 * Runs WP-CLI and returns its output.
 *
 * @param {string[]} args Arguments after "wp".
 * @return {string} Standard output.
 */
function wp( args ) {
	const result = spawnSync( 'docker', [ 'compose', 'run', '--rm', '--no-deps', '-T', 'cli', 'wp', ...args ], { cwd: root, encoding: 'utf8' } );
	return `${ result.stdout ?? '' }`;
}

wp( [ 'plugin', 'activate', 'plugin-check', 'theme-check' ] );

let failures = 0;
for ( const product of products.filter( ( p ) => p.type === 'plugin' ) ) {
	const out = wp( [ 'plugin', 'check', product.slug, '--format=csv', '--fields=line,type,code,message', '--exclude-directories=vendor,vendor-prefixed,assets/build' ] );
	const rows = out.split( '\n' ).filter( ( line ) => /,(ERROR|WARNING),/.test( line ) );
	const errors = rows.filter( ( line ) => line.includes( ',ERROR,' ) );
	console.log( `Plugin Check ${ product.slug }: ${ errors.length } error(s), ${ rows.length - errors.length } warning(s).` );
	rows.forEach( ( row ) => console.log( `  ${ row }` ) );
	failures += errors.length;
}

let items;
try {
	items = JSON.parse( wp( [ 'theme-check', 'run', 'ultra-max', '--format=json' ] ).trim() );
} catch {
	console.error( 'Theme Check did not return a result.' );
	process.exit( 1 );
}
const blocking = items.filter( ( item ) => [ 'REQUIRED', 'WARNING' ].includes( item.type ) );
console.log( `Theme Check ultra-max: ${ items.length } item(s), ${ blocking.length } required or warning.` );
blocking.forEach( ( item ) => console.log( `  ${ item.type }: ${ item.value }` ) );
failures += blocking.length;

if ( failures ) {
	console.error( `\n${ failures } blocking item(s).` );
	process.exit( 1 );
}
console.log( '\nWordPress checks passed.' );