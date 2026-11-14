/**
 * Unit tests of phase 4 part C pieces that run without WordPress: the
 * Iranian checks of the checkout (the same rules as Checkout\Iran in PHP)
 * and the bodies the cart behaviour sends.
 */
import { describe, expect, it } from 'vitest';
import { companyId, digits, economicCode, mobile, nationalCode, passes, postcode } from '../../packages/ui/widgets/checkout/iran';
import { bodyOf } from '../../packages/ui/widgets/cart/cart-actions';

describe( 'Iranian checks (CHK-04, CHK-05)', () => {
	it( 'reads Persian and Arabic digits as Latin ones', () => {
		expect( digits( '۰۹۱۲ ۳۴۵-۶۷۸۹' ) ).toBe( '09123456789' );
		expect( digits( '٠٩١٢' ) ).toBe( '0912' );
	} );

	it( 'accepts mobile numbers however they are written', () => {
		expect( mobile( '۰۹۱۲۳۴۵۶۷۸۹' ) ).toBe( '09123456789' );
		expect( mobile( '+98 912 345 6789' ) ).toBe( '09123456789' );
		expect( mobile( '00989123456789' ) ).toBe( '09123456789' );
		expect( mobile( '9123456789' ) ).toBe( '09123456789' );
		expect( mobile( '02112345678' ) ).toBeNull();
		expect( mobile( '0912345' ) ).toBeNull();
	} );

	it( 'checks the check digit of a national code', () => {
		expect( nationalCode( '0012345679' ) ).toBe( true );
		expect( nationalCode( '۰۰۱۲۳۴۵۶۷۹' ) ).toBe( true );
		expect( nationalCode( '0012345678' ) ).toBe( false );
		expect( nationalCode( '1111111111' ) ).toBe( false );
		expect( nationalCode( '12345' ) ).toBe( false );
	} );

	it( 'checks the check digit of a company id', () => {
		expect( companyId( '10100000006' ) ).toBe( true );
		expect( companyId( '10860154602' ) ).toBe( true );
		expect( companyId( '10860154603' ) ).toBe( false );
		expect( companyId( '1010000000' ) ).toBe( false );
	} );

	it( 'follows the postal code rule', () => {
		expect( postcode( '1193653471' ) ).toBe( true );
		expect( postcode( '۱۱۹۳۶۵۳۴۷۱' ) ).toBe( true );
		expect( postcode( '1234567890' ) ).toBe( false );
		expect( postcode( '1111653471' ) ).toBe( false );
		expect( postcode( '12345' ) ).toBe( false );
	} );

	it( 'accepts economic codes of ten to fourteen digits', () => {
		expect( economicCode( '411111111111' ) ).toBe( true );
		expect( economicCode( '4111' ) ).toBe( false );
	} );

	it( 'lets empty values pass, as required fields are checked apart', () => {
		expect( passes( 'national_code', '' ) ).toBe( true );
		expect( passes( 'mobile', 'abc' ) ).toBe( false );
		expect( passes( 'unknown', 'anything' ) ).toBe( true );
	} );
} );

describe( 'cart changes (CART-01, CART-02)', () => {
	it( 'sends the change, the token and the parts to draw again', () => {
		const body = bodyOf( { action: 'set', key: 'abc', qty: 3 }, 'n1', 'cart', { lines: '', summary: '{"sticky":false}' } );
		expect( body.get( 'um_action' ) ).toBe( 'set' );
		expect( body.get( 'key' ) ).toBe( 'abc' );
		expect( body.get( 'qty' ) ).toBe( '3' );
		expect( body.get( 'nonce' ) ).toBe( 'n1' );
		expect( body.get( 'parts[summary]' ) ).toBe( '{"sticky":false}' );
		expect( body.has( 'code' ) ).toBe( false );
	} );
} );
