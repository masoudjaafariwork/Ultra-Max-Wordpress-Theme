/**
 * ProductSections behaviour: marks the link of the section in view in the
 * sticky row (scroll spy) and keeps that link visible when the row scrolls.
 * Without JavaScript the links still jump to their sections.
 */
import type { Behavior } from '../../storefront/runtime';

export const productSections: Behavior = ( element ) => {
	const links = Array.from( element.querySelectorAll<HTMLAnchorElement>( '[data-um-section-link]' ) );
	const sections = Array.from( element.querySelectorAll<HTMLElement>( '[data-um-section]' ) );
	if ( links.length === 0 || ! ( 'IntersectionObserver' in window ) ) {
		return {};
	}
	const visible = new Map<string, number>();

	const mark = ( id: string ): void => {
		links.forEach( ( link ) => {
			const on = link.hash === `#${ id }`;
			if ( on ) {
				link.setAttribute( 'aria-current', 'true' );
				const row = link.parentElement;
				if ( row && ( link.offsetLeft < row.scrollLeft || link.offsetLeft + link.offsetWidth > row.scrollLeft + row.clientWidth ) ) {
					row.scrollTo( { left: link.offsetLeft - 16, behavior: 'smooth' } );
				}
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );
	};

	const observer = new IntersectionObserver(
		( entries ) => {
			entries.forEach( ( entry ) => visible.set( entry.target.id, entry.isIntersecting ? entry.intersectionRatio : 0 ) );
			const first = sections.find( ( section ) => ( visible.get( section.id ) ?? 0 ) > 0 );
			if ( first ) {
				mark( first.id );
			}
		},
		{ rootMargin: '-35% 0px -55% 0px', threshold: [ 0, 0.01 ] }
	);
	sections.forEach( ( section ) => observer.observe( section ) );

	const onClick = ( event: MouseEvent ): void => {
		const link = event.target instanceof Element ? event.target.closest<HTMLAnchorElement>( '[data-um-section-link]' ) : null;
		if ( link ) {
			mark( link.hash.slice( 1 ) );
		}
	};
	element.addEventListener( 'click', onClick );

	return {
		destroy(): void {
			observer.disconnect();
			element.removeEventListener( 'click', onClick );
		},
	};
};
