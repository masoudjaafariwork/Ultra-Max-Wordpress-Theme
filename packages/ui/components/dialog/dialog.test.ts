import { test, expect } from '@playwright/test';
import { openCatalog, expectAccessible, expectLooksRight, focusInside } from '../test-utils';

test.beforeEach( async ( { page } ) => openCatalog( page ) );

test( 'opens from its trigger and moves focus inside', async ( { page } ) => {
	await page.click( '#dialog-trigger' );
	await expect( page.locator( '#demo-dialog' ) ).toBeVisible();
	await expect.poll( () => focusInside( page, '#demo-dialog' ) ).toBe( true );
} );

test( 'keeps Tab focus inside while open', async ( { page } ) => {
	await page.click( '#dialog-trigger' );
	for ( let i = 0; i < 6; i++ ) {
		await page.keyboard.press( 'Tab' );
		expect( await focusInside( page, '#demo-dialog' ) ).toBe( true );
	}
} );

test( 'closes with Escape and returns focus to the trigger', async ( { page } ) => {
	await page.click( '#dialog-trigger' );
	await page.keyboard.press( 'Escape' );
	await expect( page.locator( '#demo-dialog' ) ).toBeHidden();
	await expect( page.locator( '#dialog-trigger' ) ).toBeFocused();
} );

test( 'closes from its close button and from a backdrop click', async ( { page } ) => {
	await page.click( '#dialog-trigger' );
	await page.locator( '#demo-dialog' ).getByRole( 'button', { name: /^(Close|بستن)$/ } ).click();
	await expect( page.locator( '#demo-dialog' ) ).toBeHidden();
	await page.click( '#dialog-trigger' );
	await page.mouse.click( 2, 2 );
	await expect( page.locator( '#demo-dialog' ) ).toBeHidden();
} );

test( 'fires um:open and um:close', async ( { page } ) => {
	await page.evaluate( () => {
		const log: string[] = [];
		( window as unknown as { umLog: string[] } ).umLog = log;
		document.addEventListener( 'um:open', () => log.push( 'open' ) );
		document.addEventListener( 'um:close', () => log.push( 'close' ) );
	} );
	await page.click( '#dialog-trigger' );
	await page.keyboard.press( 'Escape' );
	await expect.poll( () => page.evaluate( () => ( window as unknown as { umLog: string[] } ).umLog ) ).toEqual( [ 'open', 'close' ] );
} );

test( 'passes axe while open', async ( { page } ) => {
	await page.click( '#dialog-trigger' );
	await expectAccessible( page, '#demo-dialog' );
} );

test( 'looks right while open', async ( { page } ) => {
	await page.click( '#dialog-trigger' );
	await expectLooksRight( page, 'dialog-open.png' );
} );
