/**
 * Product screen helpers: picture choosers for attribute values and
 * variations, and the rows of the product's own tabs. Variation panels
 * load later by AJAX, so every handler listens on the document.
 *
 * Markup: `[data-um-media]` holds a hidden `[data-um-media-value]` (one id,
 * or ids separated by commas with `data-multiple`), a preview and the
 * choose and clear buttons.
 */

interface MediaAttachment {
	id: number;
	sizes?: Record<string, { url: string }>;
	url: string;
}

interface MediaFrame {
	on( event: string, callback: () => void ): void;
	open(): void;
	state(): { get( name: string ): { toJSON(): MediaAttachment[]; first(): { toJSON(): MediaAttachment } } };
}

/** WordPress's media library and our texts, from the page. */
const page = window as unknown as {
	wp?: { media?: ( options: Record<string, unknown> ) => MediaFrame };
	ultraMaxProductAdmin?: Record<string, string>;
};

const text = ( key: string ): string => page.ultraMaxProductAdmin?.[ key ] ?? '';

/**
 * Shows the chosen pictures as small previews.
 *
 * @param box   Chooser.
 * @param items Chosen pictures.
 */
function preview( box: HTMLElement, items: MediaAttachment[] ): void {
	const holder = box.querySelector<HTMLElement>( '[data-um-media-preview]' );
	if ( ! holder ) {
		return;
	}
	holder.replaceChildren(
		...items.map( ( item ) => {
			const image = document.createElement( 'img' );
			image.src = item.sizes?.thumbnail?.url ?? item.url;
			image.alt = '';
			image.style.cssText = 'width:48px;height:48px;object-fit:cover;margin:2px';
			return image;
		} )
	);
	box.querySelector<HTMLElement>( '[data-um-media-clear]' )?.toggleAttribute( 'hidden', items.length === 0 );
}

/**
 * Marks a variation as changed, so WooCommerce saves it.
 *
 * @param box Chooser.
 */
function changed( box: HTMLElement ): void {
	const field = box.querySelector<HTMLInputElement>( '[data-um-media-value]' );
	field?.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	box.closest( '.woocommerce_variation' )?.classList.add( 'variation-needs-update' );
	const save = document.querySelector<HTMLButtonElement>( 'button.save-variation-changes' );
	if ( save ) {
		save.disabled = false;
	}
}

/**
 * Opens the media library for a chooser.
 *
 * @param box Chooser.
 */
function choose( box: HTMLElement ): void {
	const media = page.wp?.media;
	if ( ! media ) {
		return;
	}
	const multiple = box.hasAttribute( 'data-multiple' );
	const frame = media( {
		title: text( 'choose' ),
		button: { text: text( 'use' ) },
		library: { type: 'image' },
		multiple: multiple ? 'add' : false,
	} );
	frame.on( 'select', () => {
		const selection = frame.state().get( 'selection' );
		const items = multiple ? selection.toJSON() : [ selection.first().toJSON() ];
		const field = box.querySelector<HTMLInputElement>( '[data-um-media-value]' );
		if ( field ) {
			field.value = items.map( ( item ) => String( item.id ) ).join( ',' );
		}
		preview( box, items );
		changed( box );
	} );
	frame.open();
}

/**
 * Adds a row to the product's own tabs.
 *
 * @param list The rows' list.
 */
function addRow( list: HTMLElement ): void {
	const template = list.querySelector<HTMLTemplateElement>( 'template[data-um-row-template]' );
	if ( ! template ) {
		return;
	}
	const index = Number( list.dataset.next ?? list.querySelectorAll( '[data-um-row]' ).length );
	list.dataset.next = String( index + 1 );
	const holder = document.createElement( 'div' );
	holder.innerHTML = template.innerHTML.replaceAll( '__i__', String( index ) );
	const row = holder.firstElementChild;
	if ( row ) {
		template.before( row );
		row.querySelector<HTMLInputElement>( 'input' )?.focus();
	}
}

document.addEventListener( 'click', ( event ) => {
	const target = event.target instanceof Element ? event.target : null;
	const box = target?.closest<HTMLElement>( '[data-um-media]' );
	if ( box && target?.closest( '[data-um-media-choose]' ) ) {
		event.preventDefault();
		choose( box );
		return;
	}
	if ( box && target?.closest( '[data-um-media-clear]' ) ) {
		event.preventDefault();
		const field = box.querySelector<HTMLInputElement>( '[data-um-media-value]' );
		if ( field ) {
			field.value = '';
		}
		preview( box, [] );
		changed( box );
		return;
	}
	const add = target?.closest<HTMLElement>( '[data-um-row-add]' );
	if ( add ) {
		event.preventDefault();
		const list = document.getElementById( add.dataset.umRowAdd ?? '' );
		if ( list ) {
			addRow( list );
		}
		return;
	}
	const remove = target?.closest<HTMLElement>( '[data-um-row-remove]' );
	if ( remove ) {
		event.preventDefault();
		remove.closest( '[data-um-row]' )?.remove();
	}
} );

export {};
