/**
 * Composer runner: host Composer when available, Docker otherwise.
 */
import { capture, root, run } from './run.mjs';

export const COMPOSER_IMAGE = 'composer:2.8';

let hostComposer;

/**
 * Whether Composer and PHP exist on the host.
 *
 * @return {boolean} True when the host can run Composer itself.
 */
function hasHostComposer() {
	if ( hostComposer === undefined ) {
		hostComposer = capture( 'composer', [ '--version' ] ).status === 0;
	}
	return hostComposer;
}

/**
 * Runs Composer in one folder of the repository.
 *
 * @param {string[]} args    Composer arguments.
 * @param {object}   options `dir` relative to the root, `allowFail` as in run().
 * @return {number} Exit status.
 */
export function composer( args, options = {} ) {
	const { dir = '.', allowFail = false } = options;
	if ( hasHostComposer() ) {
		return run( 'composer', [ `--working-dir=${ dir }`, ...args ], { allowFail } );
	}
	return docker( [ 'composer', `--working-dir=${ dir }`, ...args ], { allowFail } );
}

/**
 * Runs a shell command inside the Composer image, with the repository at /app.
 *
 * @param {string[]} command Command and arguments.
 * @param {object}   options `allowFail` as in run().
 * @return {number} Exit status.
 */
export function docker( command, options = {} ) {
	const mount = root.replaceAll( '\\', '/' );
	return run(
		'docker',
		[
			'run', '--rm',
			'-v', `${ mount }:/app`,
			'-v', 'ultra-max-composer-cache:/tmp/cache',
			'-e', 'COMPOSER_CACHE_DIR=/tmp/cache',
			'-e', 'COMPOSER_ALLOW_SUPERUSER=1',
			'-w', '/app',
			COMPOSER_IMAGE,
			...command,
		],
		options
	);
}

/**
 * Runs `composer dump-autoload` in several folders with one container start.
 *
 * @param {string[]} dirs  Folders relative to the root.
 * @param {string[]} extra Extra dump-autoload arguments.
 */
export function dumpAutoload( dirs, extra = [] ) {
	if ( hasHostComposer() ) {
		for ( const dir of dirs ) {
			run( 'composer', [ `--working-dir=${ dir }`, 'dump-autoload', '--quiet', ...extra ] );
		}
		return;
	}
	const script = dirs
		.map( ( dir ) => `composer --working-dir=${ dir } dump-autoload --quiet ${ extra.join( ' ' ) }` )
		.join( ' && ' );
	docker( [ 'sh', '-c', script ] );
}
