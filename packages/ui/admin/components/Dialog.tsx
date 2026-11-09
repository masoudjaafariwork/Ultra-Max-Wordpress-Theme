/**
 * Admin Dialog: same name as the storefront Dialog, with the same parts
 * (title, children, footer). The trigger is rendered by the component, so
 * focus return never depends on forwarding refs through user code.
 */
import type { ReactNode } from 'react';
import {
	Dialog as ShadDialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from '@ui-admin/components/ui/dialog';
import { buttonVariants } from '@ui-admin/components/ui/button';

export interface DialogProps {
	title: ReactNode;
	description?: ReactNode;
	/** Label of the button that opens the dialog; leave out for a dialog opened from code. */
	trigger?: ReactNode;
	children?: ReactNode;
	footer?: ReactNode;
	open?: boolean;
	onOpenChange?: ( open: boolean ) => void;
}

export function Dialog( { title, description, trigger, children, footer, open, onOpenChange }: DialogProps ) {
	return (
		<ShadDialog open={ open } onOpenChange={ onOpenChange }>
			{ trigger ? <DialogTrigger className={ buttonVariants( { variant: 'default' } ) }>{ trigger }</DialogTrigger> : null }
			<DialogContent className="um-scope" dir={ document.documentElement.dir === 'rtl' ? 'rtl' : 'ltr' }>
				<DialogHeader>
					<DialogTitle>{ title }</DialogTitle>
					{ description ? <DialogDescription>{ description }</DialogDescription> : null }
				</DialogHeader>
				{ children }
				{ footer ? <DialogFooter>{ footer }</DialogFooter> : null }
			</DialogContent>
		</ShadDialog>
	);
}
