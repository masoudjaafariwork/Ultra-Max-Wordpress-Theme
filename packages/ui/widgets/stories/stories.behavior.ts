/**
 * Stories viewer (HOME-02) on the browser's own `dialog` element: plays
 * the stories in order with progress bars, steps with taps, arrow keys and
 * swipes, pauses while pressed, and remembers which ones were seen.
 */
import type { Behavior } from '../../storefront/runtime';

interface Story {
	title: string;
	media: string;
	type: 'image' | 'video';
	href: string;
	link: string;
	id: string;
}

const SEEN_KEY = 'um-stories-seen';

/** Ids of stories already seen. */
function seen(): Set<string> {
	try {
		return new Set( JSON.parse( window.localStorage.getItem( SEEN_KEY ) ?? '[]' ) as string[] );
	} catch {
		return new Set();
	}
}

/**
 * Remembers a seen story.
 *
 * @param id Story id.
 */
function markSeen( id: string ): void {
	const all = seen();
	all.add( id );
	try {
		window.localStorage.setItem( SEEN_KEY, JSON.stringify( [ ...all ].slice( -200 ) ) );
	} catch {
		// Private browsing: nothing to remember.
	}
}

export const stories: Behavior = ( element ) => {
	const viewer = element.querySelector<HTMLDialogElement>( '[data-viewer]' );
	const stage = viewer?.querySelector<HTMLElement>( '[data-stage]' );
	const bars = viewer?.querySelector<HTMLElement>( '[data-bars]' );
	const title = viewer?.querySelector<HTMLElement>( '[data-title]' );
	const link = viewer?.querySelector<HTMLAnchorElement>( '[data-link]' );
	let list: Story[] = [];
	try {
		list = JSON.parse( element.querySelector( '[data-stories]' )?.textContent ?? '[]' ) as Story[];
	} catch {
		list = [];
	}
	if ( ! viewer || ! stage || ! bars || list.length === 0 ) {
		return {};
	}
	const duration = Number( element.dataset.duration ?? 5000 );
	const reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	let index = 0;
	let started = 0;
	let elapsed = 0;
	let frame = 0;
	let paused = false;
	let length = duration;
	let opener: HTMLElement | null = null;

	const known = seen();
	element.querySelectorAll<HTMLElement>( '[data-id]' ).forEach( ( cover ) => cover.toggleAttribute( 'data-seen', known.has( cover.dataset.id ?? '' ) ) );

	const fill = ( position: number, amount: number ): void => {
		Array.from( bars.children ).forEach( ( bar, i ) => {
			const inner = bar.firstElementChild as HTMLElement | null;
			if ( inner ) {
				inner.style.width = `${ i < position ? 100 : i > position ? 0 : Math.min( 100, amount * 100 ) }%`;
			}
		} );
	};

	const loop = ( now: number ): void => {
		if ( ! paused ) {
			const progress = ( elapsed + now - started ) / length;
			fill( index, progress );
			if ( progress >= 1 ) {
				go( index + 1 );
				return;
			}
		}
		frame = window.requestAnimationFrame( loop );
	};

	const show = ( story: Story ): void => {
		stage.replaceChildren();
		window.cancelAnimationFrame( frame );
		elapsed = 0;
		length = duration;
		if ( story.type === 'video' ) {
			const video = document.createElement( 'video' );
			video.src = story.media;
			video.playsInline = true;
			video.autoplay = true;
			video.className = 'max-h-full max-w-full';
			video.addEventListener( 'loadedmetadata', () => {
				length = Number.isFinite( video.duration ) ? video.duration * 1000 : duration;
			} );
			stage.append( video );
		} else {
			const image = document.createElement( 'img' );
			image.src = story.media;
			image.alt = story.title;
			image.className = 'max-h-full max-w-full object-contain';
			stage.append( image );
		}
		if ( title ) {
			title.textContent = story.title;
		}
		if ( link ) {
			link.hidden = ! story.href;
			link.href = story.href || '#';
			link.textContent = story.link || story.title;
		}
		markSeen( story.id );
		element.querySelector( `[data-id="${ CSS.escape( story.id ) }"]` )?.setAttribute( 'data-seen', '' );
		started = performance.now();
		if ( ! reduced ) {
			frame = window.requestAnimationFrame( loop );
		} else {
			fill( index, 1 );
		}
	};

	const go = ( next: number ): void => {
		const story = list[ next ];
		if ( ! story ) {
			viewer.close();
			return;
		}
		index = next;
		show( story );
	};

	const open = ( start: number ): void => {
		bars.replaceChildren(
			...list.map( () => {
				const bar = document.createElement( 'span' );
				bar.className = 'um-story-bar';
				bar.append( document.createElement( 'i' ) );
				return bar;
			} )
		);
		viewer.showModal();
		go( start );
	};

	const onCover = ( event: MouseEvent ): void => {
		const cover = event.target instanceof Element ? event.target.closest<HTMLElement>( '[data-story]' ) : null;
		if ( ! cover || event.ctrlKey || event.metaKey ) {
			return;
		}
		event.preventDefault();
		opener = cover;
		open( Number( cover.dataset.story ?? 0 ) );
	};

	const onViewer = ( event: MouseEvent ): void => {
		const target = event.target instanceof Element ? event.target : null;
		const step = target?.closest<HTMLElement>( '[data-step]' );
		if ( step ) {
			go( index + Number( step.dataset.step ) * ( getComputedStyle( viewer ).direction === 'rtl' ? -1 : 1 ) );
		} else if ( target?.closest( '[data-close]' ) || target === viewer ) {
			viewer.close();
		}
	};

	const onKey = ( event: KeyboardEvent ): void => {
		const rtl = getComputedStyle( viewer ).direction === 'rtl';
		if ( event.key === 'ArrowLeft' ) {
			go( index + ( rtl ? 1 : -1 ) );
		} else if ( event.key === 'ArrowRight' ) {
			go( index + ( rtl ? -1 : 1 ) );
		}
	};

	const hold = ( down: boolean ): void => {
		if ( down && ! paused ) {
			paused = true;
			elapsed += performance.now() - started;
			stage.querySelector( 'video' )?.pause();
		} else if ( ! down && paused ) {
			paused = false;
			started = performance.now();
			void stage.querySelector( 'video' )?.play();
		}
	};
	const onDown = (): void => hold( true );
	const onUp = (): void => hold( false );

	const onClose = (): void => {
		window.cancelAnimationFrame( frame );
		stage.replaceChildren();
		opener?.focus();
	};

	element.addEventListener( 'click', onCover );
	viewer.addEventListener( 'click', onViewer );
	viewer.addEventListener( 'keydown', onKey );
	viewer.addEventListener( 'pointerdown', onDown );
	viewer.addEventListener( 'pointerup', onUp );
	viewer.addEventListener( 'close', onClose );

	return {
		open: () => open( 0 ),
		close: () => viewer.close(),
		destroy(): void {
			element.removeEventListener( 'click', onCover );
			viewer.removeEventListener( 'close', onClose );
			window.cancelAnimationFrame( frame );
		},
	};
};
