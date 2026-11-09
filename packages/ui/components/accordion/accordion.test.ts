import { test, expect } from '@playwright/test';
import { openCatalog, expectAccessible, expectLooksRight } from '../test-utils';

test.beforeEach( async ( { page } ) => openCatalog( page ) );

test( 'opens one item at a time and reports aria-expanded', async ( { page } ) => {
	await page.click( '#demo-accordion-button-0' );
	await expect( page.locator( '#demo-accordion-panel-0' ) ).toBeVisible();
	await expect( page.locator( '#demo-accordion-button-0' ) ).toHaveAttribute( 'aria-expanded', 'true' );
	await page.focus( '#demo-accordion-button-1' );
	await page.keyboard.press( 'Enter' );
	await expect( page.locator( '#demo-accordion-panel-1' ) ).toBeVisible();
	await expect( page.locator( '#demo-accordion-panel-0' ) ).toBeHidden();
} );

test( 'passes axe', async ( { page } ) => {
	await page.click( '#demo-accordion-button-0' );
	await expectAccessible( page, '#c-accordion' );
} );

test( 'looks right', async ( { page } ) => {
	await expectLooksRight( page.getByTestId( 'accordion' ), 'accordion.png' );
} );