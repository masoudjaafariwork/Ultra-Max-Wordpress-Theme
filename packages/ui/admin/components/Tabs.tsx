/**
 * Admin Tabs: same name and item shape as the storefront Tabs.
 */
import type { ReactNode } from 'react';
import { Tabs as ShadTabs, TabsContent, TabsList, TabsTrigger } from '@ui-admin/components/ui/tabs';

export interface TabItem {
	value: string;
	label: ReactNode;
	content: ReactNode;
}

export interface TabsProps {
	/** Accessible name of the tab list. */
	label: string;
	items: TabItem[];
	defaultValue?: string;
	/** Layout classes only. */
	className?: string;
}

export function Tabs( { label, items, defaultValue, className }: TabsProps ) {
	return (
		<ShadTabs defaultValue={ defaultValue ?? items[ 0 ]?.value } className={ className }>
			<TabsList aria-label={ label }>
				{ items.map( ( item ) => (
					<TabsTrigger key={ item.value } value={ item.value }>
						{ item.label }
					</TabsTrigger>
				) ) }
			</TabsList>
			{ items.map( ( item ) => (
				<TabsContent key={ item.value } value={ item.value }>
					{ item.content }
				</TabsContent>
			) ) }
		</ShadTabs>
	);
}