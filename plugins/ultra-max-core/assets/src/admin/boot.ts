/**
 * What PHP passes to the panel (Admin::data), the REST client and strings.
 */

export interface Option {
	value: string;
	label: string;
	color?: string;
	ramp?: Record<string, string>;
}

export interface Field {
	id: string;
	type: string;
	label: string;
	help?: string;
	default?: unknown;
	options?: Option[];
	min?: number;
	max?: number;
	step?: number;
	unit?: string;
	language?: 'css' | 'js' | 'html';
	accept?: string;
	placeholder?: string;
	keywords?: string;
	token?: string;
	show_if?: { field: string; equals: unknown };
	fields?: Field[];
	max_rows?: number;
}

export interface Group {
	id: string;
	title: string;
	icon?: string;
	features?: string[];
	module?: string;
	description?: string;
	fields: Field[];
}

export interface Section {
	id: string;
	title: string;
	icon: string;
	order: number;
	groups: Group[];
}

export interface Backup {
	id: string;
	label: string;
	created: number;
	count: number;
}

export interface Module {
	id: string;
	title: string;
	description: string;
	icon: string;
	features: string[];
	default: boolean;
	section?: string;
	product: string;
	on: boolean;
	bytes: number;
}

export interface Check {
	id: string;
	status: 'good' | 'warning' | 'critical';
	label: string;
	description: string;
	value: string;
}

export interface Product {
	slug: string;
	name: string;
	type: 'theme' | 'plugin';
	version: string;
	active: boolean;
	installed: boolean;
	update: string;
}

export interface WizardPlugin {
	slug: string;
	name: string;
	description: string;
	source: 'wordpress.org' | 'package';
	required: boolean;
	status: 'active' | 'inactive' | 'missing';
}

export type Values = Record<string, unknown>;

export interface Boot {
	page: 'dashboard' | 'settings' | 'modules' | 'wizard' | 'help';
	rest: { root: string; nonce: string };
	brand: { name: string; version: string };
	locale: string;
	strings: Record<string, string>;
	urls: { dashboard: string; settings: string; modules: string; wizard: string; help: string; site: string; menus: string; widgets: string; plugins: string; demoImage: string };
	tour: boolean;
	settings?: {
		sections: Section[];
		values: Values;
		defaults: Values;
		backups: Backup[];
		fonts: Record<string, { family: string; url: string }>;
		files: Record<string, string>;
		canScripts: boolean;
		modules: Record<string, boolean>;
		canModules: boolean;
	};
	modules?: Module[];
	dashboard?: {
		products: Product[];
		mismatched: string[];
		changelog: { version: string; date: string; items: string[] }[];
		checks: Check[];
		summary: { good: number; warning: number; critical: number };
		report: string;
		license: { market: string; status: string };
		store: { products: number; pending: number; orders: number; days: { label: string; value: number }[] };
	};
	wizard?: {
		plugins: WizardPlugin[];
		demos: { id: string; name: string; description: string; colors: [ string, string, string ]; image: string }[];
		name: string;
		logo: number;
		preset: string;
		color: string;
		presets: Option[];
	};
	help?: { id: string; title: string; html: string }[];
}

declare global {
	interface Window {
		ultraMaxAdmin?: Boot;
	}
}

export const boot = window.ultraMaxAdmin as Boot;

/**
 * A translated panel string; `%s`, `%1$s` and `%2$s` are replaced in order.
 *
 * @param key  String key from Strings.php.
 * @param args Values for the placeholders.
 */
export function t( key: string, ...args: ( string | number )[] ): string {
	let text = boot.strings[ key ] ?? key;
	args.forEach( ( arg, index ) => {
		text = text.replace( `%${ index + 1 }$s`, String( arg ) ).replace( '%s', String( arg ) );
	} );
	return text;
}

const persian = boot.locale.startsWith( 'fa' );

/**
 * Digits in the panel's language: Persian on a Persian dashboard.
 *
 * @param value Text or number.
 */
export function digits( value: string | number ): string {
	const text = String( value );
	return persian ? text.replace( /[0-9]/g, ( digit ) => '۰۱۲۳۴۵۶۷۸۹'[ Number( digit ) ] ?? digit ) : text;
}

/** A REST error with the server's message. */
export class ApiError extends Error {}

/**
 * Calls a route of the panel's REST namespace.
 *
 * @param path   Route below ultra-max/v1/.
 * @param method HTTP method.
 * @param body   JSON body.
 */
export async function api<T>( path: string, method: 'GET' | 'POST' = 'GET', body?: unknown ): Promise<T> {
	const response = await fetch( boot.rest.root + path, {
		method,
		credentials: 'same-origin',
		headers: { 'X-WP-Nonce': boot.rest.nonce, ...( body === undefined ? {} : { 'Content-Type': 'application/json' } ) },
		body: body === undefined ? undefined : JSON.stringify( body ),
	} );
	const data: unknown = await response.json().catch( () => null );
	if ( ! response.ok ) {
		const message = data && typeof data === 'object' && 'message' in data && typeof data.message === 'string' ? data.message : t( 'errorGeneric' );
		throw new ApiError( message );
	}
	return data as T;
}

/**
 * A Jalali date and time for a Unix timestamp, in the browser's time zone.
 *
 * @param seconds Unix timestamp.
 */
export function jalali( seconds: number ): string {
	try {
		return new Intl.DateTimeFormat( persian ? 'fa-IR-u-ca-persian' : 'en-US-u-ca-persian', { dateStyle: 'medium', timeStyle: 'short' } ).format( new Date( seconds * 1000 ) );
	} catch {
		return new Date( seconds * 1000 ).toLocaleString();
	}
}
