/**
 * Admin lists and pickers: ItemList and ItemRow (a list of things with
 * actions, such as layouts), PickerCard (a choice with a preview image),
 * FieldGroup (a labelled block of controls) and Note (a short hint).
 */
import type { ReactNode } from 'react';
import { cn } from 'cn';

/** A list of rows. */
export function ItemList( { children, label }: { children: ReactNode; label?: string } ) {
	return (
		<ul className="m-0 grid list-none gap-2 p-0" aria-label={ label }>
			{ children }
		</ul>
	);
}

export interface ItemRowProps {
	title: ReactNode;
	meta?: ReactNode;
	/** Leading control, such as a switch. */
	start?: ReactNode;
	badges?: ReactNode;
	actions?: ReactNode;
	/** Data attribute for tests. */
	id?: string;
}

/** One row: a control, a title with a line under it, badges and actions. */
export function ItemRow( { title, meta, start, badges, actions, id }: ItemRowProps ) {
	return (
		<li className="flex flex-wrap items-center gap-3 rounded-lg border border-border bg-card p-3" data-item={ id }>
			{ start }
			<div className="grid min-w-0 flex-1 gap-0.5">
				<b className="text-sm">{ title }</b>
				{ meta ? <small className="text-xs break-words text-fg-3">{ meta }</small> : null }
			</div>
			{ badges ? <span className="flex flex-wrap gap-1">{ badges }</span> : null }
			{ actions ? <span className="flex flex-wrap gap-1">{ actions }</span> : null }
		</li>
	);
}

export interface PickerCardProps {
	title: string;
	description?: string;
	image?: string;
	selected: boolean;
	onSelect: () => void;
}

/** A choice with a preview image, such as a ready-made design. */
export function PickerCard( { title, description, image, selected, onSelect }: PickerCardProps ) {
	return (
		<button
			type="button"
			aria-pressed={ selected }
			onClick={ onSelect }
			className={ cn( 'grid content-start gap-1.5 rounded-lg border bg-card p-2 text-start transition-colors hover:border-border-strong focus-visible:outline-2 focus-visible:outline-ring', selected ? 'border-primary ring-2 ring-primary/30' : 'border-border' ) }
		>
			{ image ? <img src={ image } alt="" loading="lazy" className="aspect-[16/7] w-full rounded-md bg-surface-3 object-cover object-top" /> : <span className="aspect-[16/7] w-full rounded-md bg-surface-3" /> }
			<b className="text-xs">{ title }</b>
			{ description ? <small className="text-xs leading-relaxed text-fg-3">{ description }</small> : null }
		</button>
	);
}

/** A grid of picker cards. */
export function PickerGrid( { children, label }: { children: ReactNode; label: string } ) {
	return (
		<div role="group" aria-label={ label } className="grid grid-cols-[repeat(auto-fill,minmax(200px,1fr))] gap-2">
			{ children }
		</div>
	);
}

export interface FieldGroupProps {
	title: string;
	help?: string;
	/** Id of the control the title labels; without it the title is a plain heading. */
	htmlFor?: string;
	children: ReactNode;
}

/** A titled block of controls with an optional help line. */
export function FieldGroup( { title, help, htmlFor, children }: FieldGroupProps ) {
	return (
		<section className="grid gap-2">
			{ htmlFor ? <label htmlFor={ htmlFor } className="text-sm font-bold">{ title }</label> : <b className="text-sm">{ title }</b> }
			{ help ? <p className="m-0 text-[13px] leading-relaxed text-fg-2">{ help }</p> : null }
			{ children }
		</section>
	);
}

/** A bordered box around one item of an editable list. */
export function Inset( { children }: { children: ReactNode } ) {
	return <div className="grid gap-2 rounded-lg border border-border bg-surface-2 p-2.5">{ children }</div>;
}

/** A short hint under a control. */
export function Note( { tone = 'muted', children }: { tone?: 'muted' | 'warning'; children: ReactNode } ) {
	return <small className={ cn( 'text-xs', tone === 'warning' ? 'text-warning-text' : 'text-fg-3' ) }>{ children }</small>;
}
