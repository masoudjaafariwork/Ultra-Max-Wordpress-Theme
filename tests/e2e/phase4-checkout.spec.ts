/**
 * Phase 4, part C end to end: the cart, the checkout and Iranian shipping
 * on the demo store (CART-01 to CART-10, CHK-01 to CHK-10, SHIP-01, SHIP-02,
 * BLD-06), including the phase's exit criterion: a whole purchase in both
 * the classic and the block checkout. The demo store comes from
 * tools/seed/seed.php with the fixtures product-extras.php and
 * checkout-setup.php; settings, modules and test orders are restored or
 * removed afterwards.
 */
import { test, expect, type Browser, type Page } from '@playwright/test';
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
 * Saves some of our settings.
 *
 * @param values Values by setting id.
 */
function settings( values: Record<string, unknown> ): void {
	wp( 'eval', `\\UltraMax\\Core\\Lib\\Settings\\Store::save( json_decode( ${ JSON.stringify( JSON.stringify( values ) ) }, true ) );` );
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
 * A meta value of an order.
 *
 * @param order Order id.
 * @param key   Meta key.
 */
function orderMeta( order: string, key: string ): string {
	return wp( 'eval', `echo wc_get_order( ${ Number( order ) } )->get_meta( "${ key }" );` );
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

/**
 * Adds a product to the cart from its page.
 *
 * @param page Page.
 * @param sku  Product code.
 */
async function add( page: Page, sku: string ): Promise<void> {
	await page.goto( urls[ sku ] ?? url( sku ) );
	const reply = page.waitForResponse( ( response ) => response.url().includes( 'um_add_to_cart' ) );
	await page.locator( '.um-buy-box form.um-purchase [data-um-add-button]' ).click();
	await reply;
}

/**
 * Fills the classic checkout's first step.
 *
 * @param page Page.
 * @param city City to choose.
 */
async function fillClassic( page: Page, city = 'تهران' ): Promise<void> {
	await page.locator( '#billing_first_name' ).fill( 'سارا' );
	await page.locator( '#billing_last_name' ).fill( 'محمدی' );
	await page.locator( '#billing_phone' ).fill( '۰۹۱۲۳۴۵۶۷۸۹' );
	await page.locator( '#billing_city' ).selectOption( city );
	await page.locator( '#billing_address_1' ).fill( 'خیابان ولیعصر، کوچه نمونه، پلاک ۱۲' );
	await page.locator( '#billing_postcode' ).fill( '۱۱۹۳۶۵۳۴۷۱' );
}

/**
 * Waits until WooCommerce's checkout script has worked out the order again.
 *
 * @param page Page.
 */
async function settled( page: Page ): Promise<void> {
	const reply = page.waitForResponse( ( response ) => response.url().includes( 'update_order_review' ) );
	await page.evaluate( () => ( window as unknown as { jQuery: ( target: unknown ) => { trigger: ( event: string ) => void } } ).jQuery( document.body ).trigger( 'update_checkout' ) );
	await reply;
	await page.waitForFunction( () => ! document.querySelector( '.blockUI' ) );
	await page.waitForTimeout( 300 );
}

const urls: Record<string, string> = {};
const orders: string[] = [];
let saved = { settings: '{}', modules: '{}' };

test.beforeAll( () => {
	test.setTimeout( 300_000 );
	saved = { settings: getOption( 'ultra_max_settings' ), modules: getOption( 'ultra_max_modules' ) };
	wp( 'option', 'update', 'ultra_max_modules', '{}', '--format=json' );
	if ( wp( 'eval', 'echo (int) wc_get_product_id_by_sku( "UM-RUN" );' ) === '0' ) {
		wp( 'eval-file', 'wp-content/ultra-max-dev/tools/seed/seed.php' );
	}
	wp( 'eval-file', 'wp-content/ultra-max-dev/tests/e2e/fixtures/product-extras.php' );
	wp( 'eval-file', 'wp-content/ultra-max-dev/tests/e2e/fixtures/checkout-setup.php' );
	wp( 'eval-file', 'wp-content/ultra-max-dev/tests/e2e/fixtures/hidden-products.php' );
	wp( 'eval', 'delete_user_meta( 1, "um_addresses" ); delete_user_meta( 1, "um_later" ); delete_user_meta( 1, "_woocommerce_persistent_cart_" . get_current_blog_id() ); global $wpdb; $wpdb->delete( $wpdb->prefix . "woocommerce_sessions", array( "session_key" => "1" ) );' );
	wp( 'transient', 'delete', '--all' );
	for ( const sku of [ 'UM-003', 'UM-013', 'UM-015' ] ) {
		urls[ sku ] = url( sku );
	}
} );

test.afterAll( () => {
	for ( const order of orders ) {
		wp( 'eval', `wc_get_order( ${ Number( order ) } )?->delete( true );` );
	}
	wp( 'eval-file', 'wp-content/ultra-max-dev/tests/e2e/fixtures/hidden-products.php', 'remove' );
	wp( 'eval', 'delete_user_meta( 1, "um_addresses" ); delete_user_meta( 1, "um_later" );' );
	wp( 'option', 'update', 'ultra_max_settings', saved.settings, '--format=json' );
	wp( 'option', 'update', 'ultra_max_modules', saved.modules, '--format=json' );
} );

test.describe.serial( 'cart, checkout and shipping', () => {
	test( 'the side cart opens after adding and changes quantities in place (CART-01, CART-03, CART-04)', async ( { page } ) => {
		const errors = watchErrors( page );
		await add( page, 'UM-003' );
		const drawer = page.locator( '#um-side-cart' );
		await expect( drawer ).toBeVisible();
		const line = drawer.locator( '[data-um-cart-line]' );
		await expect( line ).toHaveCount( 1 );
		await expect( drawer ).toContainText( 'هدفون' );
		await expect( drawer.locator( '.um-progress-note' ) ).toBeVisible();
		await line.locator( '[data-um-cart-step="1"]' ).click();
		await expect( line.locator( 'input[type="number"]' ) ).toHaveValue( '2' );
		await expect( page.locator( '[data-um-count="cart"]' ).first() ).toHaveText( '۲' );
		await line.locator( '[data-um-cart-step="-1"]' ).click();
		await expect( drawer.locator( '[data-um-cart-line] input[type="number"]' ) ).toHaveValue( '1' );
		await drawer.locator( '[data-um-cart-action="remove"]' ).click();
		await expect( drawer.locator( '[data-um-side-content][data-count="0"]' ) ).toBeVisible();
		// The header button still goes to the cart page without JavaScript.
		await expect( page.locator( '[data-um-open="um-side-cart"]' ).first() ).toHaveAttribute( 'href', /cart|page_id/ );
		expect( errors ).toEqual( [] );
	} );

	test( 'the cart page updates in place and moves items to buy later and back (CART-02, CART-03, CART-05)', async ( { page } ) => {
		const errors = watchErrors( page );
		await add( page, 'UM-003' );
		await add( page, 'UM-013' );
		await page.goto( '/?page_id=7' );
		const area = page.locator( '[data-um-component="cart"]' );
		await expect( area.locator( '[data-um-cart-line]' ) ).toHaveCount( 2 );
		await expect( area.locator( '[data-um-row="saving"]' ).first() ).toBeVisible();
		const total = await area.locator( 'dd[data-um-row="total"]' ).textContent();
		await area.locator( '[data-um-cart-line]' ).first().locator( '[data-um-cart-step="1"]' ).click();
		await expect( area.locator( 'dd[data-um-row="total"]' ) ).not.toHaveText( total ?? '' );
		await expect( area.locator( '.um-saved-items' ) ).toBeAttached();
		await area.locator( '[data-um-cart-action="later"]' ).first().click();
		await expect( area.locator( '[data-um-cart-line]' ) ).toHaveCount( 1 );
		await expect( area.locator( '[data-um-saved]' ) ).toHaveCount( 1 );
		await expect( area.locator( '[data-um-cart-status]' ) ).not.toBeEmpty();
		await page.getByRole( 'tab', { name: /بعد|later/i } ).click();
		await area.locator( '[data-um-cart-action="restore"]' ).first().click();
		await page.getByRole( 'tab', { name: /سبد|cart/i } ).first().click();
		await expect( area.locator( '[data-um-cart-line]' ) ).toHaveCount( 2 );
		// Complementary products, none of them already in the cart.
		await expect( page.locator( '.um-cart-page section' ).last() ).toBeVisible();
		expect( errors ).toEqual( [] );
	} );

	test( 'a discount code works on the cart and counts in the saving (CART-03)', async ( { page } ) => {
		await add( page, 'UM-003' );
		await page.goto( '/?page_id=7' );
		const area = page.locator( '[data-um-component="cart"]' );
		const saving = await area.locator( 'dd[data-um-row="saving"]' ).textContent();
		await area.locator( '[data-um-coupon-code]' ).fill( 'um-test10' );
		await area.locator( '[data-um-coupon-send]' ).click();
		await expect( area.locator( '[data-um-coupon-remove="um-test10"]' ) ).toBeVisible();
		await expect( area.locator( 'dd[data-um-row="saving"]' ) ).not.toHaveText( saving ?? '' );
		await area.locator( '[data-um-coupon-remove="um-test10"]' ).click();
		await expect( area.locator( '[data-um-coupon-remove]' ) ).toHaveCount( 0 );
	} );

	test( 'order limits stop the checkout with a clear message (CART-10)', async ( { page } ) => {
		await add( page, 'UM-003' );
		settings( { cart_min: 900000000 } );
		await page.goto( '/?page_id=7' );
		await expect( page.locator( '.um-cart-summary' ) ).toContainText( /۹۰۰.۰۰۰.۰۰۰/ );
		await expect( page.locator( '.um-cart-summary a[href]' ) ).toHaveCount( 0 );
		await page.goto( '/?page_id=8' );
		await expect( page.locator( 'body' ) ).toContainText( /۹۰۰.۰۰۰.۰۰۰/ );
		await expect( page.locator( '#place_order' ) ).toHaveCount( 0 );
		settings( { cart_min: 0 } );
	} );

	test( 'a whole purchase in the classic checkout, in steps (CHK-01 to CHK-05, CHK-08, CHK-09, SHIP-01, exit criterion)', async ( { page } ) => {
		const errors = watchErrors( page );
		await add( page, 'UM-003' );
		await add( page, 'UM-013' );
		await page.goto( '/?page_id=8' );
		const form = page.locator( 'form.um-checkout-form' );
		await expect( form ).toHaveAttribute( 'data-step', 'shipping' );
		await expect( page.locator( '#um-checkout-payment' ) ).toBeHidden();
		await fillClassic( page );
		await settled( page );
		// The post's price follows the weight; the courier only reaches its cities.
		await expect( page.locator( '.um-shipping-options' ) ).toContainText( 'پست پیشتاز ایران' );
		await expect( page.locator( '.um-shipping-options' ) ).toContainText( 'پیک تهران' );
		await page.locator( '.um-shipping-options label', { hasText: 'پست پیشتاز ایران' } ).click();
		await settled( page );
		await expect( page.locator( '.um-item-thumbs li' ) ).toHaveCount( 2 );
		await page.locator( '#um_national_code' ).fill( '0012345678' );
		await page.locator( '[data-um-next]' ).first().click();
		await expect( form ).toHaveAttribute( 'data-step', 'shipping' );
		await expect( page.locator( '#um_national_code_field .um-field-error' ) ).toBeVisible();
		await page.locator( '#um_national_code' ).fill( '۰۰۱۲۳۴۵۶۷۹' );
		await page.locator( '#um_marketing' ).check();
		await page.locator( '[data-um-next]' ).first().click();
		await expect( form ).toHaveAttribute( 'data-step', 'payment' );
		await expect( page.locator( '[data-um-step-item="payment"]' ) ).toHaveAttribute( 'aria-current', 'step' );
		await page.locator( 'label[for="payment_method_cod"]' ).click();
		await Promise.all( [ page.waitForURL( /order-received/, { timeout: 45_000 } ), page.locator( '#place_order' ).click() ] );
		await expect( page.locator( '.um-order-done' ) ).toBeVisible();
		await expect( page.locator( '.um-details-card' ).last() ).toContainText( '۰۹۱۲۳۴۵۶۷۸۹' );
		const order = /order-received\/(\d+)/.exec( page.url() )?.[ 1 ] ?? '';
		orders.push( order );
		expect( orderMeta( order, '_wc_other/ultra-max/national-code' ) ).toBe( '0012345679' );
		expect( orderMeta( order, '_wc_other/ultra-max/customer-type' ) ).toBe( 'person' );
		expect( orderMeta( order, '_wc_other/ultra-max/marketing' ) ).toBe( '1' );
		expect( wp( 'eval', `$o = wc_get_order( ${ Number( order ) } ); echo $o->get_billing_phone(), "|", $o->get_billing_postcode(), "|", $o->get_billing_city(), "|", $o->get_shipping_method();` ) ).toBe( '09123456789|1193653471|تهران|پست پیشتاز ایران' );
		expect( errors ).toEqual( [] );
	} );

	test( 'a company buys on one page on a phone, with company details (CHK-01, CHK-05, SHIP-02)', async ( { browser } ) => {
		settings( { checkout_mode: 'single' } );
		const context = await browser.newContext( { viewport: { width: 390, height: 844 }, locale: 'fa-IR' } );
		const page = await context.newPage();
		const errors = watchErrors( page );
		await add( page, 'UM-015' );
		await page.goto( '/?page_id=8' );
		await expect( page.locator( 'form.um-checkout-form' ) ).not.toHaveAttribute( 'data-step', /.+/ );
		await expect( page.locator( '.um-checkout-bar' ) ).toBeVisible();
		await fillClassic( page, 'شهریار' );
		await settled( page );
		await expect( page.locator( '.um-shipping-options label', { hasText: 'پیک تهران' } ) ).toContainText( /۱۲۰.۰۰۰/ );
		await page.locator( '.um-shipping-options label', { hasText: 'پیک تهران' } ).click();
		await settled( page );
		await page.locator( 'label[for="um_customer_type_company"]' ).click();
		await expect( page.locator( '#um_national_code' ) ).toBeHidden();
		await page.locator( '#um_company_name' ).fill( 'شرکت نمونه' );
		await page.locator( '#um_company_id' ).fill( '10100000006' );
		await page.locator( '#um_economic_code' ).fill( '411111111111' );
		await page.locator( 'label[for="payment_method_cod"]' ).click();
		const width = await page.evaluate( () => document.documentElement.scrollWidth );
		expect( width ).toBeLessThanOrEqual( 390 );
		await Promise.all( [ page.waitForURL( /order-received/, { timeout: 45_000 } ), page.locator( '.um-checkout-bar button[type="submit"]' ).click() ] );
		const order = /order-received\/(\d+)/.exec( page.url() )?.[ 1 ] ?? '';
		orders.push( order );
		expect( orderMeta( order, '_wc_other/ultra-max/company-id' ) ).toBe( '10100000006' );
		expect( orderMeta( order, '_wc_other/ultra-max/national-code' ) ).toBe( '' );
		expect( wp( 'eval', `echo wc_get_order( ${ Number( order ) } )->get_shipping_total();` ) ).toBe( '120000' );
		expect( errors ).toEqual( [] );
		await context.close();
		settings( { checkout_mode: 'steps' } );
	} );

	test( 'the courier does not reach other provinces and the post costs more there (SHIP-01, SHIP-02)', async ( { page } ) => {
		await add( page, 'UM-003' );
		await page.goto( '/?page_id=8' );
		await page.locator( '#billing_state' ).selectOption( 'ESF' );
		await settled( page );
		const options = page.locator( '.um-shipping-options' );
		await expect( options ).not.toContainText( 'پیک تهران' );
		await expect( options.locator( 'label', { hasText: 'پست پیشتاز ایران' } ) ).toContainText( /۹۵.۰۰۰/ );
		await expect( page.locator( '#billing_city option', { hasText: 'کاشان' } ) ).toHaveCount( 1 );
	} );

	test( 'a whole purchase in the block checkout (CHK-03, CHK-04, CHK-10, CART-02, exit criterion)', async ( { page } ) => {
		settings( { cart_design: 'woocommerce' } );
		await add( page, 'UM-003' );
		await page.goto( '/?page_id=7' );
		await expect( page.locator( '.wp-block-woocommerce-cart' ) ).toBeVisible();
		await expect( page.locator( '[data-um-component="cart"][data-context="blocks"]' ) ).toBeAttached();
		await expect( page.locator( '.um-block-extras' ).first() ).toBeVisible( { timeout: 30_000 } );
		await page.goto( '/?page_id=8' );
		await page.waitForSelector( '.wc-block-checkout' );
		await page.locator( '#email' ).fill( 'buyer@example.com' );
		await page.locator( '#contact-ultra-max-customer-type' ).selectOption( 'person' );
		await page.locator( '#contact-ultra-max-national-code' ).fill( '0012345678' );
		await page.locator( '#billing-first_name' ).fill( 'سارا' );
		await page.locator( '#billing-last_name' ).fill( 'محمدی' );
		await expect( page.locator( '#billing-city' ) ).toHaveAttribute( 'list', 'um-cities-billing' );
		await expect( page.locator( '#um-cities-billing option[value="شهریار"]' ) ).toHaveCount( 1 );
		await page.locator( '#billing-city' ).fill( 'تهران' );
		await page.locator( '#billing-address_1' ).fill( 'خیابان ولیعصر پلاک ۱۲' );
		await page.locator( '#billing-postcode' ).fill( '1193653471' );
		await page.locator( '#billing-phone' ).fill( '09123456789' );
		await page.locator( 'label:has(#radio-control-wc-payment-method-options-cod)' ).click();
		await page.waitForTimeout( 1500 );
		await page.locator( '.wc-block-components-checkout-place-order-button' ).click();
		await expect( page.locator( '.wc-block-components-notice-banner, .wc-block-components-validation-error' ).first() ).toContainText( /کد ملی|national code/i, { timeout: 20_000 } );
		await page.locator( '#contact-ultra-max-national-code' ).fill( '0012345679' );
		await Promise.all( [ page.waitForURL( /order-received/, { timeout: 45_000 } ), page.locator( '.wc-block-components-checkout-place-order-button' ).click() ] );
		const order = /order-received\/(\d+)/.exec( page.url() )?.[ 1 ] ?? '';
		orders.push( order );
		expect( orderMeta( order, '_wc_other/ultra-max/national-code' ) ).toBe( '0012345679' );
		settings( { cart_design: 'ultra-max' } );
	} );

	test( 'saved addresses fill the checkout and a logged in cart reaches other devices (CHK-02, CART-07)', async ( { browser }: { browser: Browser } ) => {
		const first = await browser.newContext( { locale: 'fa-IR' } );
		const one = await first.newPage();
		await login( one );
		await add( one, 'UM-003' );
		await one.goto( '/?page_id=8' );
		await fillClassic( one );
		await settled( one );
		await one.locator( '[data-um-next]' ).first().click();
		await one.locator( 'label[for="payment_method_cod"]' ).click();
		await Promise.all( [ one.waitForURL( /order-received/, { timeout: 45_000 } ), one.locator( '#place_order' ).click() ] );
		orders.push( /order-received\/(\d+)/.exec( one.url() )?.[ 1 ] ?? '' );
		// The order kept its address; the next checkout offers it.
		await add( one, 'UM-013' );
		const second = await browser.newContext( { locale: 'fa-IR' } );
		const two = await second.newPage();
		await login( two );
		await two.goto( '/?page_id=7' );
		await expect( two.locator( '[data-um-cart-line]' ) ).toHaveCount( 1 );
		await two.locator( '[data-um-cart-action="later"]' ).first().click();
		await expect( two.locator( '[data-um-saved]' ) ).toHaveCount( 1 );
		await one.goto( '/?page_id=7' );
		await expect( one.locator( '[data-um-saved]' ) ).toHaveCount( 1 );
		await one.locator( '[data-um-cart-action="restore"]' ).first().click();
		await one.goto( '/?page_id=8' );
		const card = one.locator( '[data-um-address]' ).first();
		await expect( card ).toContainText( 'ولیعصر' );
		await expect( one.locator( 'form.um-checkout-form' ) ).toHaveAttribute( 'data-address', 'saved' );
		await one.locator( '[data-um-address-new]' ).click();
		await expect( one.locator( '#billing_address_1' ) ).toHaveValue( '' );
		await card.click();
		await expect( one.locator( '#billing_address_1' ) ).toHaveValue( /ولیعصر/ );
		await first.close();
		await second.close();
	} );

	test( 'private, draft, password protected and hidden products never leak into the cart (phase 4 exit criterion)', async ( { page } ) => {
		const ids = wp( 'eval', 'echo implode( ",", get_posts( array( "post_type" => "product", "post_status" => "any", "meta_key" => "_um_leaktest", "fields" => "ids", "posts_per_page" => -1 ) ) );' ).split( ',' );
		await add( page, 'UM-013' );
		for ( const id of ids ) {
			const reply = await page.request.post( '/?wc-ajax=um_add_to_cart', { form: { um_product: id, quantity: '1' } } );
			expect( ( await reply.json() ).success ).toBe( false );
		}
		await page.goto( '/?page_id=7' );
		await expect( page.locator( 'body' ) ).not.toContainText( 'leaktest' );
		const side = await page.request.post( '/?wc-ajax=um_cart', { form: { um_action: 'view', context: 'side' } } );
		expect( await side.text() ).not.toContain( 'leaktest' );
	} );

	test( 'cart and checkout pass the accessibility check (CART-01, CHK-01)', async ( { page } ) => {
		await add( page, 'UM-003' );
		for ( const address of [ '/?page_id=7', '/?page_id=8' ] ) {
			await page.goto( address );
			const result = await new AxeBuilder( { page } ).include( '.um-cart-page, .um-checkout-page' ).withTags( [ 'wcag2a', 'wcag2aa' ] ).analyze();
			expect( result.violations.map( ( violation ) => `${ violation.id }: ${ violation.nodes.map( ( node ) => node.target.join( ' ' ) ).join( ', ' ) }` ) ).toEqual( [] );
		}
	} );

	test( 'switched off, the modules leave WooCommerce\'s own cart and checkout (ADM-07)', async ( { page } ) => {
		wp( 'option', 'update', 'ultra_max_modules', '{"cart":false,"checkout":false,"iran-shipping":false}', '--format=json' );
		await page.goto( '/?page_id=7' );
		await expect( page.locator( '[data-um-component="cart"]' ) ).toHaveCount( 0 );
		expect( await page.locator( 'link#ultra-max-ui-w-cart-css, style#ultra-max-ui-w-cart-inline-css' ).count() ).toBe( 0 );
		await page.goto( '/' );
		await expect( page.locator( '#um-side-cart' ) ).toHaveCount( 0 );
		wp( 'option', 'update', 'ultra_max_modules', '{}', '--format=json' );
	} );
} );
