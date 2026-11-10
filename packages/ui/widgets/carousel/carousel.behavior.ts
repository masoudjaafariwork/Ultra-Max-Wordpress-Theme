/**
 * Carousel behaviour on Embla Carousel (MIT). This is the only file that
 * uses Embla; replacing the library means rewriting this file alone.
 *
 * Adds: dragging with momentum, arrows that disable at the ends, dots,
 * looping and autoplay that pauses on hover, on focus, in a hidden tab and
 * for visitors who prefer reduced motion. Works right to left.
 */
import EmblaCarousel, { type EmblaCarouselType } from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';
import type { Behavior } from '../../storefront/runtime';

const PERSIAN = '۰۱۲۳۴۵۶۷۸۹';

/**
 * Digits in the page's language.
 *
 * @param value Number.
 */
function digits( value: number ): string {
	const text = String( value );
	return document.documentElement.lang.startsWith( 'fa' ) ? text.replace( /\d/g, ( digit ) => PERSIAN[ Number( digit ) ] ?? digit ) : text;
}

/**
 * Builds the dots and keeps them current.
 *
 * @param embla   Carousel.
 * @param holder  Dots container.
 * @param label   Label pattern with %1$s (number) and %2$s (count).
 */
function dots( embla: EmblaCarouselType, holder: HTMLElement, label: string ): () => void {
	const render = (): void => {
		const snaps = embla.scrollSnapList();
		holder.hidden = snaps.length < 2;
		holder.replaceChildren(
			...snaps.map( ( _, index ) => {
				const dot = document.createElement( 'button' );
				dot.type = 'button';
				dot.className = 'um-carousel-dot';
				dot.setAttribute( 'aria-label', label.replace( '%1$s', digits( index + 1 ) ).replace( '%2$s', digits( snaps.length ) ) );
				dot.addEventListener( 'click', () => embla.scrollTo( index ) );
				return dot;
			} )
		);
		select();
	};
	const select = (): void => {
		const current = embla.selectedScrollSnap();
		Array.from( holder.children ).forEach( ( dot, index ) => dot.toggleAttribute( 'aria-current', index === current ) );
	};
	embla.on( 'init', render ).on( 'reInit', render ).on( 'select', select );
	render();
	return render;
}

export const carousel: Behavior = ( element ) => {
	const viewport = element.querySelector<HTMLElement>( '[data-viewport]' );
	if ( ! viewport ) {
		return {};
	}
	const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const delay = Number( element.dataset.autoplay ?? 0 );
	const rtl = getComputedStyle( element ).direction === 'rtl';
	const plugins = delay > 0 && ! reduced ? [ Autoplay( { delay, stopOnInteraction: false, stopOnMouseEnter: true, stopOnFocusIn: true } ) ] : [];

	// Hand scrolling over to Embla: no native scroll or snapping any more.
	viewport.style.overflow = 'hidden';
	viewport.style.scrollSnapType = 'none';
	viewport.scrollLeft = 0;

	const embla = EmblaCarousel(
		viewport,
		{
			loop: element.dataset.loop === 'true',
			direction: rtl ? 'rtl' : 'ltr',
			align: 'start',
			containScroll: 'trimSnaps',
			slidesToScroll: 'auto',
			duration: reduced ? 10 : 25,
		},
		plugins
	);

	const prev = element.querySelector<HTMLButtonElement>( '[data-prev]' );
	const next = element.querySelector<HTMLButtonElement>( '[data-next]' );
	const arrows = (): void => {
		const scrollable = embla.scrollSnapList().length > 1;
		if ( prev ) {
			prev.hidden = ! scrollable;
			prev.disabled = ! embla.canScrollPrev();
		}
		if ( next ) {
			next.hidden = ! scrollable;
			next.disabled = ! embla.canScrollNext();
		}
	};
	prev?.addEventListener( 'click', () => embla.scrollPrev() );
	next?.addEventListener( 'click', () => embla.scrollNext() );
	embla.on( 'select', arrows ).on( 'reInit', arrows );
	arrows();

	const holder = element.querySelector<HTMLElement>( '[data-dots]' );
	if ( holder ) {
		dots( embla, holder, element.dataset.dotLabel ?? '%1$s / %2$s' );
	}

	// Slides out of view are hidden from keyboard and screen readers.
	const slides = embla.slideNodes();
	const visibility = (): void => {
		const shown = new Set( embla.slidesInView() );
		slides.forEach( ( slide, index ) => slide.toggleAttribute( 'inert', ! shown.has( index ) ) );
	};
	embla.on( 'slidesInView', visibility ).on( 'reInit', visibility );

	const pause = (): void => {
		const autoplay = embla.plugins().autoplay;
		if ( autoplay ) {
			if ( document.hidden ) {
				autoplay.stop();
			} else {
				autoplay.play();
			}
		}
	};
	document.addEventListener( 'visibilitychange', pause );

	return {
		destroy(): void {
			document.removeEventListener( 'visibilitychange', pause );
			embla.destroy();
		},
	};
};
