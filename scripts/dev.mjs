/**
 * One command for a working development environment:
 * checks the prerequisites, builds everything and starts the Docker site.
 *
 * Usage: npm run dev
 */
import fs from 'node:fs';
import path from 'node:path';
import { capture, root, run } from './lib/run.mjs';

if ( ! fs.existsSync( path.join( root, 'node_modules' ) ) ) {
	console.error( 'Run `npm ci` first.' );
	process.exit( 1 );
}
if ( capture( 'docker', [ 'info', '--format', '{{.ServerVersion}}' ] ).status !== 0 ) {
	console.error( 'Docker is not running. Start Docker Desktop and run `npm run dev` again.' );
	process.exit( 1 );
}

run( 'node', [ 'scripts/build.mjs', '--dev' ] );
run( 'node', [ 'scripts/env.mjs', 'start' ] );

console.log( '\nReady. Keep `npm run watch` open while editing to rebuild on every change.' );