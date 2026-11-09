/**
 * Dashboard (ADM-04): licence, versions and updates (ADM-10), site health
 * (ADM-05), store numbers and the changelog.
 */
import { Alert, Badge, BarChart, Button, Card, Icon, KeyValue, PageHeader, Progress, StatusList, Text, TextLink, Tile, notify } from '@ultra-max/ui/admin';
import { boot, digits, t } from '../boot';

const LICENSE_TONE = { active: 'success', development: 'info', inactive: 'warning', invalid: 'danger', 'not-available': 'neutral' } as const;

export function DashboardPage() {
	const data = boot.dashboard;
	if ( ! data ) {
		return null;
	}
	const { summary } = data;
	const total = summary.good + summary.warning + summary.critical;
	const healthy = total ? Math.round( ( summary.good / total ) * 100 ) : 100;
	const updates = data.products.filter( ( product ) => product.update );
	const plugins = data.products.filter( ( product ) => product.type === 'plugin' && product.active ).length;
	const status = ( data.license.status in LICENSE_TONE ? data.license.status : 'not-available' ) as keyof typeof LICENSE_TONE;

	const copyReport = async () => {
		try {
			await navigator.clipboard.writeText( data.report );
			notify.success( t( 'reportCopied' ) );
		} catch {
			notify.error( t( 'reportCopyFailed' ) );
		}
	};

	return (
		<div className="mx-auto grid max-w-310 gap-4">
			<PageHeader
				title={ t( 'dashboardTitle' ) }
				subtitle={ t( 'versionLine', digits( boot.brand.version ), digits( plugins ) ) }
				actions={
					<>
						<Button variant="outline" size="sm" href={ boot.urls.wizard }>
							<Icon name="sparkles" className="size-4" />
							{ t( 'wizardTitle' ) }
						</Button>
						<Button variant="outline" size="sm" href={ boot.urls.settings }>
							<Icon name="sliders" className="size-4" />
							{ t( 'settingsTitle' ) }
						</Button>
						<Button variant="soft" size="sm" href={ boot.urls.help }>
							<Icon name="help" className="size-4" />
							{ t( 'helpTitle' ) }
						</Button>
					</>
				}
			/>

			<div className="grid gap-4 xl:grid-cols-3">
				<Card title={ t( 'license' ) } icon="key" aside={ <Badge tone={ LICENSE_TONE[ status ] }>{ t( `license_${ status }` ) }</Badge> }>
					<KeyValue
						items={ [
							{ label: t( 'market' ), value: t( `market_${ data.license.market }` ) },
							{ label: t( 'site' ), value: window.location.host, ltr: true },
						] }
					/>
					<Text tone="faint" size="xs" className="mt-3">{ t( 'licenseNote' ) }</Text>
				</Card>

				<Card title={ t( 'updates' ) } icon="download" aside={ updates.length ? <Badge tone="info">{ t( 'updateAvailable' ) }</Badge> : <Badge tone="success">{ t( 'upToDate' ) }</Badge> }>
					<KeyValue items={ data.products.filter( ( product ) => product.installed ).map( ( product ) => ( { label: product.name, value: digits( product.update ? `${ product.version } → ${ product.update }` : product.version ) + ( product.active ? '' : ` (${ t( 'inactive' ) })` ) } ) ) } />
					{ data.mismatched.length ? (
						<Alert tone="warning" className="mt-3">{ t( 'mismatch', data.mismatched.join( '، ' ) ) }</Alert>
					) : (
						<Alert tone="success" icon="shield" className="mt-3">{ t( 'backupBeforeUpdate' ) }</Alert>
					) }
				</Card>

				<Card title={ t( 'health' ) } icon="activity" aside={ summary.warning + summary.critical ? <Badge tone={ summary.critical ? 'danger' : 'warning' }>{ t( 'warnings', digits( summary.warning + summary.critical ) ) }</Badge> : <Badge tone="success">{ t( 'allGood' ) }</Badge> }>
					<div className="mb-2 flex flex-wrap gap-4">
						<Badge tone="success" icon="check">{ t( 'checksGood', digits( summary.good ) ) }</Badge>
						<Badge tone="warning" icon="alert">{ t( 'checksWarning', digits( summary.warning ) ) }</Badge>
						<Badge tone="danger" icon="alert">{ t( 'checksCritical', digits( summary.critical ) ) }</Badge>
					</div>
					<Progress value={ healthy } tone="success" label={ t( 'health' ) } />
					<TextLink href="#um-status" className="mt-3" icon="chev-left">{ t( 'viewStatus' ) }</TextLink>
				</Card>
			</div>

			<div className="grid items-start gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
				<Card title={ t( 'storeAtGlance' ) } icon="store">
					<div className="mb-4 grid gap-3 md:grid-cols-3">
						<Tile label={ t( 'products' ) } value={ digits( data.store.products ) } />
						<Tile label={ t( 'ordersTwoWeeks' ) } value={ digits( data.store.orders ) } />
						<Tile label={ t( 'pendingComments' ) } value={ digits( data.store.pending ) } />
					</div>
					{ data.store.days.length ? (
						<BarChart
							label={ t( 'ordersChart' ) }
							tableLabel={ t( 'showTable' ) }
							bars={ data.store.days.map( ( day ) => ( { label: digits( day.label ), value: day.value, display: t( 'ordersOn', digits( day.value ), digits( day.label ) ) } ) ) }
						/>
					) : (
						<Text tone="faint">{ t( 'noWoo' ) }</Text>
					) }
				</Card>

				<Card title={ t( 'changelog' ) } icon="history" id="um-changelog">
					<ul className="m-0 grid list-none gap-3 p-0">
						{ data.changelog.map( ( entry, index ) => (
							<li key={ entry.version } className="grid gap-1">
								<div className="flex items-center gap-2">
									<Badge tone={ index === 0 ? 'info' : 'neutral' }>{ digits( entry.version ) }</Badge>
									{ entry.date ? <Text size="xs" tone="faint">{ entry.date }</Text> : null }
								</div>
								{ entry.items.map( ( item ) => <Text key={ item } size="xs" tone="muted">{ item }</Text> ) }
							</li>
						) ) }
					</ul>
				</Card>
			</div>

			<Card
				title={ t( 'systemStatus' ) }
				icon="server"
				id="um-status"
				aside={
					<Button variant="outline" size="sm" onClick={ copyReport }>
						<Icon name="copy" className="size-4" />
						{ t( 'copyReport' ) }
					</Button>
				}
			>
				<StatusList items={ data.checks } labels={ { good: t( 'statusGood' ), warning: t( 'statusWarning' ), critical: t( 'statusCritical' ) } } />
			</Card>
		</div>
	);
}
