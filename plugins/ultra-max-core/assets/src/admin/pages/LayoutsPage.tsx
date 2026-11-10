/**
 * Layouts (BLD-01 to BLD-03): every header, footer and reusable block, with
 * its display conditions. Designs are edited in Elementor; this page
 * creates them (blank or from a ready-made design) and decides where each
 * one shows.
 */
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Alert, Badge, Button, Card, Dialog, DeviceToggles, FieldGroup, Inset, ItemList, ItemRow, MultiSelect, Note, PageHeader, PickerCard, PickerGrid, Segmented, Select, Switch, Text, TextInput, notify, type MultiSelectOption } from '@ultra-max/ui/admin';
import { api, boot, digits, t, type Conditions, type Layout, type LayoutTarget, type LibraryTemplate, type Rule } from '../boot';

const data = boot.layouts ?? { types: [], items: [], targets: [], roles: [], templates: [], elementor: false };

/** Strings of the multiple choice lists. */
const pickStrings = {
	add: t( 'condChoose' ),
	search: t( 'condSearch' ),
	empty: t( 'condNoMatch' ),
	remove: t( 'condRemoveItem' ),
	loading: t( 'condLoading' ),
};

/**
 * Options of a rule target, from the server.
 *
 * @param target  Target id.
 * @param search  Search words.
 * @param include Only these values, to name saved ones.
 */
async function values( target: string, search: string, include: string[] = [] ): Promise<MultiSelectOption[]> {
	const query = new URLSearchParams( { target, search, include: include.join( ',' ) } );
	const result = await api<{ options: MultiSelectOption[] }>( `layouts/values?${ query.toString() }` );
	return result.options;
}

/**
 * A short sentence of where a layout shows.
 *
 * @param conditions Conditions.
 */
function summary( conditions: Conditions ): string {
	const name = ( rule: Rule ) => data.targets.find( ( target ) => target.id === rule.target )?.label ?? rule.target;
	if ( conditions.include.length === 0 ) {
		return t( 'layoutNowhere' );
	}
	let text = conditions.include.map( name ).join( '، ' );
	if ( conditions.exclude.length ) {
		text += ` ${ t( 'layoutExcept', conditions.exclude.map( name ).join( '، ' ) ) }`;
	}
	return text;
}

export function LayoutsPage() {
	const [ items, setItems ] = useState<Layout[]>( data.items );
	const [ creating, setCreating ] = useState<string | null>( null );
	const [ editing, setEditing ] = useState<Layout | null>( null );
	const [ busy, setBusy ] = useState<number | null>( null );

	const run = async ( id: number, action: () => Promise<{ layouts: Layout[] }>, message?: string ) => {
		setBusy( id );
		try {
			const result = await action();
			setItems( result.layouts );
			if ( message ) {
				notify.success( message );
			}
		} catch ( error ) {
			notify.error( error instanceof Error ? error.message : t( 'errorGeneric' ) );
		} finally {
			setBusy( null );
		}
	};

	return (
		<div className="mx-auto grid max-w-310 gap-4">
			<PageHeader title={ t( 'layoutsTitle' ) } subtitle={ t( 'layoutsSubtitle' ) } actions={ <Button onClick={ () => setCreating( data.types[ 0 ]?.id ?? 'header' ) }>{ t( 'layoutNew' ) }</Button> } />
			{ data.elementor ? null : <Alert tone="warning">{ t( 'layoutsNoElementor' ) }</Alert> }
			{ data.types.map( ( type ) => {
				const rows = items.filter( ( item ) => item.type === type.id );
				return (
					<Card key={ type.id } id={ `um-layouts-${ type.id }` } title={ type.plural } icon={ type.icon } aside={ <Button variant="outline" size="sm" onClick={ () => setCreating( type.id ) }>{ t( 'layoutNewOf', type.label ) }</Button> }>
						<Text tone="muted">{ type.description }</Text>
						{ rows.length === 0 ? <Text tone="faint">{ t( 'layoutEmpty' ) }</Text> : null }
						<ItemList label={ type.plural }>
							{ rows.map( ( layout ) => {
								const devices = ( [ 'desktop', 'tablet', 'mobile' ] as const ).filter( ( device ) => layout.conditions.devices[ device ] );
								return (
									<ItemRow
										key={ layout.id }
										id={ String( layout.id ) }
										title={ layout.title }
										meta={ type.id === 'block' ? `${ t( 'blockShortcode' ) }: [ultra_max_layout id="${ layout.id }"]` : summary( layout.conditions ) }
										start={
											<Switch
												id={ `um-layout-on-${ layout.id }` }
												label={ t( 'layoutToggle', layout.title ) }
												hideLabel
												checked={ layout.status === 'publish' }
												disabled={ busy === layout.id }
												onCheckedChange={ ( next: boolean ) => run( layout.id, () => api( `layouts/${ layout.id }`, 'POST', { active: next } ) ) }
											/>
										}
										badges={
											<>
												{ layout.status === 'publish' ? <Badge tone="success">{ t( 'layoutActive' ) }</Badge> : <Badge>{ t( 'layoutInactive' ) }</Badge> }
												{ devices.length < 3 ? devices.map( ( device ) => <Badge key={ device } tone="info">{ t( device ) }</Badge> ) : null }
												{ type.id !== 'block' && layout.conditions.priority !== 10 ? <Badge>{ t( 'layoutPriority', digits( layout.conditions.priority ) ) }</Badge> : null }
											</>
										}
										actions={
											<>
												<Button size="sm" href={ layout.editUrl }>{ t( 'layoutEdit' ) }</Button>
												{ type.id === 'block' ? null : <Button size="sm" variant="outline" onClick={ () => setEditing( layout ) }>{ t( 'layoutConditions' ) }</Button> }
												<Button size="sm" variant="ghost" disabled={ busy === layout.id } onClick={ () => run( layout.id, () => api( `layouts/${ layout.id }/duplicate`, 'POST' ), t( 'layoutDuplicated' ) ) }>{ t( 'layoutDuplicate' ) }</Button>
												<Button
													size="sm"
													variant="ghost"
													disabled={ busy === layout.id }
													onClick={ () => window.confirm( t( 'layoutDeleteConfirm', layout.title ) ) && run( layout.id, () => api( `layouts/${ layout.id }`, 'DELETE' ), t( 'layoutDeleted' ) ) }
												>
													{ t( 'layoutDelete' ) }
												</Button>
											</>
										}
									/>
								);
							} ) }
						</ItemList>
					</Card>
				);
			} ) }
			{ creating ? <CreateDialog type={ creating } onClose={ () => setCreating( null ) } /> : null }
			{ editing ? (
				<ConditionsDialog
					layout={ editing }
					sticky={ data.types.find( ( type ) => type.id === editing.type )?.sticky ?? false }
					onClose={ () => setEditing( null ) }
					onSaved={ ( layouts ) => {
						setItems( layouts );
						setEditing( null );
						notify.success( t( 'layoutSaved' ) );
					} }
				/>
			) : null }
		</div>
	);
}

/** Makes a layout, blank or from a ready-made design, then opens the editor. */
function CreateDialog( { type: initial, onClose }: { type: string; onClose: () => void } ) {
	const [ type, setType ] = useState( initial );
	const [ title, setTitle ] = useState( '' );
	const [ template, setTemplate ] = useState( '' );
	const [ busy, setBusy ] = useState( false );
	const templates = data.elementor ? data.templates.filter( ( item: LibraryTemplate ) => item.kind === type ) : [];

	const create = async () => {
		setBusy( true );
		try {
			const result = await api<{ layout: Layout | null }>( 'layouts', 'POST', { type, title, template } );
			notify.success( t( 'layoutCreated' ) );
			if ( result.layout ) {
				window.location.href = result.layout.editUrl;
			}
		} catch ( error ) {
			notify.error( error instanceof Error ? error.message : t( 'errorGeneric' ) );
			setBusy( false );
		}
	};

	return (
		<Dialog
			open
			onOpenChange={ ( open ) => ! open && onClose() }
			title={ t( 'layoutNew' ) }
			footer={
				<>
					<Button variant="ghost" onClick={ onClose }>{ t( 'cancel' ) }</Button>
					<Button onClick={ create } disabled={ busy }>{ t( 'layoutCreate' ) }</Button>
				</>
			}
		>
			<div className="grid max-h-[65vh] gap-4 overflow-y-auto">
				<Segmented
					id="um-layout-type"
					label={ t( 'layoutNew' ) }
					value={ type }
					onChange={ ( next ) => {
						setType( next );
						setTemplate( '' );
					} }
					options={ data.types.map( ( item ) => ( { value: item.id, label: item.label } ) ) }
				/>
				<FieldGroup title={ t( 'layoutName' ) } htmlFor="um-layout-title">
					<TextInput id="um-layout-title" value={ title } onChange={ setTitle } />
				</FieldGroup>
				<FieldGroup title={ t( 'layoutStart' ) }>
					<PickerGrid label={ t( 'layoutStart' ) }>
						{ [ { id: '', title: t( 'layoutBlank' ), description: t( 'layoutBlankHelp' ), preview: '' }, ...templates ].map( ( item ) => (
							<PickerCard key={ item.id } title={ item.title } description={ item.description } image={ item.preview } selected={ template === item.id } onSelect={ () => setTemplate( item.id ) } />
						) ) }
					</PickerGrid>
				</FieldGroup>
			</div>
		</Dialog>
	);
}

/** One list of rules: target and, where needed, the chosen items. */
function RuleList( { id, rules, onChange }: { id: string; rules: Rule[]; onChange: ( rules: Rule[] ) => void } ) {
	const targets = data.targets;
	const groups: Record<string, string> = { general: t( 'groupGeneral' ), blog: t( 'groupBlog' ), store: t( 'groupStore' ) };
	const options = targets.map( ( target: LayoutTarget ) => ( { value: target.id, label: `${ groups[ target.group ] ?? '' } › ${ target.label }` } ) );
	const update = ( index: number, rule: Rule ) => onChange( rules.map( ( item, i ) => ( i === index ? rule : item ) ) );
	return (
		<div className="grid gap-2">
			{ rules.map( ( rule, index ) => {
				const target = targets.find( ( item ) => item.id === rule.target );
				return (
					<Inset key={ index }>
						<div className="flex items-center gap-2">
							<Select id={ `${ id }-${ index }` } label={ t( 'condChoose' ) } value={ rule.target } options={ options } onChange={ ( next ) => update( index, { target: next } ) } />
							<Button variant="ghost" size="sm" className="ms-auto" onClick={ () => onChange( rules.filter( ( _, i ) => i !== index ) ) }>{ t( 'condRemove' ) }</Button>
						</div>
						{ target && target.values ? (
							<RuleValues id={ `${ id }-${ index }-values` } rule={ rule } label={ target.label } onChange={ ( list ) => update( index, { ...rule, values: list } ) } />
						) : null }
					</Inset>
				);
			} ) }
			<Button variant="outline" size="sm" className="w-fit" onClick={ () => onChange( [ ...rules, { target: 'all' } ] ) }>{ t( 'condAdd' ) }</Button>
		</div>
	);
}

/** The chosen items of one rule, named from the server. */
function RuleValues( { id, rule, label, onChange }: { id: string; rule: Rule; label: string; onChange: ( values: string[] ) => void } ) {
	const chosen = useMemo( () => ( rule.values ?? [] ).map( String ), [ rule.values ] );
	const [ known, setKnown ] = useState<MultiSelectOption[]>( [] );
	const load = useCallback( ( search: string ) => values( rule.target, search ), [ rule.target ] );
	const saved = chosen.join( ',' );
	useEffect( () => {
		if ( saved ) {
			values( rule.target, '', saved.split( ',' ) ).then( setKnown ).catch( () => undefined );
		}
		// Names are fetched once per rule target; MultiSelect keeps names of later picks.
	}, [ rule.target ] );
	return (
		<div className="grid gap-1">
			<MultiSelect id={ id } label={ label } value={ chosen } onChange={ onChange } load={ load } known={ known } strings={ pickStrings } />
			{ chosen.length === 0 ? <Note tone="warning">{ t( 'condNeedsValue' ) }</Note> : null }
		</div>
	);
}

/** Edits where a layout shows (BLD-02). */
function ConditionsDialog( { layout, sticky, onClose, onSaved }: { layout: Layout; sticky: boolean; onClose: () => void; onSaved: ( layouts: Layout[] ) => void } ) {
	const [ conditions, setConditions ] = useState<Conditions>( layout.conditions );
	const [ busy, setBusy ] = useState( false );
	const set = <K extends keyof Conditions>( key: K, value: Conditions[ K ] ) => setConditions( ( current ) => ( { ...current, [ key ]: value } ) );
	const roleOptions = data.roles;
	const loadRoles = useCallback( async ( search: string ) => roleOptions.filter( ( role ) => role.label.toLowerCase().includes( search.toLowerCase() ) ), [ roleOptions ] );

	const save = async () => {
		setBusy( true );
		try {
			const result = await api<{ layouts: Layout[] }>( `layouts/${ layout.id }`, 'POST', { conditions } );
			onSaved( result.layouts );
		} catch ( error ) {
			notify.error( error instanceof Error ? error.message : t( 'errorGeneric' ) );
			setBusy( false );
		}
	};

	return (
		<Dialog
			open
			onOpenChange={ ( open ) => ! open && onClose() }
			title={ `${ t( 'layoutConditions' ) }: ${ layout.title }` }
			footer={
				<>
					<Button variant="ghost" onClick={ onClose }>{ t( 'cancel' ) }</Button>
					<Button onClick={ save } disabled={ busy }>{ t( 'condSave' ) }</Button>
				</>
			}
		>
			<div className="grid max-h-[65vh] gap-5 overflow-y-auto pe-1">
				<FieldGroup title={ t( 'condInclude' ) } help={ t( 'condIncludeHelp' ) }>
					<RuleList id="um-cond-include" rules={ conditions.include } onChange={ ( rules ) => set( 'include', rules ) } />
				</FieldGroup>
				<FieldGroup title={ t( 'condExclude' ) } help={ t( 'condExcludeHelp' ) }>
					<RuleList id="um-cond-exclude" rules={ conditions.exclude } onChange={ ( rules ) => set( 'exclude', rules ) } />
				</FieldGroup>
				<FieldGroup title={ t( 'condRoles' ) } help={ t( 'condRolesHelp' ) }>
					<MultiSelect id="um-cond-roles" label={ t( 'condRoles' ) } value={ conditions.roles } onChange={ ( roles ) => set( 'roles', roles ) } load={ loadRoles } known={ roleOptions } strings={ pickStrings } />
				</FieldGroup>
				<FieldGroup title={ t( 'condDevices' ) } help={ t( 'condDevicesHelp' ) }>
					<DeviceToggles id="um-cond-devices" value={ conditions.devices } onChange={ ( devices ) => set( 'devices', devices ) } labels={ { desktop: t( 'desktop' ), tablet: t( 'tablet' ), mobile: t( 'mobile' ) } } />
				</FieldGroup>
				<FieldGroup title={ t( 'condPriority' ) } help={ t( 'condPriorityHelp' ) } htmlFor="um-cond-priority">
					<TextInput id="um-cond-priority" value={ String( conditions.priority ) } onChange={ ( next ) => set( 'priority', Math.max( 0, Math.min( 100, Number( next ) || 0 ) ) ) } />
				</FieldGroup>
				{ sticky ? (
					<FieldGroup title={ t( 'condSticky' ) }>
						<Segmented
							id="um-cond-sticky"
							label={ t( 'condSticky' ) }
							value={ conditions.sticky }
							onChange={ ( next ) => set( 'sticky', next === 'sticky' || next === 'smart' ? next : 'none' ) }
							options={ [
								{ value: 'smart', label: t( 'stickySmart' ) },
								{ value: 'sticky', label: t( 'stickySticky' ) },
								{ value: 'none', label: t( 'stickyNone' ) },
							] }
						/>
					</FieldGroup>
				) : null }
			</div>
		</Dialog>
	);
}
