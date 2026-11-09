/**
 * Manages the Docker development site.
 *
 *   node scripts/env.mjs start    start, install WordPress on first run, prepare the store
 *   node scripts/env.mjs stop     stop the containers, keep the data
 *   node scripts/env.mjs reset    delete the site's data and start fresh
 *   node scripts/env.mjs cli ...  run WP-CLI, for example `cli plugin list`
 *   node scripts/env.mjs logs     follow the PHP debug log
 */
import { run, capture } from './lib/run.mjs';

const [ command = 'start', ...rest ] = process.argv.slice( 2 );
const port = process.env.WP_PORT ?? '8888';
const url = `http://localhost:${ port }`;

/**
 * Runs WP-CLI in a throwaway container.
 *
 * @param {string[]} args    Arguments after "wp".
 * @param {object}   options Options for run().
 * @return {number} Exit status.
 */
export function wp( args, options = {} ) {
	return run( 'docker', [ 'compose', 'run', '--rm', '--no-deps', 'cli', 'wp', ...args ], options );
}

/**
 * Installs WordPress and prepares a Persian store. Safe to run again.
 */
function prepare() {
	if ( wp( [ 'core', 'is-installed' ], { allowFail: true } ) !== 0 ) {
		wp( [ 'core', 'install', `--url=${ url }`, '--title=Ultra Max', '--admin_user=admin', '--admin_password=password', '--admin_email=admin@example.com', '--skip-email' ] );
	}
	// The Docker image can lag behind WordPress releases; WP_VERSION picks the
	// core version (default: the latest release) and WooCommerce needs a recent one.
	const version = process.env.WP_VERSION ?? 'latest';
	wp( [ 'core', 'update', ...( version === 'latest' ? [] : [ `--version=${ version }`, '--force' ] ) ] );
	wp( [ 'core', 'update-db' ] );
	const quiet = { allowFail: true };
	wp( [ 'plugin', 'install', 'woocommerce', 'elementor', 'plugin-check', 'theme-check' ], quiet );
	wp( [ 'plugin', 'activate', 'woocommerce', 'elementor', 'ultra-max-core', 'ultra-max-auth', 'ultra-max-invoice', 'ultra-max-seo', 'ultra-max-catalog' ] );
	wp( [ 'theme', 'activate', 'ultra-max' ] );
	wp( [ 'language', 'core', 'install', 'fa_IR', '--activate' ], quiet );
	wp( [ 'language', 'plugin', 'install', 'woocommerce', 'fa_IR' ], quiet );
	wp( [ 'language', 'plugin', 'install', 'elementor', 'fa_IR' ], quiet );
	wp( [ 'rewrite', 'structure', '/%postname%/', '--hard' ], quiet );
	wp( [ 'option', 'update', 'timezone_string', 'Asia/Tehran' ], quiet );
	wp( [ 'option', 'update', 'woocommerce_currency', 'IRT' ], quiet );
	wp( [ 'option', 'update', 'woocommerce_default_country', 'IR:THR' ], quiet );
	wp( [ 'option', 'update', 'woocommerce_onboarding_profile', '{"skipped":true}', '--format=json' ], quiet );
	wp( [ 'option', 'update', 'woocommerce_task_list_hidden', 'yes' ], quiet );
	wp( [ 'option', 'update', 'elementor_onboarded', '1' ], quiet );
}

switch ( command ) {
	case 'start':
		run( 'docker', [ 'compose', 'up', '-d', '--wait', 'wordpress' ] );
		prepare();
		console.log( `\nSite:  ${ url }\nAdmin: ${ url }/wp-admin  (user: admin, password: password)\nComponent catalog: ${ url }/?um-catalog` );
		break;
	case 'stop':
		run( 'docker', [ 'compose', 'stop' ] );
		break;
	case 'reset':
		run( 'docker', [ 'compose', 'down', '--volumes' ] );
		run( 'docker', [ 'compose', 'up', '-d', '--wait', 'wordpress' ] );
		prepare();
		break;
	case 'cli':
		process.exit( wp( rest, { allowFail: true } ) );
		break;
	case 'logs':
		run( 'docker', [ 'compose', 'exec', 'wordpress', 'tail', '-f', '/var/www/html/wp-content/debug.log' ], { allowFail: true } );
		break;
	case 'status':
		console.log( capture( 'docker', [ 'compose', 'ps' ] ).stdout );
		break;
	default:
		console.error( `Unknown command "${ command }". Use start, stop, reset, cli, logs or status.` );
		process.exit( 1 );
}