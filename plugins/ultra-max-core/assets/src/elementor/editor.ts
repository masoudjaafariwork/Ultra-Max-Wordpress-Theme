/**
 * Elementor editor helpers:
 *
 * 1. The "um-search" control: choose products, posts or categories by
 *    typing; choices come from the server only while editing.
 * 2. The ready-made designs library (HOME-13): a button next to Elementor's
 *    own "add template" button opens our sections and pages and inserts
 *    the chosen one into the page.
 *
 * Elementor's editor API has no types, so only the parts used here are
 * described below.
 */

interface Config {
	rest: { root: string; nonce: string };
	brand: string;
	strings: Record<string, string>;
}

interface Template {
	id: string;
	kind: string;
	title: string;
	description: string;
	preview: string;
	tags: string[];
}

interface ControlView {
	model: { get: ( key: string ) => unknown };
	ui: { select: { append: ( option: HTMLOptionElement ) => void } };
	getControlValue: () => unknown;
	getSelect2DefaultOptions: () => Record<string, unknown>;
	umLoaded?: boolean;
}

interface ControlClass {
	prototype: Record<string, ( ...args: unknown[] ) => unknown>;
	extend: ( methods: Record<string, unknown> ) => unknown;
}

interface Elementor {
	addControlView: ( name: string, view: unknown ) => void;
	modules: { controls: { Select2: ControlClass } };
	on: ( event: string, callback: () => void ) => void;
	$preview?: { 0?: HTMLIFrameElement };
	getPreviewContainer: () => unknown;
	getPreviewView?: () => { collection?: { length: number } };
}

declare global {
	interface Window {
		ultraMaxElementor?: Config;
		elementor?: Elementor;
		$e?: { run: ( command: string, args: Record<string, unknown> ) => unknown };
	}
}

const config = window.ultraMaxElementor;

/**
 * A text of the editor.
 *
 * @param key String key.
 */
const t = ( key: string ): string => config?.strings[ key ] ?? key;

/**
 * Calls our REST namespace.
 *
 * @param path Route and query.
 */
async function get<T>( path: string ): Promise<T> {
	const response = await fetch( ( config?.rest.root ?? '' ) + path, { credentials: 'same-origin', headers: { 'X-WP-Nonce': config?.rest.nonce ?? '' } } );
	if ( ! response.ok ) {
		throw new Error( t( 'error' ) );
	}
	return ( await response.json() ) as T;
}

/* ------------------------------------------------------------------ search control */

/** Registers the search control view, based on Elementor's own select2 control. */
function registerSearchControl( elementor: Elementor ): void {
	const Select2 = elementor.modules.controls.Select2;
	elementor.addControlView(
		'um-search',
		Select2.extend( {
			getSelect2Options( this: ControlView ) {
				const source = String( this.model.get( 'source' ) ?? 'product' );
				return {
					...this.getSelect2DefaultOptions(),
					minimumInputLength: 0,
					placeholder: t( 'search' ),
					ajax: {
						delay: 250,
						transport: ( params: { data: { term?: string } }, success: ( data: unknown ) => void, failure: () => void ) => {
							get( `elementor/search?source=${ encodeURIComponent( source ) }&q=${ encodeURIComponent( params.data.term ?? '' ) }` ).then( success, failure );
							return { abort: () => undefined };
						},
						processResults: ( data: { results: unknown[] } ) => ( { results: data.results } ),
					},
				};
			},
			applySavedValue( this: ControlView, ...args: unknown[] ) {
				const base = Select2.prototype.applySavedValue;
				const value = this.getControlValue();
				const ids = ( Array.isArray( value ) ? value : [ value ] ).map( String ).filter( ( id ) => id && id !== 'undefined' && id !== 'null' );
				if ( ids.length && ! this.umLoaded ) {
					this.umLoaded = true;
					const source = String( this.model.get( 'source' ) ?? 'product' );
					get<{ results: { id: string; text: string }[] }>( `elementor/search?source=${ encodeURIComponent( source ) }&include=${ ids.join( ',' ) }` )
						.then( ( data ) => {
							data.results.forEach( ( item ) => this.ui.select.append( new Option( item.text, item.id, true, true ) ) );
							base?.apply( this, args );
						} )
						.catch( () => base?.apply( this, args ) );
					return undefined;
				}
				return base?.apply( this, args );
			},
		} )
	);
}

/* ------------------------------------------------------------------ library */

let dialog: HTMLDialogElement | null = null;
let insertAt: number | undefined;

/** Builds the library window once. */
function libraryDialog(): HTMLDialogElement {
	if ( dialog ) {
		return dialog;
	}
	dialog = document.createElement( 'dialog' );
	dialog.className = 'um-library';
	dialog.setAttribute( 'aria-label', t( 'library' ) );
	dialog.innerHTML = `
		<header class="um-library__head">
			<b>${ escape( config?.brand ?? '' ) } · ${ escape( t( 'library' ) ) }</b>
			<div class="um-library__tabs" role="tablist">
				<button type="button" role="tab" aria-selected="true" data-kind="sections">${ escape( t( 'sections' ) ) }</button>
				<button type="button" role="tab" aria-selected="false" data-kind="pages">${ escape( t( 'pages' ) ) }</button>
			</div>
			<button type="button" class="um-library__close" aria-label="${ escape( t( 'close' ) ) }">×</button>
		</header>
		<div class="um-library__body" aria-live="polite"><p>${ escape( t( 'loading' ) ) }</p></div>`;
	document.body.append( dialog );
	dialog.querySelector( '.um-library__close' )?.addEventListener( 'click', () => dialog?.close() );
	dialog.querySelectorAll<HTMLButtonElement>( '[data-kind]' ).forEach( ( tab ) => {
		tab.addEventListener( 'click', () => {
			dialog?.querySelectorAll( '[data-kind]' ).forEach( ( other ) => other.setAttribute( 'aria-selected', String( other === tab ) ) );
			void showKind( tab.dataset.kind === 'pages' ? 'pages' : 'sections' );
		} );
	} );
	return dialog;
}

/**
 * Escapes text for markup.
 *
 * @param text Text.
 */
function escape( text: string ): string {
	const span = document.createElement( 'span' );
	span.textContent = text;
	return span.innerHTML;
}

let library: { sections: Template[]; pages: Template[] } | null = null;

/**
 * Shows the templates of one kind.
 *
 * @param kind Sections or pages.
 */
async function showKind( kind: 'sections' | 'pages' ): Promise<void> {
	const body = libraryDialog().querySelector<HTMLElement>( '.um-library__body' );
	if ( ! body ) {
		return;
	}
	try {
		library ??= await get<{ sections: Template[]; pages: Template[] }>( 'elementor/library' );
	} catch {
		body.innerHTML = `<p>${ escape( t( 'error' ) ) }</p>`;
		return;
	}
	const items = library[ kind ];
	if ( ! items.length ) {
		body.innerHTML = `<p>${ escape( t( 'empty' ) ) }</p>`;
		return;
	}
	body.innerHTML = `<ul class="um-library__grid">${ items
		.map(
			( item ) => `<li><figure>${ item.preview ? `<img src="${ escape( item.preview ) }" alt="" loading="lazy">` : '<span class="um-library__blank"></span>' }<figcaption><b>${ escape( item.title ) }</b><small>${ escape( item.description ) }</small></figcaption></figure><button type="button" data-template="${ escape( item.id ) }">${ escape( t( 'insert' ) ) }</button></li>`
		)
		.join( '' ) }</ul>`;
	body.querySelectorAll<HTMLButtonElement>( '[data-template]' ).forEach( ( button ) => {
		button.addEventListener( 'click', () => void insert( button.dataset.template ?? '', button ) );
	} );
}

/**
 * Inserts a template into the page.
 *
 * @param id     Template id such as "section/hero".
 * @param button The insert button, disabled while loading.
 */
async function insert( id: string, button: HTMLButtonElement ): Promise<void> {
	const elementor = window.elementor;
	if ( ! elementor || ! window.$e ) {
		return;
	}
	button.disabled = true;
	try {
		const data = await get<{ content: Record<string, unknown>[] }>( `elementor/library/${ id }` );
		let at = insertAt ?? elementor.getPreviewView?.()?.collection?.length ?? 0;
		for ( const model of data.content ) {
			window.$e.run( 'document/elements/create', { container: elementor.getPreviewContainer(), model, options: { at, edit: false } } );
			at++;
		}
		dialog?.close();
	} catch {
		window.alert( t( 'error' ) );
	} finally {
		button.disabled = false;
	}
}

/**
 * Opens the library.
 *
 * @param at Position in the page to insert at, or the end.
 */
function openLibrary( at?: number ): void {
	insertAt = at;
	libraryDialog().showModal();
	void showKind( 'sections' );
}

/** Adds our button to Elementor's "add section" area. */
function addLibraryButton(): void {
	const template = document.getElementById( 'tmpl-elementor-add-section' );
	if ( ! template || template.innerHTML.includes( 'um-library-button' ) ) {
		return;
	}
	const button = `<button type="button" class="elementor-add-section-area-button um-library-button" title="${ escape( t( 'library' ) ) }" aria-label="${ escape( t( 'library' ) ) }">UM</button>`;
	template.innerHTML = template.innerHTML.replace( /(<[^>]+elementor-add-template-button[\s\S]*?<\/(?:div|button)>)/, `$1${ button }` );
}

const listening = new WeakSet<Document>();

/** Listens for clicks on our button inside the preview frame. */
function listenInPreview( elementor: Elementor ): void {
	const frame = elementor.$preview?.[ 0 ] ?? document.querySelector<HTMLIFrameElement>( '#elementor-preview-iframe' );
	const doc = frame?.contentDocument;
	if ( ! doc || listening.has( doc ) ) {
		return;
	}
	listening.add( doc );
	doc.addEventListener( 'click', ( event ) => {
		const target = event.target instanceof Element ? event.target.closest( '.um-library-button' ) : null;
		if ( ! target ) {
			return;
		}
		event.preventDefault();
		const section = target.closest( '.elementor-add-section' );
		const inline = section?.closest( '.elementor-element' );
		const siblings = inline?.parentElement ? Array.from( inline.parentElement.children ).filter( ( child ) => child.classList.contains( 'elementor-element' ) ) : [];
		openLibrary( inline ? siblings.indexOf( inline ) : undefined );
	} );
}

/** Starts once the editor exists. */
function start(): void {
	const elementor = window.elementor;
	if ( ! elementor || ! config ) {
		return;
	}
	registerSearchControl( elementor );
	addLibraryButton();
	elementor.on( 'preview:loaded', () => listenInPreview( elementor ) );
	listenInPreview( elementor );
	document.querySelector( '#elementor-preview-iframe' )?.addEventListener( 'load', () => listenInPreview( elementor ) );
}

if ( window.elementor ) {
	start();
} else {
	window.addEventListener( 'elementor/init', start );
	document.addEventListener( 'DOMContentLoaded', start, { once: true } );
}

export {};
