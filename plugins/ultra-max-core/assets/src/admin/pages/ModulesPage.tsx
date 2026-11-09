/**
 * Modules (ADM-07): switch each module on or off from one page. A module
 * that is off loads no CSS or JS and runs no queries.
 */
import { useState } from 'react';
import { Alert, ModuleCard, PageHeader, Segmented, TextLink, notify } from '@ultra-max/ui/admin';
import { api, boot, digits, t, type Module } from '../boot';

export function ModulesPage() {
	const [ modules, setModules ] = useState<Module[]>( boot.modules ?? [] );
	const [ filter, setFilter ] = useState( 'all' );
	const [ busy, setBusy ] = useState<string | null>( null );

	const on = modules.filter( ( module ) => module.on );
	const kb = ( bytes: number ) => digits( ( bytes / 1024 ).toFixed( 1 ) );
	const total = on.reduce( ( sum, module ) => sum + module.bytes, 0 );

	const toggle = async ( module: Module, next: boolean ) => {
		setBusy( module.id );
		try {
			const result = await api<{ modules: Module[] }>( 'modules', 'POST', { id: module.id, on: next } );
			setModules( result.modules );
			notify.success( next ? t( 'moduleOn' ) : t( 'moduleOff' ) );
		} catch ( error ) {
			notify.error( error instanceof Error ? error.message : t( 'errorGeneric' ) );
		} finally {
			setBusy( null );
		}
	};

	const shown = modules.filter( ( module ) => ( filter === 'on' ? module.on : filter === 'off' ? ! module.on : true ) );

	return (
		<div className="mx-auto grid max-w-310 gap-4">
			<PageHeader
				title={ t( 'modulesTitle' ) }
				subtitle={ t( 'modulesSubtitle' ) }
				actions={
					<Segmented
						id="um-module-filter"
						label={ t( 'filter' ) }
						value={ filter }
						onChange={ setFilter }
						options={ [
							{ value: 'all', label: t( 'filterAll', digits( modules.length ) ) },
							{ value: 'on', label: t( 'filterOn', digits( on.length ) ) },
							{ value: 'off', label: t( 'filterOff', digits( modules.length - on.length ) ) },
						] }
					/>
				}
			/>
			<Alert icon="zap">{ t( 'modulesWeight', kb( total ) ) }</Alert>
			<div className="grid grid-cols-[repeat(auto-fill,minmax(280px,1fr))] gap-3">
				{ shown.map( ( module ) => (
					<ModuleCard
						key={ module.id }
						id={ module.id }
						title={ module.title }
						description={ module.description }
						icon={ module.icon }
						on={ module.on }
						busy={ busy === module.id }
						stateLabel={ module.on ? t( 'on' ) : t( 'off' ) }
						toggleLabel={ t( 'toggleModule', module.title ) }
						features={ module.features }
						weight={ module.bytes ? t( 'kb', kb( module.bytes ) ) : t( 'noAssets' ) }
						settings={ module.section ? <TextLink href={ `${ boot.urls.settings }#${ module.section }` }>{ t( 'settingsLink' ) }</TextLink> : undefined }
						onToggle={ ( next ) => toggle( module, next ) }
					/>
				) ) }
			</div>
		</div>
	);
}
