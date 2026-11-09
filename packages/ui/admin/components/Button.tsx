/**
 * Admin Button: same name and variants as the storefront Button. With
 * `href` it renders a link that looks like a button.
 */
import type { ComponentProps } from 'react';
import { cn } from 'cn';
import { Button as ShadButton, buttonVariants } from '@ui-admin/components/ui/button';

const VARIANTS = {
	primary: 'default',
	outline: 'outline',
	soft: 'secondary',
	ghost: 'ghost',
	danger: 'destructive',
} as const;

const SIZES = { sm: 'sm', md: 'default', lg: 'lg' } as const;

/** Heights and weight from the design (design/assets/css/components.css). */
const LOOK = {
	sm: 'h-9 rounded-md px-3 text-[13px] font-bold',
	md: 'h-11 rounded-lg px-5 text-sm font-bold',
	lg: 'h-13 rounded-lg px-6 text-base font-bold',
} as const;

export interface ButtonProps extends Omit<ComponentProps<'button'>, 'className'> {
	variant?: keyof typeof VARIANTS;
	size?: keyof typeof SIZES;
	/** Renders a link instead of a button. */
	href?: string;
	/** Layout classes only. */
	className?: string;
}

export function Button( { variant = 'primary', size = 'md', type = 'button', href, className, children, ...props }: ButtonProps ) {
	const classes = cn( LOOK[ size ], variant === 'outline' && 'border-border-strong bg-card', className );
	if ( href !== undefined ) {
		return (
			<a href={ href } className={ cn( buttonVariants( { variant: VARIANTS[ variant ], size: SIZES[ size ] } ), 'no-underline', classes ) } id={ props.id } aria-label={ props[ 'aria-label' ] }>
				{ children }
			</a>
		);
	}
	return (
		<ShadButton variant={ VARIANTS[ variant ] } size={ SIZES[ size ] } type={ type } className={ classes } { ...props }>
			{ children }
		</ShadButton>
	);
}
