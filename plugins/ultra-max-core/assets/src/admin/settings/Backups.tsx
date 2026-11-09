/**
 * Export, import, reset and backups (ADM-02).
 */
import { useRef, useState } from 'react';
import { Alert, Button, DataTable, Dialog, Heading, Icon, Text, notify } from '@ultra-max/ui/admin';
import { api, digits, jalali, t, type Backup, type Values } from '../boot';

export interface BackupsProps {
	backups: Backup[];
	onBackups: ( backups: Backup[] ) => void;
	onValues: ( values: Values ) => void;
}

export function Backups( { backups, onBackups, onValues }: BackupsProps ) {
	const file = useRef<HTMLInputElement>( null );
	const [ confirm, setConfirm ] = useState<null | { kind: 'reset' } | { kind: 'restore'; id: string }>( null );
	const [ busy, setBusy ] = useState( false );

	const run = async ( task: () => Promise<void> ) => {
		setBusy( true );
		try {
			await task();
		} catch ( error ) {
			notify.error( error instanceof Error ? error.message : t( 'errorGeneric' ) );
		} finally {
			setBusy( false );
		}
	};

	const exportFile = () =>
		run( async () => {
			const document = await api<unknown>( 'settings/export' );
			const blob = new Blob( [ JSON.stringify( document, null, 2 ) ], { type: 'application/json' } );
			const link = window.document.createElement( 'a' );
			link.href = URL.createObjectURL( blob );
			link.download = `ultra-max-settings-${ new Date().toISOString().slice( 0, 10 ) }.json`;
			link.click();
			URL.revokeObjectURL( link.href );
			notify.success( t( 'exported' ) );
		} );

	const importFile = ( input: HTMLInputElement ) =>
		run( async () => {
			const chosen = input.files?.[ 0 ];
			input.value = '';
			if ( ! chosen ) {
				return;
			}
			let document: unknown;
			try {
				document = JSON.parse( await chosen.text() );
			} catch {
				throw new Error( t( 'importInvalid' ) );
			}
			const result = await api<{ values: Values; rejected: string[] }>( 'settings/import', 'POST', { document } );
			onValues( result.values );
			onBackups( ( await api<{ backups: Backup[] }>( 'settings/backups' ) ).backups );
			notify.success( result.rejected.length ? t( 'importedSome', digits( result.rejected.length ) ) : t( 'imported' ) );
		} );

	const backupNow = () =>
		run( async () => {
			onBackups( ( await api<{ backups: Backup[] }>( 'settings/backups', 'POST' ) ).backups );
			notify.success( t( 'backupTaken' ) );
		} );

	const confirmed = () =>
		run( async () => {
			if ( confirm?.kind === 'reset' ) {
				onValues( ( await api<{ values: Values }>( 'settings/reset', 'POST', {} ) ).values );
				onBackups( ( await api<{ backups: Backup[] }>( 'settings/backups' ) ).backups );
				notify.success( t( 'resetDone' ) );
			} else if ( confirm?.kind === 'restore' ) {
				const result = await api<{ values: Values; backups: Backup[] }>( 'settings/backups/restore', 'POST', { id: confirm.id } );
				onValues( result.values );
				onBackups( result.backups );
				notify.success( t( 'restored' ) );
			}
			setConfirm( null );
		} );

	return (
		<div className="grid gap-5">
			<div className="grid gap-3 md:grid-cols-3">
				<div className="grid content-start gap-2">
					<Heading level={ 3 } size="sm">{ t( 'exportTitle' ) }</Heading>
					<Text tone="faint" size="xs">{ t( 'exportHelp' ) }</Text>
					<Button variant="outline" size="sm" className="w-fit" onClick={ exportFile } disabled={ busy }>
						<Icon name="download" className="size-4" />
						{ t( 'export' ) }
					</Button>
				</div>
				<div className="grid content-start gap-2">
					<Heading level={ 3 } size="sm">{ t( 'importTitle' ) }</Heading>
					<Text tone="faint" size="xs">{ t( 'importHelp' ) }</Text>
					<input ref={ file } type="file" accept="application/json,.json" className="sr-only" id="um-import-file" onChange={ ( event ) => importFile( event.currentTarget ) } />
					<Button variant="outline" size="sm" className="w-fit" onClick={ () => file.current?.click() } disabled={ busy }>
						<Icon name="upload" className="size-4" />
						{ t( 'import' ) }
					</Button>
				</div>
				<div className="grid content-start gap-2">
					<Heading level={ 3 } size="sm">{ t( 'resetAllTitle' ) }</Heading>
					<Text tone="faint" size="xs">{ t( 'resetAllHelp' ) }</Text>
					<Button variant="danger" size="sm" className="w-fit" onClick={ () => setConfirm( { kind: 'reset' } ) } disabled={ busy }>
						{ t( 'resetAll' ) }
					</Button>
				</div>
			</div>
			<Alert tone="success" icon="shield">{ t( 'backupAuto' ) }</Alert>
			<div className="flex flex-wrap items-center justify-between gap-3">
				<Heading level={ 3 } size="sm">{ t( 'backupsTitle' ) }</Heading>
				<Button variant="soft" size="sm" onClick={ backupNow } disabled={ busy }>{ t( 'backupNow' ) }</Button>
			</div>
			<DataTable
				caption={ t( 'backupsTitle' ) }
				empty={ t( 'backupsEmpty' ) }
				columns={ [
					{ key: 'label', label: t( 'backupWhy' ) },
					{ key: 'date', label: t( 'backupWhen' ) },
					{ key: 'count', label: t( 'backupCount' ) },
					{ key: 'action', label: t( 'backupAction' ), align: 'end' },
				] }
				rows={ backups.map( ( backup ) => ( {
					id: backup.id,
					label: backup.label,
					date: jalali( backup.created ),
					count: digits( backup.count ),
					action: (
						<Button variant="ghost" size="sm" onClick={ () => setConfirm( { kind: 'restore', id: backup.id } ) } disabled={ busy }>
							{ t( 'restore' ) }
						</Button>
					),
				} ) ) }
			/>
			<Dialog
				open={ confirm !== null }
				onOpenChange={ ( open ) => ! open && setConfirm( null ) }
				title={ confirm?.kind === 'reset' ? t( 'resetAllConfirm' ) : t( 'restoreConfirm' ) }
				description={ t( 'confirmBackupNote' ) }
				footer={
					<>
						<Button variant="ghost" onClick={ () => setConfirm( null ) }>{ t( 'cancel' ) }</Button>
						<Button variant={ confirm?.kind === 'reset' ? 'danger' : 'primary' } onClick={ confirmed } disabled={ busy }>
							{ confirm?.kind === 'reset' ? t( 'resetAll' ) : t( 'restore' ) }
						</Button>
					</>
				}
			/>
		</div>
	);
}
