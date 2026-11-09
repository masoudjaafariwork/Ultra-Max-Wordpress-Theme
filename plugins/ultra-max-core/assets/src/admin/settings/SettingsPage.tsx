/**
 * Settings page (ADM-01): sections, search over every setting, live
 * preview, save bar with section reset, and backups (ADM-02).
 */
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Alert, Badge, Button, Card, Dialog, PageHeader, SaveBar, SearchBox, SettingRow, SideNav, Text, Tour, notify, type SearchResult } from '@ultra-max/ui/admin';
import { api, boot, digits, t, type Backup, type Field, type Section, type Values } from '../boot';
import { FieldControl, looksLikeJalali } from './FieldControl';
import { Preview } from './Preview';
import { Backups } from './Backups';

/**
 * Lowercases and unifies Arabic and Persian letters for search.
 *
 * @param text Text.
 */
export function normalize( text: string ): string {
	return text.toLowerCase().replace( /ي/g, 'ی' ).replace( /ك/g, 'ک' ).replace( /‌/g, ' ' ).replace( /\s+/g, ' ' ).trim();
}

/**
 * Whether a field is shown for the current values.
 *
 * @param field  Field.
 * @param values Draft values.
 */
function visible( field: Field, values: Values ): boolean {
	if ( ! field.show_if ) {
		return true;
	}
	const current = values[ field.show_if.field ];
	const expected = field.show_if.equals;
	return Array.isArray( expected ) ? expected.includes( current ) : current === expected;
}

const same = ( a: unknown, b: unknown ): boolean => JSON.stringify( a ) === JSON.stringify( b );

export function SettingsPage() {
	const data = boot.settings;
	const sections: Section[] = data?.sections ?? [];
	const fields = useMemo( () => {
		const all: Record<string, Field> = {};
		sections.forEach( ( section ) => section.groups.forEach( ( group ) => group.fields.forEach( ( field ) => ( all[ field.id ] = field ) ) ) );
		return all;
	}, [ sections ] );
	const sectionOf = useMemo( () => {
		const map: Record<string, string> = {};
		sections.forEach( ( section ) => section.groups.forEach( ( group ) => group.fields.forEach( ( field ) => ( map[ field.id ] = section.id ) ) ) );
		return map;
	}, [ sections ] );

	const initialSection = () => {
		const hash = window.location.hash.slice( 1 );
		return sections.some( ( section ) => section.id === hash ) ? hash : sections[ 0 ]?.id ?? '';
	};
	const [ current, setCurrent ] = useState( initialSection );
	const [ values, setValues ] = useState<Values>( data?.values ?? {} );
	const [ saved, setSaved ] = useState<Values>( data?.values ?? {} );
	const [ backups, setBackups ] = useState<Backup[]>( data?.backups ?? [] );
	const [ modules, setModules ] = useState<Record<string, boolean>>( data?.modules ?? {} );
	const [ files, setFiles ] = useState<Record<string, string>>( data?.files ?? {} );
	const [ highlight, setHighlight ] = useState<string | null>( null );
	const [ saving, setSaving ] = useState( false );
	const [ confirmReset, setConfirmReset ] = useState( false );
	const [ tour, setTour ] = useState( boot.tour );

	const dirty = Object.keys( values ).filter( ( key ) => fields[ key ] && ! same( values[ key ], saved[ key ] ) );
	const invalid = dirty.filter( ( key ) => fields[ key ]?.type === 'jdate' && ! looksLikeJalali( String( values[ key ] ?? '' ) ) );

	useEffect( () => {
		const warn = ( event: BeforeUnloadEvent ) => {
			if ( dirty.length ) {
				event.preventDefault();
			}
		};
		window.addEventListener( 'beforeunload', warn );
		return () => window.removeEventListener( 'beforeunload', warn );
	}, [ dirty.length ] );

	const go = useCallback( ( id: string ) => {
		setCurrent( id );
		window.history.replaceState( null, '', `#${ id }` );
		window.scrollTo( { top: 0 } );
	}, [] );

	const index = useMemo(
		() =>
			sections.flatMap( ( section ) =>
				section.groups.flatMap( ( group ) =>
					group.fields
						.filter( ( field ) => field.type !== 'preview' )
						.map( ( field ) => ( {
							id: field.id,
							label: field.label,
							context: `${ section.title } › ${ group.title }`,
							text: normalize( [ field.label, field.help, field.keywords, group.title, section.title ].join( ' ' ) ),
						} ) )
				)
			),
		[ sections ]
	);

	const search = ( query: string ): SearchResult[] => {
		const words = normalize( query ).split( ' ' );
		return index.filter( ( item ) => words.every( ( word ) => item.text.includes( word ) ) ).slice( 0, 8 );
	};

	const pick = ( id: string ) => {
		const section = sectionOf[ id ];
		if ( section ) {
			setCurrent( section );
			window.history.replaceState( null, '', `#${ section }` );
		}
		setHighlight( id );
		window.setTimeout( () => {
			const row = document.getElementById( `um-row-${ id }` );
			row?.scrollIntoView( { block: 'center' } );
			( row?.querySelector<HTMLElement>( 'input, button, textarea, [role="combobox"], [role="switch"]' ) )?.focus( { preventScroll: true } );
		}, 60 );
		window.setTimeout( () => setHighlight( null ), 2400 );
	};

	const save = async () => {
		if ( invalid.length ) {
			notify.error( t( 'fixErrors' ) );
			return;
		}
		setSaving( true );
		try {
			const changes = Object.fromEntries( dirty.map( ( key ) => [ key, values[ key ] ] ) );
			const result = await api<{ values: Values; rejected: string[] }>( 'settings', 'POST', { values: changes } );
			setValues( result.values );
			setSaved( result.values );
			if ( result.rejected.length ) {
				notify.error( t( 'savedSome', result.rejected.map( ( key ) => fields[ key ]?.label ?? key ).join( '، ' ) ) );
			} else {
				notify.success( t( 'saved' ) );
			}
		} catch ( error ) {
			notify.error( error instanceof Error ? error.message : t( 'errorGeneric' ) );
		} finally {
			setSaving( false );
		}
	};

	const resetSection = async () => {
		setSaving( true );
		try {
			const result = await api<{ values: Values }>( 'settings/reset', 'POST', { section: current } );
			setValues( result.values );
			setSaved( result.values );
			setBackups( ( await api<{ backups: Backup[] }>( 'settings/backups' ) ).backups );
			notify.success( t( 'sectionReset' ) );
		} catch ( error ) {
			notify.error( error instanceof Error ? error.message : t( 'errorGeneric' ) );
		} finally {
			setSaving( false );
			setConfirmReset( false );
		}
	};

	const turnOn = async ( id: string ) => {
		try {
			await api( 'modules', 'POST', { id, on: true } );
			setModules( { ...modules, [ id ]: true } );
			notify.success( t( 'moduleOn' ) );
		} catch ( error ) {
			notify.error( error instanceof Error ? error.message : t( 'errorGeneric' ) );
		}
	};

	const section = sections.find( ( item ) => item.id === current ) ?? sections[ 0 ];
	const separated = new Set( [ 'notice', 'performance' ] );

	return (
		<div className="mx-auto grid max-w-310 gap-4">
			<PageHeader
				title={ t( 'settingsTitle' ) }
				actions={
					<Button variant="outline" size="sm" href="#backup" onClick={ () => go( 'backup' ) }>
						{ t( 'backupAndReset' ) }
					</Button>
				}
			>
				<div className="contents" data-tour="search">
					<SearchBox label={ t( 'searchLabel' ) } placeholder={ t( 'searchPlaceholder' ) } empty={ t( 'searchEmpty' ) } search={ search } onPick={ pick } />
				</div>
			</PageHeader>

			<div className="grid items-start gap-4 xl:grid-cols-[230px_minmax(0,1fr)]">
				<div data-tour="nav" className="min-w-0 xl:sticky xl:top-12">
					<SideNav
						label={ t( 'sections' ) }
						current={ section?.id ?? '' }
						onSelect={ go }
						items={ sections.map( ( item ) => ( { id: item.id, label: item.title, icon: item.icon, separated: separated.has( item.id ) } ) ) }
					/>
				</div>

				<div className="grid min-w-0 gap-4">
					{ section?.groups.map( ( group, groupIndex ) => {
						const off = group.module ? modules[ group.module ] === false : false;
						return (
							<Card
								key={ group.id }
								id={ `um-group-${ group.id }` }
								title={ group.title }
								icon={ group.icon }
								aside={ group.features?.length ? <span dir="ltr"><Badge>{ group.features.join( ' · ' ) }</Badge></span> : undefined }
							>
								<div data-tour={ groupIndex === 0 ? 'group' : undefined } className="grid">
									{ group.description ? <Text tone="faint" size="xs" className="mb-4">{ group.description }</Text> : null }
									{ off && group.module ? (
										<Alert
											tone="warning"
											className="mb-5"
											action={ data?.canModules ? <Button size="sm" variant="outline" onClick={ () => group.module && turnOn( group.module ) }>{ t( 'turnOn' ) }</Button> : undefined }
										>
											{ t( 'moduleOffNote' ) }
										</Alert>
									) : null }
									{ group.fields.filter( ( field ) => visible( field, values ) ).map( ( field ) => {
										if ( field.type === 'backups' ) {
											return <Backups key={ field.id } backups={ backups } onBackups={ setBackups } onValues={ ( next ) => {
												setValues( next );
												setSaved( next );
											} } />;
										}
										if ( field.type === 'preview' ) {
											return (
												<SettingRow key={ field.id } id={ `um-row-${ field.id }` } label={ field.label } help={ field.help }>
													<Preview values={ values } fields={ fields } />
												</SettingRow>
											);
										}
										const helpId = field.help ? `um-row-${ field.id }-help` : undefined;
										const group_ = [ 'devices', 'repeater', 'preset', 'choice', 'segmented' ].includes( field.type );
										return (
											<SettingRow
												key={ field.id }
												id={ `um-row-${ field.id }` }
												htmlFor={ `um-field-${ field.id }` }
												label={ field.label }
												help={ field.help }
												token={ field.token }
												highlighted={ highlight === field.id }
												group={ group_ }
											>
												<FieldControl
													field={ field }
													value={ values[ field.id ] }
													describedBy={ helpId }
													fileName={ files[ field.id ] }
													onFile={ ( name ) => setFiles( { ...files, [ field.id ]: name } ) }
													onChange={ ( next ) => setValues( ( draft ) => ( { ...draft, [ field.id ]: next } ) ) }
												/>
											</SettingRow>
										);
									} ) }
								</div>
							</Card>
						);
					} ) }

					{ section?.id !== 'backup' ? (
						<div data-tour="save">
							<SaveBar dirty={ dirty.length > 0 } message={ dirty.length ? t( 'unsaved', digits( dirty.length ) ) : t( 'allSaved' ) }>
								<Button variant="ghost" size="sm" onClick={ () => setConfirmReset( true ) } disabled={ saving }>
									{ t( 'resetSection' ) }
								</Button>
								{ dirty.length ? (
									<Button variant="outline" size="sm" onClick={ () => setValues( saved ) } disabled={ saving }>
										{ t( 'discard' ) }
									</Button>
								) : null }
								<Button onClick={ save } disabled={ saving || dirty.length === 0 }>
									{ saving ? t( 'saving' ) : t( 'save' ) }
								</Button>
							</SaveBar>
						</div>
					) : null }
				</div>
			</div>

			<Dialog
				open={ confirmReset }
				onOpenChange={ setConfirmReset }
				title={ t( 'resetSectionConfirm', section?.title ?? '' ) }
				description={ t( 'confirmBackupNote' ) }
				footer={
					<>
						<Button variant="ghost" onClick={ () => setConfirmReset( false ) }>{ t( 'cancel' ) }</Button>
						<Button variant="danger" onClick={ resetSection } disabled={ saving }>{ t( 'resetSection' ) }</Button>
					</>
				}
			/>

			{ tour ? (
				<Tour
					steps={ [
						{ target: '[data-tour="nav"]', title: t( 'tourNavTitle' ), text: t( 'tourNavText' ) },
						{ target: '[data-tour="search"] input', title: t( 'tourSearchTitle' ), text: t( 'tourSearchText' ) },
						{ target: '[data-tour="group"]', title: t( 'tourGroupTitle' ), text: t( 'tourGroupText' ) },
						{ target: '[data-tour="save"]', title: t( 'tourSaveTitle' ), text: t( 'tourSaveText' ) },
					] }
					strings={ { next: t( 'tourNext' ), back: t( 'tourBack' ), done: t( 'tourDone' ), skip: t( 'tourSkip' ), progress: t( 'tourProgress' ) } }
					onFinish={ () => {
						setTour( false );
						api( 'tour', 'POST', { page: 'settings' } ).catch( () => undefined );
					} }
				/>
			) : null }
		</div>
	);
}
