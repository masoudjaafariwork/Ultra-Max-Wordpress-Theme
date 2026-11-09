/**
 * Setup wizard (ADM-03): plugins, demo, logo and colour, done. Every step
 * can be skipped and finished later from the dashboard.
 */
import { useState } from 'react';
import { Alert, Badge, Button, Card, ColorField, DemoCard, Heading, Icon, LogoMark, MediaField, PresetPicker, SettingRow, Steps, Switch, Text, TextInput, TextLink, notify } from '@ultra-max/ui/admin';
import { api, boot, t, type WizardPlugin } from '../boot';

export function WizardPage() {
	const data = boot.wizard;
	const [ step, setStep ] = useState( 0 );
	const [ plugins, setPlugins ] = useState<WizardPlugin[]>( data?.plugins ?? [] );
	const [ wanted, setWanted ] = useState<Record<string, boolean>>( () => Object.fromEntries( ( data?.plugins ?? [] ).map( ( plugin ) => [ plugin.slug, true ] ) ) );
	const [ working, setWorking ] = useState<string | null>( null );
	const [ errors, setErrors ] = useState<Record<string, string>>( {} );
	const [ demo, setDemo ] = useState( data?.demos[ 0 ]?.id ?? '' );
	const [ name, setName ] = useState( data?.name ?? '' );
	const [ logo, setLogo ] = useState( data?.logo ?? 0 );
	const [ logoName, setLogoName ] = useState( '' );
	const [ preset, setPreset ] = useState( data?.preset ?? 'indigo' );
	const [ color, setColor ] = useState( data?.color ?? '#4c42d3' );

	if ( ! data ) {
		return null;
	}

	const steps = [
		{ label: t( 'wizWelcome' ), icon: 'sparkles' },
		{ label: t( 'wizPlugins' ), icon: 'puzzle' },
		{ label: t( 'wizDemo' ), icon: 'layers' },
		{ label: t( 'wizBrand' ), icon: 'palette' },
		{ label: t( 'wizDone' ), icon: 'check' },
	];

	const installPlugins = async () => {
		setErrors( {} );
		for ( const plugin of plugins ) {
			if ( plugin.status === 'active' || ! ( wanted[ plugin.slug ] || plugin.required ) ) {
				continue;
			}
			setWorking( plugin.slug );
			try {
				const result = await api<{ plugins: WizardPlugin[] }>( 'wizard/plugin', 'POST', { slug: plugin.slug } );
				setPlugins( result.plugins );
			} catch ( error ) {
				setErrors( ( current ) => ( { ...current, [ plugin.slug ]: error instanceof Error ? error.message : t( 'errorGeneric' ) } ) );
			}
		}
		setWorking( null );
		setStep( 2 );
	};

	const saveBrand = async () => {
		setWorking( 'brand' );
		try {
			await api( 'wizard/brand', 'POST', { name, logo, preset, color } );
			await api( 'wizard/finish', 'POST' );
			setStep( 4 );
		} catch ( error ) {
			notify.error( error instanceof Error ? error.message : t( 'errorGeneric' ) );
		} finally {
			setWorking( null );
		}
	};

	const next = () => {
		if ( step === 1 ) {
			installPlugins();
		} else if ( step === 3 ) {
			saveBrand();
		} else {
			setStep( step + 1 );
		}
	};

	const status = ( plugin: WizardPlugin ) => {
		if ( working === plugin.slug ) {
			return <Badge tone="info">{ t( 'wizInstalling' ) }</Badge>;
		}
		if ( plugin.status === 'active' ) {
			return <Badge tone="success" icon="check">{ t( 'wizReady' ) }</Badge>;
		}
		if ( plugin.required ) {
			return <Badge tone="warning">{ plugin.status === 'missing' ? t( 'wizWillInstall' ) : t( 'wizWillActivate' ) }</Badge>;
		}
		return <Switch id={ `um-wiz-${ plugin.slug }` } label={ t( 'wizInstallThis', plugin.name ) } hideLabel checked={ wanted[ plugin.slug ] ?? false } onCheckedChange={ ( checked ) => setWanted( { ...wanted, [ plugin.slug ]: checked } ) } />;
	};

	return (
		<div className="mx-auto grid max-w-245 gap-6 py-6">
			<div className="flex items-center justify-between">
				<span className="flex items-center gap-2"><LogoMark /><Heading size="md">{ boot.brand.name }</Heading></span>
				<TextLink href={ boot.urls.dashboard }>{ t( 'wizLater' ) }</TextLink>
			</div>
			<Card
				footer={
					step < 4 ? (
						<>
							{ step > 0 ? <Button variant="ghost" onClick={ () => setStep( step - 1 ) } disabled={ working !== null }>{ t( 'wizBack' ) }</Button> : null }
							<span className="flex-1" />
							{ step > 0 ? <Button variant="ghost" onClick={ () => setStep( step + 1 ) } disabled={ working !== null }>{ t( 'wizSkip' ) }</Button> : null }
							<Button onClick={ next } disabled={ working !== null }>
								{ working ? t( 'wizWorking' ) : [ t( 'wizStart' ), t( 'wizInstallContinue' ), t( 'wizContinue' ), t( 'wizSaveContinue' ) ][ step ] }
								<Icon name="chev-left" className="size-4" directional />
							</Button>
						</>
					) : undefined
				}
			>
				<div className="grid gap-8 md:p-3">
					<Steps label={ t( 'wizardTitle' ) } steps={ steps } current={ step } onSelect={ ( index ) => setStep( index ) } />
					<div className="grid min-h-90 content-start gap-4" aria-live="polite">
						{ step === 0 ? (
							<>
								<Heading>{ t( 'wizWelcomeTitle', boot.brand.name ) }</Heading>
								<Text tone="muted">{ t( 'wizWelcomeText' ) }</Text>
								<div className="grid gap-3">
									{ [ 'wizNoEncoding', 'wizElementorFree', 'wizAutoBackup' ].map( ( key ) => (
										<Alert key={ key } tone="success" title={ t( key ) }>{ t( `${ key }Text` ) }</Alert>
									) ) }
								</div>
							</>
						) : null }

						{ step === 1 ? (
							<>
								<Heading>{ t( 'wizPlugins' ) }</Heading>
								<Text tone="muted">{ t( 'wizPluginsText' ) }</Text>
								<div className="grid">
									{ plugins.map( ( plugin ) => (
										<SettingRow key={ plugin.slug } label={ plugin.name } help={ errors[ plugin.slug ] ?? plugin.description }>
											<div className="flex items-center gap-3">{ status( plugin ) }</div>
										</SettingRow>
									) ) }
								</div>
							</>
						) : null }

						{ step === 2 ? (
							<>
								<Heading>{ t( 'wizDemoTitle' ) }</Heading>
								<Text tone="muted">{ t( 'wizDemoText' ) }</Text>
								<Alert tone="info">{ t( 'wizDemoLater' ) }</Alert>
								<div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="radiogroup" aria-label={ t( 'wizDemoTitle' ) }>
									{ data.demos.map( ( item ) => (
										<DemoCard
											key={ item.id }
											name={ item.name }
											description={ item.description }
											image={ `${ boot.urls.demoImage }${ item.image }.svg` }
											colors={ item.colors }
											selected={ demo === item.id }
											onSelect={ () => setDemo( item.id ) }
										/>
									) ) }
								</div>
							</>
						) : null }

						{ step === 3 ? (
							<>
								<Heading>{ t( 'wizBrandTitle' ) }</Heading>
								<Text tone="muted">{ t( 'wizBrandText' ) }</Text>
								<div className="grid">
									<SettingRow htmlFor="um-wiz-name" label={ t( 'wizStoreName' ) }>
										<TextInput id="um-wiz-name" value={ name } onChange={ setName } />
									</SettingRow>
									<SettingRow htmlFor="um-wiz-logo" label={ t( 'wizLogo' ) } help={ t( 'wizLogoHelp' ) }>
										<MediaField
											id="um-wiz-logo"
											value={ logo }
											current={ logoName || undefined }
											accept="image"
											strings={ { choose: t( 'mediaChoose' ), replace: t( 'mediaReplace' ), remove: t( 'mediaRemove' ), title: t( 'wizLogo' ), none: t( 'mediaNone' ) } }
											onChange={ ( id, file ) => {
												setLogo( id );
												setLogoName( file?.filename ?? '' );
											} }
										/>
									</SettingRow>
									<SettingRow label={ t( 'wizColor' ) } group>
										<PresetPicker id="um-wiz-preset" label={ t( 'wizColor' ) } value={ preset } onChange={ setPreset } options={ data.presets } />
										{ preset === 'custom' ? (
											<ColorField id="um-wiz-color" label={ t( 'wizColor' ) } value={ color } onChange={ setColor } contrastCheck strings={ { hex: t( 'colorCode' ), good: t( 'contrastGood' ), poor: t( 'contrastPoor' ) } } />
										) : null }
									</SettingRow>
								</div>
							</>
						) : null }

						{ step === 4 ? (
							<div className="grid justify-items-center gap-4 py-6 text-center">
								<Badge tone="success" icon="check">{ t( 'wizDone' ) }</Badge>
								<Heading>{ t( 'wizDoneTitle' ) }</Heading>
								<Text tone="muted">{ t( 'wizDoneText' ) }</Text>
								<div className="flex flex-wrap justify-center gap-3">
									<Button href={ boot.urls.site }>{ t( 'wizViewSite' ) }</Button>
									<Button variant="outline" href={ boot.urls.dashboard }>{ t( 'wizToDashboard' ) }</Button>
									<Button variant="outline" href={ boot.urls.menus }>{ t( 'wizMenus' ) }</Button>
								</div>
							</div>
						) : null }
					</div>
				</div>
			</Card>
		</div>
	);
}
