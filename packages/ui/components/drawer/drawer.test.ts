import { test, expect } from '@playwright/test';
import { openCatalog, expectAccessible, expectLooksRight, focusInside, isRtl } from '../test-utils';

test.beforeEach( async ( { page } ) => openCatalog( page ) );

test( 'slides in from the start edge', async ( { page } ) => {
	await page.click( '#drawer-trigger' );
	const drawer = page.locator( '#demo-drawer' );
	await expect( drawer ).toBeVisible();
	const width = page.viewportSize()?.width ?? 0;
	const rtl = await isRtl( page );
	await expect.poll( async () => {
		const box = await drawer.boundingBox();
		return box ? Math.round( rtl ? box.x + box.width : box.x ) : -1;
	} ).toBe( rtl ? width : 0 );
} );

test( 'moves focus inside, closes with Escape and returns focus', async ( { page } ) => {
	await page.click( '#drawer-trigger' );
	await expect.poll( () => focusInside( page, '#demo-drawer' ) ).toBe( true );
	await page.keyboard.press( 'Escape' );
	await expect( page.locator( '#demo-drawer' ) ).toBeHidden();
	await expect( page.locator( '#drawer-trigger' ) ).toBeFocused();
} );

test( 'passes axe while open', async ( { page } ) => {
	await page.click( '#drawer-trigger' );
	await expectAccessible( page, '#demo-drawer' );
} );

test( 'looks right while open', async ( { page } ) => {
	await page.click( '#drawer-trigger' );
	await page.waitForTimeout( 400 );
	await expectLooksRight( page, 'drawer-open.png' );
} );
