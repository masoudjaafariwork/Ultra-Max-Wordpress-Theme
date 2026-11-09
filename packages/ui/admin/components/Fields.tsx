/**
 * Admin form controls of the settings panel: Select, Segmented,
 * ChoiceCards, ColorField, PresetPicker, RangeField, DeviceToggles,
 * CodeField and TextArea. Each takes `value` and `onChange`, like a
 * controlled React input.
 */
import { useId, type ReactNode } from 'react';
import { cn } from 'cn';
import { Select as ShadSelect, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@ui-admin/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@ui-admin/components/ui/toggle-group';
import { RadioGroup, RadioGroupItem } from '@ui-admin/components/ui/radio-group';
import { Slider } from '@ui-admin/components/ui/slider';
import { Checkbox } from '@ui-admin/components/ui/checkbox';
import { Textarea } from '@ui-admin/components/ui/textarea';
import { Input } from '@ui-admin/components/ui/input';
import { contrast } from '../lib/color';
import { Icon } from './Icon';

export interface Choice {
	value: string;
	label: string;
	color?: string;
}

interface ControlProps<T> {
	id: string;
	value: T;
	onChange: ( value: T ) => void;
	/** Accessible name when no visible label points at the control. */
	label?: string;
	describedBy?: string;
	disabled?: boolean;
}

export interface SelectProps extends ControlProps<string> {
	options: Choice[];
}

/** A drop-down list. */
export function Select( { id, value, onChange, options, label, describedBy, disabled }: SelectProps ) {
	return (
		<ShadSelect value={ value } onValueChange={ onChange } disabled={ disabled }>
			<SelectTrigger id={ id } aria-label={ label } aria-describedby={ describedBy } className="w-full max-w-80">
				<SelectValue />
			</SelectTrigger>
			<SelectContent>
				{ options.map( ( option ) => (
					<SelectItem key={ option.value } value={ option.value }>
						{ option.label }
					</SelectItem>
				) ) }
			</SelectContent>
		</ShadSelect>
	);
}

export interface SegmentedProps extends ControlProps<string> {
	options: Choice[];
}

/** A row of exclusive buttons for two to four choices. */
export function Segmented( { id, value, onChange, options, label, describedBy, disabled }: SegmentedProps ) {
	return (
		<ToggleGroup
			id={ id }
			type="single"
			value={ value }
			onValueChange={ ( next ) => next && onChange( next ) }
			aria-label={ label }
			aria-describedby={ describedBy }
			disabled={ disabled }
			className="w-fit rounded-lg bg-surface-3 p-1"
		>
			{ options.map( ( option ) => (
				<ToggleGroupItem
					key={ option.value }
					value={ option.value }
					className="h-8 rounded-md px-3.5 text-[13px] text-fg-2 data-[state=on]:bg-card data-[state=on]:font-bold data-[state=on]:text-foreground data-[state=on]:shadow-sm"
				>
					{ option.label }
				</ToggleGroupItem>
			) ) }
		</ToggleGroup>
	);
}

export interface ChoiceCardsProps extends ControlProps<string> {
	options: Choice[];
}

/** Larger choices as cards, such as a layout or a style. */
export function ChoiceCards( { id, value, onChange, options, label, describedBy, disabled }: ChoiceCardsProps ) {
	return (
		<RadioGroup id={ id } value={ value } onValueChange={ onChange } aria-label={ label } aria-describedby={ describedBy } disabled={ disabled } className="grid grid-cols-[repeat(auto-fill,minmax(150px,1fr))] gap-2">
			{ options.map( ( option ) => {
				const itemId = `${ id }-${ option.value }`;
				return (
					<label
						key={ option.value }
						htmlFor={ itemId }
						className={ cn(
							'flex cursor-pointer items-center gap-2.5 rounded-lg border-[1.5px] border-border p-3 text-xs transition-colors hover:border-border-strong has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-ring',
							value === option.value && 'border-primary bg-primary-soft hover:border-primary'
						) }
					>
						<RadioGroupItem id={ itemId } value={ option.value } />
						<span className={ cn( 'font-medium', value === option.value && 'font-bold text-primary' ) }>{ option.label }</span>
					</label>
				);
			} ) }
		</RadioGroup>
	);
}

export interface PresetPickerProps extends ControlProps<string> {
	options: Choice[];
}

/** Colour presets as chips with a swatch. */
export function PresetPicker( { id, value, onChange, options, label, describedBy } : PresetPickerProps ) {
	return (
		<ToggleGroup id={ id } type="single" value={ value } onValueChange={ ( next ) => next && onChange( next ) } aria-label={ label } aria-describedby={ describedBy } className="flex flex-wrap justify-start gap-2">
			{ options.map( ( option ) => (
				<ToggleGroupItem
					key={ option.value }
					value={ option.value }
					className="h-9 gap-2 rounded-full border border-border bg-card px-3.5 text-[13px] text-fg-2 hover:bg-surface-3 data-[state=on]:border-primary data-[state=on]:bg-primary-soft data-[state=on]:font-bold data-[state=on]:text-primary"
				>
					{ option.color ? (
						<span className="size-4 rounded-full border border-black/10" style={ { background: option.color } } />
					) : (
						<span className="size-4 rounded-full border border-black/10 bg-[conic-gradient(#e5484d,#f5a524,#30a46c,#0091ff,#8e4ec6,#e5484d)]" />
					) }
					{ option.label }
				</ToggleGroupItem>
			) ) }
		</ToggleGroup>
	);
}

export interface ColorFieldProps extends ControlProps<string> {
	/** Shows whether white text is readable on the colour. */
	contrastCheck?: boolean;
	strings: { hex: string; good: string; poor: string };
}

/** A colour picker with its hex code and an optional contrast check. */
export function ColorField( { id, value, onChange, label, describedBy, contrastCheck, strings }: ColorFieldProps ) {
	const ratio = contrastCheck && /^#[0-9a-f]{6}$/i.test( value ) ? contrast( value, '#ffffff' ) : null;
	return (
		<div className="grid gap-3">
			<div className="flex flex-wrap items-center gap-3">
				<input
					id={ id }
					type="color"
					value={ /^#[0-9a-f]{6}$/i.test( value ) ? value : '#000000' }
					onChange={ ( event ) => onChange( event.target.value ) }
					aria-label={ label }
					aria-describedby={ describedBy }
					className="size-11 cursor-pointer rounded-lg border border-border bg-card p-1"
				/>
				<Input
					value={ value.toUpperCase() }
					onChange={ ( event ) => onChange( event.target.value.trim().toLowerCase() ) }
					aria-label={ strings.hex }
					dir="ltr"
					className="w-32 font-mono"
					maxLength={ 7 }
				/>
			</div>
			{ ratio !== null ? (
				<p className={ cn( 'm-0 flex items-start gap-2 rounded-md border p-3 text-[13px]', ratio >= 4.5 ? 'border-success/25 bg-success-soft' : 'border-warning/30 bg-warning-soft' ) } role="status">
					<Icon name={ ratio >= 4.5 ? 'check' : 'alert' } className={ cn( 'mt-0.5 size-4', ratio >= 4.5 ? 'text-success' : 'text-warning' ) } />
					<span>{ ( ratio >= 4.5 ? strings.good : strings.poor ).replace( '%s', ratio.toFixed( 1 ) ) }</span>
				</p>
			) : null }
		</div>
	);
}

export interface RangeFieldProps extends ControlProps<number> {
	min: number;
	max: number;
	step?: number;
	unit?: string;
	format?: ( value: number ) => string;
}

/** A slider with its value shown next to it. */
export function RangeField( { id, value, onChange, min, max, step = 1, unit = '', label, describedBy, format }: RangeFieldProps ) {
	return (
		<div className="flex items-center gap-3">
			<Slider
				id={ id }
				value={ [ value ] }
				min={ min }
				max={ max }
				step={ step }
				onValueChange={ ( next ) => onChange( next[ 0 ] ?? value ) }
				aria-label={ label }
				aria-describedby={ describedBy }
				className="max-w-96 flex-1"
			/>
			<output htmlFor={ id } className="min-w-16 text-center text-[13px] font-bold">
				{ format ? format( value ) : value }
				{ unit }
			</output>
		</div>
	);
}

export interface DeviceTogglesProps extends ControlProps<{ desktop: boolean; tablet: boolean; mobile: boolean }> {
	labels: { desktop: string; tablet: string; mobile: string };
}

/** Show on desktop, tablet and mobile (CORE-13). */
export function DeviceToggles( { id, value, onChange, labels } : DeviceTogglesProps ) {
	const devices = [ 'desktop', 'tablet', 'mobile' ] as const;
	return (
		<div className="flex flex-wrap gap-5">
			{ devices.map( ( device ) => (
				<label key={ device } htmlFor={ `${ id }-${ device }` } className="inline-flex cursor-pointer items-center gap-2 text-[13px]">
					<Checkbox id={ `${ id }-${ device }` } checked={ value[ device ] } onCheckedChange={ ( checked ) => onChange( { ...value, [ device ]: checked === true } ) } />
					{ labels[ device ] }
				</label>
			) ) }
		</div>
	);
}

export interface CodeFieldProps extends ControlProps<string> {
	language: 'css' | 'js' | 'html';
	placeholder?: string;
	rows?: number;
}

/** A left-to-right monospaced editor for custom code. */
export function CodeField( { id, value, onChange, language, placeholder, rows = 8, label, describedBy, disabled }: CodeFieldProps ) {
	return (
		<Textarea
			id={ id }
			value={ value }
			onChange={ ( event ) => onChange( event.target.value ) }
			dir="ltr"
			spellCheck={ false }
			rows={ rows }
			placeholder={ placeholder }
			aria-label={ label }
			aria-describedby={ describedBy }
			disabled={ disabled }
			data-language={ language }
			className="min-h-40 bg-[#1d2327] font-mono text-xs leading-relaxed text-[#e6e6e6] placeholder:text-[#8c8f94]"
		/>
	);
}

export interface TextInputProps extends ControlProps<string> {
	type?: 'text' | 'url' | 'email';
	placeholder?: string;
	multiline?: boolean;
	dir?: 'ltr' | 'rtl' | 'auto';
	/** Shown under the field when the value is invalid. */
	error?: ReactNode;
}

/** A plain text input or text area, with an inline error. */
export function TextInput( { id, value, onChange, type = 'text', placeholder, multiline, dir, label, describedBy, disabled, error }: TextInputProps ) {
	const errorId = useId();
	const described = [ describedBy, error ? errorId : undefined ].filter( Boolean ).join( ' ' ) || undefined;
	return (
		<div className="grid max-w-xl gap-1.5">
			{ multiline ? (
				<Textarea id={ id } value={ value } onChange={ ( event ) => onChange( event.target.value ) } placeholder={ placeholder } aria-label={ label } aria-describedby={ described } aria-invalid={ error ? true : undefined } disabled={ disabled } dir={ dir } rows={ 3 } />
			) : (
				<Input id={ id } type={ type } value={ value } onChange={ ( event ) => onChange( event.target.value ) } placeholder={ placeholder } aria-label={ label } aria-describedby={ described } aria-invalid={ error ? true : undefined } disabled={ disabled } dir={ dir ?? ( type === 'url' || type === 'email' ? 'ltr' : undefined ) } />
			) }
			{ error ? <small id={ errorId } className="text-xs text-destructive">{ error }</small> : null }
		</div>
	);
}
