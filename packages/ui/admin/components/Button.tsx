/**
 * Admin Button: same name and variants as the storefront Button.
 */
import type { ComponentProps } from 'react';
import { Button as ShadButton } from '@ui-admin/components/ui/button';

const VARIANTS = {
	primary: 'default',
	outline: 'outline',
	soft: 'secondary',
	ghost: 'ghost',
	danger: 'destructive',
} as const;

const SIZES = { sm: 'sm', md: 'default', lg: 'lg' } as const;

export interface ButtonProps extends Omit<ComponentProps<'button'>, 'className'> {
	variant?: keyof typeof VARIANTS;
	size?: keyof typeof SIZES;
	/** Layout classes only. */
	className?: string;
}

export function Button( { variant = 'primary', size = 'md', type = 'button', ...props }: ButtonProps ) {
	return <ShadButton variant={ VARIANTS[ variant ] } size={ SIZES[ size ] } type={ type } { ...props } />;
}
