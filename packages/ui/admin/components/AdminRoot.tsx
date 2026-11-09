/**
 * Root of every admin screen: sets the writing direction for Radix (so
 * arrow keys and placement follow RTL) and the scope our styles apply in.
 */
import type { ReactNode } from 'react';
import { Direction } from 'radix-ui';

export interface AdminRootProps {
	children: ReactNode;
}

export function AdminRoot( { children }: AdminRootProps ) {
	const dir = document.documentElement.dir === 'rtl' ? 'rtl' : 'ltr';
	return (
		<Direction.DirectionProvider dir={ dir }>
			<div className="um-admin um-scope" dir={ dir }>
				{ children }
			</div>
		</Direction.DirectionProvider>
	);
}
