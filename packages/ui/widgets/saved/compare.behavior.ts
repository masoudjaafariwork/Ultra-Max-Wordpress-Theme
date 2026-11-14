/**
 * CompareTable behaviour (SAVE-02): "Only differences" hides the rows whose
 * values are all the same, and the group headings left without rows.
 */
import type { Behavior } from '../../storefront/runtime';

/**
 * Shows or hides the rows that are the same for every product.
 *
 * @param element Table root.
 * @param only    Whether to show only the differences.
 */
export function onlyDifferences( element: HTMLElement, only: boolean ): void {
	element.toggleAttribute( 'data-only-differences', only );
	let group: HTMLTableRowElement | null = null;
	let shown = 0;
	const close = (): void => {
		if ( group ) {
			group.hidden = only && shown === 0;
		}
	};
	element.querySelectorAll<HTMLTableRowElement>( 'tbody tr' ).forEach( ( row ) => {
		if ( row.classList.contains( 'um-compare-group' ) ) {
			close();
			group = row;
			shown = 0;
			return;
		}
		if ( ! only || row.hasAttribute( 'data-differs' ) ) {
			shown++;
		}
	} );
	close();
}

export const compare: Behavior = ( element ) => {
	const toggle = element.querySelector<HTMLInputElement>( '[data-um-compare-differences]' );
	const onChange = (): void => onlyDifferences( element, Boolean( toggle?.checked ) );
	toggle?.addEventListener( 'change', onChange );
	if ( toggle?.checked ) {
		onChange();
	}
	return {
		destroy(): void {
			toggle?.removeEventListener( 'change', onChange );
		},
	};
};
