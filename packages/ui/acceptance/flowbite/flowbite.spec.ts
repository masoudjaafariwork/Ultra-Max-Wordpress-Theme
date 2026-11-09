/**
 * Flowbite acceptance test (roadmap section 4, CORE-20).
 *
 * Judges Flowbite's free core, exactly as documented, against what our
 * storefront needs: right-to-left layout, keyboard use as in the WAI-ARIA
 * Authoring Practices, and automatic accessibility checks with axe-core.
 *
 * A check that Flowbite fails is marked `knownGap()`: it stays in the suite
 * as an expected failure, so CI is green today and turns red the day a
 * Flowbite release fixes the gap, which is our cue to re-evaluate. The
 * decision for each component is recorded in packages/ui/acceptance/RESULTS.fa.md.
 */
import { test, expect, type Page } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const fixture = pathToFileURL( path.resolve( import.meta.dirname, 'fixture.html' ) ).href;

/**
 * Marks the current test as a known Flowbite gap.
 *
 * @param reason What Flowbite does not do.
 */
function knownGap( reason: string ): void {
	test.info().annotations.push( { type: 'flowbite-gap', description: reason } );
	test.fail();
}

/**
 * Runs axe-core on part of the page and expects no violations.
 *
 * @param page     Page.
 * @param selector Region to check.
 */
async function expectAccessible( page: Page, selector: string ): Promise<void> {
	const results = await new AxeBuilder( { page } ).include( selector ).withTags( [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa' ] ).analyze();
	expect( results.violations.map( ( v ) => `${ v.id }: ${ v.help }` ) ).toEqual( [] );
}

/**
 * Whether the focused element is inside an element.
 *
 * @param page     Page.
 * @param selector Container.
 */
async function focusInside( page: Page, selector: string ): Promise<boolean> {
	return page.evaluate( ( s ) => Boolean( document.querySelector( s )?.contains( document.activeElement ) ), selector );
}

test.beforeEach( async ( { page } ) => {
	await page.goto( fixture );
	await expect( page.locator( 'html' ) ).toHaveAttribute( 'data-ready', 'true' );
} );

test.describe( 'Modal', () => {
	test( 'opens with role dialog and aria-modal', async ( { page } ) => {
		await page.click( '#modal-trigger' );
		const modal = page.locator( '#modal' );
		await expect( modal ).toBeVisible();
		await expect( modal ).toHaveAttribute( 'role', 'dialog' );
		await expect( modal ).toHaveAttribute( 'aria-modal', 'true' );
	} );

	test( 'closes with Escape', async ( { page } ) => {
		await page.click( '#modal-trigger' );
		await page.keyboard.press( 'Escape' );
		await expect( page.locator( '#modal' ) ).toBeHidden();
	} );

	test( 'moves focus into the dialog when it opens', async ( { page } ) => {
		knownGap( 'Modal leaves focus on the trigger, behind the dialog.' );
		await page.click( '#modal-trigger' );
		await expect.poll( () => focusInside( page, '#modal' ), { timeout: 1000 } ).toBe( true );
	} );

	test( 'keeps Tab focus inside the dialog', async ( { page } ) => {
		knownGap( 'Modal has no focus trap; Tab reaches the page behind it.' );
		await page.click( '#modal-trigger' );
		await page.focus( '#modal-close' );
		await page.keyboard.press( 'Tab' );
		expect( await focusInside( page, '#modal' ) ).toBe( true );
	} );

	test( 'returns focus to the trigger when it closes', async ( { page } ) => {
		knownGap( 'Modal does not restore focus after closing.' );
		await page.click( '#modal-trigger' );
		await page.click( '#modal-close' );
		await expect( page.locator( '#modal-trigger' ) ).toBeFocused();
	} );

	test( 'passes axe while open', async ( { page } ) => {
		await page.click( '#modal-trigger' );
		await expectAccessible( page, '#modal' );
	} );
} );

test.describe( 'Drawer', () => {
	test( 'opens from the right edge, the start side in RTL', async ( { page } ) => {
		await page.click( '#drawer-trigger' );
		const drawer = page.locator( '#drawer' );
		await expect( drawer ).toHaveAttribute( 'aria-modal', 'true' );
		await expect.poll( async () => {
			const box = await drawer.boundingBox();
			return box ? Math.round( box.x + box.width ) : 0;
		} ).toBe( page.viewportSize()?.width ?? 0 );
	} );

	test( 'closes with Escape', async ( { page } ) => {
		await page.click( '#drawer-trigger' );
		await page.keyboard.press( 'Escape' );
		await expect( page.locator( '#drawer' ) ).toHaveAttribute( 'aria-hidden', 'true' );
	} );

	test( 'moves focus into the drawer when it opens', async ( { page } ) => {
		knownGap( 'Drawer leaves focus on the trigger.' );
		await page.click( '#drawer-trigger' );
		await expect.poll( () => focusInside( page, '#drawer' ), { timeout: 1000 } ).toBe( true );
	} );

	test( 'returns focus to the trigger when it closes', async ( { page } ) => {
		knownGap( 'Drawer does not restore focus after closing.' );
		await page.click( '#drawer-trigger' );
		await page.click( '#drawer-close' );
		await expect( page.locator( '#drawer-trigger' ) ).toBeFocused();
	} );

	test( 'passes axe while open', async ( { page } ) => {
		await page.click( '#drawer-trigger' );
		await expectAccessible( page, '#drawer' );
	} );
} );

test.describe( 'Dropdown', () => {
	// Popper placements are physical, so RTL needs "bottom-end" (set in the fixture).
	test( 'opens below the trigger, aligned to the start side in RTL', async ( { page } ) => {
		await page.click( '#dropdown-trigger' );
		const menu = page.locator( '#dropdown' );
		await expect( menu ).toBeVisible();
		const trigger = await page.locator( '#dropdown-trigger' ).boundingBox();
		const box = await menu.boundingBox();
		expect( box && trigger && box.y >= trigger.y + trigger.height - 1 ).toBe( true );
		expect( box && trigger && Math.abs( box.x + box.width - ( trigger.x + trigger.width ) ) < 2 ).toBe( true );
	} );

	test( 'reports its state with aria-expanded', async ( { page } ) => {
		knownGap( 'Dropdown never sets aria-expanded on the trigger.' );
		await page.click( '#dropdown-trigger' );
		await expect( page.locator( '#dropdown-trigger' ) ).toHaveAttribute( 'aria-expanded', 'true' );
	} );

	test( 'closes with Escape and returns focus', async ( { page } ) => {
		knownGap( 'Dropdown ignores Escape.' );
		await page.click( '#dropdown-trigger' );
		await page.keyboard.press( 'Escape' );
		await expect( page.locator( '#dropdown' ) ).toBeHidden();
		await expect( page.locator( '#dropdown-trigger' ) ).toBeFocused();
	} );

	test( 'closes on an outside click', async ( { page } ) => {
		await page.click( '#dropdown-trigger' );
		await page.mouse.click( 5, 5 );
		await expect( page.locator( '#dropdown' ) ).toBeHidden();
	} );

	test( 'passes axe while open', async ( { page } ) => {
		await page.click( '#dropdown-trigger' );
		await expectAccessible( page, '#s-dropdown' );
	} );
} );

test.describe( 'Tabs', () => {
	test( 'switches panels and aria-selected on click', async ( { page } ) => {
		await page.click( '#tab-2' );
		await expect( page.locator( '#panel-2' ) ).toBeVisible();
		await expect( page.locator( '#panel-1' ) ).toBeHidden();
		await expect( page.locator( '#tab-2' ) ).toHaveAttribute( 'aria-selected', 'true' );
	} );

	test( 'moves between tabs with the arrow keys, mirrored in RTL', async ( { page } ) => {
		knownGap( 'Tabs have no arrow-key navigation.' );
		await page.focus( '#tab-1' );
		await page.keyboard.press( 'ArrowLeft' );
		await expect( page.locator( '#tab-2' ) ).toBeFocused();
	} );

	test( 'keeps only the selected tab in the Tab order', async ( { page } ) => {
		knownGap( 'Tabs do not manage tabindex (no roving focus).' );
		await expect( page.locator( '#tab-2' ) ).toHaveAttribute( 'tabindex', '-1' );
	} );

	test( 'passes axe', async ( { page } ) => {
		await expectAccessible( page, '#s-tabs' );
	} );
} );

test.describe( 'Accordion', () => {
	test( 'toggles with click and keyboard and updates aria-expanded', async ( { page } ) => {
		await page.click( '#acc-1' );
		await expect( page.locator( '#acc-1-body' ) ).toBeVisible();
		await expect( page.locator( '#acc-1' ) ).toHaveAttribute( 'aria-expanded', 'true' );
		await page.focus( '#acc-2' );
		await page.keyboard.press( 'Enter' );
		await expect( page.locator( '#acc-2-body' ) ).toBeVisible();
		await expect( page.locator( '#acc-1-body' ) ).toBeHidden();
	} );

	test( 'passes axe', async ( { page } ) => {
		await page.click( '#acc-1' );
		await expectAccessible( page, '#s-accordion' );
	} );
} );

test.describe( 'Tooltip', () => {
	test( 'shows on hover', async ( { page } ) => {
		await page.hover( '#tooltip-trigger' );
		await expect( page.locator( '#tooltip' ) ).toBeVisible();
	} );

	test( 'shows on keyboard focus and hides with Escape', async ( { page } ) => {
		await page.focus( '#tooltip-trigger' );
		await expect( page.locator( '#tooltip' ) ).toBeVisible();
		await page.keyboard.press( 'Escape' );
		await expect( page.locator( '#tooltip' ) ).toBeHidden();
	} );

	test( 'links the tooltip to its trigger with aria-describedby', async ( { page } ) => {
		knownGap( 'Tooltip is not announced: no aria-describedby on the trigger.' );
		await page.hover( '#tooltip-trigger' );
		await expect( page.locator( '#tooltip-trigger' ) ).toHaveAttribute( 'aria-describedby', 'tooltip' );
	} );
} );

test.describe( 'Dismiss', () => {
	test( 'removes the message with its close button', async ( { page } ) => {
		await page.click( '#toast-close' );
		await expect( page.locator( '#toast' ) ).toBeHidden();
	} );

	test( 'passes axe', async ( { page } ) => {
		await expectAccessible( page, '#s-dismiss' );
	} );
} );

test.describe( 'Carousel', () => {
	test( 'shows the next slide', async ( { page } ) => {
		await page.click( '#carousel-next' );
		await expect( page.locator( '[data-carousel-item]' ).nth( 1 ) ).toBeInViewport();
	} );

	test( 'slides in from the left in RTL when moving forward', async ( { page } ) => {
		knownGap( 'Carousel always slides as in LTR; in RTL the motion is reversed.' );
		await page.click( '#carousel-next' );
		// The previous slide must leave to the right (positive translate) in RTL.
		await expect( page.locator( '[data-carousel-item]' ).nth( 0 ) ).toHaveClass( /(^|\s)translate-x-full/ );
	} );

	test( 'passes axe', async ( { page } ) => {
		await expectAccessible( page, '#s-carousel' );
	} );
} );
