/**
 * Unit tests of phase 3 pieces that run without WordPress: the cascade
 * layer step of the build, widget asset folders, the template library and
 * the small helpers of component behaviours.
 */
import fs from 'node:fs';
import path from 'node:path';
import postcss from 'postcss';
import { describe, expect, it } from 'vitest';
import { dropKnown, LAYER_ORDER, layeredRuleKeys, layerFor, layerMedia } from '../../scripts/lib/layer-media.mjs';
import { pad, split } from '../../packages/ui/widgets/countdown/countdown.behavior';
import { formatNumber } from '../../packages/ui/widgets/counter/counter.behavior';
import { matches } from '../../packages/ui/widgets/discount-picks/filter-chips.behavior';

const root = path.resolve( import.meta.dirname, '../..' );

describe( 'responsive rules keep winning across stylesheets (BLD-12)', () => {
	it( 'puts each media query in its own ordered layer', () => {
		expect( layerFor( '(min-width:64rem)' ) ).toBe( 'utilities-min-64' );
		expect( layerFor( 'not all and (min-width:48rem)' ) ).toBe( 'utilities-max-48' );
		expect( layerFor( '(max-width:767px)' ) ).toBe( 'utilities-max-48' );
		expect( layerFor( '(hover:hover)' ) ).toBe( 'utilities-hover' );
		expect( layerFor( '(prefers-reduced-motion:reduce)' ) ).toBe( 'utilities-motion' );
		expect( layerFor( '(min-width:33rem)' ) ).toBeNull();
		expect( LAYER_ORDER.indexOf( 'utilities' ) ).toBeLessThan( LAYER_ORDER.indexOf( 'utilities-min-48' ) );
		expect( LAYER_ORDER.indexOf( 'utilities-min-48' ) ).toBeLessThan( LAYER_ORDER.indexOf( 'utilities-min-64' ) );
		expect( LAYER_ORDER.indexOf( 'utilities-max-64' ) ).toBeLessThan( LAYER_ORDER.indexOf( 'utilities-max-48' ) );
	} );

	it( 'moves the rules out and declares the full order first', async () => {
		const input = '@layer theme, base, components, utilities;@layer utilities{.grid-cols-2{x:1}@media (min-width:64rem){.lg\\:grid-cols-4{x:2}}}';
		const { css } = await postcss( [ layerMedia() ] ).process( input, { from: undefined } );
		expect( css.startsWith( `@layer ${ LAYER_ORDER.join( ', ' ) };` ) ).toBe( true );
		expect( css ).toContain( '@layer utilities-min-64{@media (min-width:64rem)' );
		expect( css.match( /@layer theme, base/g ) ?? [] ).toHaveLength( 0 );
	} );

	it( 'leaves out of later files the rules the storefront file already has', async () => {
		const storefront = await postcss( [ layerMedia() ] ).process( '@layer utilities{.border-0{border-width:0}.border-b-3{border-bottom-width:3px}}', { from: undefined } );
		const known = layeredRuleKeys( storefront.root );
		const widget = await postcss( [ layerMedia(), dropKnown( known ) ] ).process( '@layer utilities{.border-0{border-width:0}.gap-2{gap:.5rem}}@layer utilities{@media (min-width:64rem){.lg\\:hidden{display:none}}}@keyframes um-spin{0%{opacity:0}}', { from: undefined } );
		expect( widget.css ).not.toContain( '.border-0' );
		expect( widget.css ).toContain( '.gap-2{gap:.5rem}' );
		expect( widget.css ).toContain( '.lg\\:hidden' );
		expect( widget.css ).toContain( '0%{opacity:0}' );
		expect( widget.css.startsWith( `@layer ${ LAYER_ORDER.join( ', ' ) };` ) ).toBe( true );
	} );

	it( 'also leaves out a selector the storefront file styles in a merged rule', async () => {
		const storefront = await postcss( [ layerMedia() ] ).process( '@layer utilities{.text-danger,.text-danger-text{color:red}@supports (color:color-mix(in lab, red, red)){.text-danger-text{color:darkred}}}', { from: undefined } );
		const known = layeredRuleKeys( storefront.root );
		const widget = await postcss( [ layerMedia(), dropKnown( known ) ] ).process( '@layer utilities{.text-danger-text{color:red}.text-link,.text-danger{color:red}@supports (color:color-mix(in lab, red, red)){.text-danger-text{color:darkred}}}', { from: undefined } );
		expect( widget.css ).not.toContain( '.text-danger-text' );
		expect( widget.css ).toContain( '.text-link{color:red}' );
	} );

	it( 'is applied to every built widget stylesheet', () => {
		const built = path.join( root, 'plugins/ultra-max-core/assets/build/ui/widgets' );
		const files = fs.existsSync( built ) ? fs.readdirSync( built ).filter( ( file ) => file.endsWith( '.css' ) ) : [];
		for ( const file of files ) {
			expect( fs.readFileSync( path.join( built, file ), 'utf8' ).startsWith( `@layer ${ LAYER_ORDER[ 0 ] }` ), file ).toBe( true );
		}
	} );
} );

describe( 'widget assets and templates', () => {
	it( 'lists every widget folder in the PHP asset list', () => {
		const php = fs.readFileSync( path.join( root, 'packages/ui/src/Assets.php' ), 'utf8' );
		const listed = [ ...( php.match( /WIDGETS = array\(([\s\S]*?)\);/ )?.[ 1 ] ?? '' ).matchAll( /'([a-z-]+)'/g ) ].map( ( match ) => match[ 1 ] ).sort();
		const folders = fs.readdirSync( path.join( root, 'packages/ui/widgets' ), { withFileTypes: true } ).filter( ( entry ) => entry.isDirectory() ).map( ( entry ) => entry.name ).sort();
		expect( listed ).toEqual( folders );
	} );

	it( 'has 8 desktop and 8 phone headers with the right devices (HDR-03, HDR-04)', () => {
		const dir = path.join( root, 'plugins/ultra-max-core/templates/library/header' );
		const files = fs.readdirSync( dir );
		expect( files.filter( ( file ) => file.startsWith( 'desktop-' ) ) ).toHaveLength( 8 );
		expect( files.filter( ( file ) => file.startsWith( 'mobile-' ) ) ).toHaveLength( 8 );
		for ( const file of files ) {
			const data = JSON.parse( fs.readFileSync( path.join( dir, file ), 'utf8' ) );
			expect( data.devices.desktop, file ).toBe( file.startsWith( 'desktop-' ) );
			expect( data.devices.mobile, file ).toBe( file.startsWith( 'mobile-' ) );
		}
	} );

	it( 'keeps every template text in the translation list', () => {
		const php = fs.readFileSync( path.join( root, 'plugins/ultra-max-core/src/Builder/library-strings.php' ), 'utf8' );
		const dir = path.join( root, 'plugins/ultra-max-core/templates/library' );
		for ( const kind of fs.readdirSync( dir ) ) {
			for ( const file of fs.readdirSync( path.join( dir, kind ) ).filter( ( name ) => name.endsWith( '.json' ) ) ) {
				const data = JSON.parse( fs.readFileSync( path.join( dir, kind, file ), 'utf8' ) );
				expect( php, `${ kind }/${ file }` ).toContain( `'${ data.title.replace( /'/g, "\\'" ) }'` );
			}
		}
	} );
} );

describe( 'behaviour helpers', () => {
	it( 'splits and pads countdown time in Persian digits', () => {
		expect( split( 90061 ) ).toEqual( [ 1, 1, 1, 1 ] );
		expect( split( -5 ) ).toEqual( [ 0, 0, 0, 0 ] );
		expect( pad( 7, 'fa' ) ).toBe( '۰۷' );
		expect( pad( 7, 'en' ) ).toBe( '07' );
	} );

	it( 'formats counters', () => {
		expect( formatNumber( 12000, 'fa' ) ).toBe( '۱۲٬۰۰۰' );
		expect( formatNumber( 12000.4, 'en' ) ).toBe( '12,000' );
	} );

	it( 'matches filter chip keys exactly', () => {
		expect( matches( ' c12 c3 ', 'c3' ) ).toBe( true );
		expect( matches( ' c12 c3 ', 'c1' ) ).toBe( false );
		expect( matches( ' c12 ', '' ) ).toBe( true );
		expect( matches( undefined, 'c1' ) ).toBe( false );
	} );
} );
