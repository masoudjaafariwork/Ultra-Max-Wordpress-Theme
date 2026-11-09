/**
 * Helpers shared by the component tests. Every test runs on the live
 * catalog (/?um-catalog), rendered by the same PHP the products use.
 */
import { expect, type Locator, type Page } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

/**
 * Opens the catalog and waits until the behaviours are mounted.
 *
 * @param page Page.
 */
export async function openCatalog( page: Page ): Promise<void> {
	await page.goto( '/?um-catalog' );
	await page.waitForFunction( () => Boolean( window.UltraMaxUI ) && document.readyState === 'complete' );
}

/**
 * Runs axe-core with the WCAG 2.1 AA rules on part of the page.
 *
 * @param page     Page.
 * @param selector Region to check.
 */
export async function expectAccessible( page: Page, selector: string ): Promise<void> {
	const results = await new AxeBuilder( { page } ).include( selector ).withTags( [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa' ] ).analyze();
	expect( results.violations.map( ( v ) => `${ v.id }: ${ v.help } (${ v.nodes.map( ( n ) => n.target.join( ' ' ) ).join( ', ' ) })` ) ).toEqual( [] );
}

/**
 * Compares a screenshot with the approved one.
 *
 * Fonts render differently on each operating system, so approved images
 * are made and compared on Linux only: in CI, or locally with
 * `npm run test:visual:update` (Playwright's Docker image).
 *
 * @param target Element or page.
 * @param name   Image name.
 */
export async function expectLooksRight( target: Locator | Page, name: string ): Promise<void> {
	if ( process.platform !== 'linux' ) {
		return;
	}
	await expect( target ).toHaveScreenshot( name );
}

/**
 * Whether the focused element is inside an element.
 *
 * @param page     Page.
 * @param selector Container.
 */
export function focusInside( page: Page, selector: string ): Promise<boolean> {
	return page.evaluate( ( s ) => Boolean( document.querySelector( s )?.contains( document.activeElement ) ), selector );
}

/**
 * Whether the page is laid out right to left.
 *
 * @param page Page.
 */
export function isRtl( page: Page ): Promise<boolean> {
	return page.evaluate( () => getComputedStyle( document.body ).direction === 'rtl' );
}