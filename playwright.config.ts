import { defineConfig, devices } from '@playwright/test';

/**
 * Three suites:
 * - acceptance: Flowbite judged on its own, from a static page (no WordPress).
 * - components: our components on the live catalog, desktop and mobile.
 * - e2e: theme and plugins on the wp-env test site.
 *
 * WordPress suites need `npm run env:start` first. WP_BASE_URL overrides the site.
 */
const baseURL = process.env.WP_BASE_URL ?? 'http://localhost:8888';
// The Playwright browser download is blocked in some regions. Locally on
// Windows we use the installed Microsoft Edge (same Chromium engine); CI
// installs Playwright's Chromium. PW_CHANNEL overrides either way.
const channel = process.env.PW_CHANNEL ?? ( ! process.env.CI && process.platform === 'win32' ? 'msedge' : undefined );
const desktop = { ...devices[ 'Desktop Chrome' ], channel, viewport: { width: 1440, height: 900 }, locale: 'fa-IR' };
const mobile = { ...devices[ 'Pixel 7' ], channel, viewport: { width: 390, height: 844 }, locale: 'fa-IR' };

export default defineConfig( {
	forbidOnly: Boolean( process.env.CI ),
	retries: process.env.CI ? 1 : 0,
	workers: process.env.CI ? 2 : undefined,
	reporter: process.env.CI ? [ [ 'github' ], [ 'html', { open: 'never' } ] ] : [ [ 'list' ] ],
	// The seeded demo store makes the development server slower; WP-CLI calls in tests add a few seconds each.
	timeout: 60_000,
	expect: { toHaveScreenshot: { maxDiffPixelRatio: 0.01, animations: 'disabled' } },
	snapshotPathTemplate: '{testDir}/__screenshots__/{projectName}/{arg}{ext}',
	use: { baseURL, trace: 'retain-on-failure' },
	globalSetup: './tests/e2e/global-setup.ts',
	projects: [
		{ name: 'acceptance', testDir: 'packages/ui/acceptance', testMatch: '**/*.spec.ts', use: desktop },
		{ name: 'components-desktop', testDir: 'packages/ui/components', testMatch: '**/*.test.ts', use: desktop },
		{ name: 'components-mobile', testDir: 'packages/ui/components', testMatch: '**/*.test.ts', use: mobile },
		{ name: 'e2e', testDir: 'tests/e2e', testMatch: '**/*.spec.ts', use: desktop },
	],
} );
