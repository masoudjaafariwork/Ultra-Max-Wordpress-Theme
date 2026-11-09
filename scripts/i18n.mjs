/**
 * Translation pipeline: for every product, extract a .pot with WP-CLI,
 * merge it into the Persian .po, then compile .mo, .l10n.php (WordPress
 * 6.5+ fast format) and .json files for scripts.
 *
 * Needs the Docker site running (npm run env:start) and a fresh build, so
 * that the shared packages' strings in vendor-prefixed/ are included.
 *
 * Translators edit only languages/*.po (for example with Poedit).
 */
import fs from 'node:fs';
import path from 'node:path';
import { root, run } from './lib/run.mjs';
import { products, childTheme } from './lib/products.mjs';

const LOCALE = 'fa_IR';

/**
 * Runs WP-CLI in the cli container.
 *
 * @param {string[]} args Arguments after "wp".
 */
function wp( args ) {
	run( 'docker', [ 'compose', 'run', '--rm', '--no-deps', 'cli', 'wp', ...args ] );
}

/**
 * Path of a product inside the container.
 *
 * @param {{slug: string, type: string}} product Product.
 */
const containerDir = ( product ) => `wp-content/${ product.type }s/${ product.type === 'plugin' && product.slug === 'ultra-max-catalog' ? 'ultra-max-catalog' : product.slug }`;

for ( const product of [ ...products, childTheme ] ) {
	const dir = path.join( root, product.dir, 'languages' );
	fs.mkdirSync( dir, { recursive: true } );
	const inside = containerDir( product );
	const pot = `${ inside }/languages/${ product.slug }.pot`;
	// Themes load <locale>.mo from their folder; plugins load <domain>-<locale>.mo.
	const poName = product.type === 'theme' ? `${ LOCALE }.po` : `${ product.slug }-${ LOCALE }.po`;
	const po = path.join( dir, poName );

	wp( [ 'i18n', 'make-pot', inside, pot, `--slug=${ product.slug }`, `--domain=${ product.slug }`, '--exclude=node_modules,vendor,assets/build,tests', '--skip-audit' ] );

	if ( ! fs.existsSync( po ) ) {
		const template = fs.readFileSync( path.join( dir, `${ product.slug }.pot` ), 'utf8' );
		fs.writeFileSync(
			po,
			template
				.replace( /"Language: \\n"\n?/, '' )
				.replace( '"Content-Type: text/plain; charset=UTF-8\\n"', `"Content-Type: text/plain; charset=UTF-8\\n"\n"Language: ${ LOCALE }\\n"\n"Plural-Forms: nplurals=2; plural=(n > 1);\\n"` )
		);
	}
	wp( [ 'i18n', 'update-po', pot, `${ inside }/languages/${ poName }` ] );
	wp( [ 'i18n', 'make-mo', `${ inside }/languages` ] );
	wp( [ 'i18n', 'make-php', `${ inside }/languages` ] );
	wp( [ 'i18n', 'make-json', `${ inside }/languages`, '--no-purge' ] );
}

console.log( '\nTranslations updated. Edit languages/*.po and run `npm run i18n` again to compile.' );
