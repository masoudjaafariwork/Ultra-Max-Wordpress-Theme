/**
 * ProductGallery behaviour (PDP-01, PDP-02, PDP-03).
 *
 * - Thumbnails and dots follow the slide in view; a thumbnail scrolls the
 *   row to its slide.
 * - On a mouse, the picture under the pointer shows its large file at twice
 *   the size (hover zoom).
 * - A click or tap opens the full screen viewer: swipe, arrow buttons, the
 *   arrow keys (mirrored in RTL), a tap toggles zoom, videos play there.
 * - `um:variation` with a `gallery` key swaps in that variation's pictures;
 *   an empty key puts the product's own pictures back.
 */
import type { Behavior } from '../../storefront/runtime';

interface VariationEvent {
	product?: number;
	gallery?: string;
}

/**
 * The address a video plays from in the viewer.
 *
 * @param slide Slide element.
 */
export function videoSource( slide: HTMLElement ): { kind: 'frame' | 'file'; src: string } | null {
	const src = slide.dataset.video ?? '';
	if ( src === '' ) {
		return null;
	}
	const provider = slide.dataset.provider ?? 'file';
	if ( provider === 'file' ) {
		return { kind: 'file', src };
	}
	const url = new URL( src, window.location.href );
	url.searchParams.set( 'autoplay', '1' );
	return { kind: 'frame', src: url.toString() };
}

/**
 * Index of the slide closest to the start of a scrolling row.
 *
 * @param track The row.
 */
export function currentIndex( track: HTMLElement ): number {
	const slides = Array.from( track.children ) as HTMLElement[];
	if ( slides.length === 0 ) {
		return 0;
	}
	const box = track.getBoundingClientRect();
	const rtl = getComputedStyle( track ).direction === 'rtl';
	let best = 0;
	let distance = Infinity;
	slides.forEach( ( slide, index ) => {
		const rect = slide.getBoundingClientRect();
		const gap = Math.abs( rtl ? box.right - rect.right : rect.left - box.left );
		if ( gap < distance ) {
			distance = gap;
			best = index;
		}
	} );
	return best;
}

export const productGallery: Behavior = ( element ) => {
	const track = element.querySelector<HTMLElement>( '[data-um-gallery-track]' );
	const thumbs = element.querySelector<HTMLElement>( '[data-um-gallery-thumbs]' );
	const dots = element.querySelector<HTMLElement>( '[data-um-gallery-dots]' );
	const viewer = element.querySelector<HTMLDialogElement>( '[data-um-gallery-viewer]' );
	if ( ! track ) {
		return {};
	}
	const original = { track: track.innerHTML, thumbs: thumbs?.innerHTML ?? '' };
	const product = Number( element.dataset.product ?? '0' );
	const zoom = element.dataset.zoom === 'true' && window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches;

	const slides = (): HTMLElement[] => Array.from( track.querySelectorAll<HTMLElement>( '[data-um-slide]' ) );

	const goTo = ( index: number ): void => {
		const slide = slides()[ index ];
		if ( slide ) {
			track.scrollTo( { left: slide.offsetLeft - track.offsetLeft, behavior: 'smooth' } );
		}
	};

	const mark = ( index: number ): void => {
		thumbs?.querySelectorAll<HTMLElement>( '[data-um-gallery-thumb]' ).forEach( ( thumb ) => {
			if ( Number( thumb.dataset.umGalleryThumb ) === index ) {
				thumb.setAttribute( 'aria-current', 'true' );
			} else {
				thumb.removeAttribute( 'aria-current' );
			}
		} );
		dots?.querySelectorAll( 'i' ).forEach( ( dot, dotIndex ) => dot.toggleAttribute( 'data-current', dotIndex === index ) );
	};

	let frame = 0;
	const onScroll = (): void => {
		cancelAnimationFrame( frame );
		frame = requestAnimationFrame( () => mark( currentIndex( track ) ) );
	};

	/* ---------------------------------------------------------- hover zoom */

	let lens: HTMLElement | null = null;
	const onMove = ( event: PointerEvent ): void => {
		if ( ! zoom || event.pointerType !== 'mouse' ) {
			return;
		}
		const slide = event.target instanceof Element ? event.target.closest<HTMLElement>( '[data-um-slide]' ) : null;
		const full = slide?.dataset.full ?? '';
		if ( ! slide || full === '' || slide.dataset.video ) {
			lens?.remove();
			lens = null;
			return;
		}
		if ( ! lens || lens.parentElement !== slide ) {
			lens?.remove();
			lens = document.createElement( 'span' );
			lens.className = 'pointer-events-none absolute inset-0 z-1 bg-surface bg-no-repeat';
			lens.style.backgroundImage = `url("${ full.replace( /["\\\n]/g, encodeURIComponent ) }")`;
			lens.style.backgroundSize = '200%';
			slide.append( lens );
		}
		const rect = slide.getBoundingClientRect();
		const x = ( ( event.clientX - rect.left ) / rect.width ) * 100;
		const y = ( ( event.clientY - rect.top ) / rect.height ) * 100;
		lens.style.backgroundPosition = `${ Math.max( 0, Math.min( 100, x ) ) }% ${ Math.max( 0, Math.min( 100, y ) ) }%`;
	};
	const onLeave = (): void => {
		lens?.remove();
		lens = null;
	};

	/* ---------------------------------------------------------- viewer */

	const viewerTrack = viewer?.querySelector<HTMLElement>( '[data-um-viewer-track]' ) ?? null;
	const viewerThumbs = viewer?.querySelector<HTMLElement>( '[data-um-viewer-thumbs]' ) ?? null;
	const viewerCount = viewer?.querySelector<HTMLElement>( '[data-um-viewer-count]' ) ?? null;
	const counter = element.dataset.counter ?? '%1$s / %2$s';

	const viewerIndex = (): number => ( viewerTrack ? currentIndex( viewerTrack ) : 0 );

	const viewerMark = (): void => {
		if ( ! viewerTrack ) {
			return;
		}
		const index = viewerIndex();
		const total = viewerTrack.children.length;
		if ( viewerCount ) {
			viewerCount.textContent = counter.replace( '%1$s', ( index + 1 ).toLocaleString( document.documentElement.lang || undefined ) ).replace( '%2$s', total.toLocaleString( document.documentElement.lang || undefined ) );
		}
		viewerThumbs?.querySelectorAll<HTMLElement>( 'button' ).forEach( ( button, buttonIndex ) => button.setAttribute( 'aria-current', String( buttonIndex === index ) ) );
		viewer?.querySelectorAll<HTMLButtonElement>( '[data-um-viewer-step]' ).forEach( ( button ) => {
			const step = Number( button.dataset.umViewerStep );
			button.disabled = ( step < 0 && index === 0 ) || ( step > 0 && index === total - 1 );
		} );
		// A video stops when the shopper moves on.
		Array.from( viewerTrack.children ).forEach( ( child, childIndex ) => {
			if ( childIndex !== index ) {
				child.querySelector( 'video' )?.pause();
				const iframe = child.querySelector( 'iframe' );
				if ( iframe ) {
					iframe.remove();
					child.querySelector<HTMLElement>( '[data-um-viewer-play]' )?.removeAttribute( 'hidden' );
				}
			}
		} );
	};

	const viewerGo = ( index: number ): void => {
		const child = viewerTrack?.children[ index ] as HTMLElement | undefined;
		if ( viewerTrack && child ) {
			viewerTrack.scrollTo( { left: child.offsetLeft - viewerTrack.offsetLeft, behavior: 'instant' as ScrollBehavior } );
			viewerMark();
		}
	};

	const play = ( holder: HTMLElement, slide: HTMLElement ): void => {
		const source = videoSource( slide );
		if ( ! source ) {
			return;
		}
		holder.querySelector<HTMLElement>( '[data-um-viewer-play]' )?.setAttribute( 'hidden', '' );
		if ( source.kind === 'file' ) {
			const video = document.createElement( 'video' );
			video.src = source.src;
			video.controls = true;
			video.autoplay = true;
			video.playsInline = true;
			video.className = 'max-h-full max-w-full';
			holder.append( video );
			return;
		}
		const iframe = document.createElement( 'iframe' );
		iframe.src = source.src;
		iframe.title = slide.dataset.label ?? '';
		iframe.allow = 'autoplay; fullscreen; picture-in-picture';
		iframe.allowFullscreen = true;
		iframe.className = 'aspect-video w-full max-w-5xl border-0';
		holder.append( iframe );
	};

	const fill = (): void => {
		if ( ! viewerTrack || ! viewerThumbs ) {
			return;
		}
		viewerTrack.replaceChildren();
		viewerThumbs.replaceChildren();
		slides().forEach( ( slide, index ) => {
			const item = document.createElement( 'div' );
			item.className = 'relative grid h-full w-full shrink-0 snap-center place-items-center overflow-hidden p-4';
			const picture = slide.querySelector( 'img' );
			if ( slide.dataset.video ) {
				const start = document.createElement( 'button' );
				start.type = 'button';
				start.dataset.umViewerPlay = '';
				start.className = 'relative grid cursor-pointer place-items-center border-0 bg-transparent p-0 text-white';
				start.setAttribute( 'aria-label', slide.querySelector( 'button' )?.getAttribute( 'aria-label' ) ?? '' );
				if ( picture ) {
					const poster = picture.cloneNode() as HTMLImageElement;
					poster.removeAttribute( 'srcset' );
					poster.className = 'max-h-[70dvh] max-w-full object-contain opacity-60';
					start.append( poster );
				}
				const icon = slide.querySelector( 'svg' )?.cloneNode( true );
				if ( icon instanceof SVGElement ) {
					const badge = document.createElement( 'span' );
					badge.className = 'absolute inset-0 grid place-items-center';
					badge.append( icon );
					start.append( badge );
				}
				start.addEventListener( 'click', () => play( item, slide ) );
				item.append( start );
			} else {
				const image = document.createElement( 'img' );
				image.src = slide.dataset.full || picture?.currentSrc || picture?.src || '';
				image.alt = picture?.alt ?? '';
				image.loading = Math.abs( index - currentIndex( track ) ) > 1 ? 'lazy' : 'eager';
				image.decoding = 'async';
				image.className = 'max-h-full max-w-full cursor-zoom-in object-contain transition-transform duration-200 data-zoomed:scale-200 data-zoomed:cursor-zoom-out';
				image.addEventListener( 'click', ( event ) => {
					const zoomed = image.toggleAttribute( 'data-zoomed' );
					if ( zoomed ) {
						const rect = image.getBoundingClientRect();
						image.style.transformOrigin = `${ ( ( event.clientX - rect.left ) / rect.width ) * 100 }% ${ ( ( event.clientY - rect.top ) / rect.height ) * 100 }%`;
					}
				} );
				item.append( image );
			}
			viewerTrack.append( item );

			const thumb = document.createElement( 'button' );
			thumb.type = 'button';
			thumb.className = 'grid size-14 shrink-0 cursor-pointer place-items-center overflow-hidden rounded-md border border-white/20 bg-white p-1 opacity-60 aria-[current=true]:opacity-100 aria-[current=true]:outline-2 aria-[current=true]:outline-white [&_img]:size-full [&_img]:object-contain';
			thumb.setAttribute( 'aria-label', slide.dataset.label ?? '' );
			if ( picture ) {
				const small = picture.cloneNode() as HTMLImageElement;
				small.removeAttribute( 'srcset' );
				small.removeAttribute( 'sizes' );
				small.alt = '';
				thumb.append( small );
			}
			thumb.addEventListener( 'click', () => viewerGo( index ) );
			viewerThumbs.append( thumb );
		} );
	};

	const openViewer = ( index: number ): void => {
		if ( ! viewer ) {
			return;
		}
		fill();
		window.UltraMaxUI?.get( viewer )?.open?.();
		requestAnimationFrame( () => {
			viewerGo( index );
			const slide = slides()[ index ];
			const holder = viewerTrack?.children[ index ] as HTMLElement | undefined;
			if ( slide?.dataset.video && holder ) {
				play( holder, slide );
			}
		} );
	};

	let viewerFrame = 0;
	const onViewerScroll = (): void => {
		cancelAnimationFrame( viewerFrame );
		viewerFrame = requestAnimationFrame( viewerMark );
	};

	const onViewerKey = ( event: KeyboardEvent ): void => {
		if ( event.key !== 'ArrowLeft' && event.key !== 'ArrowRight' ) {
			return;
		}
		event.preventDefault();
		const rtl = getComputedStyle( viewer ?? document.body ).direction === 'rtl';
		const forward = event.key === ( rtl ? 'ArrowLeft' : 'ArrowRight' );
		viewerGo( Math.max( 0, Math.min( ( viewerTrack?.children.length ?? 1 ) - 1, viewerIndex() + ( forward ? 1 : -1 ) ) ) );
	};

	const onViewerClick = ( event: MouseEvent ): void => {
		const step = event.target instanceof Element ? event.target.closest<HTMLElement>( '[data-um-viewer-step]' ) : null;
		if ( step ) {
			viewerGo( Math.max( 0, Math.min( ( viewerTrack?.children.length ?? 1 ) - 1, viewerIndex() + Number( step.dataset.umViewerStep ) ) ) );
		}
	};

	const onViewerClose = (): void => {
		viewerTrack?.replaceChildren();
		viewerThumbs?.replaceChildren();
	};

	/* ---------------------------------------------------------- clicks */

	const onClick = ( event: MouseEvent ): void => {
		const target = event.target instanceof Element ? event.target : null;
		const thumb = target?.closest<HTMLAnchorElement>( '[data-um-gallery-thumb]' );
		if ( thumb ) {
			event.preventDefault();
			goTo( Number( thumb.dataset.umGalleryThumb ) );
			mark( Number( thumb.dataset.umGalleryThumb ) );
			return;
		}
		const step = target?.closest<HTMLElement>( '[data-um-gallery-step]' );
		if ( step ) {
			const total = slides().length;
			goTo( ( currentIndex( track ) + Number( step.dataset.umGalleryStep ) + total ) % total );
			return;
		}
		const opener = target?.closest<HTMLElement>( '[data-um-gallery-open]' );
		if ( opener ) {
			openViewer( Number( opener.dataset.umGalleryOpen ) );
		}
	};

	/* ---------------------------------------------------------- variations */

	let currentSet = '';
	const onVariation = ( event: Event ): void => {
		const detail = ( event as CustomEvent<VariationEvent> ).detail ?? {};
		if ( product > 0 && detail.product !== product ) {
			return;
		}
		const key = detail.gallery ?? '';
		if ( key === currentSet ) {
			return;
		}
		currentSet = key;
		const part = ( name: string ): string | null => {
			if ( key === '' ) {
				return null;
			}
			const template = element.querySelector<HTMLTemplateElement>( `template[data-um-gallery-set="${ CSS.escape( key ) }"][data-part="${ name }"]` );
			return template ? template.innerHTML : null;
		};
		track.innerHTML = part( 'track' ) ?? original.track;
		if ( thumbs ) {
			thumbs.innerHTML = part( 'thumbs' ) ?? original.thumbs;
		}
		if ( dots ) {
			const total = slides().length;
			dots.replaceChildren( ...Array.from( { length: total }, ( _, index ) => {
				const dot = document.createElement( 'i' );
				dot.className = dots.firstElementChild?.className ?? '';
				dot.toggleAttribute( 'data-current', index === 0 );
				return dot;
			} ) );
			dots.hidden = total < 2;
		}
		track.scrollTo( { left: 0 } );
		mark( 0 );
	};

	track.addEventListener( 'scroll', onScroll, { passive: true } );
	track.addEventListener( 'pointermove', onMove );
	track.addEventListener( 'pointerleave', onLeave );
	element.addEventListener( 'click', onClick );
	document.addEventListener( 'um:variation', onVariation );
	viewerTrack?.addEventListener( 'scroll', onViewerScroll, { passive: true } );
	viewer?.addEventListener( 'keydown', onViewerKey );
	viewer?.addEventListener( 'click', onViewerClick );
	viewer?.addEventListener( 'um:close', onViewerClose );

	return {
		destroy(): void {
			track.removeEventListener( 'scroll', onScroll );
			track.removeEventListener( 'pointermove', onMove );
			track.removeEventListener( 'pointerleave', onLeave );
			element.removeEventListener( 'click', onClick );
			document.removeEventListener( 'um:variation', onVariation );
			viewerTrack?.removeEventListener( 'scroll', onViewerScroll );
			viewer?.removeEventListener( 'keydown', onViewerKey );
			viewer?.removeEventListener( 'click', onViewerClick );
			viewer?.removeEventListener( 'um:close', onViewerClose );
		},
	};
};
