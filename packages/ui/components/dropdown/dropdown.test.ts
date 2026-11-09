import { test, expect } from '@playwright/test';
import { openCatalog, expectAccessible, isRtl } from '../test-utils';

test.beforeEach( async ( { page } ) => openCatalog( page ) );

const button = '#demo-dropdown-button';
const menu = '#demo-dropdown-menu';

test( 'opens next to the button on the start side and reports aria-expanded', async ( { page } ) => {
	await page.click( button );
	await expect( page.locator( menu ) ).toBeVisible();
	await expect( page.locator( button ) ).toHaveAttribute( 'aria-expanded', 'true' );
	const b = await page.locator( button ).boundingBox();
	const m = await page.locator( menu ).boundingBox();
	// Below the button, or above it when there is no room below (Popper flips).
	expect( m && b && ( m.y >= b.y + b.height - 1 || m.y + m.height <= b.y + 1 ) ).toBe( true );
	const rtl = await isRtl( page );
	const aligned = rtl ? Math.abs( ( m?.x ?? 0 ) + ( m?.width ?? 0 ) - ( ( b?.x ?? 0 ) + ( b?.width ?? 0 ) ) ) : Math.abs( ( m?.x ?? 0 ) - ( b?.x ?? 0 ) );
	expect( aligned ).toBeLessThan( 2 );
} );

test( 'closes with Escape and returns focus', async ( { page } ) => {
	await page.click( button );
	await page.keyboard.press( 'Escape' );
	await expect( page.locator( menu ) ).toBeHidden();
	await expect( page.locator( button ) ).toHaveAttribute( 'aria-expanded', 'false' );
	await expect( page.locator( button ) ).toBeFocused();
} );

test( 'closes on an outside click', async ( { page } ) => {
	await page.click( button );
	await page.mouse.click( 2, 2 );
	await expect( page.locator( menu ) ).toBeHidden();
} );

test( 'passes axe while open', async ( { page } ) => {
	await page.click( button );
	await expectAccessible( page, '#c-dropdown' );
} );
