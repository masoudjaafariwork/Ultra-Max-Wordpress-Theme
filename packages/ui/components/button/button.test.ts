import { test, expect } from '@playwright/test';
import { openCatalog, expectAccessible, expectLooksRight } from '../test-utils';

test.beforeEach( async ( { page } ) => openCatalog( page ) );

test( 'renders every variant with a visible focus ring', async ( { page } ) => {
	for ( const variant of [ 'primary', 'outline', 'soft', 'ghost', 'neutral', 'accent', 'danger' ] ) {
		await expect( page.getByTestId( `button-${ variant }` ) ).toBeVisible();
	}
	await page.getByTestId( 'button-primary' ).focus();
	await page.keyboard.press( 'Tab' );
	await page.keyboard.press( 'Shift+Tab' );
	const outline = await page.getByTestId( 'button-primary' ).evaluate( ( el ) => getComputedStyle( el ).outlineStyle );
	expect( outline ).not.toBe( 'none' );
} );

test( 'is at least 44px tall at the default size, for touch', async ( { page } ) => {
	const box = await page.getByTestId( 'button-primary' ).boundingBox();
	expect( box?.height ?? 0 ).toBeGreaterThanOrEqual( 44 );
} );

test( 'passes axe', async ( { page } ) => {
	await expectAccessible( page, '#c-button' );
} );

test( 'looks right', async ( { page } ) => {
	await expectLooksRight( page.getByTestId( 'button' ), 'button.png' );
} );