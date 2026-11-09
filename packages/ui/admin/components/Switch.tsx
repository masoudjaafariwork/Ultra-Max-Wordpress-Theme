/**
 * Admin Switch with its label: the on/off control of every module.
 */
import { Switch as ShadSwitch } from '@ui-admin/components/ui/switch';
import { Label } from '@ui-admin/components/ui/label';

export interface SwitchProps {
	id: string;
	label: string;
	checked?: boolean;
	defaultChecked?: boolean;
	disabled?: boolean;
	onCheckedChange?: ( checked: boolean ) => void;
}

export function Switch( { id, label, ...props }: SwitchProps ) {
	return (
		<div className="flex items-center gap-3">
			<ShadSwitch id={ id } { ...props } />
			<Label htmlFor={ id }>{ label }</Label>
		</div>
	);
}