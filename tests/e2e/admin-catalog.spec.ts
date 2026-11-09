/**
 * Admin component library (shadcn/ui behind @ultra-max/ui/admin), on the
 * catalog screen in wp-admin with WordPress's own React.
 */
import { test, expect, type Page } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import fs from 'node:fs';
import { adminState } from './global-setup';

test.skip( () => ! fs.existsSync( adminState ), 'The site was not reachable during global setup.' );
test.use( { storageState: adminState } );

let errors: string[] = [];

/**
 * Opens the admin catalog and records console errors.
 *
 * @param page Page.
 */
async function open( page: Page ): Promise<void> {
	errors = [];
	page.on( 'console', ( m ) => m.type() === 'error' && errors.push( m.text() ) );
	page.on( 'pageerror', ( e ) => errors.push( e.message ) );
	await page.goto( '/wp-admin/tools.php?page=ultra-max-catalog' );
	await expect( page.getByTestId( 'admin-button' ) ).toBeVisible();
}

test.beforeEach( async ( { page } ) => open( page ) );
test.afterEach( () => expect( errors ).toEqual( [] ) );

test( 'keeps our look against wp-admin styles', async ( { page } ) => {
	const button = page.getByTestId( 'admin-button' ).getByRole( 'button', { name: 'ذخیره' } );
	const bg = await button.evaluate( ( el ) => getComputedStyle( el ).backgroundColor );
	expect( bg ).toBe( 'rgb(76, 66, 211)' );
	const input = page.locator( '#store-name' );
	const radius = await input.evaluate( ( el ) => getComputedStyle( el ).borderTopLeftRadius );
	expect( radius ).not.toBe( '4px' );
} );

test( 'switch toggles with click and Space and reports its state', async ( { page } ) => {
	const sw = page.locator( '#module-invoice' );
	await expect( sw ).toHaveAttribute( 'aria-checked', 'false' );
	await page.getByText( 'ماژول فاکتور' ).click();
	await expect( sw ).toHaveAttribute( 'aria-checked', 'true' );
	await sw.focus();
	await page.keyboard.press( 'Space' );
	await expect( sw ).toHaveAttribute( 'aria-checked', 'false' );
} );

test( 'tabs move with arrow keys mirrored in RTL', async ( { page } ) => {
	const tabs = page.getByTestId( 'admin-tabs' ).getByRole( 'tab' );
	await tabs.nth( 0 ).focus();
	await page.keyboard.press( 'ArrowLeft' );
	await expect( tabs.nth( 1 ) ).toBeFocused();
	await expect( tabs.nth( 1 ) ).toHaveAttribute( 'aria-selected', 'true' );
} );

test( 'dialog traps focus, closes with Escape and returns focus', async ( { page } ) => {
	const trigger = page.getByRole( 'button', { name: 'بازنشانی تنظیمات' } );
	await trigger.click();
	const dialog = page.getByRole( 'dialog' );
	await expect( dialog ).toBeVisible();
	for ( let i = 0; i < 5; i++ ) {
		await page.keyboard.press( 'Tab' );
		expect( await dialog.evaluate( ( el ) => el.contains( document.activeElement ) ) ).toBe( true );
	}
	await page.keyboard.press( 'Escape' );
	await expect( dialog ).toBeHidden();
	await expect( trigger ).toBeFocused();
} );

test( 'passes axe', async ( { page } ) => {
	const results = await new AxeBuilder( { page } ).include( '#um-admin-catalog' ).withTags( [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa' ] ).analyze();
	expect( results.violations.map( ( v ) => `${ v.id }: ${ v.help }` ) ).toEqual( [] );
} );
