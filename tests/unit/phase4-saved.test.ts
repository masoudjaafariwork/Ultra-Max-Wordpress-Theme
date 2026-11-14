/**
 * Unit tests of phase 4 part D pieces that run without WordPress: reading
 * the saved lists from cookies and showing their counts.
 */
import { describe, expect, it } from 'vitest';
import { formatCount, parseIds, readCookie } from '../../packages/ui/widgets/saved/saved.behavior';

describe( 'saved lists in cookies (SAVE-01, SAVE-02)', () => {
	it( 'keeps positive unique ids in their order', () => {
		expect( parseIds( '12.7.12.0.-3.x.40' ) ).toEqual( [ 12, 7, 40 ] );
		expect( parseIds( '' ) ).toEqual( [] );
	} );

	it( 'reads one cookie among others', () => {
		const source = 'wp_lang=fa_IR; um_wishlist=5.9; um_compare=3';
		expect( readCookie( 'um_wishlist', source ) ).toBe( '5.9' );
		expect( readCookie( 'um_compare', source ) ).toBe( '3' );
		expect( readCookie( 'um_missing', source ) ).toBe( '' );
	} );
} );

describe( 'counters (SAVE-01)', () => {
	it( 'hides zero and uses the page language digits', () => {
		expect( formatCount( 0, 'fa-IR' ) ).toBe( '' );
		expect( formatCount( 12, 'fa-IR' ) ).toBe( '۱۲' );
		expect( formatCount( 3, 'en-US' ) ).toBe( '3' );
	} );
} );
