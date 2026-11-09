/**
 * Larger admin parts: SearchBox (settings search, ADM-01), ModuleCard
 * (ADM-07), MediaField, Repeater, DataTable and Tour (ADM-06).
 */
import { useEffect, useId, useRef, useState, type ReactNode } from 'react';
import { cn } from 'cn';
import { Input } from '@ui-admin/components/ui/input';
import { Switch as ShadSwitch } from '@ui-admin/components/ui/switch';
import { Popover, PopoverAnchor, PopoverContent } from '@ui-admin/components/ui/popover';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@ui-admin/components/ui/table';
import { Button } from './Button';
import { Icon } from './Icon';

export interface SearchResult {
	id: string;
	label: string;
	context: string;
}

export interface SearchBoxProps {
	label: string;
	placeholder: string;
	empty: string;
	search: ( query: string ) => SearchResult[];
	onPick: ( id: string ) => void;
}

/** A search field with a list of matches; Ctrl+K focuses it. */
export function SearchBox( { label, placeholder, empty, search, onPick }: SearchBoxProps ) {
	const [ query, setQuery ] = useState( '' );
	const [ active, setActive ] = useState( 0 );
	const [ open, setOpen ] = useState( false );
	const input = useRef<HTMLInputElement>( null );
	const listId = useId();
	const results = query.trim() ? search( query ) : [];

	useEffect( () => {
		const onKey = ( event: KeyboardEvent ) => {
			if ( ( event.ctrlKey || event.metaKey ) && event.key.toLowerCase() === 'k' ) {
				event.preventDefault();
				input.current?.focus();
			}
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [] );

	const pick = ( id: string ) => {
		onPick( id );
		setOpen( false );
		setQuery( '' );
	};

	return (
		<div className="relative max-w-105 flex-1 max-md:max-w-none max-md:basis-full">
			<Icon name="search" className="pointer-events-none absolute start-3 top-1/2 size-4.5 -translate-y-1/2 text-fg-3" />
			<Input
				ref={ input }
				type="search"
				role="combobox"
				aria-label={ label }
				aria-expanded={ open && query.trim() !== '' }
				aria-controls={ listId }
				aria-activedescendant={ results[ active ] ? `${ listId }-${ active }` : undefined }
				aria-autocomplete="list"
				placeholder={ placeholder }
				value={ query }
				onChange={ ( event ) => {
					setQuery( event.target.value );
					setActive( 0 );
					setOpen( true );
				} }
				onFocus={ () => setOpen( true ) }
				onBlur={ () => window.setTimeout( () => setOpen( false ), 150 ) }
				onKeyDown={ ( event ) => {
					if ( event.key === 'ArrowDown' ) {
						event.preventDefault();
						setActive( ( index ) => Math.min( index + 1, results.length - 1 ) );
					} else if ( event.key === 'ArrowUp' ) {
						event.preventDefault();
						setActive( ( index ) => Math.max( index - 1, 0 ) );
					} else if ( event.key === 'Enter' && results[ active ] ) {
						event.preventDefault();
						pick( results[ active ].id );
					} else if ( event.key === 'Escape' ) {
						setOpen( false );
					}
				} }
				className="h-11 bg-surface-3 ps-10 pe-16"
			/>
			<kbd className="pointer-events-none absolute end-2.5 top-1/2 -translate-y-1/2 rounded-sm border border-border-strong bg-card px-1.5 text-[11px] text-fg-3 max-md:hidden" dir="ltr">Ctrl K</kbd>
			{ open && query.trim() !== '' ? (
				<ul id={ listId } role="listbox" aria-label={ label } className="absolute inset-x-0 top-[calc(100%+6px)] z-30 m-0 grid max-h-96 list-none overflow-y-auto rounded-lg border border-border bg-popover p-1.5 shadow-um-lg">
					{ results.length === 0 ? <li className="px-2.5 py-2 text-xs text-fg-3">{ empty }</li> : null }
					{ results.map( ( result, index ) => (
						<li
							key={ result.id }
							id={ `${ listId }-${ index }` }
							role="option"
							aria-selected={ index === active }
							onMouseDown={ ( event ) => {
								event.preventDefault();
								pick( result.id );
							} }
							onMouseEnter={ () => setActive( index ) }
							className={ cn( 'grid cursor-pointer rounded-md px-2.5 py-2 text-[13px]', index === active && 'bg-surface-3' ) }
						>
							<span>{ result.label }</span>
							<small className="text-xs text-fg-3">{ result.context }</small>
						</li>
					) ) }
				</ul>
			) : null }
		</div>
	);
}

export interface ModuleCardProps {
	id: string;
	title: string;
	description: string;
	icon: string;
	on: boolean;
	stateLabel: string;
	toggleLabel: string;
	features: string[];
	weight?: string;
	settings?: ReactNode;
	busy?: boolean;
	onToggle: ( on: boolean ) => void;
}

/** One module with its switch (ADM-07). */
export function ModuleCard( { id, title, description, icon, on, stateLabel, toggleLabel, features, weight, settings, busy, onToggle }: ModuleCardProps ) {
	return (
		<article className={ cn( 'flex flex-col gap-2.5 rounded-xl border border-border p-4 transition-colors', on ? 'bg-card' : 'bg-surface-2' ) } data-module={ id } data-on={ on }>
			<div className="flex items-start gap-3">
				<span className={ cn( 'grid size-11 shrink-0 place-items-center rounded-xl bg-primary-soft text-primary', ! on && 'opacity-60 grayscale' ) }>
					<Icon name={ icon } className="size-6" />
				</span>
				<div className="grid flex-1 gap-0.5">
					<b className="text-[13px]">{ title }</b>
					<small className="text-xs text-fg-3" aria-live="polite">{ stateLabel }</small>
				</div>
				<ShadSwitch checked={ on } disabled={ busy } onCheckedChange={ onToggle } aria-label={ toggleLabel } />
			</div>
			<p className="m-0 flex-1 text-xs leading-loose text-fg-2">{ description }</p>
			<div className="flex items-center gap-2 border-t border-border pt-2.5 text-xs">
				{ settings }
				<span className="text-[10px] text-fg-3" dir="ltr">{ features.join( ' · ' ) }</span>
				{ weight ? <span className="ms-auto text-fg-3">{ weight }</span> : null }
			</div>
		</article>
	);
}

interface WpMediaFrame {
	on: ( event: string, callback: () => void ) => void;
	open: () => void;
	state: () => { get: ( key: string ) => { first: () => { toJSON: () => { id: number; url: string; filename?: string; mime?: string } } } };
}

declare global {
	interface Window {
		wp?: { media?: ( options: Record<string, unknown> ) => WpMediaFrame };
	}
}

export interface MediaFieldProps {
	id: string;
	value: number;
	/** File name or URL of the current file, if known. */
	current?: string;
	accept?: string;
	strings: { choose: string; replace: string; remove: string; title: string; none: string };
	onChange: ( id: number, file?: { url: string; filename?: string } ) => void;
	describedBy?: string;
}

/** Picks a file from the WordPress media library. */
export function MediaField( { id, value, current, accept, strings, onChange, describedBy }: MediaFieldProps ) {
	const open = () => {
		const frame = window.wp?.media?.( {
			title: strings.title,
			button: { text: strings.choose },
			multiple: false,
			library: accept ? { type: accept } : undefined,
		} );
		if ( ! frame ) {
			return;
		}
		frame.on( 'select', () => {
			const file = frame.state().get( 'selection' ).first().toJSON();
			onChange( file.id, { url: file.url, filename: file.filename } );
		} );
		frame.open();
	};
	return (
		<div className="flex flex-wrap items-center gap-3">
			<Button id={ id } variant="outline" size="sm" onClick={ open } aria-describedby={ describedBy }>
				<Icon name="upload" className="size-4" />
				{ value > 0 ? strings.replace : strings.choose }
			</Button>
			<span className="text-xs text-fg-3" dir="auto">{ value > 0 ? current ?? `#${ value }` : strings.none }</span>
			{ value > 0 ? (
				<Button variant="ghost" size="sm" onClick={ () => onChange( 0 ) }>
					{ strings.remove }
				</Button>
			) : null }
		</div>
	);
}

export interface RepeaterProps<Row> {
	label: string;
	rows: Row[];
	onChange: ( rows: Row[] ) => void;
	renderRow: ( row: Row, update: ( row: Row ) => void, index: number ) => ReactNode;
	newRow: () => Row;
	max?: number;
	strings: { add: string; remove: string; up: string; down: string; row: string };
}

/** A list of rows that can be added, removed and reordered. */
export function Repeater<Row>( { label, rows, onChange, renderRow, newRow, max = 50, strings }: RepeaterProps<Row> ) {
	const move = ( from: number, to: number ) => {
		const next = [ ...rows ];
		const [ item ] = next.splice( from, 1 );
		if ( item !== undefined ) {
			next.splice( to, 0, item );
			onChange( next );
		}
	};
	return (
		<div className="grid gap-3" role="group" aria-label={ label }>
			{ rows.map( ( row, index ) => (
				<div key={ index } className="grid gap-3 rounded-lg border border-border bg-surface-2 p-3" role="group" aria-label={ strings.row.replace( '%d', String( index + 1 ) ) }>
					{ renderRow( row, ( updated ) => onChange( rows.map( ( item, i ) => ( i === index ? updated : item ) ) ), index ) }
					<div className="flex gap-1">
						<Button variant="ghost" size="sm" disabled={ index === 0 } onClick={ () => move( index, index - 1 ) } aria-label={ strings.up }>
							<Icon name="chev-up" className="size-4" />
						</Button>
						<Button variant="ghost" size="sm" disabled={ index === rows.length - 1 } onClick={ () => move( index, index + 1 ) } aria-label={ strings.down }>
							<Icon name="chev-down" className="size-4" />
						</Button>
						<Button variant="ghost" size="sm" className="ms-auto" onClick={ () => onChange( rows.filter( ( _, i ) => i !== index ) ) }>
							<Icon name="trash" className="size-4" />
							{ strings.remove }
						</Button>
					</div>
				</div>
			) ) }
			<Button variant="outline" size="sm" className="w-fit" disabled={ rows.length >= max } onClick={ () => onChange( [ ...rows, newRow() ] ) }>
				<Icon name="plus" className="size-4" />
				{ strings.add }
			</Button>
		</div>
	);
}

export interface DataTableProps {
	columns: { key: string; label: string; align?: 'start' | 'end' }[];
	rows: ( Record<string, ReactNode> & { id: string } )[];
	empty: string;
	caption: string;
}

/** A simple table, such as the list of backups. */
export function DataTable( { columns, rows, empty, caption }: DataTableProps ) {
	return (
		<Table aria-label={ caption }>
			<TableHeader>
				<TableRow className="bg-surface-2">
					{ columns.map( ( column ) => (
						<TableHead key={ column.key } className={ cn( 'text-xs font-medium text-fg-3', column.align === 'end' ? 'text-end' : 'text-start' ) }>{ column.label }</TableHead>
					) ) }
				</TableRow>
			</TableHeader>
			<TableBody>
				{ rows.length === 0 ? (
					<TableRow>
						<TableCell colSpan={ columns.length } className="py-6 text-center text-fg-3">{ empty }</TableCell>
					</TableRow>
				) : null }
				{ rows.map( ( row ) => (
					<TableRow key={ row.id }>
						{ columns.map( ( column ) => (
							<TableCell key={ column.key } className={ cn( 'text-[13px]', column.align === 'end' ? 'text-end' : 'text-start' ) }>{ row[ column.key ] }</TableCell>
						) ) }
					</TableRow>
				) ) }
			</TableBody>
		</Table>
	);
}

export interface TourStep {
	/** CSS selector of the element the step points at. */
	target: string;
	title: string;
	text: string;
}

export interface TourProps {
	steps: TourStep[];
	strings: { next: string; back: string; done: string; skip: string; progress: string };
	onFinish: () => void;
}

/** A short guided tour of a page, one popover per step (ADM-06). */
export function Tour( { steps, strings, onFinish }: TourProps ) {
	const [ index, setIndex ] = useState( 0 );
	const [ rect, setRect ] = useState<DOMRect | null>( null );
	const step = steps[ index ];

	useEffect( () => {
		const element = step ? document.querySelector<HTMLElement>( step.target ) : null;
		if ( ! element ) {
			setRect( null );
			return;
		}
		element.scrollIntoView( { block: 'center', behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth' } );
		const update = () => setRect( element.getBoundingClientRect() );
		const timer = window.setTimeout( update, 250 );
		window.addEventListener( 'resize', update );
		window.addEventListener( 'scroll', update, true );
		return () => {
			window.clearTimeout( timer );
			window.removeEventListener( 'resize', update );
			window.removeEventListener( 'scroll', update, true );
		};
	}, [ step ] );

	if ( ! step || ! rect ) {
		return null;
	}
	const last = index === steps.length - 1;
	return (
		<Popover open onOpenChange={ ( open ) => ! open && onFinish() }>
			<PopoverAnchor asChild>
				<span className="pointer-events-none fixed z-[99998] rounded-lg ring-4 ring-primary/60 transition-all" style={ { top: rect.top - 4, left: rect.left - 4, width: rect.width + 8, height: rect.height + 8 } } />
			</PopoverAnchor>
			<PopoverContent className="z-[99999] grid w-80 gap-2 p-4" side="bottom" align="start" aria-label={ step.title }>
				<small className="text-xs text-fg-3">{ strings.progress.replace( '%1$s', String( index + 1 ) ).replace( '%2$s', String( steps.length ) ) }</small>
				<b className="text-sm">{ step.title }</b>
				<p className="m-0 text-[13px] leading-relaxed text-fg-2">{ step.text }</p>
				<div className="mt-2 flex items-center gap-2">
					<Button variant="ghost" size="sm" onClick={ onFinish }>{ strings.skip }</Button>
					<span className="flex-1" />
					{ index > 0 ? <Button variant="outline" size="sm" onClick={ () => setIndex( index - 1 ) }>{ strings.back }</Button> : null }
					<Button size="sm" onClick={ () => ( last ? onFinish() : setIndex( index + 1 ) ) }>{ last ? strings.done : strings.next }</Button>
				</div>
			</PopoverContent>
		</Popover>
	);
}
