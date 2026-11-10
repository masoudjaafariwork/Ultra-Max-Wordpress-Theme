/**
 * Writes the ready-made designs of the layout builder (BLD-03, HDR-03,
 * HOME-13) as Elementor JSON into plugins/ultra-max-core/templates/library,
 * and the list of their texts into src/Builder/library-strings.php so the
 * Persian translation covers them.
 *
 * Every template is defined here in a compact form; run after changing it:
 *
 *   node scripts/library.mjs
 *
 * Images point at {library}/<name>.svg, the product drawings made for this
 * project (design/ASSETS.fa.md); the plugin swaps the token for its URL.
 */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { root } from './lib/run.mjs';

const out = path.join( root, 'plugins', 'ultra-max-core', 'templates', 'library' );
const strings = new Set();

/**
 * Marks a text as translatable and returns it.
 *
 * @param {string} text English source text.
 * @return {string} The same text.
 */
const T = ( text ) => {
	strings.add( text );
	return text;
};

let counter = 0;
/** A stable element id, so regenerating gives the same files. */
const id = () => crypto.createHash( 'md5' ).update( String( ++counter ) ).digest( 'hex' ).slice( 0, 7 );

const px = ( size ) => ( { unit: 'px', size, column: String( size ), row: String( size ) } );
const box = ( top, side = top, bottom = top ) => ( { unit: 'px', top: String( top ), right: String( side ), bottom: String( bottom ), left: String( side ), isLinked: false } );
const img = ( name ) => ( { id: 0, url: `{library}/${ name }.svg` } );

/**
 * A container.
 *
 * @param {object}   settings Settings.
 * @param {object[]} elements Children.
 * @param {boolean}  inner    Nested in another container.
 */
function con( settings, elements, inner = false ) {
	return { id: id(), elType: 'container', isInner: inner, settings, elements };
}

/**
 * A row: a flex container laid out horizontally.
 *
 * @param {object}   options  gap, align, justify, wrap, padding, bg, full, hide, extra settings.
 * @param {object[]} elements Children.
 * @param {boolean}  inner    Nested.
 */
function row( options, elements, inner = false ) {
	const settings = {
		content_width: options.full ? 'full' : 'boxed',
		flex_direction: 'row',
		flex_align_items: options.align ?? 'center',
		flex_justify_content: options.justify ?? 'flex-start',
		flex_gap: px( options.gap ?? 16 ),
		flex_wrap: options.wrap ? 'wrap' : 'nowrap',
		padding: options.padding ?? box( 10, 0 ),
		...hide( options.hide ),
		...background( options.bg ),
		...( options.extra ?? {} ),
	};
	return con( settings, elements, inner );
}

/**
 * A column: a flex container laid out vertically.
 *
 * @param {object}   options  gap, padding, bg, width, hide, extra.
 * @param {object[]} elements Children.
 * @param {boolean}  inner    Nested.
 */
function col( options, elements, inner = true ) {
	return con(
		{
			content_width: 'full',
			flex_direction: 'column',
			flex_gap: px( options.gap ?? 12 ),
			padding: options.padding ?? box( 0 ),
			...( options.width ? { width: { unit: '%', size: options.width } } : {} ),
			...hide( options.hide ),
			...background( options.bg ),
			...( options.extra ?? {} ),
		},
		elements,
		inner
	);
}

/**
 * Elementor's responsive hide settings.
 *
 * @param {string[]|undefined} devices Devices to hide on.
 */
function hide( devices = [] ) {
	return Object.fromEntries( devices.map( ( device ) => [ `hide_${ device }`, `hidden-${ device }` ] ) );
}

/**
 * Background settings: a kit colour id (primary, secondary, text, accent) or a hex colour.
 *
 * @param {string|undefined} color Colour.
 */
function background( color ) {
	if ( ! color ) {
		return {};
	}
	if ( color.startsWith( '#' ) ) {
		return { background_background: 'classic', background_color: color };
	}
	return { background_background: 'classic', __globals__: { background_color: `globals/colors?id=${ color }` } };
}

/**
 * A widget.
 *
 * @param {string}  type     Our widget id, without "um-".
 * @param {object}  settings Settings.
 * @param {boolean} grow     Whether it takes the free space of its row.
 */
function w( type, settings = {}, grow = false ) {
	return { id: id(), elType: 'widget', widgetType: `um-${ type }`, settings: { ...settings, ...( grow ? { _flex_size: 'grow' } : {} ) }, elements: [] };
}

const link = ( url ) => ( { url, is_external: '', nofollow: '' } );

/* ------------------------------------------------------------------ header parts */

const logo = ( size = 'md' ) => w( 'logo', { size } );
const search = ( grow = true ) => w( 'search', { layout: 'field', placeholder: T( 'Search the store' ) }, grow );
const searchIcon = () => w( 'search', { layout: 'icon', placeholder: T( 'Search the store' ) } );
const account = ( look = 'button' ) => w( 'account', { look, guest_text: T( 'Log in | Sign up' ), user_text: T( 'My account' ) } );
const cart = ( look = 'icon' ) => w( 'cart', { look, text: T( 'Cart' ) } );
const wishlist = ( look = 'icon' ) => w( 'wishlist', { look, text: T( 'Wishlist' ) } );
const compare = () => w( 'compare', { look: 'icon', text: T( 'Compare' ) } );
const divider = () => w( 'divider' );
const spacer = () => w( 'spacer', { grow: 'yes' }, true );
const cats = () => w( 'category-menu', { label: T( 'Product categories' ), promo: 'yes', promo_title: T( 'Up to 30% off' ) } );
const nav = ( align = 'start', grow = true ) => w( 'nav-menu', { menu: '', align, size: 'sm', label: T( 'Main menu' ) }, grow );
const toggle = () =>
	w( 'menu-toggle', {
		source: 'categories',
		heading: T( 'Product categories' ),
		account: 'yes',
		quick: [
			{ _id: id(), label: T( 'Offers' ), icon: 'fire', link: link( '#deals' ) },
			{ _id: id(), label: T( 'Discounts' ), icon: 'percent', link: link( '' ) },
			{ _id: id(), label: T( 'Best sellers' ), icon: 'star', link: link( '' ) },
			{ _id: id(), label: T( 'Track order' ), icon: 'box', link: link( '' ) },
		],
	} );
const html = ( text ) => w( 'html', { code: text } );
const social = ( size = 'sm' ) =>
	w( 'social', {
		size,
		items: [
			{ _id: id(), network: 'instagram', link: link( '#' ) },
			{ _id: id(), network: 'telegram', link: link( '#' ) },
			{ _id: id(), network: 'whatsapp', link: link( '#' ) },
			{ _id: id(), network: 'aparat', link: link( '#' ) },
		],
	} );

const thin = { padding: box( 0, 0 ), gap: 20 };
const shadow = { extra: { box_shadow_box_shadow_type: 'yes', box_shadow_box_shadow: { horizontal: 0, vertical: 1, blur: 0, spread: 0, color: 'rgba(21,22,28,0.08)' } } };

const desktop = { desktop: true, tablet: false, mobile: false };
const mobile = { desktop: false, tablet: true, mobile: true };

const headers = {
	'desktop-1': {
		title: T( 'Store header with categories' ),
		description: T( 'Logo, wide search, account and cart; categories and menu below. The classic big-store header.' ),
		devices: desktop,
		content: [
			row( { padding: box( 14, 0 ), gap: 24, bg: '#ffffff' }, [ logo(), search(), account(), divider(), wishlist(), cart() ] ),
			row( { ...thin, bg: '#ffffff', ...shadow }, [ cats(), divider(), nav() ] ),
		],
	},
	'desktop-2': {
		title: T( 'Centred logo' ),
		description: T( 'Menu on one side, logo in the middle, actions on the other.' ),
		devices: desktop,
		content: [
			row( { padding: box( 14, 0 ), gap: 16, bg: '#ffffff', justify: 'space-between' }, [ searchIcon(), spacer(), logo( 'lg' ), spacer(), account( 'icon' ), wishlist(), cart() ] ),
			row( { ...thin, bg: '#ffffff', justify: 'center', ...shadow }, [ nav( 'center' ) ] ),
		],
	},
	'desktop-3': {
		title: T( 'One row' ),
		description: T( 'Logo, menu and icons in a single compact row.' ),
		devices: desktop,
		content: [ row( { padding: box( 10, 0 ), gap: 20, bg: '#ffffff', ...shadow }, [ logo(), nav( 'start' ), searchIcon(), account( 'icon' ), cart() ] ) ],
	},
	'desktop-4': {
		title: T( 'With a dark top bar' ),
		description: T( 'A thin dark bar with a message and social links above the store header.' ),
		devices: desktop,
		content: [
			row( { padding: box( 6, 0 ), gap: 16, bg: '#15161c', extra: { _css_classes: 'um-dark-bar' } }, [ html( T( 'Free delivery on orders over 500,000 toman' ) ), spacer(), social( 'sm' ) ] ),
			row( { padding: box( 14, 0 ), gap: 24, bg: '#ffffff' }, [ logo(), search(), account(), cart() ] ),
			row( { ...thin, bg: '#ffffff', ...shadow }, [ cats(), divider(), nav() ] ),
		],
	},
	'desktop-5': {
		title: T( 'Brand coloured menu' ),
		description: T( 'White top row and a menu row in your brand colour.' ),
		devices: desktop,
		content: [
			row( { padding: box( 14, 0 ), gap: 24, bg: '#ffffff' }, [ logo(), search(), account(), cart() ] ),
			row( { ...thin, bg: 'primary', extra: { _css_classes: 'um-on-brand' } }, [ cats(), nav() ] ),
		],
	},
	'desktop-6': {
		title: T( 'Search first' ),
		description: T( 'A large search field in the middle for stores with many products.' ),
		devices: desktop,
		content: [ row( { padding: box( 16, 0 ), gap: 24, bg: '#ffffff', ...shadow }, [ logo( 'lg' ), cats(), search(), account( 'stacked' ), wishlist( 'stacked' ), cart( 'stacked' ) ] ) ],
	},
	'desktop-7': {
		title: T( 'Categories beside the logo' ),
		description: T( 'Categories button first, search in its own row below.' ),
		devices: desktop,
		content: [
			row( { padding: box( 12, 0 ), gap: 20, bg: '#ffffff' }, [ logo(), cats(), nav( 'start' ), account( 'icon' ), compare(), wishlist(), cart() ] ),
			row( { padding: box( 0, 0, 12 ), bg: '#ffffff', justify: 'center', ...shadow }, [ search() ] ),
		],
	},
	'desktop-8': {
		title: T( 'Minimal' ),
		description: T( 'Just the logo, search and cart, for small stores.' ),
		devices: desktop,
		content: [ row( { padding: box( 14, 0 ), gap: 24, bg: '#ffffff', ...shadow }, [ logo(), spacer(), search( false ), cart( 'button' ) ] ) ],
	},
	'mobile-1': {
		title: T( 'Phone: menu, logo, cart and search' ),
		description: T( 'Menu button, logo and cart, with the search field below.' ),
		devices: mobile,
		content: [
			row( { padding: box( 8, 12, 4 ), gap: 4, bg: '#ffffff' }, [ toggle(), logo(), spacer(), cart() ] ),
			row( { padding: box( 4, 12, 10 ), bg: '#ffffff', ...shadow }, [ search() ] ),
		],
	},
	'mobile-2': {
		title: T( 'Phone: logo and icons' ),
		description: T( 'Logo at the start, search icon, cart and menu at the end.' ),
		devices: mobile,
		content: [ row( { padding: box( 8, 12 ), gap: 4, bg: '#ffffff', ...shadow }, [ logo(), spacer(), searchIcon(), cart(), toggle() ] ) ],
	},
	'mobile-3': {
		title: T( 'Phone: search in the bar' ),
		description: T( 'Menu button, a search field filling the row, and the cart.' ),
		devices: mobile,
		content: [ row( { padding: box( 8, 12 ), gap: 6, bg: '#ffffff', ...shadow }, [ toggle(), search(), cart() ] ) ],
	},
	'mobile-4': {
		title: T( 'Phone: centred logo' ),
		description: T( 'Menu at the start, logo in the middle, account and cart at the end.' ),
		devices: mobile,
		content: [ row( { padding: box( 8, 12 ), gap: 4, bg: '#ffffff', justify: 'space-between', ...shadow }, [ toggle(), spacer(), logo(), spacer(), account( 'icon' ), cart() ] ) ],
	},
	'mobile-5': {
		title: T( 'Phone: brand coloured' ),
		description: T( 'The first phone header on your brand colour.' ),
		devices: mobile,
		content: [
			row( { padding: box( 8, 12, 4 ), gap: 4, bg: 'primary', extra: { _css_classes: 'um-on-brand' } }, [ toggle(), logo(), spacer(), cart() ] ),
			row( { padding: box( 4, 12, 10 ), bg: 'primary', extra: { _css_classes: 'um-on-brand' } }, [ search() ] ),
		],
	},
	'mobile-6': {
		title: T( 'Phone: all icons' ),
		description: T( 'Menu, logo, then search, account and cart icons.' ),
		devices: mobile,
		content: [ row( { padding: box( 8, 12 ), gap: 2, bg: '#ffffff', ...shadow }, [ toggle(), logo(), spacer(), searchIcon(), account( 'icon' ), cart() ] ) ],
	},
	'mobile-7': {
		title: T( 'Phone: logo with search below' ),
		description: T( 'Logo and menu button, with the search field below.' ),
		devices: mobile,
		content: [
			row( { padding: box( 8, 12, 4 ), gap: 4, bg: '#ffffff' }, [ logo(), spacer(), account( 'icon' ), toggle() ] ),
			row( { padding: box( 4, 12, 10 ), bg: '#ffffff', ...shadow }, [ search() ] ),
		],
	},
	'mobile-8': {
		title: T( 'Phone: app-like' ),
		description: T( 'A search field and labelled icons, like a shopping app. Pairs with the bottom bar.' ),
		devices: mobile,
		content: [ row( { padding: box( 8, 12 ), gap: 6, bg: '#ffffff', ...shadow }, [ search(), wishlist( 'stacked' ), cart( 'stacked' ) ] ) ],
	},
};

/* ------------------------------------------------------------------ footers */

const perks = () =>
	w( 'perks', {
		perks: [
			{ _id: id(), icon: 'truck', title: T( 'Fast delivery' ), text: T( 'All over the country' ) },
			{ _id: id(), icon: 'cash', title: T( 'Cash on delivery' ), text: T( 'In selected cities' ) },
			{ _id: id(), icon: 'return', title: T( '7-day returns' ), text: T( 'No questions asked' ) },
			{ _id: id(), icon: 'verified', title: T( 'Genuine products' ), text: T( 'From the brand or its agent' ) },
			{ _id: id(), icon: 'headset', title: T( 'Support 7 days a week' ), text: T( 'By phone and chat' ) },
		],
	} );
const links = ( title, items ) =>
	w( 'icon-list', {
		title: T( title ),
		layout: 'column',
		items: items.map( ( text ) => ( { _id: id(), text: T( text ), icon: '', link: link( '#' ) } ) ),
	} );
const guide = () => links( 'Shopping guide', [ 'How to order', 'Delivery', 'Payment methods', 'Track your order' ] );
const service = () => links( 'Customer service', [ 'Frequently asked questions', 'Returns', 'Terms of use', 'Privacy' ] );
const about = () => links( 'About us', [ 'About the store', 'Contact us', 'Blog', 'Work with us' ] );
const newsletter = () => w( 'newsletter', { title: T( 'Hear about offers and new arrivals first' ), placeholder: T( 'Email or mobile number' ), button: T( 'Join' ) } );
const trust = () =>
	w( 'trust-marks', {
		title: T( 'About the store' ),
		text: T( 'Write a short description of your store, its guarantees and delivery here. Search engines read it too.' ),
		marks: [
			{ _id: id(), label: T( 'e-Namad trust badge' ) },
			{ _id: id(), label: T( 'National registration' ) },
			{ _id: id(), label: T( 'Business licence' ) },
		],
		perks: [],
	} );
const apps = () => w( 'app-links', { label: T( 'Store app:' ), items: [ { _id: id(), label: T( 'Add to home screen' ), icon: 'mobile', link: link( '#' ) }, { _id: id(), label: T( 'Direct download' ), icon: 'download', link: link( '#' ) } ] } );
const copyright = () => w( 'copyright', { text: T( 'All rights reserved for {site}. {year}' ), top: 'yes' } );
const contactLine = () =>
	w( 'icon-list', {
		layout: 'row',
		items: [
			{ _id: id(), text: T( 'Support: your store phone number' ), icon: 'headset', link: link( '' ) },
			{ _id: id(), text: T( 'Every day from 9 to 21' ), icon: 'clock', link: link( '' ) },
		],
	} );

const footers = {
	'store-1': {
		title: T( 'Full store footer' ),
		description: T( 'Advantages, link columns, newsletter, social links, trust badges, app links and copyright.' ),
		devices: { desktop: true, tablet: true, mobile: true },
		content: [
			con( { content_width: 'boxed', flex_direction: 'column', flex_gap: px( 28 ), padding: box( 32, 0, 8 ), background_background: 'classic', background_color: '#ffffff', border_border: 'solid', border_width: { unit: 'px', top: '1', right: '0', bottom: '0', left: '0', isLinked: false }, border_color: '#dfe1e7' }, [
				row( { padding: box( 0 ), gap: 16, wrap: true, justify: 'space-between' }, [ logo(), contactLine() ], true ),
				perks(),
				row( { padding: box( 24, 0 ), gap: 32, wrap: true, align: 'flex-start' }, [ col( { width: 18 }, [ guide() ] ), col( { width: 18 }, [ service() ] ), col( { width: 18 }, [ about() ] ), col( { width: 36, gap: 16 }, [ newsletter(), social( 'md' ) ] ) ], true ),
				trust(),
				apps(),
			] ),
			row( { full: true, padding: box( 0 ), gap: 0 }, [ copyright() ] ),
		],
	},
	'store-2': {
		title: T( 'Compact footer' ),
		description: T( 'Logo and short text, three link columns, social links and copyright.' ),
		devices: { desktop: true, tablet: true, mobile: true },
		content: [
			row( { padding: box( 32, 0 ), gap: 32, wrap: true, align: 'flex-start', bg: '#ffffff' }, [
				col( { width: 30, gap: 12 }, [ logo(), w( 'text', { content: T( 'Write a sentence about your store here.' ) } ), social( 'sm' ) ] ),
				col( { width: 18 }, [ guide() ] ),
				col( { width: 18 }, [ service() ] ),
				col( { width: 18 }, [ about() ] ),
			] ),
			row( { full: true, padding: box( 0 ), gap: 0 }, [ copyright() ] ),
		],
	},
	'store-3': {
		title: T( 'Minimal footer' ),
		description: T( 'One line with the copyright and social links.' ),
		devices: { desktop: true, tablet: true, mobile: true },
		content: [ row( { padding: box( 14, 0 ), gap: 16, wrap: true, justify: 'space-between', bg: '#ffffff' }, [ w( 'copyright', { text: T( 'All rights reserved for {site}. {year}' ), top: '' } ), social( 'sm' ) ] ) ],
	},
};

/* ------------------------------------------------------------------ home sections */

const section = ( elements, options = {} ) => con( { content_width: 'boxed', flex_direction: 'column', flex_gap: px( options.gap ?? 16 ), padding: options.padding ?? box( 12, 0 ), ...( options.css_id ? { _element_id: options.css_id } : {} ), ...hide( options.hide ) }, elements );

const heroSlides = [
	{ title: T( 'New phones with up to 25% off' ), kicker: T( 'Autumn festival' ), text: T( 'Instalments without a down payment and free delivery' ), button: T( 'Shop now' ), from: '#3d34ad', to: '#7f78f0', image: img( 'phone' ) },
	{ title: T( 'Headphones and speakers from 990,000 toman' ), kicker: T( 'Pure sound' ), text: T( '18-month warranty on every item' ), button: T( 'View' ), from: '#0a4f51', to: '#35aeb0', image: img( 'headphones' ) },
	{ title: T( 'Weekly groceries delivered the same day' ), kicker: T( 'Supermarket' ), text: T( 'Fresh, fast and always discounted' ), button: T( 'Order now' ), from: '#b86400', to: '#ffb347', image: img( 'juice' ) },
];

const sections = {
	stories: {
		title: T( 'Stories' ),
		description: T( 'Round covers that open full-screen stories.' ),
		content: [
			section(
				[
					w( 'stories', {
						duration: 5,
						stories: [ [ 'headphones', 'Wireless sound' ], [ 'perfume', 'Autumn scents' ], [ 'sneaker', 'Comfortable shoes' ], [ 'watch', 'Smart watches' ], [ 'coffee', 'Fresh coffee' ], [ 'kettle', 'Kitchen' ], [ 'camera', 'Photography' ], [ 'backpack', 'Back to school' ] ].map( ( [ name, title ] ) => ( {
							_id: id(),
							title: T( title ),
							cover: img( name ),
							media: img( name ),
							link: link( '' ),
							link_label: T( 'View' ),
						} ) ),
					} ),
				],
				{ padding: box( 4, 0 ) }
			),
		],
	},
	hero: {
		title: T( 'Hero slider' ),
		description: T( 'Three large slides with text, a button and a product image.' ),
		content: [ section( [ w( 'hero-slider', { height: 'md', autoplay: 5, slides: heroSlides.map( ( slide ) => ( { _id: id(), ...slide, link: link( '' ), photo: { id: 0, url: '' }, photo_mobile: { id: 0, url: '' }, dark: '' } ) ) } ) ] ) ],
	},
	'quick-links': {
		title: T( 'Quick links' ),
		description: T( 'Eight coloured icons for the most used places.' ),
		content: [
			section( [
				w( 'quick-links', {
					items: [
						[ 'Amazing offers', 'fire', '#ff9a1f', '#deals' ],
						[ 'Supermarket', 'store', '#0e9f6e', '' ],
						[ 'Discounts', 'percent', '#4c42d3', '' ],
						[ 'Best sellers', 'star', '#1c74d0', '' ],
						[ 'Gifts', 'gift', '#c46aa0', '' ],
						[ 'New arrivals', 'sparkles', '#c98a00', '' ],
						[ 'Track order', 'box', '#4f5361', '' ],
						[ 'More', 'dots', '#6f7381', '' ],
					].map( ( [ label, icon, color, url ] ) => ( { _id: id(), label: T( label ), icon, color, link: link( url ) } ) ),
				} ),
			] ),
		],
	},
	deals: {
		title: T( 'Amazing offers' ),
		description: T( 'A coloured band with a countdown and a slider of discounted products.' ),
		content: [ section( [ w( 'deals', { title: T( 'Amazing offers' ), end: '', image: img( 'speaker' ), more_label: T( 'All offers' ), more_link: link( '' ), source: 'sale', count: 10, in_stock: 'yes' } ) ], { css_id: 'deals' } ) ],
	},
	'banners-4': {
		title: T( 'Four banners' ),
		description: T( 'Four small promotional banners in a row.' ),
		content: [
			section( [
				w( 'banners', {
					columns: '4',
					banners: [
						[ 'Up to 12% off', 'Work laptops', '#e1e0fe', '#c4c1fb', 'laptop' ],
						[ 'Buy in instalments', 'Smart watches', '#ffe8c2', '#ffb347', 'watch' ],
						[ 'Buy 2, get 1 free', 'Skin care', '#cbeedb', '#9bddb9', 'cream' ],
						[ 'The newest models', 'Cameras and accessories', '#d3e6ff', '#a8ccff', 'camera' ],
					].map( ( [ title, kicker, from, to, image ] ) => ( { _id: id(), title: T( title ), kicker: T( kicker ), from, to, image: img( image ), link: link( '' ), button: '', light: '' } ) ),
				} ),
			] ),
		],
	},
	categories: {
		title: T( 'Shop by category' ),
		description: T( 'Round category tiles with their images.' ),
		content: [ section( [ w( 'category-circles', { title: T( 'Shop by category' ), count: 8, layout: 'grid', shape: 'circle', more_link: link( '' ) } ) ] ) ],
	},
	'product-tabs': {
		title: T( 'Product tabs' ),
		description: T( 'Newest, best selling and discounted products in tabs.' ),
		content: [
			section( [
				w( 'product-tabs', {
					title: T( 'Recommended for you' ),
					boxed: 'yes',
					count: 10,
					design: 'hover',
					layout: 'carousel',
					tabs: [
						{ _id: id(), label: T( 'Newest' ), source: 'recent' },
						{ _id: id(), label: T( 'Best selling' ), source: 'best_selling' },
						{ _id: id(), label: T( 'On sale' ), source: 'sale' },
					],
				} ),
			] ),
		],
	},
	'banners-2': {
		title: T( 'Two wide banners' ),
		description: T( 'Two large banners with a button.' ),
		content: [
			section( [
				w( 'banners', {
					columns: '2',
					banners: [
						[ 'Free delivery on your first order', 'Log in with a one-time code', '#2e2783', '#6158e6', 'backpack', 'Sign up' ],
						[ 'Kitchen appliances up to 30% off', 'Smart kitchen', '#0a4f51', '#149a9c', 'kettle', 'View' ],
					].map( ( [ title, kicker, from, to, image, button ] ) => ( { _id: id(), title: T( title ), kicker: T( kicker ), from, to, image: img( image ), button: T( button ), link: link( '' ), light: 'yes' } ) ),
				} ),
			] ),
		],
	},
	supermarket: {
		title: T( 'Supermarket row' ),
		description: T( 'Small product cards with a round add button.' ),
		content: [ section( [ w( 'products', { title: T( 'Supermarket' ), boxed: 'yes', source: 'recent', count: 10, design: 'mini', layout: 'carousel', columns: 6, columns_tablet: 4, columns_mobile: 2 } ) ] ) ],
	},
	bestsellers: {
		title: T( 'Numbered best sellers' ),
		description: T( 'The best selling products in a numbered list.' ),
		content: [ section( [ w( 'bestsellers', { title: T( 'Best selling products' ), title_icon: 'fire', boxed: 'yes', source: 'best_selling', count: 9 } ) ] ) ],
	},
	brands: {
		title: T( 'Popular brands' ),
		description: T( 'A row of brand tiles.' ),
		content: [ section( [ w( 'brands', { title: T( 'Popular brands' ), boxed: 'yes', count: 8 } ) ] ) ],
	},
	'discount-picks': {
		title: T( 'Discount picks' ),
		description: T( 'Discounted products with category filters.' ),
		content: [ section( [ w( 'discount-picks', { title: T( 'Discount picks' ), all_label: T( 'All' ), count: 8, columns: 4 } ) ] ) ],
	},
	posts: {
		title: T( 'Blog posts' ),
		description: T( 'The four newest articles.' ),
		content: [ section( [ w( 'posts', { title: T( 'Worth reading' ), more_label: T( 'More articles' ), count: 4, columns: '4' } ) ] ) ],
	},
	trust: {
		title: T( 'Trust badges' ),
		description: T( 'Secure payment text with trust and payment badges.' ),
		content: [ section( [ w( 'trust-marks', { title: T( 'Secure payment with every Shetab card' ), text: T( 'Add your trust badges and payment gateways in the widget settings.' ), marks: [ 'Payment gateway 1', 'Payment gateway 2', 'Trust badge', 'Shetab network' ].map( ( label ) => ( { _id: id(), label: T( label ) } ) ), perks: [] } ) ] ) ],
	},
	features: {
		title: T( 'Four advantages' ),
		description: T( 'Four icon boxes in a row.' ),
		content: [
			row(
				{ gap: 16, wrap: true, padding: box( 12, 0 ) },
				[
					[ 'truck', 'Fast delivery', 'Orders placed before 2 pm leave the same day.' ],
					[ 'shield', 'Secure payment', 'Pay with any Shetab card through a bank gateway.' ],
					[ 'return', 'Easy returns', 'Seven days to return what you do not like.' ],
					[ 'headset', 'Real support', 'People who answer, every day of the week.' ],
				].map( ( [ icon, title, text ] ) => w( 'icon-box', { icon, title: T( title ), text: T( text ), layout: 'inline', framed: 'yes' }, true ) )
			),
		],
	},
	faq: {
		title: T( 'Questions and answers' ),
		description: T( 'A title and an accordion of common questions.' ),
		content: [
			section( [
				w( 'heading', { title: T( 'Common questions' ), level: '2', align: 'start' } ),
				w( 'accordion', {
					first_open: 'yes',
					items: [
						[ 'How long does delivery take?', 'Orders reach most cities in two to four working days.' ],
						[ 'Can I pay on delivery?', 'Yes, in the cities shown at checkout.' ],
						[ 'How do I return an item?', 'Ask for a return from your account within seven days of delivery.' ],
					].map( ( [ title, content ] ) => ( { _id: id(), title: T( title ), content: `<p>${ T( content ) }</p>` } ) ),
				} ),
			] ),
		],
	},
	testimonials: {
		title: T( 'Customer reviews' ),
		description: T( 'A slider of what customers say.' ),
		content: [
			section( [
				w( 'heading', { title: T( 'What our customers say' ), level: '2', align: 'center' } ),
				w( 'testimonials', {
					per_view: 3,
					items: [
						[ 'My order arrived quickly and well packed. I will buy again.', 'A customer from Tehran' ],
						[ 'The support team answered all my questions patiently.', 'A customer from Isfahan' ],
						[ 'Prices are fair and the products are genuine.', 'A customer from Shiraz' ],
					].map( ( [ quote, name ] ) => ( { _id: id(), quote: T( quote ), name: T( name ), role: '', rating: 5 } ) ),
				} ),
			] ),
		],
	},
	pricing: {
		title: T( 'Three plans' ),
		description: T( 'Three pricing tables side by side, the middle one highlighted.' ),
		content: [
			row(
				{ gap: 16, wrap: true, align: 'stretch', padding: box( 12, 0 ) },
				[
					[ 'Basic', 0, '', '' ],
					[ 'Gold', 490000, 'yes', 'Most popular' ],
					[ 'Diamond', 990000, '', '' ],
				].map( ( [ title, price, featured, ribbon ] ) =>
					w(
						'pricing-table',
						{
							title: T( title ),
							price,
							period: T( 'month' ),
							featured,
							ribbon: ribbon ? T( ribbon ) : '',
							button: T( 'Choose this plan' ),
							features: [
								{ _id: id(), text: T( 'Free delivery' ), included: 'yes' },
								{ _id: id(), text: T( 'Early access to offers' ), included: featured || price > 490000 ? 'yes' : '' },
								{ _id: id(), text: T( 'Priority support' ), included: price > 490000 ? 'yes' : '' },
							],
						},
						true
					)
				)
			),
		],
	},
	contact: {
		title: T( 'Contact' ),
		description: T( 'Contact details beside a contact form.' ),
		content: [
			row( { gap: 32, wrap: true, align: 'flex-start', padding: box( 24, 0 ) }, [
				col( { width: 40, gap: 16 }, [
					w( 'heading', { title: T( 'Get in touch' ), level: '2' } ),
					w( 'icon-list', { layout: 'column', items: [ [ 'headset', 'Your store phone number' ], [ 'mail', 'Your store email' ], [ 'pin', 'Your store address' ] ].map( ( [ icon, text ] ) => ( { _id: id(), icon, text: T( text ), link: link( '' ) } ) ) } ),
					social( 'md' ),
				] ),
				col( { width: 55 }, [ w( 'contact-form', { title: T( 'Send us a message' ), button: T( 'Send' ) } ) ] ),
			] ),
		],
	},
};

const pages = {
	'home-store': {
		title: T( 'Big store home page' ),
		description: T( 'Stories, hero slider, offers, banners, categories, product tabs, best sellers, brands, discount picks, blog and trust badges.' ),
		content: [ 'stories', 'hero', 'quick-links', 'deals', 'banners-4', 'categories', 'product-tabs', 'banners-2', 'supermarket', 'bestsellers', 'brands', 'discount-picks', 'posts', 'trust' ].flatMap( ( name ) => structuredClone( sections[ name ].content ) ),
	},
	'home-simple': {
		title: T( 'Simple store home page' ),
		description: T( 'Hero slider, advantages, categories, products and reviews.' ),
		content: [ 'hero', 'features', 'categories', 'product-tabs', 'testimonials', 'faq' ].flatMap( ( name ) => structuredClone( sections[ name ].content ) ),
	},
	'landing': {
		title: T( 'Landing page' ),
		description: T( 'A product launch page with plans, reviews, questions and a contact form.' ),
		content: [ 'hero', 'features', 'pricing', 'testimonials', 'faq', 'contact' ].flatMap( ( name ) => structuredClone( sections[ name ].content ) ),
	},
};

/**
 * Gives cloned elements new ids, so a page never repeats a section's ids.
 *
 * @param {object[]} elements Elements.
 */
function renumber( elements ) {
	for ( const element of elements ) {
		element.id = id();
		renumber( element.elements ?? [] );
	}
	return elements;
}

/**
 * Writes one kind of template.
 *
 * @param {string} kind      Folder: a layout type, "section" or "page".
 * @param {object} templates Templates by name.
 */
function write( kind, templates ) {
	const dir = path.join( out, kind );
	fs.mkdirSync( dir, { recursive: true } );
	for ( const [ name, template ] of Object.entries( templates ) ) {
		const data = { title: template.title, description: template.description, devices: template.devices ?? { desktop: true, tablet: true, mobile: true }, tags: template.tags ?? [], content: kind === 'page' ? renumber( template.content ) : template.content };
		fs.writeFileSync( path.join( dir, `${ name }.json` ), JSON.stringify( data, null, '\t' ) + '\n' );
	}
}

write( 'header', headers );
write( 'footer', footers );
write( 'section', sections );
write( 'page', pages );

const php = [
	'<?php',
	'/**',
	' * Every text used inside the ready-made templates (templates/library).',
	' * Generated by scripts/library.mjs; do not edit by hand.',
	' *',
	' * @package UltraMax\\Core',
	' */',
	'',
	'declare(strict_types=1);',
	'',
	"defined( 'ABSPATH' ) || exit;",
	'',
	'$ultra_max_strings = array();',
	'$ultra_max_add     = static function ( string $source, string $translated ) use ( &$ultra_max_strings ): void {',
	'\t$ultra_max_strings[ $source ] = $translated;',
	'};',
	'',
	...[ ...strings ].sort().map( ( text ) => {
		const quoted = `'${ text.replace( /\\/g, '\\\\' ).replace( /'/g, "\\'" ) }'`;
		return `$ultra_max_add( ${ quoted }, __( ${ quoted }, 'ultra-max-core' ) );`;
	} ),
	'',
	'return $ultra_max_strings;',
	'',
];
fs.writeFileSync( path.join( root, 'plugins', 'ultra-max-core', 'src', 'Builder', 'library-strings.php' ), php.join( '\n' ) );
console.log( `Wrote ${ Object.keys( headers ).length } headers, ${ Object.keys( footers ).length } footers, ${ Object.keys( sections ).length } sections, ${ Object.keys( pages ).length } pages and ${ strings.size } texts.` );
