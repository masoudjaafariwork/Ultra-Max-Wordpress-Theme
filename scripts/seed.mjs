/**
 * Fills the development site with a small demo store (development only).
 *
 * 1. Turns the product drawings of design/assets/img/products into PNG
 *    files with the installed browser, because WordPress does not accept
 *    SVG uploads.
 * 2. Runs tools/seed/seed.php with WP-CLI.
 *
 * Usage: npm run seed
 */
import fs from 'node:fs';
import path from 'node:path';
import { chromium } from '@playwright/test';
import { root, run } from './lib/run.mjs';

const source = path.join( root, 'design', 'assets', 'img', 'products' );
const target = path.join( root, '.cache', 'seed' );
fs.mkdirSync( target, { recursive: true } );

const channel = process.env.PW_CHANNEL ?? ( process.platform === 'win32' ? 'msedge' : undefined );
const browser = await chromium.launch( { channel } );
const page = await browser.newPage( { viewport: { width: 600, height: 600 } } );
for ( const file of fs.readdirSync( source ).filter( ( name ) => name.endsWith( '.svg' ) ) ) {
	const svg = fs.readFileSync( path.join( source, file ), 'utf8' );
	await page.setContent( `<html><body style="margin:0;background:#fff;display:grid;place-items:center;width:600px;height:600px"><div style="width:480px;height:480px">${ svg.replace( '<svg', '<svg width="480" height="480"' ) }</div></body></html>` );
	await page.screenshot( { path: path.join( target, file.replace( '.svg', '.png' ) ) } );
}
await browser.close();
console.log( `Rasterised ${ fs.readdirSync( target ).length } images.` );

run( 'docker', [ 'compose', 'run', '--rm', '--no-deps', 'cli', 'wp', 'eval-file', 'wp-content/ultra-max-dev/tools/seed/seed.php' ] );
