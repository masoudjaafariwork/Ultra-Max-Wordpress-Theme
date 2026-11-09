/**
 * Enforces the library boundary rule on the whole repository.
 * See scripts/lib/boundaries.mjs for what the rule checks.
 */
import fs from 'node:fs';
import path from 'node:path';
import { root } from './lib/run.mjs';
import { checkSource } from './lib/boundaries.mjs';

const scanned = [ 'themes', 'plugins', 'tools', 'packages' ];
const extensions = new Set( [ '.php', '.ts', '.tsx', '.js', '.mjs', '.html', '.css' ] );
const skipped = /(^|\/)(node_modules|vendor|vendor-prefixed|build|languages)(\/|$)/;

const violations = [];
for ( const dir of scanned ) {
	for ( const entry of fs.readdirSync( path.join( root, dir ), { recursive: true, withFileTypes: true } ) ) {
		if ( ! entry.isFile() || ! extensions.has( path.extname( entry.name ) ) ) {
			continue;
		}
		const file = path.relative( root, path.join( entry.parentPath, entry.name ) ).split( path.sep ).join( '/' );
		if ( skipped.test( file ) ) {
			continue;
		}
		violations.push( ...checkSource( file, fs.readFileSync( path.join( root, file ), 'utf8' ) ) );
	}
}

for ( const v of violations ) {
	console.error( `${ v.file }:${ v.line }  ${ v.message }` );
}
if ( violations.length ) {
	console.error( `\n${ violations.length } boundary violation(s).` );
	process.exit( 1 );
}
console.log( 'Library boundaries: no violations.' );
