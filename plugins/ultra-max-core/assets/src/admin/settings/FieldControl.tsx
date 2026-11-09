/**
 * Renders one setting by its type, with the admin components.
 */
import {
	CodeField,
	ColorField,
	ChoiceCards,
	DeviceToggles,
	FieldLabel,
	MediaField,
	PresetPicker,
	RangeField,
	Repeater,
	Segmented,
	Select,
	Switch,
	TextInput,
} from '@ultra-max/ui/admin';
import { boot, digits, t, type Field } from '../boot';

type Devices = { desktop: boolean; tablet: boolean; mobile: boolean };

export interface FieldControlProps {
	field: Field;
	value: unknown;
	onChange: ( value: unknown ) => void;
	/** Element id of the help text. */
	describedBy?: string;
	/** Hides the visible label of a toggle; the row shows it. */
	inRow?: boolean;
	/** Remembers the name of a picked file. */
	onFile?: ( name: string ) => void;
	fileName?: string;
}

const asString = ( value: unknown ): string => ( typeof value === 'string' || typeof value === 'number' ? String( value ) : '' );
const asNumber = ( value: unknown, fallback: number ): number => ( typeof value === 'number' ? value : Number( value ) || fallback );

/**
 * Whether a Jalali date text is shaped like 1405/08/18.
 *
 * @param text Text.
 */
export function looksLikeJalali( text: string ): boolean {
	const latin = text.replace( /[۰-۹]/g, ( digit ) => String( '۰۱۲۳۴۵۶۷۸۹'.indexOf( digit ) ) );
	return text.trim() === '' || /^1[34]\d\d[/\-.](0?[1-9]|1[0-2])[/\-.](0?[1-9]|[12]\d|3[01])$/.test( latin.trim() );
}

export function FieldControl( { field, value, onChange, describedBy, inRow = true, onFile, fileName }: FieldControlProps ) {
	const id = `um-field-${ field.id }`;
	const common = { id, label: field.label, describedBy };

	switch ( field.type ) {
		case 'toggle':
			return <Switch id={ id } label={ field.label } hideLabel={ inRow } describedBy={ describedBy } checked={ value === true } onCheckedChange={ ( checked ) => onChange( checked ) } />;
		case 'select':
			return <Select { ...common } value={ asString( value ) } options={ field.options ?? [] } onChange={ onChange } />;
		case 'segmented':
			return <Segmented { ...common } value={ asString( value ) } options={ field.options ?? [] } onChange={ onChange } />;
		case 'choice':
			return <ChoiceCards { ...common } value={ asString( value ) } options={ field.options ?? [] } onChange={ onChange } />;
		case 'preset':
			return <PresetPicker { ...common } value={ asString( value ) } options={ field.options ?? [] } onChange={ onChange } />;
		case 'color':
			return (
				<ColorField
					{ ...common }
					value={ asString( value ) }
					onChange={ onChange }
					contrastCheck={ field.id === 'color_primary' }
					strings={ { hex: t( 'colorCode' ), good: t( 'contrastGood' ), poor: t( 'contrastPoor' ) } }
				/>
			);
		case 'range':
		case 'number':
			return (
				<RangeField
					{ ...common }
					value={ asNumber( value, field.min ?? 0 ) }
					min={ field.min ?? 0 }
					max={ field.max ?? 100 }
					step={ field.step ?? 1 }
					unit={ field.unit ?? '' }
					format={ digits }
					onChange={ onChange }
				/>
			);
		case 'text':
		case 'url':
			return <TextInput { ...common } type={ field.type === 'url' ? 'url' : 'text' } value={ asString( value ) } placeholder={ field.placeholder } onChange={ onChange } dir={ field.type === 'url' ? 'ltr' : 'auto' } />;
		case 'textarea':
			return <TextInput { ...common } multiline value={ asString( value ) } placeholder={ field.placeholder } onChange={ onChange } dir="auto" />;
		case 'jdate': {
			const text = asString( value );
			return (
				<TextInput
					{ ...common }
					value={ text }
					placeholder={ field.placeholder }
					onChange={ onChange }
					dir="ltr"
					error={ looksLikeJalali( text ) ? undefined : t( 'jdateInvalid' ) }
				/>
			);
		}
		case 'code': {
			const locked = field.language !== 'css' && ! boot.settings?.canScripts;
			return <CodeField { ...common } language={ field.language ?? 'css' } value={ asString( value ) } placeholder={ field.placeholder } onChange={ onChange } disabled={ locked } />;
		}
		case 'devices':
			return (
				<DeviceToggles
					{ ...common }
					value={ { desktop: true, tablet: true, mobile: true, ...( value as Partial<Devices> ) } }
					labels={ { desktop: t( 'desktop' ), tablet: t( 'tablet' ), mobile: t( 'mobile' ) } }
					onChange={ onChange }
				/>
			);
		case 'media':
			return (
				<MediaField
					id={ id }
					value={ asNumber( value, 0 ) }
					current={ fileName }
					accept={ field.accept }
					describedBy={ describedBy }
					strings={ { choose: t( 'mediaChoose' ), replace: t( 'mediaReplace' ), remove: t( 'mediaRemove' ), title: field.label, none: t( 'mediaNone' ) } }
					onChange={ ( attachment, file ) => {
						onChange( attachment );
						onFile?.( file?.filename ?? '' );
					} }
				/>
			);
		case 'repeater': {
			const subs = field.fields ?? [];
			const rows = Array.isArray( value ) ? ( value as Record<string, unknown>[] ) : [];
			return (
				<Repeater<Record<string, unknown>>
					label={ field.label }
					rows={ rows }
					max={ field.max_rows }
					onChange={ onChange }
					newRow={ () => Object.fromEntries( subs.map( ( sub ) => [ sub.id, sub.default ?? '' ] ) ) }
					strings={ { add: t( 'rowAdd' ), remove: t( 'rowRemove' ), up: t( 'rowUp' ), down: t( 'rowDown' ), row: t( 'rowLabel' ) } }
					renderRow={ ( row, update, index ) => (
						<div className="grid gap-3 md:grid-cols-3">
							{ subs.map( ( sub ) => {
								const subField = { ...sub, id: `${ field.id }-${ index }-${ sub.id }` };
								return (
									<div key={ sub.id } className="grid gap-1.5">
										<FieldLabel htmlFor={ `um-field-${ subField.id }` }>{ sub.label }</FieldLabel>
										<FieldControl field={ subField } value={ row[ sub.id ] } inRow={ false } onChange={ ( next ) => update( { ...row, [ sub.id ]: next } ) } />
									</div>
								);
							} ) }
						</div>
					) }
				/>
			);
		}
		default:
			return null;
	}
}
