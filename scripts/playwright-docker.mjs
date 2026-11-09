/**
 * Runs the component tests inside Playwright's official Linux image, on the
 * Docker network of the development site. Screenshots are compared only
 * here and in CI, so approved images never depend on the developer's
 * operating system or fonts.
 *
 * Usage:
 *   node scripts/playwright-docker.mjs                     compare with the approved images
 *   node scripts/playwright-docker.mjs --update-snapshots  approve the current look
 */
import { root, run } from './lib/run.mjs';

const IMAGE = 'mcr.microsoft.com/playwright:v1.64.1-noble';
const extra = process.argv.slice( 2 );

run( 'docker', [
	'run', '--rm', '--ipc=host',
	'--network', 'ultra-max_default',
	'-v', `${ root.replaceAll( '\\', '/' ) }:/work`,
	'-w', '/work',
	'-e', 'WP_BASE_URL=http://wordpress',
	'-e', 'CI=1',
	IMAGE,
	'npx', 'playwright', 'test', '--project=components-desktop', '--project=components-mobile', '--reporter=line', ...extra,
] );