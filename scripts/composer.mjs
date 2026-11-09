/**
 * Runs Composer, or a tool Composer installed, without PHP on the host.
 *
 * Uses the host's `composer` when it exists (as in CI); otherwise runs the
 * official Composer Docker image with the repository mounted at /app.
 *
 * Usage:
 *   node scripts/composer.mjs install
 *   node scripts/composer.mjs exec -- phpcs
 *   node scripts/composer.mjs --dir=plugins/ultra-max-core dump-autoload
 */
import { composer } from './lib/composer.mjs';

const args = process.argv.slice( 2 );
let dir = '.';
const rest = [];
for ( const arg of args ) {
	if ( arg.startsWith( '--dir=' ) ) {
		dir = arg.slice( 6 );
	} else {
		rest.push( arg );
	}
}

// `exec -- tool args` runs a tool from vendor/bin.
if ( rest[ 0 ] === 'exec' ) {
	const tool = rest.slice( rest[ 1 ] === '--' ? 2 : 1 );
	process.exit( composer( [ 'exec', '--', ...tool ], { dir, allowFail: true } ) );
}
process.exit( composer( rest, { dir, allowFail: true } ) );
