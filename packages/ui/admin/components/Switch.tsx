/**
 * Admin Switch with its label: the on/off control of every module.
 */
import { Switch as ShadSwitch } from '@ui-admin/components/ui/switch';
import { Label } from '@ui-admin/components/ui/label';

export interface SwitchProps {
	id: string;
	label: string;
	/** Keeps the label for screen readers only, when a row label is already visible. */
	hideLabel?: boolean;
	describedBy?: string;
	checked?: boolean;
	defaultChecked?: boolean;
	disabled?: boolean;
	onCheckedChange?: ( checked: boolean ) => void;
}

export function Switch( { id, label, hideLabel, describedBy, ...props }: SwitchProps ) {
	return (
		<div className="flex items-center gap-3">
			<ShadSwitch id={ id } aria-describedby={ describedBy } { ...props } />
			<Label htmlFor={ id } className={ hideLabel ? 'sr-only' : undefined }>{ label }</Label>
		</div>
	);
}

/**
 * A small visible label for a control inside a group, such as a repeater row.
 */
export function FieldLabel( { htmlFor, children }: { htmlFor: string; children: string } ) {
	return <Label htmlFor={ htmlFor } className="text-xs font-medium text-fg-2">{ children }</Label>;
}
