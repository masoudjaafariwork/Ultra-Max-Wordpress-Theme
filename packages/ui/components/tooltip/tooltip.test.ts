import { test, expect } from '@playwright/test';
import { openCatalog, expectAccessible } from '../test-utils';

test.beforeEach( async ( { page } ) => openCatalog( page ) );

test( 'describes its trigger for screen readers', async ( { page } ) => {
	await expect( page.locator( '#tooltip-trigger' ) ).toHaveAttribute( 'aria-describedby', 'demo-tooltip' );
} );

test( 'shows on hover and on keyboard focus, hides with Escape', async ( { page, isMobile } ) => {
	test.skip( isMobile, 'Touch screens have no hover; the description is still read aloud.' );
	await page.hover( '#tooltip-trigger' );
	await expect( page.locator( '#demo-tooltip' ) ).toBeVisible();
	await page.mouse.move( 0, 0 );
	await expect( page.locator( '#demo-tooltip' ) ).toBeHidden();
	await page.focus( '#tooltip-trigger' );
	await expect( page.locator( '#demo-tooltip' ) ).toBeVisible();
	await page.keyboard.press( 'Escape' );
	await expect( page.locator( '#demo-tooltip' ) ).toBeHidden();
} );

test( 'passes axe', async ( { page } ) => {
	await expectAccessible( page, '#c-tooltip' );
} );
