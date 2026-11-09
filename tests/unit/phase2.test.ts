/**
 * Unit tests of phase 2 pieces that run without WordPress.
 */
import fs from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vitest';
import { contrast, mix, on, ramp } from '../../packages/ui/admin/lib/color';
import { isScheduled } from '../../packages/ui/components/notice-bar/notice-bar.behavior';

const root = path.resolve( import.meta.dirname, '../..' );

describe( 'colour ramp (same results as Color.php, see FoundationTest)', () => {
	it( 'builds the brand ramp', () => {
		const result = ramp( '#4C42D3' );
		expect( result[ 600 ] ).toBe( '#4c42d3' );
		expect( result[ 700 ] ).toBe( '#3d35a9' );
		expect( Object.keys( result ).map( Number ) ).toEqual( [ 50, 100, 200, 400, 500, 600, 700, 800 ] );
	} );

	it( 'picks readable text and measures contrast', () => {
		expect( on( '#4c42d3' ) ).toBe( '#ffffff' );
		expect( on( '#ffd400' ) ).toBe( '#15161c' );
		expect( contrast( '#000', '#fff' ) ).toBeCloseTo( 21, 1 );
		expect( mix( '#ff0000', 50, '#0000ff' ) ).toBe( '#800080' );
	} );
} );

describe( 'notice bar schedule', () => {
	const bar = ( start?: number, end?: number ) => {
		const element = { dataset: {} as Record<string, string> } as unknown as HTMLElement;
		if ( start !== undefined ) element.dataset.start = String( start );
		if ( end !== undefined ) element.dataset.end = String( end );
		return element;
	};
	it( 'shows only between its dates', () => {
		expect( isScheduled( bar(), 100 ) ).toBe( true );
		expect( isScheduled( bar( 200 ), 100 ) ).toBe( false );
		expect( isScheduled( bar( 50, 90 ), 100 ) ).toBe( false );
		expect( isScheduled( bar( 50, 150 ), 100 ) ).toBe( true );
	} );
} );

describe( 'storefront scripts without jQuery (PERF-04)', () => {
	const build = path.join( root, 'build', 'ui' );
	const entries = [ 'storefront', 'modules/notice-bar', 'modules/contact-buttons', 'modules/back-to-top' ];
	it.each( entries )( '%s has no jQuery dependency', ( entry ) => {
		const manifest = path.join( build, `${ entry }.asset.php` );
		if ( ! fs.existsSync( manifest ) ) {
			return;
		}
		expect( fs.readFileSync( manifest, 'utf8' ) ).not.toMatch( /jquery/i );
		expect( fs.readFileSync( path.join( build, `${ entry }.js` ), 'utf8' ) ).not.toMatch( /\bjQuery\b/ );
	} );
} );

describe( 'help articles (ADM-06, REL-03)', () => {
	const dir = path.join( root, 'docs', 'help', 'fa' );
	it( 'have a title and no outside links', () => {
		for ( const file of fs.readdirSync( dir ) ) {
			const text = fs.readFileSync( path.join( dir, file ), 'utf8' );
			expect( text, file ).toMatch( /^# \S/ );
			expect( text, file ).not.toMatch( /https?:\/\//i );
		}
	} );
} );
