/**
 * Gallery lightbox on the browser's own `dialog`: opens the clicked image
 * large, steps with the arrow buttons, the arrow keys (mirrored right to
 * left) and swipes, and returns focus to the thumbnail when closed.
 */
import type { Behavior } from '../../storefront/runtime';

export const gallery: Behavior = ( element ) => {
	const viewer = element.querySelector<HTMLDialogElement>( '[data-viewer]' );
	const image = viewer?.querySelector<HTMLImageElement>( '[data-image]' );
	const caption = viewer?.querySelector<HTMLElement>( '[data-caption]' );
	const links = Array.from( element.querySelectorAll<HTMLAnchorElement>( 'a[data-index]' ) );
	if ( ! viewer || ! image || ! links.length ) {
		return {};
	}
	let index = 0;
	let opener: HTMLElement | null = null;
	let startX: number | null = null;

	const show = ( next: number ): void => {
		index = ( next + links.length ) % links.length;
		const link = links[ index ];
		if ( ! link ) {
			return;
		}
		image.src = link.href;
		image.alt = link.dataset.caption ?? link.querySelector( 'img' )?.alt ?? '';
		if ( caption ) {
			caption.textContent = link.dataset.caption ?? '';
		}
	};
	const rtl = (): boolean => getComputedStyle( viewer ).direction === 'rtl';

	const onThumb = ( event: MouseEvent ): void => {
		const link = event.target instanceof Element ? event.target.closest<HTMLAnchorElement>( 'a[data-index]' ) : null;
		if ( ! link || event.ctrlKey || event.metaKey ) {
			return;
		}
		event.preventDefault();
		opener = link;
		show( Number( link.dataset.index ) );
		viewer.showModal();
	};
	const onViewer = ( event: MouseEvent ): void => {
		const target = event.target instanceof Element ? event.target : null;
		const step = target?.closest<HTMLElement>( '[data-step]' );
		if ( step ) {
			show( index + Number( step.dataset.step ) );
		} else if ( target?.closest( '[data-close]' ) || target === viewer || target?.classList.contains( 'grid' ) ) {
			viewer.close();
		}
	};
	const onKey = ( event: KeyboardEvent ): void => {
		if ( event.key === 'ArrowLeft' ) {
			show( index + ( rtl() ? 1 : -1 ) );
		} else if ( event.key === 'ArrowRight' ) {
			show( index + ( rtl() ? -1 : 1 ) );
		}
	};
	const onDown = ( event: PointerEvent ): void => {
		startX = event.clientX;
	};
	const onUp = ( event: PointerEvent ): void => {
		if ( startX !== null && Math.abs( event.clientX - startX ) > 40 ) {
			const forward = event.clientX < startX;
			show( index + ( forward !== rtl() ? 1 : -1 ) );
		}
		startX = null;
	};
	const onClose = (): void => opener?.focus();

	element.addEventListener( 'click', onThumb );
	viewer.addEventListener( 'click', onViewer );
	viewer.addEventListener( 'keydown', onKey );
	viewer.addEventListener( 'pointerdown', onDown );
	viewer.addEventListener( 'pointerup', onUp );
	viewer.addEventListener( 'close', onClose );
	return {
		close: () => viewer.close(),
		destroy(): void {
			element.removeEventListener( 'click', onThumb );
		},
	};
};
