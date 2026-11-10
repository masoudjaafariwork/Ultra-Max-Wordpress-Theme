/**
 * MultiSelect: choose several items from a list that may be long, by
 * typing. The options come from `load`, which may ask the server; chosen
 * items show as removable chips. Used by the layout conditions (BLD-02).
 */
import { useEffect, useState } from 'react';
import { Popover, PopoverContent, PopoverTrigger } from '@ui-admin/components/ui/popover';
import { Command, CommandEmpty, CommandInput, CommandItem, CommandList } from '@ui-admin/components/ui/command';
import { buttonVariants } from '@ui-admin/components/ui/button';
import { Icon } from './Icon';

export interface MultiSelectOption {
	value: string;
	label: string;
}

export interface MultiSelectProps {
	id: string;
	/** Accessible name of the list. */
	label: string;
	value: string[];
	onChange: ( value: string[] ) => void;
	/** Options matching a search; called again as the user types. */
	load: ( search: string ) => Promise<MultiSelectOption[]>;
	/** Labels of the chosen values, known before the first search. */
	known?: MultiSelectOption[];
	strings: { add: string; search: string; empty: string; remove: string; loading: string };
}

export function MultiSelect( { id, label, value, onChange, load, known = [], strings }: MultiSelectProps ) {
	const [ open, setOpen ] = useState( false );
	const [ search, setSearch ] = useState( '' );
	const [ options, setOptions ] = useState<MultiSelectOption[]>( [] );
	const [ busy, setBusy ] = useState( false );
	const [ names, setNames ] = useState<Record<string, string>>( () => Object.fromEntries( known.map( ( option ) => [ option.value, option.label ] ) ) );

	useEffect( () => {
		setNames( ( current ) => ( { ...current, ...Object.fromEntries( known.map( ( option ) => [ option.value, option.label ] ) ) } ) );
	}, [ known ] );

	useEffect( () => {
		if ( ! open ) {
			return;
		}
		let live = true;
		setBusy( true );
		const timer = window.setTimeout( () => {
			load( search )
				.then( ( found ) => {
					if ( live ) {
						setOptions( found );
						setNames( ( current ) => ( { ...current, ...Object.fromEntries( found.map( ( option ) => [ option.value, option.label ] ) ) } ) );
					}
				} )
				.catch( () => live && setOptions( [] ) )
				.finally( () => live && setBusy( false ) );
		}, 200 );
		return () => {
			live = false;
			window.clearTimeout( timer );
		};
	}, [ open, search, load ] );

	const toggle = ( option: string ) => onChange( value.includes( option ) ? value.filter( ( item ) => item !== option ) : [ ...value, option ] );

	return (
		<div className="flex flex-wrap items-center gap-1.5" role="group" aria-label={ label }>
			{ value.map( ( item ) => (
				<span key={ item } className="inline-flex h-7 items-center gap-1 rounded-full bg-primary-soft ps-2.5 pe-1 text-xs text-primary">
					{ names[ item ] ?? `#${ item }` }
					<button type="button" className="grid size-5 place-items-center rounded-full hover:bg-primary/15" aria-label={ strings.remove.replace( '%s', names[ item ] ?? item ) } onClick={ () => toggle( item ) }>
						<Icon name="close" className="size-3" />
					</button>
				</span>
			) ) }
			<Popover open={ open } onOpenChange={ setOpen }>
				<PopoverTrigger id={ id } className={ buttonVariants( { variant: 'outline', size: 'sm' } ) }>
					<Icon name="plus" className="size-4" />
					{ strings.add }
				</PopoverTrigger>
				<PopoverContent className="w-72 p-0" align="start">
					<Command shouldFilter={ false } label={ label }>
						<CommandInput placeholder={ strings.search } value={ search } onValueChange={ setSearch } />
						<CommandList>
							<CommandEmpty>{ busy ? strings.loading : strings.empty }</CommandEmpty>
							{ options.map( ( option ) => (
								<CommandItem key={ option.value } value={ option.value } onSelect={ () => toggle( option.value ) }>
									<Icon name={ value.includes( option.value ) ? 'check' : 'plus' } className="size-4" />
									{ option.label }
								</CommandItem>
							) ) }
						</CommandList>
					</Command>
				</PopoverContent>
			</Popover>
		</div>
	);
}
