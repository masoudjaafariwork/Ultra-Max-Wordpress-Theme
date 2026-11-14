/**
 * Phase 4, part B end to end: the product page on the demo store
 * (PDP-01 to PDP-26, BLD-04). The demo store comes from tools/seed/seed.php
 * with the fixtures product-extras.php and product-discussion.php; settings,
 * modules and test content are restored afterwards.
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
 * The address of a demo product.
 *
 * @param sku Product code.
 */
function url( sku: string ): string {
	return wp( 'eval', `echo get_permalink( wc_get_product_id_by_sku( "${ sku }" ) );` );
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
 * Logs in as the site's administrator.
 *
 * @param page Page.
 */
async function login( page: Page ): Promise<void> {
	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( 'admin' );
	await page.locator( '#user_pass' ).fill( 'password' );
	await Promise.all( [ page.waitForURL( /wp-admin/ ), page.locator( '#wp-submit' ).click() ] );
}

const mobile = { width: 390, height: 844 };
let saved = { settings: '{}', modules: '{}' };
const urls = { phone: '', tshirt: '', sneaker: '' };

test.beforeAll( () => {
	test.setTimeout( 300_000 );
	saved = { settings: getOption( 'ultra_max_settings' ), modules: getOption( 'ultra_max_modules' ) };
	wp( 'option', 'update', 'ultra_max_modules', '{}', '--format=json' );
	if ( wp( 'eval', 'echo (int) wc_get_product_id_by_sku( "UM-RUN" );' ) === '0' ) {
		wp( 'eval-file', 'wp-content/ultra-max-dev/tools/seed/seed.php' );
	}
	wp( 'eval-file', 'wp-content/ultra-max-dev/tests/e2e/fixtures/product-extras.php' );
	wp( 'eval-file', 'wp-content/ultra-max-dev/tests/e2e/fixtures/product-discussion.php' );
	wp( 'eval-file', 'wp-content/ultra-max-dev/tests/e2e/fixtures/hidden-products.php' );
	wp( 'transient', 'delete', '--all' );
	urls.phone = url( 'UM-001' );
	urls.tshirt = url( 'UM-RUN' );
	urls.sneaker = url( 'UM-005' );
} );

test.afterAll( () => {
	wp( 'eval', 'foreach ( get_comments( array( "search" => "e2e-test", "status" => "all", "post_type" => "product" ) ) as $c ) { wp_delete_comment( $c->comment_ID, true ); }' );
	wp( 'eval-file', 'wp-content/ultra-max-dev/tests/e2e/fixtures/hidden-products.php', 'remove' );
	wp( 'eval-file', 'wp-content/ultra-max-dev/tests/e2e/fixtures/product-discussion.php' );
	wp( 'option', 'update', 'ultra_max_settings', saved.settings, '--format=json' );
	wp( 'option', 'update', 'ultra_max_modules', saved.modules, '--format=json' );
} );

test.describe.serial( 'product page', () => {
	test( 'a product shows its gallery, info, buy box, sections and data (PDP-01, PDP-05, PDP-06, PDP-26)', async ( { page } ) => {
		const errors = watchErrors( page );
		await page.goto( urls.phone );
		const area = page.locator( '[data-um-product]' );
		await expect( area.locator( 'h1' ) ).toContainText( 'نوویکس' );
		await expect( area.locator( '[data-um-component="product-gallery"]' ) ).toBeVisible();
		await expect( area.locator( '.um-buy-box form.um-purchase' ) ).toBeVisible();
		await expect( area.locator( '.um-key-features li' ).first() ).toBeVisible();
		await expect( area.locator( '#specs dl' ).first() ).toBeVisible();
		await expect( area.locator( '[data-um-section-link]' ) ).toHaveCount( 5 );
		await expect( area.locator( '.um-delivery li' ).first() ).toBeVisible();
		await expect( area.locator( '.um-progress-note' ).first() ).toBeVisible();
		const data = await page.locator( 'script[type="application/ld+json"]' ).allTextContents();
		const joined = data.join( '\n' );
		expect( joined ).toContain( '"Product"' );
		expect( joined ).toContain( '"BreadcrumbList"' );
		expect( joined ).toContain( '"Brand"' );
		expect( joined.match( /"@type":"Product"/g ) ?? [] ).toHaveLength( 1 );
		// WooCommerce's jQuery gallery and lightbox do not load on our page.
		await expect( page.locator( '.pswp' ) ).toHaveCount( 0 );
		expect( await page.evaluate( () => 'jQuery' in window && typeof ( window as unknown as { jQuery?: { fn?: { flexslider?: unknown } } } ).jQuery?.fn?.flexslider !== 'undefined' ) ).toBe( false );
		expect( errors ).toEqual( [] );
	} );

	test( 'the gallery switches pictures and opens full screen (PDP-01)', async ( { page } ) => {
		await page.goto( urls.phone );
		const gallery = page.locator( '[data-um-component="product-gallery"]' );
		await gallery.locator( '[data-um-gallery-thumb="1"]' ).click();
		await expect( gallery.locator( '[data-um-gallery-thumb="1"]' ) ).toHaveAttribute( 'aria-current', 'true' );
		await gallery.locator( '[data-um-slide="1"] button' ).click();
		const viewer = gallery.locator( '[data-um-gallery-viewer]' );
		await expect( viewer ).toBeVisible();
		await expect( viewer.locator( '[data-um-viewer-track] img' ) ).toHaveCount( 2 );
		await page.keyboard.press( 'Escape' );
		await expect( viewer ).toBeHidden();
	} );

	test( 'choosing a variation changes price, pictures and stock in place (PDP-03, PDP-04)', async ( { page } ) => {
		await page.goto( urls.tshirt );
		const form = page.locator( 'form.um-purchase' );
		const picture = () => page.locator( '[data-um-gallery-track] img' ).first().getAttribute( 'src' );
		const before = await picture();
		await page.locator( '.um-variation-picker label', { hasText: 'مشکی' } ).click();
		await page.locator( '.um-variation-picker label', { hasText: /^\s*M\s*$/ } ).click();
		await expect( form.locator( '[data-um-variation-id]' ) ).not.toHaveValue( '0' );
		expect( await picture() ).not.toBe( before );
		await page.locator( '.um-variation-picker label', { hasText: /^\s*S\s*$/ } ).click();
		await expect( form.locator( '[data-um-add-button]' ) ).toBeDisabled();
		await expect( form.locator( '[data-um-purchase-status]' ) ).toBeVisible();
	} );

	test( 'add to cart works in place and "buy now" goes to checkout (PDP-05, PDP-21)', async ( { page } ) => {
		await page.goto( urls.tshirt );
		const form = page.locator( 'form.um-purchase' );
		await page.locator( '.um-variation-picker label', { hasText: 'نارنجی' } ).click();
		await page.locator( '.um-variation-picker label', { hasText: /^\s*L\s*$/ } ).click();
		await form.locator( '[data-um-add-button]' ).click();
		await expect( form.locator( '[data-um-purchase-status]' ) ).toHaveAttribute( 'data-tone', 'success' );
		await expect( page.locator( '[data-um-count="cart"]' ).first() ).not.toHaveText( /^[0۰]?$/ );
		await Promise.all( [ page.waitForURL( /checkout|%D8%AA%D8%B3%D9%88%DB%8C%D9%87/i ), form.locator( '[data-um-quick-button]' ).click() ] );
	} );

	test( 'quantity limits hold on the page and on the server (PDP-25)', async ( { page, request } ) => {
		const id = wp( 'eval', 'echo wc_get_product_id_by_sku( "UM-009" );' );
		wp( 'eval', `$p = wc_get_product( ${ id } ); $p->update_meta_data( "um_qty_min", "2" ); $p->update_meta_data( "um_qty_step", "2" ); $p->update_meta_data( "um_qty_max", "6" ); $p->save();` );
		try {
			await page.goto( url( 'UM-009' ) );
			const quantity = page.locator( 'form.um-purchase input[name="quantity"]' );
			await expect( quantity ).toHaveAttribute( 'min', '2' );
			await expect( quantity ).toHaveAttribute( 'step', '2' );
			await page.locator( 'form.um-purchase [data-um-step="1"]' ).click();
			await expect( quantity ).toHaveValue( '4' );
			const reply = await request.post( '/?wc-ajax=um_add_to_cart', { form: { um_product: id, quantity: '3' } } );
			const body = ( await reply.json() ) as { success: boolean; message: string };
			expect( body.success ).toBe( false );
			expect( body.message ).not.toBe( '' );
		} finally {
			wp( 'eval', `$p = wc_get_product( ${ id } ); foreach ( array( "um_qty_min", "um_qty_step", "um_qty_max" ) as $k ) { $p->delete_meta_data( $k ); } $p->save();` );
		}
	} );

	test( 'reviews filter in place, take one vote each and show strengths (PDP-07 to PDP-09)', async ( { page } ) => {
		await page.goto( urls.phone );
		const list = page.locator( '.um-review-list' );
		await expect( list.locator( 'article' ) ).toHaveCount( 5 );
		await list.locator( 'a[data-um-reviews-link]' ).nth( 1 ).click();
		await expect( page ).toHaveURL( /review_filter=buyers/ );
		await expect( list.locator( 'article' ) ).toHaveCount( 4 );
		await expect( list.locator( 'article' ).first().locator( 'li' ).first() ).toBeVisible();
		const up = list.locator( 'article' ).first().locator( '[data-um-vote="up"]' );
		const before = await up.locator( '[data-um-vote-count]' ).textContent();
		await up.click();
		await expect( up ).toHaveAttribute( 'aria-pressed', 'true' );
		await expect( up.locator( '[data-um-vote-count]' ) ).not.toHaveText( before ?? '' );
		await expect( page.locator( '.um-review-summary' ) ).toContainText( '٪' );
		expect( await page.evaluate( () => document.querySelector( 'meta[name="robots"]' )?.getAttribute( 'content' ) ?? '' ) ).not.toContain( 'noindex' );
	} );

	test( 'a logged in shopper reviews with strengths and asks a question (PDP-08, PDP-10)', async ( { page } ) => {
		await login( page );
		await page.goto( urls.phone + '#reviews' );
		await page.locator( '[data-um-review-write]' ).click();
		const form = page.locator( '#um-review-form form' );
		await form.locator( 'label[for$="rating-4"]' ).click();
		await form.locator( 'textarea[name="comment"]' ).fill( 'e2e-test: a review written by the browser test.' );
		await form.locator( 'textarea[name="um_pros"]' ).fill( 'e2e strength' );
		await form.locator( 'input[name="um_recommend"][value="yes"]' ).check( { force: true } );
		await Promise.all( [ page.waitForURL( /#review-\d+|#reviews/ ), form.locator( 'button[type="submit"]' ).click() ] );
		await page.goto( urls.phone + '?review_sort=newest#reviews' );
		await expect( page.locator( '.um-review-list article' ).first() ).toContainText( 'e2e strength' );

		const ask = page.locator( '.um-question-list form[data-um-qa-form]' ).first();
		await ask.locator( 'textarea' ).fill( 'e2e-test: does it come with a case?' );
		await ask.locator( 'button[type="submit"]' ).click();
		await expect( ask.locator( '[data-um-qa-status]' ) ).toHaveAttribute( 'data-tone', 'success' );
		await page.reload();
		await expect( page.locator( '.um-question-list' ) ).toContainText( 'does it come with a case' );
	} );

	test( 'guests cannot ask without logging in; the store answer shows its badge (PDP-10)', async ( { page } ) => {
		await page.goto( urls.phone );
		const questions = page.locator( '.um-question-list' );
		await expect( questions.locator( 'a[href*="wp-login.php"]' ) ).toBeVisible();
		await expect( questions.locator( 'article' ).filter( { hasText: 'کارت حافظه' } ) ).toContainText( 'فروشگاه' );
	} );

	test( 'bundle, size guide and the store tab (PDP-13, PDP-14, PDP-22)', async ( { page } ) => {
		await page.goto( urls.phone );
		await expect( page.locator( '[data-um-section-link]', { hasText: 'ارسال و بازگشت' } ) ).toBeVisible();
		const bundle = page.locator( '.um-bundle' );
		const all = await bundle.locator( '[data-um-bundle-now]' ).textContent();
		await bundle.locator( '[data-um-bundle-item]' ).nth( 2 ).uncheck();
		await expect( bundle.locator( '[data-um-bundle-now]' ) ).not.toHaveText( all ?? '' );
		await bundle.locator( '[data-um-bundle-add]' ).click();
		await expect( bundle.locator( '[data-um-bundle-status]' ) ).toHaveAttribute( 'data-tone', 'success' );
		// The block cart's Store API counts the bundle discount as a negative fee.
		const cart = await page.evaluate( async () => ( await fetch( '/wp-json/wc/store/v1/cart', { credentials: 'same-origin' } ) ).json() as Promise<{ totals: { total_fees: string } }> );
		expect( Number( cart.totals.total_fees ) ).toBeLessThan( 0 );

		await page.goto( urls.sneaker );
		await page.locator( '[data-um-open="um-size-guide"]' ).click();
		await expect( page.locator( '#um-size-guide table' ) ).toBeVisible();
	} );

	test( 'delivery by province, sticky bar and recent views on a phone (PDP-18, PDP-20, PDP-23)', async ( { page } ) => {
		await page.setViewportSize( mobile );
		await page.goto( urls.phone );
		const delivery = page.locator( '.um-delivery' ).first();
		await delivery.locator( 'select' ).selectOption( { index: 3 } );
		await expect( delivery.locator( 'li' ).first() ).toBeVisible();
		const bar = page.locator( '.um-sticky-buy' );
		await page.mouse.wheel( 0, 2500 );
		await expect( bar ).toBeVisible();
		await expect( bar ).not.toHaveAttribute( 'data-away', '' );
		expect( await page.evaluate( () => document.documentElement.scrollWidth - document.documentElement.clientWidth ) ).toBeLessThanOrEqual( 0 );
		await page.goto( urls.sneaker );
		await expect( page.locator( '.um-recent-products .um-product-card' ).first() ).toBeVisible();
	} );

	test( 'the product page passes an accessibility check', async ( { page } ) => {
		await page.goto( urls.phone );
		const result = await new AxeBuilder( { page } ).include( '[data-um-product]' ).withTags( [ 'wcag2a', 'wcag2aa' ] ).analyze();
		expect( result.violations.map( ( violation ) => `${ violation.id }: ${ violation.nodes.map( ( node ) => node.target.join( ' ' ) ).join( ', ' ) }` ) ).toEqual( [] );
	} );

	test( 'private, draft, password protected and hidden products never leak (phase 4 exit criterion)', async ( { page, request } ) => {
		const ids = wp( 'eval', 'echo implode( ",", get_posts( array( "post_type" => "product", "post_status" => "any", "meta_key" => "_um_leaktest", "fields" => "ids", "posts_per_page" => -1 ) ) );' );
		expect( ids.split( ',' ) ).toHaveLength( 4 );
		const cards = await ( await request.get( `/wp-json/ultra-max/v1/product-cards?ids=${ ids }` ) ).json() as { count: number; html: string };
		expect( cards.count ).toBe( 0 );
		expect( cards.html ).not.toContain( 'leaktest' );
		for ( const id of ids.split( ',' ) ) {
			expect( ( await request.get( `/wp-json/ultra-max/v1/reviews?product=${ id }` ) ).status() ).toBe( 404 );
			expect( ( await request.get( `/wp-json/ultra-max/v1/delivery?product=${ id }` ) ).status() ).toBe( 404 );
			const add = await ( await request.post( '/?wc-ajax=um_add_to_cart', { form: { um_items: id } } ) ).json() as { success: boolean };
			expect( add.success ).toBe( false );
		}
		const locked = wp( 'eval', 'echo get_permalink( wc_get_product_id_by_sku( "LEAK-publish-visible-locked" ) );' );
		await page.goto( locked );
		await expect( page.locator( 'form.post-password-form' ) ).toBeVisible();
		await expect( page.locator( '.um-buy-box, [data-um-product]' ) ).toHaveCount( 0 );
		expect( await page.content() ).not.toContain( '"@type":"Product"' );
		const draft = wp( 'eval', 'echo add_query_arg( "p", wc_get_product_id_by_sku( "LEAK-draft-visible" ), home_url( "/" ) );' );
		expect( ( await request.get( draft ) ).status() ).toBe( 404 );
	} );

	test( 'a product layout designed with the library draws the page (BLD-04)', async ( { page } ) => {
		const layout = wp( 'eval', '$t = UltraMax\\Core\\Builder\\Library::get( "single-product/store" ); echo UltraMax\\Core\\Builder\\Layouts::create( "single-product", "e2e product layout", UltraMax\\Core\\Builder\\LibraryStrings::translate( $t["content"] ) );' );
		try {
			const errors = watchErrors( page );
			await page.goto( urls.phone );
			const region = page.locator( '.um-layout-single-product' );
			await expect( region ).toBeVisible();
			await expect( region.locator( '.um-buy-box form.um-purchase' ) ).toBeVisible();
			await expect( region.locator( '.um-review-list' ) ).toBeVisible();
			await expect( page.locator( '#um-share-dialog' ) ).toHaveCount( 1 );
			expect( errors ).toEqual( [] );
		} finally {
			wp( 'post', 'delete', layout, '--force' );
			wp( 'eval', 'UltraMax\\Core\\Builder\\Layouts::rebuild();' );
		}
	} );

	test( 'with the module off WooCommerce\'s own page shows and our files do not load (ADM-07)', async ( { page } ) => {
		wp( 'option', 'update', 'ultra_max_modules', '{"single-product":false}', '--format=json' );
		try {
			await page.goto( urls.phone );
			await expect( page.locator( '[data-um-product]' ) ).toHaveCount( 0 );
			await expect( page.locator( 'form.cart' ) ).toBeVisible();
			expect( await page.content() ).not.toContain( 'ultra-max-ui-w-product-' );
		} finally {
			wp( 'option', 'update', 'ultra_max_modules', '{}', '--format=json' );
		}
	} );
} );
