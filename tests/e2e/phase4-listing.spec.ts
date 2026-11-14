/**
 * Phase 4, part A end to end: product lists and search on the demo store
 * (PLP-01 to PLP-13, SRCH-01 to SRCH-04, BLD-05). The demo store comes
 * from tools/seed/seed.php; settings and modules are restored afterwards.
 */
import { test, expect, type Page } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { execFileSync } from 'node:child_process';

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
 * Collects console errors of a page.
 *
 * @param page Page.
 */
function watchErrors( page: Page ): string[] {
	const errors: string[] = [];
	page.on( 'console', ( message ) => message.type() === 'error' && errors.push( message.text() ) );
	page.on( 'pageerror', ( error ) => errors.push( String( error ) ) );
	return errors;
}

/**
 * Marks the page, so a test can tell an in-place update from a reload.
 *
 * @param page Page.
 */
const mark = ( page: Page ) => page.evaluate( () => ( ( window as unknown as { umMark: number } ).umMark = 1 ) );
const marked = ( page: Page ) => page.evaluate( () => ( window as unknown as { umMark?: number } ).umMark === 1 );

/**
 * Opens a folded filter group and ticks one of its values.
 *
 * @param page  Page.
 * @param group Group title.
 * @param value Value label.
 */
async function choose( page: Page, group: string, value: string ): Promise<void> {
	const details = page.locator( '#um-shop-filters details', { has: page.locator( 'summary', { hasText: group } ) } );
	if ( ( await details.getAttribute( 'open' ) ) === null ) {
		await details.locator( 'summary' ).click();
	}
	await details.locator( 'label', { hasText: value } ).click();
}

const mobile = { width: 390, height: 844 };
const digital = `/product-category/${ encodeURIComponent( 'کالای-دیجیتال' ) }/`;
let saved = { settings: '{}', modules: '{}' };

test.beforeAll( () => {
	test.setTimeout( 240_000 );
	saved = { settings: getOption( 'ultra_max_settings' ), modules: getOption( 'ultra_max_modules' ) };
	wp( 'option', 'update', 'ultra_max_modules', '{}', '--format=json' );
	if ( wp( 'eval', 'echo (int) wc_get_product_id_by_sku( "UM-RUN" );' ) === '0' ) {
		wp( 'eval-file', 'wp-content/ultra-max-dev/tools/seed/seed.php' );
	}
	wp( 'eval-file', 'wp-content/ultra-max-dev/tests/e2e/fixtures/hidden-products.php' );
	wp( 'transient', 'delete', '--all' );
} );

test.afterAll( () => {
	wp( 'eval-file', 'wp-content/ultra-max-dev/tests/e2e/fixtures/hidden-products.php', 'remove' );
	wp( 'option', 'update', 'ultra_max_settings', saved.settings, '--format=json' );
	wp( 'option', 'update', 'ultra_max_modules', saved.modules, '--format=json' );
} );

test.describe.serial( 'product lists', () => {
	test( 'a category shows its intro, subcategories, filters, products and guide (PLP-07, PLP-09, PLP-10)', async ( { page } ) => {
		const errors = watchErrors( page );
		await page.goto( digital );
		const area = page.locator( '[data-um-component="shop"]' );
		await expect( area.locator( '.um-archive-intro h1' ) ).toHaveText( 'کالای دیجیتال' );
		await expect( area.locator( '.um-subcategories a' ).first() ).toBeVisible();
		await expect( area.locator( 'form#um-shop-filters' ) ).toBeVisible();
		await expect( area.locator( '#um-shop-grid .um-product-card' ).first() ).toBeVisible();
		const guide = area.locator( '.um-seo-text' );
		await expect( guide ).toContainText( 'راهنمای خرید کالای دیجیتال' );
		const more = guide.locator( 'button[aria-controls]' );
		await expect( more ).toBeVisible();
		await more.click();
		await expect( more ).toHaveAttribute( 'aria-expanded', 'true' );
		expect( errors ).toEqual( [] );
	} );

	test( 'choosing a brand filters in place, with a chip that removes it (PLP-01, PLP-12)', async ( { page } ) => {
		await page.goto( digital );
		await mark( page );
		const grid = page.locator( '#um-shop-grid' );
		const before = await grid.locator( '.um-product-card' ).count();
		await page.locator( '#um-shop-filters label', { hasText: 'لومو' } ).click();
		await expect( page ).toHaveURL( /brand=/ );
		await expect.poll( () => grid.locator( '.um-product-card' ).count() ).toBeLessThan( before );
		await expect( grid.locator( '.um-product-card' ).first() ).toContainText( 'لومو' );
		expect( await marked( page ) ).toBe( true );
		const chip = page.locator( '.um-active-filters a', { hasText: 'لومو' } ).first();
		await expect( chip ).toBeVisible();
		await chip.click();
		await expect( page ).not.toHaveURL( /brand=/ );
		await expect.poll( () => grid.locator( '.um-product-card' ).count() ).toBe( before );
		expect( await marked( page ) ).toBe( true );
		await page.goBack();
		await expect( page ).toHaveURL( /brand=/ );
		await expect.poll( () => grid.locator( '.um-product-card' ).count() ).toBeLessThan( before );
	} );

	test( 'colour swatches and the "only discounted" switch filter too (PLP-01)', async ( { page } ) => {
		await page.goto( digital );
		await choose( page, 'رنگ', 'فیروزه‌ای' );
		await expect( page ).toHaveURL( /filter_color=/ );
		await expect( page.locator( '#um-shop-grid .um-product-card' ) ).toHaveCount( 2 );
		await page.locator( '#um-shop-filters label', { hasText: /discounted|تخفیف/ } ).click();
		await expect( page ).toHaveURL( /on_sale=1/ );
		await expect( page.locator( '#um-shop-grid .um-product-card' ) ).toHaveCount( 1 );
	} );

	test( 'the price range filters by price (PLP-01)', async ( { page } ) => {
		await page.goto( digital );
		const field = page.locator( '#um-shop-filters input[name="max_price"]' );
		await field.fill( '2000000' );
		await field.press( 'Enter' );
		await expect( page ).toHaveURL( /max_price=2000000/ );
		const titles = await page.locator( '#um-shop-grid .um-pc-title' ).allTextContents();
		expect( titles.length ).toBeGreaterThan( 0 );
		expect( titles.join( ' ' ) ).not.toContain( 'لپ‌تاپ' );
	} );

	test( 'sorting by price orders the list, in place (PLP-03)', async ( { page } ) => {
		await page.goto( '/shop/' );
		await mark( page );
		await page.locator( '.um-sort-bar a', { hasText: /Cheapest|ارزان/ } ).click();
		await expect( page ).toHaveURL( /orderby=price/ );
		await expect( page.locator( '.um-sort-bar a[aria-current="true"]' ) ).toHaveText( /Cheapest|ارزان/ );
		await expect( page.locator( '#um-shop-grid .um-pc-title' ).first() ).toContainText( 'آبمیوه' );
		expect( await marked( page ) ).toBe( true );
	} );

	test( '"show more" adds the next page below the list (PLP-04)', async ( { page } ) => {
		await page.goto( '/shop/?per_page=12' );
		const grid = page.locator( '#um-shop-grid' );
		await expect( grid.locator( '.um-product-card' ) ).toHaveCount( 12 );
		await expect( page.locator( '.um-load-more nav[aria-label] a' ).first() ).toBeVisible();
		await page.locator( 'a[data-um-load-more]' ).click();
		await expect( grid.locator( '.um-product-card' ) ).toHaveCount( 19 );
		await expect( page.locator( 'a[data-um-load-more]' ) ).toHaveCount( 0 );
		await expect( page ).toHaveURL( /per_page=12$/ );
	} );

	test( 'the view switch shows rows and is remembered (PLP-05)', async ( { page } ) => {
		await page.goto( '/shop/' );
		await page.locator( 'button[data-um-view="list"]' ).click();
		await expect( page.locator( '#um-shop-grid' ) ).toHaveAttribute( 'data-view', 'list' );
		await expect( page.locator( '#um-shop-grid .um-pc-details' ).first() ).toBeVisible();
		await page.reload();
		await expect( page.locator( '#um-shop-grid' ) ).toHaveAttribute( 'data-view', 'list' );
		await page.locator( 'button[data-um-view="grid"]' ).click();
		await expect( page.locator( '#um-shop-grid' ) ).toHaveAttribute( 'data-view', 'grid' );
	} );

	test( 'phones filter and sort in bottom sheets (PLP-06)', async ( { page } ) => {
		await page.setViewportSize( mobile );
		await page.goto( digital );
		await expect( page.locator( '#um-shop-filters' ) ).toBeHidden();
		await page.locator( '.um-shop-bar button[data-um-open="um-shop-filters-sheet"]' ).click();
		const sheet = page.locator( '#um-shop-filters-sheet' );
		await expect( sheet ).toBeVisible();
		await sheet.locator( 'label', { hasText: /discounted|تخفیف/ } ).click();
		await expect( page ).toHaveURL( /on_sale=1/ );
		await expect( sheet ).toBeVisible();
		await expect( page.locator( '.um-shop-bar button[data-um-open="um-shop-filters-sheet"]' ) ).toContainText( /[1۱]/ );
		await sheet.locator( '[data-um-shop-part="sheet-foot"] button' ).click();
		await expect( sheet ).toBeHidden();
		await page.locator( '.um-shop-bar button[data-um-open="um-shop-sort-sheet"]' ).click();
		await page.locator( '#um-shop-sort-sheet a', { hasText: /expensive|گران/ } ).click();
		await expect( page ).toHaveURL( /orderby=price-desc/ );
		await expect( page.locator( '#um-shop-sort-sheet' ) ).toBeHidden();
		expect( await page.evaluate( () => document.documentElement.scrollWidth > window.innerWidth + 1 ) ).toBe( false );
	} );

	test( 'a brand page shows the brand header and no brand filter (PLP-11)', async ( { page } ) => {
		await page.goto( '/shop/' );
		const link = await page.locator( '#um-shop-filters label', { hasText: 'نوویکس' } ).count();
		expect( link ).toBe( 1 );
		const brandUrl = wp( 'eval', 'echo get_term_link( get_term_by( "name", "نوویکس", "product_brand" ) );' );
		await page.goto( brandUrl );
		await expect( page.locator( '.um-archive-intro h1' ) ).toHaveText( 'نوویکس' );
		await expect( page.locator( '#um-shop-filters input[name="brand[]"]' ) ).toHaveCount( 0 );
	} );

	test( 'filter addresses: one value is indexed, combinations are not (PLP-13)', async ( { page, request } ) => {
		const one = await request.get( `${ digital }?brand=${ encodeURIComponent( 'لومو' ) }` );
		const oneHtml = await one.text();
		expect( oneHtml ).not.toMatch( /<meta name=['"]robots['"][^>]*noindex/ );
		expect( oneHtml ).toMatch( /<link rel="canonical" href="[^"]*brand=/ );

		const two = await request.get( `${ digital }?brand=${ encodeURIComponent( 'لومو' ) },${ encodeURIComponent( 'نوویکس' ) }&orderby=price` );
		const twoHtml = await two.text();
		expect( twoHtml ).toMatch( /<meta name=['"]robots['"][^>]*noindex, follow/ );
		expect( twoHtml ).toMatch( /<link rel="canonical" href="[^"?]*\/">/ );

		// A form sent without JavaScript is tidied to the one address.
		await page.goto( `${ digital }?brand[]=${ encodeURIComponent( 'نوویکس' ) }&brand[]=${ encodeURIComponent( 'لومو' ) }&in_stock=1&min_price=` );
		expect( decodeURIComponent( page.url() ) ).toMatch( /\?brand=لومو,نوویکس&in_stock=1$/ );
	} );

	test( 'the swatch card shows each colour\'s picture (PLP-07)', async ( { page } ) => {
		wp( 'option', 'update', 'ultra_max_settings', JSON.stringify( { ...JSON.parse( saved.settings === '{}' ? '{}' : saved.settings ), shop_card: 'swatch' } ), '--format=json' );
		await page.goto( '/shop/?per_page=96' );
		const card = page.locator( '.um-product-card', { hasText: 'Run' } ).first();
		const image = card.locator( '[data-um-card-image] img' );
		const first = await image.getAttribute( 'src' );
		await card.locator( '[data-um-swatch]' ).nth( 1 ).click();
		await expect( card.locator( '[data-um-swatch]' ).nth( 1 ) ).toHaveAttribute( 'aria-pressed', 'true' );
		await expect.poll( () => image.getAttribute( 'src' ) ).not.toBe( first );
		await expect( card.locator( 'li.line-through' ).first() ).toHaveText( 'S' );
		wp( 'option', 'update', 'ultra_max_settings', saved.settings, '--format=json' );
	} );

	test( 'private, draft, locked and hidden products never show (phase 4 exit criterion)', async ( { page, request } ) => {
		for ( const url of [ '/shop/', digital, '/shop/?per_page=96', `/?s=leaktest&post_type=product` ] ) {
			await page.goto( url );
			// The search page repeats the query in its title, so look for the products' names.
			await expect( page.locator( 'body' ) ).not.toContainText( 'محصول leaktest' );
		}
		const live = await request.get( '/wp-json/ultra-max/v1/search?q=leaktest' );
		expect( ( await live.json() ).count ).toBe( 0 );
		const code = await request.get( '/wp-json/ultra-max/v1/search?q=LEAK-' );
		expect( JSON.stringify( await code.json() ) ).not.toContain( 'محصول leaktest' );
		// The filter counts leave them out too: Novix's count equals its visible products.
		await page.goto( '/shop/' );
		const count = await page.locator( '#um-shop-filters label', { hasText: 'نوویکس' } ).locator( 'span' ).last().textContent();
		await page.locator( '#um-shop-filters label', { hasText: 'نوویکس' } ).click();
		await expect( page ).toHaveURL( /brand=/ );
		const shown = await page.locator( '#um-shop-grid .um-product-card' ).count();
		expect( count?.replace( /[۰-۹]/g, ( d ) => String( '۰۱۲۳۴۵۶۷۸۹'.indexOf( d ) ) ) ).toBe( String( shown ) );
	} );

	test( 'product lists pass the accessibility check', async ( { page } ) => {
		await page.goto( digital );
		const results = await new AxeBuilder( { page } ).withTags( [ 'wcag2a', 'wcag2aa' ] ).exclude( '#wpadminbar' ).analyze();
		const serious = results.violations.filter( ( violation ) => violation.impact === 'serious' || violation.impact === 'critical' );
		expect( serious.map( ( violation ) => `${ violation.id }: ${ violation.nodes[ 0 ]?.target.join( ' ' ) }` ) ).toEqual( [] );
	} );
} );

test.describe.serial( 'search', () => {
	test( 'suggestions appear while typing and arrow keys move through them (SRCH-01, SRCH-02)', async ( { page } ) => {
		const errors = watchErrors( page );
		await page.goto( '/' );
		const form = page.locator( 'header.um-layout-header:visible form[data-um-component="live-search"]' ).first();
		const input = form.locator( 'input[type="search"]' );
		await input.fill( 'novix' );
		const panel = form.locator( '[data-um-search-results]' );
		await expect( panel ).toBeVisible();
		await expect( input ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( panel.locator( '[role="option"]', { hasText: 'نوویکس' } ).first() ).toBeVisible();
		await input.press( 'ArrowDown' );
		await expect( panel.locator( '[aria-selected="true"]' ) ).toHaveCount( 1 );
		expect( await input.getAttribute( 'aria-activedescendant' ) ).toBeTruthy();
		await input.press( 'Escape' );
		await expect( panel ).toBeHidden();
		expect( errors ).toEqual( [] );
	} );

	test( 'search understands Latin digits, Arabic letters and product codes (SRCH-02, SRCH-03)', async ( { page } ) => {
		// WooCommerce opens the product itself when a search finds only one.
		const finds = async ( query: string, title: string ): Promise<void> => {
			await page.goto( `/?s=${ encodeURIComponent( query ) }&post_type=product` );
			await expect( page.locator( 'main, #um-main' ).first() ).toContainText( title );
		};
		await finds( '256', 'X12' );
		await finds( 'كتري', 'K20' );
		await finds( 'UM-003', 'Air Pro' );
		await finds( '۵۱۲ گیگابایت', 'ProBook' );
		await finds( 'NOVIX', 'Boom Mini' );
	} );

	test( 'phones search in a full screen window with recent and popular searches (SRCH-04)', async ( { page } ) => {
		wp( 'option', 'update', 'ultra_max_settings', JSON.stringify( { ...JSON.parse( saved.settings === '{}' ? '{}' : saved.settings ), bottom_nav_items: [ { type: 'home' }, { type: 'search' }, { type: 'cart' }, { type: 'account' } ] } ), '--format=json' );
		await page.setViewportSize( mobile );
		await page.goto( '/' );
		await page.locator( '.um-bottom-nav a[data-opens=".um-search-dialog"]' ).click();
		const window = page.locator( 'dialog.um-search-dialog[open]' );
		await expect( window ).toBeVisible();
		await expect( window.locator( '.um-search-sheet' ) ).toContainText( 'ساعت هوشمند' );
		await window.locator( 'input[type="search"]' ).fill( 'قهوه' );
		await window.locator( 'input[type="search"]' ).press( 'Enter' );
		await page.waitForURL( /s=/ );
		await page.locator( '.um-bottom-nav a[data-opens=".um-search-dialog"]' ).click();
		await expect( page.locator( 'dialog.um-search-dialog[open] [data-um-recent-list] a', { hasText: 'قهوه' } ) ).toBeVisible();
		wp( 'option', 'update', 'ultra_max_settings', saved.settings, '--format=json' );
	} );
} );

test.describe.serial( 'modules', () => {
	test( 'with the modules off, WooCommerce\'s own list and search return (ADM-07)', async ( { page, request } ) => {
		wp( 'option', 'update', 'ultra_max_modules', '{"shop-archive":false,"live-search":false}', '--format=json' );
		await page.goto( '/shop/' );
		await expect( page.locator( '[data-um-component="shop"]' ) ).toHaveCount( 0 );
		await expect( page.locator( 'ul.products li.product' ).first() ).toBeVisible();
		await expect( page.locator( '#ultra-max-ui-w-shop-css, #ultra-max-ui-w-shop-inline-css, #ultra-max-ui-w-search-js' ) ).toHaveCount( 0 );
		await expect( page.locator( 'form[data-um-component="live-search"]' ) ).toHaveCount( 0 );
		expect( ( await request.get( '/wp-json/ultra-max/v1/search?q=novix' ) ).status() ).toBe( 404 );
		wp( 'option', 'update', 'ultra_max_modules', '{}', '--format=json' );
	} );
} );
