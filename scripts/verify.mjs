/**
 * Runs every check of the project on this computer. The project has no
 * hosted CI: the owner decided on 2026-11-09 that nothing runs on GitHub.
 * Run this before every push.
 *
 *   node scripts/verify.mjs          everything (needs Docker)
 *   node scripts/verify.mjs --quick  only the checks that need no site
 *
 * Every step runs even when an earlier one failed, except the steps the
 * later ones depend on (build, site start). The summary at the end lists
 * each step; the exit status is 1 when any step failed.
 */
import { existsSync } from 'node:fs';
import path from 'node:path';
import { root, run } from './lib/run.mjs';

const quick = process.argv.includes( '--quick' );
const hasPhpTools = existsSync( path.join( root, 'vendor', 'bin', 'phpcs' ) );
const PHP_TOOLS_HINT = 'PHP tools are not installed: turn the VPN on and run `npm run composer -- install`.';

const phpunit = [ 'compose', 'run', '--rm', 'cli', 'php', 'wp-content/ultra-max-dev/vendor/bin/phpunit', '-c', 'wp-content/ultra-max-dev/phpunit.xml.dist' ];

/**
 * One step: label, command, arguments and options.
 * `required` stops the run when the step fails; `php` needs the Composer tools.
 *
 * @type {Array<{label: string, command: string, args: string[], required?: boolean, php?: boolean, env?: object}>}
 */
const steps = [
	{ label: 'Build', command: 'npm', args: [ 'run', 'build' ], required: true },
	{ label: 'ESLint', command: 'npm', args: [ 'run', 'lint:js' ] },
	{ label: 'Stylelint', command: 'npm', args: [ 'run', 'lint:css' ] },
	{ label: 'Library boundaries', command: 'npm', args: [ 'run', 'lint:boundaries' ] },
	{ label: 'TypeScript', command: 'npm', args: [ 'run', 'typecheck' ] },
	{ label: 'TypeScript (packages/ui)', command: 'npx', args: [ 'tsc', '--noEmit', '-p', 'packages/ui/tsconfig.json' ] },
	{ label: 'Unit tests', command: 'npm', args: [ 'run', 'test:unit' ] },
	{ label: 'PHPCS', command: 'npm', args: [ 'run', 'lint:php' ], php: true },
	{ label: 'PHPStan', command: 'npm', args: [ 'run', 'analyse:php' ], php: true },
];

if ( ! quick ) {
	steps.push(
		{ label: 'Start the site', command: 'npm', args: [ 'run', 'env:start' ], required: true },
		{ label: 'PHPUnit (PHP 8.1)', command: 'docker', args: phpunit, php: true },
		{ label: 'PHPUnit (PHP 8.4)', command: 'docker', args: phpunit, php: true, env: { CLI_IMAGE: 'wordpress:cli-php8.4' } },
		{ label: 'Flowbite acceptance and end-to-end', command: 'npx', args: [ 'playwright', 'test', '--project=acceptance', '--project=e2e' ] },
		{ label: 'Components and screenshots (Linux)', command: 'npm', args: [ 'run', 'test:visual' ] },
		{ label: 'Plugin Check and Theme Check', command: 'npm', args: [ 'run', 'check:wp' ] },
		{ label: 'Marketplace packages', command: 'npm', args: [ 'run', 'package' ], required: true },
		{ label: 'Install package (rtl-theme)', command: 'npm', args: [ 'run', 'test:package', '--', '--market=rtl-theme' ] },
		{ label: 'Install package (zhaket)', command: 'npm', args: [ 'run', 'test:package', '--', '--market=zhaket' ] }
	);
}

const results = [];
for ( const step of steps ) {
	console.log( `\n=== ${ step.label } ===` );
	const started = Date.now();
	let status;
	if ( step.php && ! hasPhpTools ) {
		console.error( PHP_TOOLS_HINT );
		status = 1;
	} else {
		status = run( step.command, step.args, {
			allowFail: true,
			env: { ...process.env, ...step.env },
		} );
	}
	results.push( { label: step.label, ok: status === 0, seconds: Math.round( ( Date.now() - started ) / 1000 ) } );
	if ( status !== 0 && step.required ) {
		console.error( `\n"${ step.label }" failed; the steps after it need it, so the run stops here.` );
		break;
	}
}

console.log( '\n=== Summary ===' );
for ( const { label, ok, seconds } of results ) {
	console.log( `${ ok ? 'PASS' : 'FAIL' }  ${ label } (${ seconds }s)` );
}
const skipped = steps.length - results.length;
if ( skipped > 0 ) {
	console.log( `SKIP  ${ skipped } later step(s)` );
}
if ( ! hasPhpTools ) {
	console.log( `\n${ PHP_TOOLS_HINT }` );
}
const failed = results.some( ( result ) => ! result.ok ) || skipped > 0;
console.log( failed ? '\nSome checks failed.' : '\nAll checks passed.' );
process.exit( failed ? 1 : 0 );
