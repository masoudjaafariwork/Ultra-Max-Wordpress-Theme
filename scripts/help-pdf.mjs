/**
 * Builds the Persian help.pdf (REL-03) from docs/help/fa/*.md.
 *
 * Markdown becomes one right-to-left HTML document set in Vazirmatn, which
 * Chromium prints to A4 with page numbers. The date on the cover uses the
 * Jalali calendar, as Rtl-theme requires.
 *
 * Usage: node scripts/help-pdf.mjs [--out=dist/help.pdf] [--html]  (--html also keeps the HTML for review)
 */
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { marked } from 'marked';
import { chromium } from '@playwright/test';
import { root } from './lib/run.mjs';

const out = path.resolve( root, ( process.argv.find( ( a ) => a.startsWith( '--out=' ) ) ?? '--out=dist/help.pdf' ).slice( 6 ) );
const source = path.join( root, 'docs', 'help', 'fa' );
const version = fs.readFileSync( path.join( root, 'themes', 'ultra-max', 'style.css' ), 'utf8' ).match( /Version:\s*(\S+)/ )?.[ 1 ] ?? '';
const font = pathToFileURL( path.join( root, 'packages', 'ui', 'fonts', 'vazirmatn', 'Vazirmatn-Variable.woff2' ) ).href;
const date = new Intl.DateTimeFormat( 'fa-IR-u-ca-persian', { year: 'numeric', month: 'long', day: 'numeric' } ).format( new Date() );
const toPersianDigits = ( text ) => text.replace( /\d/g, ( d ) => '۰۱۲۳۴۵۶۷۸۹'[ d ] );

const chapters = fs
	.readdirSync( source )
	.filter( ( file ) => file.endsWith( '.md' ) )
	.sort()
	.map( ( file ) => `<section class="chapter">${ marked.parse( fs.readFileSync( path.join( source, file ), 'utf8' ) ) }</section>` )
	.join( '\n' );

const html = `<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<style>
@font-face { font-family: Vazirmatn; src: url("${ font }") format("woff2"); font-weight: 100 900; }
@page { size: A4; margin: 22mm 18mm 20mm; }
body { font-family: Vazirmatn, sans-serif; font-size: 11pt; line-height: 1.9; color: #15161c; }
.cover { height: 230mm; display: flex; flex-direction: column; justify-content: center; text-align: center; }
.cover h1 { font-size: 30pt; margin: 0 0 6mm; color: #3d34ad; }
.cover p { margin: 0; color: #4f5361; }
.chapter { break-before: page; }
h1 { font-size: 20pt; color: #3d34ad; margin: 0 0 6mm; }
h2 { font-size: 14pt; margin: 8mm 0 3mm; }
table { width: 100%; border-collapse: collapse; margin: 4mm 0; }
th, td { border: 1px solid #dfe1e7; padding: 2mm 3mm; text-align: start; vertical-align: top; }
th { background: #f4f5f7; }
code { font-family: Consolas, monospace; direction: ltr; unicode-bidi: isolate; background: #f4f5f7; padding: 0 1mm; border-radius: 1mm; }
li { margin-block: 1mm; }
ol { list-style-type: persian; }
</style>
</head>
<body>
<section class="cover">
<h1>راهنمای الترا مکس</h1>
<p>نسخه ${ toPersianDigits( version ) }</p>
<p>${ date }</p>
</section>
${ chapters }
</body>
</html>`;

const channel = process.env.PW_CHANNEL ?? ( process.platform === 'win32' && ! process.env.CI ? 'msedge' : undefined );
const browser = await chromium.launch( { channel } );
const page = await browser.newPage();
await page.setContent( html, { waitUntil: 'load' } );
if ( process.argv.includes( '--html' ) ) {
	fs.mkdirSync( path.dirname( out ), { recursive: true } );
	fs.writeFileSync( out.replace( /\.pdf$/, '.html' ), html );
}
await page.evaluate( () => document.fonts.ready );
fs.mkdirSync( path.dirname( out ), { recursive: true } );
await page.pdf( {
	path: out,
	format: 'A4',
	printBackground: true,
	displayHeaderFooter: true,
	headerTemplate: '<span></span>',
	footerTemplate: '<div style="width:100%;text-align:center;font-size:9px;color:#6f7381;"><span class="pageNumber"></span></div>',
	margin: { top: '22mm', bottom: '20mm', left: '18mm', right: '18mm' },
} );
await browser.close();
console.log( `Wrote ${ path.relative( root, out ) }` );
