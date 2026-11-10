/**
 * End-to-end checks of the test site: the theme and the four plugins are
 * installed and active, pages render without PHP notices or console errors,
 * and the component library works next to WooCommerce and Elementor.
 */
import { test, expect, type Page } from '@playwright/test';
import { execSync } from 'node:child_process';
import fs from 'node:fs';
import { adminState } from './global-setup';

/**
 * Reads the PHP debug log of the Docker site.
 */
function debugLog(): string {
	try {
		return execSync( 'docker compose exec -T wordpress sh -c "cat /var/www/html/wp-content/debug.log 2>/dev/null || true"', { encoding: 'utf8' } );
	} catch {
		return '';
	}
}

/**
 * Collects console errors and uncaught exceptions of a page.
 *
 * @param page Page.
 */
function collectErrors( page: Page ): string[] {
	const errors: string[] = [];
	page.on( 'console', ( message ) => {
		if ( message.type() === 'error' ) {
			errors.push( message.text() );
		}
	} );
	page.on( 'pageerror', ( error ) => errors.push( error.message ) );
	return errors;
}

let logBefore = '';
test.beforeAll( () => {
	logBefore = debugLog();
} );

test.afterAll( () => {
	const added = debugLog().slice( logBefore.length );
	const ours = added.split( '\n' ).filter( ( line ) => /ultra-max/i.test( line ) );
	expect( ours, 'PHP notices, warnings or errors from Ultra Max code' ).toEqual( [] );
} );

test.describe( 'storefront', () => {
	test( 'renders the home page with the Ultra Max theme, right to left', async ( { page } ) => {
		const errors = collectErrors( page );
		const response = await page.goto( '/' );
		expect( response?.status() ).toBe( 200 );
		await expect( page.locator( 'html' ) ).toHaveAttribute( 'dir', 'rtl' );
		await expect( page.locator( 'html' ) ).toHaveAttribute( 'lang', 'fa-IR' );
		// The storefront stylesheet is printed inline as critical CSS (PERF-02), or linked when that setting is off.
		await expect( page.locator( 'link#ultra-max-ui-css, style#ultra-max-ui-css' ) ).toHaveCount( 1 );
		await expect( page.locator( 'script#ultra-max-ui-js' ) ).toHaveCount( 1 );
		// The theme's own header, or a header designed with the layout builder (phase 3).
		await expect( page.locator( 'header.um-site-header:visible, header.um-layout-header:visible' ).first() ).toBeVisible();
		expect( errors ).toEqual( [] );
	} );

	test( 'loads the right-to-left stylesheet on a Persian site', async ( { page } ) => {
		await page.goto( '/' );
		const link = page.locator( 'link#ultra-max-ui-css' );
		if ( await link.count() ) {
			const href = await link.getAttribute( 'href' );
			expect( href ).toMatch( /ui\/storefront\.css/ );
			expect( href ).not.toMatch( /-ltr\.css/ );
			return;
		}
		// Printed inline as critical CSS (PERF-02): it must be the right-to-left build.
		const inline = ( await page.locator( 'style#ultra-max-ui-css' ).textContent() ) ?? '';
		const base = '/wp-content/themes/ultra-max/assets/build/ui/';
		const rtl = await ( await page.request.get( base + 'storefront.css' ) ).text();
		const ltr = await ( await page.request.get( base + 'storefront-ltr.css' ) ).text();
		const rtlOnly = rtl.split( '}' ).find( ( rule ) => rule.length > 20 && ! ltr.includes( rule ) && ! rule.includes( 'url(' ) );
		expect( rtlOnly, 'a rule that differs between the two builds' ).toBeTruthy();
		expect( inline ).toContain( rtlOnly );
	} );

	test( 'runs the components next to WooCommerce and Elementor without errors', async ( { page } ) => {
		const errors = collectErrors( page );
		await page.goto( '/?um-catalog' );
		await page.waitForFunction( () => Boolean( window.UltraMaxUI ) );
		await page.click( '#dialog-trigger' );
		await expect( page.locator( '#demo-dialog' ) ).toBeVisible();
		await page.keyboard.press( 'Escape' );
		await page.click( '#demo-dropdown-button' );
		await expect( page.locator( '#demo-dropdown-menu' ) ).toBeVisible();
		expect( errors ).toEqual( [] );
	} );

	test( 'uses the Persian translations shipped with the products', async ( { page } ) => {
		await page.goto( '/?um-catalog' );
		await expect( page.locator( '#demo-dialog [data-um-close]' ).first() ).toHaveAttribute( 'aria-label', 'بستن' );
		await expect( page.locator( 'a[href="#um-main"]' ).first() ).toHaveText( 'رفتن به محتوا' );
	} );

	test( 'shop page renders under the theme', async ( { page } ) => {
		const errors = collectErrors( page );
		const response = await page.goto( '/?post_type=product' );
		expect( response?.status() ).toBe( 200 );
		expect( errors ).toEqual( [] );
	} );
} );

test.describe( 'admin', () => {
	test.skip( () => ! fs.existsSync( adminState ), 'The site was not reachable during global setup.' );
	test.use( { storageState: adminState } );

	test( 'lists the four plugins as active', async ( { page } ) => {
		await page.goto( '/wp-admin/plugins.php' );
		for ( const slug of [ 'ultra-max-core', 'ultra-max-auth', 'ultra-max-invoice', 'ultra-max-seo' ] ) {
			await expect( page.locator( `tr[data-plugin="${ slug }/${ slug }.php"]` ) ).toHaveClass( /\bactive\b/ );
		}
		// Other plugins keep hidden, empty notice templates; only ours matter.
		await expect( page.locator( '.notice-error', { hasText: /Ultra Max|ultra-max|الترا مکس/i } ) ).toHaveCount( 0 );
		await expect( page.locator( 'tr[data-plugin="ultra-max-core/ultra-max-core.php"] .plugin-title strong' ) ).toHaveText( 'هسته الترا مکس' );
	} );

	test( 'shows the theme as active and offers the child theme', async ( { page } ) => {
		await page.goto( '/wp-admin/themes.php' );
		await expect( page.locator( '.theme.active[data-slug="ultra-max"]' ) ).toBeVisible();
		await expect( page.locator( '.theme[data-slug="ultra-max-child"]' ) ).toBeVisible();
	} );

	test( 'opens the dashboard without PHP errors on screen', async ( { page } ) => {
		const errors = collectErrors( page );
		await page.goto( '/wp-admin/' );
		await expect( page.locator( '#wpbody' ) ).toBeVisible();
		await expect( page.locator( 'body' ) ).not.toContainText( 'Fatal error' );
		expect( errors.filter( ( e ) => /ultra-max|UltraMax/i.test( e ) ) ).toEqual( [] );
	} );
} );
