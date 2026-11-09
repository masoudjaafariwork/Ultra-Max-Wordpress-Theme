/**
 * Live preview of colours, corners, shadows and fonts before saving.
 *
 * Components read semantic variables such as --um-primary, which the
 * root resolves once; the preview sets those variables on its own frame
 * so only the preview changes.
 */
import { useEffect, type CSSProperties } from 'react';
import { Badge, Button, Card, PreviewFrame, Progress, Segmented, Switch, Text, mix, ramp, textOn } from '@ultra-max/ui/admin';
import { boot, digits, t, type Field, type Values } from '../boot';

/**
 * The CSS variables of the draft values.
 *
 * @param values Draft values.
 * @param fields Every field, for the preset ramps.
 */
export function previewVars( values: Values, fields: Record<string, Field> ): CSSProperties {
	const vars: Record<string, string> = {};
	const preset = String( values.color_preset ?? 'indigo' );
	const option = fields.color_preset?.options?.find( ( item ) => item.value === preset );
	const brand = preset === 'custom' ? ramp( String( values.color_primary ?? '#4c42d3' ) ) : option?.ramp;
	if ( brand ) {
		Object.entries( brand ).forEach( ( [ step, color ] ) => {
			vars[ `--um-brand-${ step }` ] = color;
		} );
		const main = brand[ 600 ] ?? '#4c42d3';
		vars[ '--um-primary' ] = main;
		vars[ '--um-primary-hover' ] = brand[ 700 ] ?? main;
		vars[ '--um-primary-soft' ] = brand[ 50 ] ?? main;
		vars[ '--um-primary-soft-2' ] = brand[ 100 ] ?? main;
		vars[ '--um-on-primary' ] = textOn( main );
	}
	const accent = String( values.color_accent ?? '#ff9a1f' );
	if ( /^#[0-9a-f]{6}$/i.test( accent ) ) {
		vars[ '--um-accent-500' ] = accent;
		vars[ '--um-accent-100' ] = mix( accent, 24, '#ffffff' );
	}
	const radius = Number( values.radius ?? 14 );
	vars[ '--um-radius-xs' ] = `${ Math.round( radius * 0.29 ) }px`;
	vars[ '--um-radius-sm' ] = `${ Math.round( radius * 0.43 ) }px`;
	vars[ '--um-radius-md' ] = `${ Math.round( radius * 0.7 ) }px`;
	vars[ '--um-radius-lg' ] = `${ radius }px`;
	vars[ '--um-radius-xl' ] = `${ Math.round( radius * 1.43 ) }px`;
	if ( values.shadow === 'none' ) {
		vars[ '--um-shadow-md' ] = 'none';
		vars[ '--um-shadow-lg' ] = 'none';
	}
	const fonts = boot.settings?.fonts ?? {};
	const body = String( values.font_body ?? 'vazirmatn' );
	const heading = String( values.font_heading ?? 'body' );
	const family = ( key: string ) => ( fonts[ key ]?.family ?? '"Vazirmatn"' ) + ', Tahoma, sans-serif';
	vars[ '--um-font-body' ] = family( body );
	vars.fontFamily = family( body );
	vars[ '--um-font-heading' ] = family( heading === 'body' ? body : heading );
	return vars as CSSProperties;
}

/**
 * Declares the fonts the preview may draw, once.
 */
function useFontFaces() {
	useEffect( () => {
		const fonts = boot.settings?.fonts ?? {};
		const css = Object.values( fonts )
			.filter( ( font ) => font.url )
			.map( ( font ) => `@font-face{font-family:${ font.family };src:url("${ font.url }") format("woff2");font-weight:100 900;font-display:swap}` )
			.join( '' );
		if ( css && ! document.getElementById( 'um-preview-fonts' ) ) {
			const style = document.createElement( 'style' );
			style.id = 'um-preview-fonts';
			style.textContent = css;
			document.head.appendChild( style );
		}
	}, [] );
}

export function Preview( { values, fields }: { values: Values; fields: Record<string, Field> } ) {
	useFontFaces();
	return (
		<PreviewFrame label={ t( 'preview' ) } style={ previewVars( values, fields ) }>
			<div className="grid gap-3 md:grid-cols-[220px_minmax(0,1fr)]">
				<Card title={ t( 'previewCardTitle' ) } icon="box">
					<Text tone="muted">{ t( 'previewCardText' ) }</Text>
				</Card>
				<div className="flex flex-col gap-3">
					<div className="flex flex-wrap gap-2">
						<Button>{ t( 'previewAdd' ) }</Button>
						<Button variant="outline">{ t( 'previewAll' ) }</Button>
					</div>
					<div className="flex flex-wrap items-center gap-3">
						<Badge tone="brand">{ digits( '۱۶٪' ) }</Badge>
						<Segmented id="um-preview-sort" label={ t( 'previewSort' ) } value="a" onChange={ () => undefined } options={ [ { value: 'a', label: t( 'previewRelevant' ) }, { value: 'b', label: t( 'previewNewest' ) } ] } />
						<Switch id="um-preview-switch" label={ t( 'previewSwitch' ) } hideLabel defaultChecked />
					</div>
					<Progress value={ 64 } label={ t( 'preview' ) } />
				</div>
			</div>
		</PreviewFrame>
	);
}
