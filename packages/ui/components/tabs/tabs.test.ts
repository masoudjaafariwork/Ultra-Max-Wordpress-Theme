import { test, expect } from '@playwright/test';
import { openCatalog, expectAccessible, expectLooksRight, isRtl } from '../test-utils';

test.beforeEach( async ( { page } ) => openCatalog( page ) );

const tab = ( i: number ) => `#demo-tabs-tab-${ i }`;
const panel = ( i: number ) => `#demo-tabs-panel-${ i }`;

test( 'switches panels on click', async ( { page } ) => {
	await page.click( tab( 1 ) );
	await expect( page.locator( panel( 1 ) ) ).toBeVisible();
	await expect( page.locator( panel( 0 ) ) ).toBeHidden();
	await expect( page.locator( tab( 1 ) ) ).toHaveAttribute( 'aria-selected', 'true' );
} );

test( 'moves with arrow keys mirrored by direction, and Home and End', async ( { page } ) => {
	const next = ( await isRtl( page ) ) ? 'ArrowLeft' : 'ArrowRight';
	await page.focus( tab( 0 ) );
	await page.keyboard.press( next );
	await expect( page.locator( tab( 1 ) ) ).toBeFocused();
	await expect( page.locator( panel( 1 ) ) ).toBeVisible();
	await page.keyboard.press( 'End' );
	await expect( page.locator( tab( 2 ) ) ).toBeFocused();
	await page.keyboard.press( next );
	await expect( page.locator( tab( 0 ) ) ).toBeFocused();
	await page.keyboard.press( 'Home' );
	await expect( page.locator( tab( 0 ) ) ).toBeFocused();
} );

test( 'keeps only the selected tab in the Tab order', async ( { page } ) => {
	await page.click( tab( 2 ) );
	await expect( page.locator( tab( 2 ) ) ).toHaveAttribute( 'tabindex', '0' );
	await expect( page.locator( tab( 0 ) ) ).toHaveAttribute( 'tabindex', '-1' );
} );

test( 'passes axe', async ( { page } ) => {
	await expectAccessible( page, '#c-tabs' );
} );

test( 'looks right', async ( { page } ) => {
	await expectLooksRight( page.getByTestId( 'tabs' ), 'tabs.png' );
} );