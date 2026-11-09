/**
 * Logs in to the wp-env site once and saves the admin session for the
 * suites that need it. Does nothing when the site is not running, so the
 * acceptance suite works without WordPress.
 */
import { chromium, type FullConfig } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

export const adminState = path.resolve( import.meta.dirname, '../../.cache/admin-state.json' );

export default async function globalSetup( config: FullConfig ): Promise<void> {
	const project = config.projects.find( ( p ) => p.name !== 'acceptance' );
	const baseURL = project?.use.baseURL ?? 'http://localhost:8888';
	try {
		const response = await fetch( `${ baseURL }/wp-login.php`, { signal: AbortSignal.timeout( 3000 ) } );
		if ( ! response.ok ) {
			return;
		}
	} catch {
		return;
	}

	const browser = await chromium.launch( { channel: project?.use.channel } );
	const page = await browser.newPage( { baseURL } );
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', 'admin' );
	await page.fill( '#user_pass', 'password' );
	await page.click( '#wp-submit' );
	await page.waitForURL( /wp-admin/ );
	fs.mkdirSync( path.dirname( adminState ), { recursive: true } );
	await page.context().storageState( { path: adminState } );
	await browser.close();
}
