/**
 * Sticky section navigation: marks the link of the section in view with
 * `aria-current`, using IntersectionObserver, and keeps it scrolled into
 * view inside the row.
 */
import type { Behavior } from '../../storefront/runtime';

export const stickyNav: Behavior = ( element ) => {
	const links = Array.from( element.querySelectorAll<HTMLAnchorElement>( 'a[href^="#"]' ) );
	const sections = links
		.map( ( link ) => document.getElementById( decodeURIComponent( link.hash.slice( 1 ) ) ) )
		.filter( ( section ): section is HTMLElement => section !== null );
	if ( ! sections.length || ! ( 'IntersectionObserver' in window ) ) {
		return {};
	}
	const mark = ( id: string ): void => {
		links.forEach( ( link ) => {
			const active = link.hash.slice( 1 ) === id;
			link.setAttribute( 'aria-current', String( active ) );
			if ( active ) {
				link.scrollIntoView( { block: 'nearest', inline: 'nearest' } );
			}
		} );
	};
	const observer = new IntersectionObserver(
		( entries ) => {
			const visible = entries.filter( ( entry ) => entry.isIntersecting ).sort( ( a, b ) => a.boundingClientRect.top - b.boundingClientRect.top );
			if ( visible[ 0 ] ) {
				mark( visible[ 0 ].target.id );
			}
		},
		{ rootMargin: '-30% 0px -60% 0px' }
	);
	sections.forEach( ( section ) => observer.observe( section ) );
	return {
		destroy(): void {
			observer.disconnect();
		},
	};
};
