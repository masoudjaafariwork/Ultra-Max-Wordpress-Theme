/**
 * Renders the theme's screenshot.png (1200 x 900, as WordPress expects)
 * from the approved home page prototype in design/. Phase 2 switches this
 * to the real storefront once its templates exist.
 */
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { chromium } from '@playwright/test';
import { root } from './lib/run.mjs';

const channel = process.env.PW_CHANNEL ?? ( process.platform === 'win32' && ! process.env.CI ? 'msedge' : undefined );
const browser = await chromium.launch( { channel } );
const page = await browser.newPage( { viewport: { width: 1200, height: 900 }, deviceScaleFactor: 1 } );
await page.goto( pathToFileURL( path.join( root, 'design', 'home.html' ) ).href );
await page.waitForLoadState( 'networkidle' );
await page.screenshot( { path: path.join( root, 'themes', 'ultra-max', 'screenshot.png' ) } );
await browser.close();
console.log( 'Wrote themes/ultra-max/screenshot.png' );