/**
 * Admin TextField: a labelled text input with optional help text.
 */
import type { ComponentProps } from 'react';
import { Input } from '@ui-admin/components/ui/input';
import { Label } from '@ui-admin/components/ui/label';

export interface TextFieldProps extends Omit<ComponentProps<'input'>, 'id' | 'className'> {
	id: string;
	label: string;
	help?: string;
}

export function TextField( { id, label, help, ...props }: TextFieldProps ) {
	const helpId = help ? `${ id }-help` : undefined;
	return (
		<div className="grid gap-2">
			<Label htmlFor={ id }>{ label }</Label>
			<Input id={ id } aria-describedby={ helpId } { ...props } />
			{ help ? <p id={ helpId } className="text-sm text-muted-foreground">{ help }</p> : null }
		</div>
	);
}