/**
 * Small helpers shared by the scripts in this folder.
 */
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

export const root = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..', '..' );

const isWindows = process.platform === 'win32';

/**
 * On Windows, tools such as npx are .cmd files that need a shell. Node
 * warns when arguments are passed next to shell: true, so the command is
 * joined into one string with each argument quoted.
 *
 * @param {string}   command Program.
 * @param {string[]} args    Arguments.
 * @return {[string, string[]]} Command and arguments for spawnSync.
 */
function forShell( command, args ) {
	if ( ! isWindows ) {
		return [ command, args ];
	}
	const quote = ( arg ) => ( /^[\w@%+=:,./\\-]+$/.test( arg ) ? arg : `"${ arg.replaceAll( '"', '\\"' ) }"` );
	return [ [ command, ...args ].map( quote ).join( ' ' ), [] ];
}

/**
 * Runs a command, streams its output and stops the script when it fails.
 *
 * @param {string}   command Program to run.
 * @param {string[]} args    Arguments.
 * @param {object}   options Extra spawn options; `allowFail` returns the status instead of exiting.
 * @return {number} Exit status.
 */
export function run( command, args = [], options = {} ) {
	const { allowFail = false, ...spawnOptions } = options;
	const [ cmd, cmdArgs ] = forShell( command, args );
	const result = spawnSync( cmd, cmdArgs, {
		cwd: root,
		stdio: 'inherit',
		shell: isWindows,
		...spawnOptions,
	} );
	const status = result.status ?? 1;
	if ( status !== 0 && ! allowFail ) {
		console.error( `\nFailed (${ status }): ${ command } ${ args.join( ' ' ) }` );
		process.exit( status );
	}
	return status;
}

/**
 * Runs a command and returns its trimmed standard output.
 *
 * @param {string}   command Program to run.
 * @param {string[]} args    Arguments.
 * @return {{status: number, stdout: string}} Result.
 */
export function capture( command, args = [] ) {
	const [ cmd, cmdArgs ] = forShell( command, args );
	const result = spawnSync( cmd, cmdArgs, {
		cwd: root,
		encoding: 'utf8',
		shell: isWindows,
	} );
	return { status: result.status ?? 1, stdout: ( result.stdout ?? '' ).trim() };
}
