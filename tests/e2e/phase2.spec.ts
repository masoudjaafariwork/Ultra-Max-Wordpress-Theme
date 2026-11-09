/**
 * Phase 2 end to end: the theme core on the storefront and the Ultra Max
 * panel in the dashboard. Settings are written with WP-CLI before each
 * group and restored afterwards, so the suite leaves the site as it was.
 */
import { test, expect, type Page } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { execFileSync } from 'node:child_process';
import { adminState } from './global-setup';

/**
 * Runs WP-CLI on the test site.
 *
 * @param args Arguments after "wp".
 */
function wp( ...args: string[] ): string {
	return execFileSync( 'docker', [ 'compose', 'run', '--rm', '--no-deps', '-T', 'cli', 'wp', ...args ], { encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'ignore' ] } ).trim();
}

/**
 * Reads an option as JSON text, or "{}" when it does not exist.
 *
 * @param name Option name.
 */
function getOption( name: string ): string {
	try {
		return wp( 'option', 'get', name, '--format=json' ) || '{}';
	} catch {
		return '{}';
	}
}

/**
 * Replaces the stored settings and module switches.
 *
 * @param settings Values that differ from the defaults.
 * @param modules  Module switches.
 */
function setOptions( settings: Record<string, unknown>, modules: Record<string, boolean> = {} ): void {
	wp( 'option', 'update', 'ultra_max_settings', JSON.stringify( settings ), '--format=json' );
	wp( 'option', 'update', 'ultra_max_modules', JSON.stringify( modules ), '--format=json' );
}

let saved = { settings: '{}', modules: '{}' };
let postUrl = '';

test.beforeAll( () => {
	saved = { settings: getOption( 'ultra_max_settings' ), modules: getOption( 'ultra_max_modules' ) };
	const id = wp( 'post', 'create', '--post_title=Phase two post', '--post_content=<p>Hello</p>', '--post_status=publish', '--post_date=2026-11-09 10:00:00', '--porcelain' );
	postUrl = wp( 'post', 'url', id );
} );

test.afterAll( () => {
	wp( 'option', 'update', 'ultra_max_settings', saved.settings, '--format=json' );
	wp( 'option', 'update', 'ultra_max_modules', saved.modules, '--format=json' );
	const ids = wp( 'post', 'list', '--title=Phase two post', '--post_type=post', '--format=ids' );
	if ( ids ) {
		wp( 'post', 'delete', ...ids.split( ' ' ), '--force' );
	}
} );

/**
 * Whether the page scrolls sideways.
 *
 * @param page Page.
 */
const overflows = ( page: Page ) => page.evaluate( () => document.documentElement.scrollWidth > window.innerWidth + 1 );

test.describe.serial( 'storefront', () => {
	test( 'post shows a Jalali date in Persian digits and a valid datetime (CORE-03, CORE-04)', async ( { page } ) => {
		setOptions( {} );
		await page.goto( postUrl );
		const time = page.locator( '.um-page-header time' ).first();
		await expect( time ).toHaveText( /۱۴۰۵/ );
		await expect( time ).toHaveAttribute( 'datetime', /^2026-11-09T/ );
		await expect( page.locator( 'nav[aria-label] ol' ).first() ).toBeVisible();
	} );

	test( 'colour preset, radius and boxed layout change the page (CORE-06, CORE-07)', async ( { page } ) => {
		setOptions( { color_preset: 'ocean', radius: 4, site_layout: 'boxed' } );
		await page.goto( '/' );
		const vars = await page.evaluate( () => {
			const style = getComputedStyle( document.documentElement );
			return { brand: style.getPropertyValue( '--um-brand-600' ).trim(), radius: style.getPropertyValue( '--um-radius-lg' ).trim() };
		} );
		expect( vars ).toEqual( { brand: '#1a64cc', radius: '4px' } );
		await expect( page.locator( 'body' ) ).toHaveClass( /um-boxed/ );
	} );

	test( 'announcement bar closes and stays closed (CORE-10)', async ( { page } ) => {
		setOptions( { notice_text: 'Free shipping today', notice_link_label: 'Shop', notice_link_url: 'http://localhost:8888/' } );
		await page.goto( '/' );
		const bar = page.locator( '#um-notice-bar' );
		await expect( bar ).toBeVisible();
		await bar.getByRole( 'button' ).click();
		await expect( bar ).toBeHidden();
		await page.reload();
		await expect( page.locator( '#um-notice-bar' ) ).toBeHidden();
	} );

	test( 'announcement bar respects its end date and its module switch (CORE-10, ADM-07)', async ( { page } ) => {
		setOptions( { notice_text: 'Old sale', notice_end: '1400-01-01' } );
		await page.goto( '/' );
		await expect( page.locator( '#um-notice-bar' ) ).toHaveCount( 0 );
		setOptions( { notice_text: 'Sale' }, { 'notice-bar': false } );
		await page.goto( '/' );
		await expect( page.locator( '#um-notice-bar' ) ).toHaveCount( 0 );
		await expect( page.locator( 'link#ultra-max-ui-notice-bar-css' ) ).toHaveCount( 0 );
	} );

	test( 'contact buttons open with the keyboard and close with Escape (CORE-11)', async ( { page } ) => {
		setOptions( {
			contact_items: [
				{ type: 'whatsapp', value: '09120000000', label: '' },
				{ type: 'telegram', value: '@shop', label: '' },
			],
		} );
		await page.goto( '/' );
		const toggle = page.locator( '#um-contact button[aria-controls]' );
		await toggle.focus();
		await page.keyboard.press( 'Enter' );
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( page.locator( '#um-contact a[href="https://wa.me/989120000000"]' ) ).toBeVisible();
		await page.keyboard.press( 'Escape' );
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
	} );

	test( 'back to top appears after scrolling (CORE-12)', async ( { page } ) => {
		setOptions( {} );
		await page.setViewportSize( { width: 1440, height: 500 } );
		await page.goto( postUrl );
		await page.evaluate( () => {
			document.body.style.minHeight = '3000px';
			window.scrollTo( 0, 1200 );
		} );
		await expect( page.locator( '[data-um-component="back-to-top"]' ) ).toHaveAttribute( 'data-visible', 'true' );
	} );

	test( '404 page offers search and the home page (CORE-09)', async ( { page } ) => {
		const response = await page.goto( '/this-page-does-not-exist-um/' );
		expect( response?.status() ).toBe( 404 );
		await expect( page.locator( 'main form[role="search"]' ) ).toBeVisible();
		await expect( page.locator( 'main h1' ) ).toBeVisible();
	} );

	test( 'maintenance mode answers 503 to visitors only (CORE-09)', async ( { page, browser } ) => {
		setOptions( { maintenance_mode: 'maintenance' } );
		const response = await page.goto( '/' );
		expect( response?.status() ).toBe( 503 );
		await expect( page.locator( 'h1' ) ).toBeVisible();
		const admin = await browser.newContext( { storageState: adminState } );
		const adminPage = await admin.newPage();
		expect( ( await adminPage.goto( '/' ) )?.status() ).toBe( 200 );
		await admin.close();
		setOptions( {} );
	} );

	test( 'storefront has no axe violations and no sideways scroll on a phone', async ( { page } ) => {
		setOptions( { notice_text: 'Sale', contact_items: [ { type: 'phone', value: '02100000000', label: '' } ] } );
		for ( const width of [ 1440, 390 ] ) {
			await page.setViewportSize( { width, height: 844 } );
			for ( const url of [ '/', postUrl, '/?s=hello' ] ) {
				await page.goto( url );
				expect( await overflows( page ), `${ url } at ${ width }px` ).toBe( false );
			}
		}
		await page.goto( postUrl );
		const results = await new AxeBuilder( { page } ).include( '.um-scope' ).exclude( '#wpadminbar' ).analyze();
		expect( results.violations.map( ( violation ) => violation.id ) ).toEqual( [] );
	} );

	test( 'theme works without WooCommerce and Elementor (phase 2 exit criterion)', async ( { page } ) => {
		setOptions( {} );
		wp( 'plugin', 'deactivate', 'ultra-max-core', 'ultra-max-auth', 'ultra-max-invoice', 'ultra-max-seo', 'woocommerce', 'elementor' );
		try {
			for ( const url of [ '/', postUrl, '/?s=a', '/missing-page-um/' ] ) {
				const response = await page.goto( url );
				expect( [ 200, 404 ] ).toContain( response?.status() );
				await expect( page.locator( '.um-site-header' ) ).toBeVisible();
			}
		} finally {
			wp( 'plugin', 'activate', 'woocommerce', 'elementor', 'ultra-max-core', 'ultra-max-auth', 'ultra-max-invoice', 'ultra-max-seo' );
			// Reactivating the core plugin asks for the first-run wizard; this test is not a first run.
			wp( 'transient', 'delete', 'ultra_max_wizard_redirect' );
		}
	} );
} );

test.describe.serial( 'panel', () => {
	test.use( { storageState: adminState } );

	test( 'settings: search jumps to a setting and saving changes the store (ADM-01, CORE-06)', async ( { page } ) => {
		setOptions( {} );
		wp( 'user', 'meta', 'update', 'admin', 'ultra_max_tours_seen', '["settings"]', '--format=json' );
		await page.goto( '/wp-admin/admin.php?page=ultra-max-settings' );
		const search = page.getByRole( 'combobox', { name: 'جستجو در همه تنظیمات' } );
		await expect( search ).toBeVisible();
		await expect( page.getByRole( 'button', { name: 'رد کردن راهنما' } ) ).toHaveCount( 0 );
		await search.click();
		await page.keyboard.type( 'گوشه' );
		await page.getByRole( 'option' ).first().click();
		await expect( page ).toHaveURL( /#appearance$/ );
		await page.getByRole( 'radio', { name: 'زمردی' } ).click();
		await expect( page.getByText( /تغییر ذخیره نشده/ ) ).toBeVisible();
		await page.getByRole( 'button', { name: 'ذخیره تغییرات' } ).click();
		await expect( page.getByText( 'تنظیمات ذخیره شد' ) ).toBeVisible();
		expect( getOption( 'ultra_max_settings' ) ).toContain( '"color_preset":"emerald"' );
	} );

	test( 'settings: backups restore the previous values (ADM-02)', async ( { page } ) => {
		setOptions( { radius: 6 } );
		wp( 'user', 'meta', 'update', 'admin', 'ultra_max_tours_seen', '["settings"]', '--format=json' );
		await page.goto( '/wp-admin/admin.php?page=ultra-max-settings#appearance' );
		await page.getByRole( 'button', { name: 'بازنشانی این بخش' } ).click();
		await page.getByRole( 'dialog' ).getByRole( 'button', { name: 'بازنشانی این بخش' } ).click();
		await expect( page.getByText( 'این بخش بازنشانی شد' ) ).toBeVisible();
		await page.getByRole( 'navigation', { name: 'بخش‌های تنظیمات' } ).getByRole( 'link', { name: 'پشتیبان و بازنشانی' } ).click();
		await page.getByRole( 'button', { name: 'بازگردانی' } ).first().click();
		await page.getByRole( 'dialog' ).getByRole( 'button', { name: 'بازگردانی' } ).click();
		await expect( page.getByText( 'پشتیبان بازگردانی شد' ) ).toBeVisible();
		expect( getOption( 'ultra_max_settings' ) ).toContain( '"radius":6' );
	} );

	test( 'settings: the tour runs once (ADM-06)', async ( { page } ) => {
		wp( 'user', 'meta', 'delete', 'admin', 'ultra_max_tours_seen' );
		await page.goto( '/wp-admin/admin.php?page=ultra-max-settings' );
		await expect( page.getByText( 'قدم ۱ از ۴' ).or( page.getByText( 'قدم 1 از 4' ) ) ).toBeVisible();
		await page.getByRole( 'button', { name: 'رد کردن راهنما' } ).click();
		await page.reload();
		await expect( page.getByRole( 'button', { name: 'رد کردن راهنما' } ) ).toHaveCount( 0 );
	} );

	test( 'modules: switching a module off saves it (ADM-07)', async ( { page } ) => {
		setOptions( {} );
		await page.goto( '/wp-admin/admin.php?page=ultra-max-modules' );
		const card = page.locator( '[data-module="back-to-top"]' );
		await card.getByRole( 'switch' ).click();
		await expect( card ).toHaveAttribute( 'data-on', 'false' );
		expect( getOption( 'ultra_max_modules' ) ).toContain( '"back-to-top":false' );
	} );

	test( 'dashboard shows licence, health and system status (ADM-04, ADM-05, ADM-10)', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=ultra-max' );
		await expect( page.locator( '#um-status table tr' ) ).not.toHaveCount( 0 );
		await expect( page.locator( '#um-changelog li' ).first() ).toBeVisible();
	} );

	test( 'wizard moves through its steps (ADM-03)', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=ultra-max-wizard' );
		await page.getByRole( 'button', { name: 'شروع' } ).click();
		await expect( page.getByRole( 'heading', { name: 'افزونه‌های لازم' } ) ).toBeVisible();
		await page.getByRole( 'button', { name: 'رد کردن این مرحله' } ).click();
		await page.getByRole( 'button', { name: 'رد کردن این مرحله' } ).click();
		await expect( page.getByRole( 'heading', { name: 'لوگو و رنگ فروشگاه' } ) ).toBeVisible();
	} );

	test( 'help shows the Persian articles (ADM-06)', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=ultra-max-help#settings' );
		await expect( page.getByRole( 'heading', { name: 'پنل تنظیمات' } ) ).toBeVisible();
	} );

	test( 'posts list offers Duplicate (ADM-09)', async ( { page } ) => {
		await page.goto( '/wp-admin/edit.php' );
		await expect( page.locator( '.row-actions .ultra_max_duplicate a' ).first() ).toHaveCount( 1 );
	} );
} );
