/**
 * Phase 4, part D end to end: wishlist, compare, quick view, the customer
 * account and order tracking (SAVE-01 to SAVE-05, ACC-01 to ACC-09,
 * SHIP-03, BLD-07). The demo store comes from tools/seed/seed.php and the
 * demo shopper "sara" from tools/seed/account.php; settings, modules and
 * test content are restored afterwards.
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
 * The id of a product by its code.
 *
 * @param sku Product code.
 */
function productId( sku: string ): number {
	return Number( wp( 'eval', `echo (int) wc_get_product_id_by_sku( "${ sku }" );` ) );
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
 * Logs in as the demo shopper through the account page.
 *
 * @param page Page.
 */
async function loginShopper( page: Page ): Promise<void> {
	await page.goto( '/my-account/' );
	await page.locator( '#username' ).fill( 'sara' );
	await page.locator( '#password' ).fill( 'password' );
	await Promise.all( [ page.waitForURL( /my-account/ ), page.locator( 'button[name="login"]' ).click() ] );
	await expect( page.locator( '.um-account' ) ).toBeVisible();
}

/**
 * Posts to the lists endpoint as a guest.
 *
 * @param page   Page.
 * @param fields Fields.
 */
async function lists( page: Page, fields: Record<string, string> ): Promise<{ success?: boolean; lists?: { wishlist: number[]; compare: number[] } }> {
	const response = await page.request.post( '/?wc-ajax=um_lists', { form: fields } );
	return response.json();
}

const mobile = { width: 390, height: 844 };
let saved = { settings: '{}', modules: '{}' };
const ids = { phone: 0, headphones: 0, headphones2: 0, tshirt: 0, sent: 0, sentNumber: '' };

test.beforeAll( () => {
	test.setTimeout( 300_000 );
	saved = { settings: getOption( 'ultra_max_settings' ), modules: getOption( 'ultra_max_modules' ) };
	wp( 'option', 'update', 'ultra_max_modules', '{}', '--format=json' );
	if ( wp( 'eval', 'echo (int) wc_get_product_id_by_sku( "UM-RUN" );' ) === '0' ) {
		wp( 'eval-file', 'wp-content/ultra-max-dev/tools/seed/seed.php' );
	}
	wp( 'eval-file', 'wp-content/ultra-max-dev/tools/seed/account.php' );
	wp( 'eval-file', 'wp-content/ultra-max-dev/tests/e2e/fixtures/hidden-products.php' );
	wp( 'eval', 'UltraMax\\Core\\Store\\Pages::ensure(); flush_rewrite_rules( false );' );
	wp( 'transient', 'delete', '--all' );
	ids.phone = productId( 'UM-001' );
	ids.headphones = productId( 'UM-003' );
	ids.headphones2 = productId( 'UM-015' );
	ids.tshirt = productId( 'UM-RUN' );
	ids.sent = Number( wp( 'eval', '$o = wc_get_orders( array( "meta_key" => "_um_demo", "meta_value" => "sent", "limit" => 1 ) ); echo $o ? $o[0]->get_id() : 0;' ) );
	ids.sentNumber = wp( 'eval', `echo wc_get_order( ${ ids.sent } )->get_order_number();` );
} );

test.afterAll( () => {
	wp( 'eval-file', 'wp-content/ultra-max-dev/tests/e2e/fixtures/hidden-products.php', 'remove' );
	wp( 'eval', '$u = get_user_by( "login", "sara" ); if ( $u ) { delete_user_meta( $u->ID, "_um_wishlist" ); }' );
	wp( 'eval-file', 'wp-content/ultra-max-dev/tools/seed/account.php' );
	wp( 'option', 'update', 'ultra_max_settings', saved.settings, '--format=json' );
	wp( 'option', 'update', 'ultra_max_modules', saved.modules, '--format=json' );
} );

test.describe.serial( 'wishlist, compare and quick view', () => {
	test( 'a guest saves a product: the heart, the counter, the toast and the wishlist page (SAVE-01)', async ( { page } ) => {
		const errors = watchErrors( page );
		await page.goto( '/shop/' );
		const heart = page.locator( `.um-product-card [data-um-save="wishlist"][data-id="${ ids.headphones }"]` ).first();
		await expect( heart ).toHaveAttribute( 'aria-pressed', 'false' );
		await heart.click();
		await expect( heart ).toHaveAttribute( 'aria-pressed', 'true' );
		await expect( page.locator( '#um-toast' ) ).toBeVisible();
		await expect( page.locator( '[data-um-count="wishlist"]' ).first() ).toHaveText( '۱' );
		const cookies = await page.context().cookies();
		expect( cookies.find( ( cookie ) => cookie.name === 'um_wishlist' )?.value ).toBe( String( ids.headphones ) );

		await page.goto( '/wishlist/' );
		const cards = page.locator( '[data-um-saved-list="wishlist"] .um-product-card' );
		await expect( cards ).toHaveCount( 1 );
		// Taking it out of the list on the wishlist page empties it.
		await cards.first().locator( '[data-um-save="wishlist"]' ).click();
		await expect( page.getByRole( 'heading', { level: 2 } ).filter( { hasText: /خالی|empty/i } ) ).toBeVisible();
		expect( errors ).toEqual( [] );
	} );

	test( 'a guest list joins the account on login, without losing what was there (SAVE-01)', async ( { page } ) => {
		wp( 'eval', `$u = get_user_by( "login", "sara" ); update_user_meta( $u->ID, "_um_wishlist", array( ${ ids.phone } ) );` );
		await page.goto( '/shop/' );
		expect( ( await lists( page, { op: 'add', list: 'wishlist', id: String( ids.headphones2 ) } ) ).success ).toBe( true );
		await loginShopper( page );
		const stored = wp( 'eval', '$u = get_user_by( "login", "sara" ); echo implode( ",", get_user_meta( $u->ID, "_um_wishlist", true ) );' );
		expect( stored ).toBe( `${ ids.headphones2 },${ ids.phone }` );
		expect( ( await page.context().cookies() ).find( ( cookie ) => cookie.name === 'um_wishlist' ) ).toBeUndefined();
		await page.goto( '/wishlist/' );
		await expect( page.locator( '[data-um-saved-list="wishlist"] .um-product-card' ) ).toHaveCount( 2 );
		// A logged in shopper's page marks the saved hearts after asking the store.
		await page.goto( '/shop/' );
		await expect( page.locator( `.um-product-card [data-um-save="wishlist"][data-id="${ ids.phone }"]` ).first() ).toHaveAttribute( 'aria-pressed', 'true' );
	} );

	test( 'compare: products of one kind side by side, differences only, and the one-category rule (SAVE-02, SAVE-03)', async ( { page } ) => {
		const errors = watchErrors( page );
		await page.goto( '/shop/' );
		await page.locator( `.um-product-card [data-um-save="compare"][data-id="${ ids.headphones }"]` ).first().click();
		await expect( page.locator( '#um-toast' ) ).toBeVisible();
		await page.locator( `.um-product-card [data-um-save="compare"][data-id="${ ids.headphones2 }"]` ).first().click();
		await expect.poll( async () => ( await page.context().cookies() ).find( ( cookie ) => cookie.name === 'um_compare' )?.value ).toBe( `${ ids.headphones2 }.${ ids.headphones }` );
		// A phone is not a headphone: the shopper is asked before a new comparison starts.
		await page.locator( `.um-product-card [data-um-save="compare"][data-id="${ ids.phone }"]` ).first().click();
		await expect( page.locator( '#um-toast button:not([data-um-toast-close])' ) ).toBeVisible();
		await page.goto( '/compare/' );
		await expect( page.locator( '.um-compare thead th' ) ).toHaveCount( 3 );
		const rows = page.locator( '.um-compare tbody tr:not(.um-compare-group)' );
		const all = await rows.count();
		expect( all ).toBeGreaterThan( 3 );
		await page.locator( '[data-um-compare-differences]' ).check();
		const visible = await rows.filter( { has: page.locator( 'th' ) } ).evaluateAll( ( found ) => found.filter( ( row ) => ( row as HTMLElement ).offsetParent !== null ).length );
		expect( visible ).toBeLessThan( all );
		expect( visible ).toBeGreaterThan( 0 );
		const axe = await new AxeBuilder( { page } ).include( '.um-compare' ).analyze();
		expect( axe.violations ).toEqual( [] );

		await page.goto( '/shop/' );
		await page.locator( `.um-product-card [data-um-save="compare"][data-id="${ ids.phone }"]` ).first().click();
		await page.locator( '#um-toast button:not([data-um-toast-close])' ).click();
		await expect.poll( async () => ( await page.context().cookies() ).find( ( cookie ) => cookie.name === 'um_compare' )?.value ).toBe( String( ids.phone ) );
		expect( errors ).toEqual( [] );
	} );

	test( 'quick view shows a product and buys a size of a T-shirt without leaving the list (SAVE-04, SAVE-05)', async ( { page } ) => {
		const errors = watchErrors( page );
		await page.goto( '/shop/' );
		await page.locator( `.um-product-card [data-um-quick="${ ids.headphones }"]` ).first().click();
		const dialog = page.locator( '#um-quick-view' );
		await expect( dialog ).toBeVisible();
		await expect( dialog.locator( 'h2' ) ).toBeVisible();
		await expect( dialog.locator( 'form.um-purchase' ) ).toBeVisible();
		await page.keyboard.press( 'Escape' );
		await expect( dialog ).toBeHidden();

		// "Choose options" of a product with sizes opens the same window.
		// The card's "Choose options" link opens the window instead of the product page (it shows in the row view).
		await expect( page.locator( `.um-product-card a[data-um-quick="${ ids.tshirt }"]` ).first() ).toHaveAttribute( 'href', /product/ );
		await page.locator( `.um-product-card button[data-um-quick="${ ids.tshirt }"]` ).first().click();
		const form = dialog.locator( 'form.um-purchase' );
		await dialog.locator( '.um-variation-picker label', { hasText: 'نارنجی' } ).click();
		await dialog.locator( '.um-variation-picker label', { hasText: /^\s*L\s*$/ } ).click();
		await form.locator( '[data-um-add-button]' ).click();
		await expect( form.locator( '[data-um-purchase-status]' ) ).toHaveAttribute( 'data-tone', 'success' );
		await expect( page.locator( '[data-um-count="cart"]' ).first() ).not.toHaveText( /^[0۰]?$/ );
		await expect( dialog ).toBeHidden();
		expect( errors ).toEqual( [] );
	} );

	test( 'products nobody may see are never saved, compared or shown in a window (phase 4 exit criterion)', async ( { page } ) => {
		await page.goto( '/shop/' );
		const hidden = wp( 'eval', 'echo implode( ",", get_posts( array( "post_type" => "product", "post_status" => "any", "meta_key" => "_um_leaktest", "fields" => "ids", "posts_per_page" => -1 ) ) );' ).split( ',' );
		expect( hidden ).toHaveLength( 4 );
		for ( const id of hidden ) {
			expect( ( await lists( page, { op: 'add', list: 'wishlist', id } ) ).success ).toBe( false );
			expect( ( await lists( page, { op: 'add', list: 'compare', id } ) ).success ).toBe( false );
			const quick = await page.request.get( `/?wc-ajax=um_quick_view&id=${ id }` );
			expect( quick.status() ).toBe( 404 );
		}
		// Even a hand made cookie does not show them.
		await page.context().addCookies( [ { name: 'um_wishlist', value: hidden.join( '.' ), url: page.url() }, { name: 'um_compare', value: hidden.slice( 0, 4 ).join( '.' ), url: page.url() } ] );
		await page.goto( '/wishlist/' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'leaktest' );
		await page.goto( '/compare/' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'leaktest' );
	} );
} );

test.describe.serial( 'customer account', () => {
	test( 'the dashboard: orders by status, latest orders, lists and details (ACC-01, ACC-04, ACC-07)', async ( { page } ) => {
		const errors = watchErrors( page );
		await loginShopper( page );
		await expect( page.locator( '.um-account-nav [aria-current="page"]' ) ).toHaveCount( 1 );
		await expect( page.locator( '.um-order-stats li' ) ).toHaveCount( 4 );
		await expect( page.locator( '.um-order-stats li' ).first() ).toContainText( '۳' );
		await expect( page.locator( '#um-account-latest .um-order-card' ).first() ).toBeVisible();
		await expect( page.locator( '.um-info-grid' ) ).toContainText( '۰۰۱۳۵۴۲۴۱۹' );
		await expect( page.locator( '.um-info-grid' ) ).toContainText( 'اردیبهشت ۱۳۷۰' );
		const axe = await new AxeBuilder( { page } ).include( '.um-account' ).analyze();
		expect( axe.violations ).toEqual( [] );
		expect( errors ).toEqual( [] );
	} );

	test( 'orders by status and one order with its progress and tracking code (ACC-01, ACC-02, SHIP-03)', async ( { page } ) => {
		const errors = watchErrors( page );
		await loginShopper( page );
		await page.goto( '/my-account/orders/?status=cancelled' );
		await expect( page.locator( '.um-order-card' ) ).toHaveCount( 1 );
		await page.goto( '/my-account/orders/?status=current' );
		await expect( page.locator( '.um-order-card' ) ).toHaveCount( 3 );
		await page.goto( `/my-account/view-order/${ ids.sent }/` );
		const steps = page.locator( '.um-order-steps li' );
		await expect( steps ).toHaveCount( 5 );
		await expect( page.locator( '.um-order-steps [aria-current="step"]' ) ).toHaveCount( 1 );
		await expect( page.locator( '.um-tracking-box [data-um-code]' ) ).toHaveText( '248910007342165501820' );
		await expect( page.locator( '.um-order-products li' ) ).toHaveCount( 2 );
		await page.context().grantPermissions( [ 'clipboard-read', 'clipboard-write' ] );
		await page.locator( '.um-tracking-box [data-um-copy]' ).click();
		await expect( page.locator( '.um-tracking-box [data-um-copy-status]' ) ).not.toHaveText( '' );
		await page.setViewportSize( mobile );
		await page.reload();
		expect( await page.evaluate( () => document.documentElement.scrollWidth - document.documentElement.clientWidth ) ).toBeLessThanOrEqual( 0 );
		expect( errors ).toEqual( [] );
	} );

	test( 'lists, reviews and questions, addresses and recent views (ACC-04, ACC-05, ACC-06, ACC-08)', async ( { page } ) => {
		await loginShopper( page );
		await page.goto( '/my-account/lists/' );
		await expect( page.locator( '[data-um-saved-list="wishlist"] .um-product-card' ).first() ).toBeVisible();
		await page.goto( '/my-account/activity/' );
		await expect( page.locator( '.um-activity-list li' ) ).toHaveCount( 1 );
		await page.goto( '/my-account/activity/?type=questions' );
		await expect( page.locator( '.um-activity-list li' ) ).toHaveCount( 1 );
		await expect( page.locator( '.um-activity-list' ) ).toContainText( 'گارانتی' );
		await page.goto( '/my-account/edit-address/' );
		await expect( page.locator( '.um-address-card' ).first() ).toContainText( 'سارا' );
		await page.goto( '/my-account/edit-address/billing/' );
		await expect( page.locator( '.um-account-form #billing_first_name' ) ).toBeVisible();
		// Recent views live in the browser: open a product, then the account shows it.
		await page.goto( `/?p=${ ids.headphones }` );
		await page.goto( `/?p=${ ids.phone }` );
		await page.goto( '/my-account/recent/' );
		await expect( page.locator( '.um-recent-products .um-product-card' ).first() ).toBeVisible();
	} );

	test( 'account details check the mobile and national code and keep the birthday (ACC-07)', async ( { page } ) => {
		await loginShopper( page );
		await page.goto( '/my-account/edit-account/' );
		await page.locator( '#um_national_code' ).fill( '1234567890' );
		await page.locator( 'button[name="save_account_details"]' ).click();
		await expect( page.locator( '.woocommerce-error, .is-error' ).first() ).toBeVisible();
		await page.locator( '#um_national_code' ).fill( '۰۰۱۳۵۴۲۴۱۹' );
		await page.locator( '#um_mobile' ).fill( '+98 935 111 2233' );
		await page.locator( 'select[name="um_birth_day"]' ).selectOption( '5' );
		await page.locator( 'button[name="save_account_details"]' ).click();
		await expect( page.locator( '.woocommerce-message, .is-success' ).first() ).toBeVisible();
		const stored = wp( 'eval', '$u = get_user_by( "login", "sara" ); echo get_user_meta( $u->ID, "billing_phone", true ), "|", get_user_meta( $u->ID, "um_national_code", true ), "|", get_user_meta( $u->ID, "um_birthday", true );' );
		expect( stored ).toBe( '09351112233|0013542419|1991-04-25' );
	} );

	test( 'the store adds a tracking code on the order screen; the shopper is told (SHIP-03)', async ( { page } ) => {
		const order = Number( wp( 'eval', '$o = wc_get_orders( array( "meta_key" => "_um_demo", "meta_value" => "preparing", "limit" => 1 ) ); echo $o[0]->get_id();' ) );
		const clear = (): string => wp( 'eval', `$o = wc_get_order( ${ order } ); $o->delete_meta_data( "_um_tracking_code" ); $o->delete_meta_data( "_um_tracking_carrier" ); $o->delete_meta_data( "_um_tracking_date" ); $o->save(); foreach ( wc_get_order_notes( array( "order_id" => ${ order } ) ) as $n ) { if ( str_contains( $n->content, "TPX-5566" ) ) { wc_delete_order_note( $n->id ); } }` );
		clear();
		await page.goto( '/wp-login.php' );
		await page.locator( '#user_login' ).fill( 'admin' );
		await page.locator( '#user_pass' ).fill( 'password' );
		await Promise.all( [ page.waitForURL( /wp-admin/ ), page.locator( '#wp-submit' ).click() ] );
		const hpos = wp( 'eval', 'echo \\Automattic\\WooCommerce\\Utilities\\OrderUtil::custom_orders_table_usage_is_enabled() ? 1 : 0;' ) === '1';
		await page.goto( hpos ? `/wp-admin/admin.php?page=wc-orders&action=edit&id=${ order }` : `/wp-admin/post.php?post=${ order }&action=edit` );
		await page.locator( '#um-tracking-code' ).fill( 'TPX-5566' );
		await page.locator( 'button.save_order, #publish' ).first().click();
		await expect( page.locator( '#um-tracking-code' ) ).toHaveValue( 'TPX-5566' );
		const notes = wp( 'eval', `echo count( wc_get_order_notes( array( "order_id" => ${ order }, "type" => "customer" ) ) );` );
		clear();
		expect( Number( notes ) ).toBeGreaterThan( 0 );
	} );
} );

test.describe.serial( 'order tracking without logging in', () => {
	test( 'the right number and mobile show the order; anything else shows nothing (ACC-09)', async ( { page } ) => {
		const errors = watchErrors( page );
		await page.goto( '/track-order/' );
		await page.locator( '#um-track-number' ).fill( ids.sentNumber );
		await page.locator( '#um-track-mobile' ).fill( '09120000000' );
		await page.locator( '.um-order-lookup button[type="submit"]' ).click();
		await expect( page.locator( '.um-order-lookup [role="alert"]' ) ).toBeVisible();
		await expect( page.locator( '#um-track-result' ) ).toHaveCount( 0 );

		await page.locator( '#um-track-mobile' ).fill( '+98 912 123 4567' );
		await page.locator( '.um-order-lookup button[type="submit"]' ).click();
		await expect( page.locator( '#um-track-result' ) ).toBeVisible();
		await expect( page.locator( '#um-track-result .um-order-steps li' ) ).toHaveCount( 5 );
		await expect( page.locator( '#um-track-result' ) ).not.toContainText( 'کوچه' );
		const axe = await new AxeBuilder( { page } ).include( '.um-order-lookup' ).analyze();
		expect( axe.violations ).toEqual( [] );
		expect( errors ).toEqual( [] );
		wp( 'transient', 'delete', '--all' );
	} );
} );

test.describe.serial( 'modules off', () => {
	test( 'with the four modules off, no button, file or page of theirs remains (ADM-07)', async ( { page } ) => {
		wp( 'option', 'update', 'ultra_max_modules', '{"wishlist":false,"compare":false,"quick-view":false,"account":false,"order-tracking":false}', '--format=json' );
		try {
			await page.goto( '/shop/' );
			await expect( page.locator( '[data-um-save], [data-um-quick]' ) ).toHaveCount( 0 );
			await expect( page.locator( '#um-toast, #um-quick-view' ) ).toHaveCount( 0 );
			const files = await page.locator( 'link[href*="widgets/saved"], script[src*="widgets/saved"], link[href*="widgets/account"]' ).count();
			expect( files ).toBe( 0 );
			await loginShopper( page ).catch( () => undefined );
			await page.goto( '/my-account/' );
			await expect( page.locator( '.um-account' ) ).toHaveCount( 0 );
			await expect( page.locator( '.woocommerce-MyAccount-navigation' ) ).toBeVisible();
		} finally {
			wp( 'option', 'update', 'ultra_max_modules', '{}', '--format=json' );
		}
	} );
} );
