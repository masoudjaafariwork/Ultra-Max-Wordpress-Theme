/**
 * Unit tests of phase 4 part A pieces that run without WordPress: the
 * address a filter form asks for, matching typed text to filter names,
 * the price range and the recent searches.
 */
import { describe, expect, it } from 'vitest';
import { fold, formUrl } from '../../packages/ui/widgets/shop/shop.behavior';
import { clampRange } from '../../packages/ui/widgets/shop/price-range.behavior';
import { remember } from '../../packages/ui/widgets/search/live-search.behavior';

describe( 'filter form addresses (PLP-01, PLP-13)', () => {
	it( 'joins several values of one name with commas', () => {
		expect( formUrl( 'https://shop.test/c/audio/', [ [ 'brand[]', 'lumo' ], [ 'brand[]', 'novix' ], [ 'in_stock', '1' ] ] ) ).toBe( 'https://shop.test/c/audio/?brand=lumo,novix&in_stock=1' );
	} );

	it( 'leaves out empty fields and untouched price bounds', () => {
		const url = formUrl( '/shop/', [ [ 'min_price', '0' ], [ 'max_price', '5000' ], [ 'filter_color[]', '' ] ], { min_price: '0', max_price: '9000' } );
		expect( url ).toBe( '/shop/?max_price=5000' );
	} );

	it( 'keeps the search words and drops the page number', () => {
		expect( formUrl( 'https://shop.test/?s=x&post_type=product&paged=3', [ [ 's', 'هدفون' ], [ 'post_type', 'product' ] ] ) ).toBe( 'https://shop.test/?s=%D9%87%D8%AF%D9%81%D9%88%D9%86&post_type=product' );
	} );
} );

describe( 'brand search inside the filters (SRCH-02)', () => {
	it( 'matches Arabic and Persian letters and ignores case', () => {
		expect( fold( 'كاويرا KAVIRA' ) ).toBe( 'کاویرا kavira' );
		expect( fold( 'نیم‌فاصله' ) ).toBe( 'نیم فاصله' );
	} );
} );

describe( 'price range (PLP-01)', () => {
	it( 'keeps the handles inside the bounds and in order', () => {
		expect( clampRange( -5, 50, 0, 100, 'from' ) ).toEqual( [ 0, 50 ] );
		expect( clampRange( 80, 60, 0, 100, 'from' ) ).toEqual( [ 60, 60 ] );
		expect( clampRange( 80, 60, 0, 100, 'to' ) ).toEqual( [ 80, 80 ] );
		expect( clampRange( 10, 500, 0, 100, 'to' ) ).toEqual( [ 10, 100 ] );
	} );
} );

describe( 'recent searches (SRCH-04)', () => {
	it( 'puts the newest first without repeats and keeps eight', () => {
		const list = [ 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h' ];
		expect( remember( list, '  B ' ) ).toEqual( [ 'B', 'a', 'c', 'd', 'e', 'f', 'g', 'h' ] );
		expect( remember( list, 'new' ) ).toHaveLength( 8 );
		expect( remember( list, '   ' ) ).toBe( list );
	} );
} );
