/**
 * Installs a release package on a fresh, throwaway WordPress site, exactly
 * as a buyer would, and checks that everything activates and renders
 * without PHP errors. Run `npm run package` first.
 *
 * Usage: node scripts/test-package.mjs [--market=rtl-theme]
 */
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { root, run } from './lib/run.mjs';

const market = ( process.argv.find( ( a ) => a.startsWith( '--market=' ) ) ?? '--market=rtl-theme' ).slice( 9 );
const bundle = path.join( root, 'dist', market, 'bundle' );
if ( ! fs.existsSync( bundle ) ) {
	console.error( `No package for ${ market }. Run: npm run package -- --market=${ market }` );
	process.exit( 1 );
}

const compose = [ 'compose', '-f', 'docker/package-test.yml' ];
const wp = ( args, options = {} ) => run( 'docker', [ ...compose, 'run', '--rm', '--no-deps', 'cli', 'wp', ...args ], options );
const zipPath = ( folder, slug ) => `/dist/${ market }/bundle/${ folder }/${ slug }.zip`;

let failed = false;
try {
	run( 'docker', [ ...compose, 'up', '-d', '--wait', 'wordpress' ] );
	wp( [ 'core', 'install', '--url=http://localhost:8890', '--title=Package test', '--admin_user=admin', '--admin_password=password', '--admin_email=admin@example.com', '--skip-email' ] );
	wp( [ 'core', 'update' ] );
	wp( [ 'plugin', 'install', 'woocommerce', '--activate' ] );
	for ( const slug of [ 'ultra-max-core', 'ultra-max-auth', 'ultra-max-invoice', 'ultra-max-seo' ] ) {
		wp( [ 'plugin', 'install', zipPath( 'plugins', slug ), '--activate' ] );
	}
	wp( [ 'theme', 'install', zipPath( 'themes', 'ultra-max' ) ] );
	wp( [ 'theme', 'install', zipPath( 'themes', 'ultra-max-child' ), '--activate' ] );

	// Docker Desktop can take a moment to publish the port; retry briefly.
	let home;
	for ( let attempt = 0; attempt < 30 && ! home; attempt++ ) {
		home = await fetch( 'http://127.0.0.1:8890/' ).catch( () => new Promise( ( resolve ) => setTimeout( () => resolve( undefined ), 1000 ) ) );
	}
	if ( ! home ) {
		throw new Error( 'The package test site did not answer on port 8890.' );
	}
	const html = await home.text();
	if ( home.status !== 200 || ! html.includes( 'ultra-max-ui-css' ) ) {
		console.error( `Home page: HTTP ${ home.status }, component library ${ html.includes( 'ultra-max-ui-css' ) ? 'loaded' : 'missing' }.` );
		failed = true;
	}
	const log = spawnSync( 'docker', [ ...compose, 'exec', '-T', 'wordpress', 'sh', '-c', 'cat /var/www/html/wp-content/debug.log 2>/dev/null || true' ], { cwd: root, encoding: 'utf8' } ).stdout ?? '';
	const ours = log.split( '\n' ).filter( ( line ) => /ultra-max/i.test( line ) );
	if ( ours.length ) {
		console.error( 'PHP messages from Ultra Max code:\n' + ours.join( '\n' ) );
		failed = true;
	}
	wp( [ 'plugin', 'list', '--fields=name,status,version' ] );
} finally {
	run( 'docker', [ ...compose, 'down', '--volumes' ], { allowFail: true } );
}

if ( failed ) {
	process.exit( 1 );
}
console.log( `\nThe ${ market } package installs and runs on a fresh site.` );