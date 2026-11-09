/**
 * Admin feedback and status parts: Badge, Alert, Progress, Toaster with
 * notify(), StatusList, BarChart and Steps.
 */
import type { ReactNode } from 'react';
import { cn } from 'cn';
import { toast } from 'sonner';
import { Toaster as ShadToaster } from '@ui-admin/components/ui/sonner';
import { Progress as ShadProgress } from '@ui-admin/components/ui/progress';
import { Icon } from './Icon';

type Tone = 'neutral' | 'brand' | 'success' | 'warning' | 'danger' | 'info';

const BADGE: Record<Tone, string> = {
	neutral: 'bg-surface-3 text-fg-2',
	brand: 'bg-primary-soft text-primary',
	success: 'bg-success-soft text-success-text',
	warning: 'bg-warning-soft text-warning',
	danger: 'bg-danger-soft text-destructive',
	info: 'bg-info-soft text-info-text',
};

export interface BadgeProps {
	tone?: Tone;
	icon?: string;
	children: ReactNode;
}

/** A short status label. */
export function Badge( { tone = 'neutral', icon, children }: BadgeProps ) {
	return (
		<span className={ cn( 'inline-flex h-6 w-fit shrink-0 items-center gap-1 rounded-full px-2.5 text-xs font-bold whitespace-nowrap', BADGE[ tone ] ) }>
			{ icon ? <Icon name={ icon } className="size-3.5" /> : null }
			{ children }
		</span>
	);
}

const ALERT: Record<'info' | 'success' | 'warning' | 'danger', [ string, string ]> = {
	info: [ 'info', 'border-info/25 bg-info-soft [&>svg]:text-info' ],
	success: [ 'check', 'border-success/25 bg-success-soft [&>svg]:text-success' ],
	warning: [ 'alert', 'border-warning/30 bg-warning-soft [&>svg]:text-warning' ],
	danger: [ 'alert', 'border-danger/25 bg-danger-soft [&>svg]:text-destructive' ],
};

export interface AlertProps {
	tone?: 'info' | 'success' | 'warning' | 'danger';
	icon?: string;
	title?: string;
	children?: ReactNode;
	action?: ReactNode;
	/** Layout classes only. */
	className?: string;
}

/** A message box with a tone icon. */
export function Alert( { tone = 'info', icon, title, children, action, className }: AlertProps ) {
	const [ toneIcon, look ] = ALERT[ tone ];
	return (
		<div className={ cn( 'flex flex-wrap items-start gap-3 rounded-lg border p-4 text-[13px] leading-relaxed text-foreground', look, className ) } role={ tone === 'danger' ? 'alert' : undefined }>
			<Icon name={ icon ?? toneIcon } className="mt-0.5 size-5" />
			<div className="grid min-w-0 flex-1 gap-1">
				{ title ? <strong className="font-bold">{ title }</strong> : null }
				{ children }
			</div>
			{ action }
		</div>
	);
}

export interface ProgressProps {
	value: number;
	label: string;
	tone?: 'brand' | 'success';
}

/** A progress bar. */
export function Progress( { value, label, tone = 'brand' }: ProgressProps ) {
	return <ShadProgress value={ value } aria-label={ label } className={ cn( 'h-2', tone === 'success' && '[&>div]:bg-success' ) } />;
}

/** Where toasts appear; render once per page. */
export function Toaster() {
	const rtl = document.documentElement.dir === 'rtl';
	return <ShadToaster position={ rtl ? 'bottom-left' : 'bottom-right' } dir={ rtl ? 'rtl' : 'ltr' } toastOptions={ { className: 'font-[family-name:var(--um-font-body)]' } } />;
}

/** Shows a short message. */
export const notify = {
	success: ( message: string ) => toast.success( message ),
	error: ( message: string ) => toast.error( message ),
	info: ( message: string ) => toast( message ),
};

export interface StatusItem {
	id: string;
	status: 'good' | 'warning' | 'critical';
	label: string;
	description?: string;
	value: string;
}

export interface StatusListProps {
	items: StatusItem[];
	labels: Record<StatusItem[ 'status' ], string>;
}

/** System status rows (ADM-05). */
export function StatusList( { items, labels }: StatusListProps ) {
	const look = {
		good: [ 'check', 'text-success' ],
		warning: [ 'alert', 'text-warning' ],
		critical: [ 'alert', 'text-destructive' ],
	} as const;
	return (
		<table className="w-full border-collapse text-[13px]">
			<tbody>
				{ items.map( ( item ) => (
					<tr key={ item.id } className="border-b border-border last:border-b-0">
						<td className="w-7 py-2.5 align-top">
							<Icon name={ look[ item.status ][ 0 ] } className={ cn( 'size-4.5', look[ item.status ][ 1 ] ) } />
							<span className="sr-only">{ labels[ item.status ] }</span>
						</td>
						<td className="py-2.5 align-top">
							{ item.label }
							{ item.description ? <small className="block text-xs text-fg-3">{ item.description }</small> : null }
						</td>
						<td className={ cn( 'py-2.5 text-end align-top font-bold whitespace-nowrap', look[ item.status ][ 1 ] ) } dir="ltr">
							{ item.value }
						</td>
					</tr>
				) ) }
			</tbody>
		</table>
	);
}

export interface BarChartProps {
	/** Accessible summary of the chart. */
	label: string;
	bars: { label: string; value: number; display: string }[];
	tableLabel: string;
}

/** A small column chart with a data table for screen readers. */
export function BarChart( { label, bars, tableLabel }: BarChartProps ) {
	const max = Math.max( 1, ...bars.map( ( bar ) => bar.value ) );
	return (
		<figure className="m-0">
			<div className="flex h-40 items-end gap-1.5 border-b border-border" role="img" aria-label={ label }>
				{ bars.map( ( bar ) => (
					<div key={ bar.label } className="group relative flex h-full flex-1 items-end">
						<span className="block w-full rounded-t-sm bg-primary transition-opacity group-hover:opacity-80" style={ { height: `${ Math.max( 2, ( bar.value / max ) * 100 ) }%` } } />
						<span className="pointer-events-none absolute -top-7 start-1/2 hidden -translate-x-1/2 rounded-sm bg-foreground px-1.5 py-0.5 text-[11px] whitespace-nowrap text-background group-hover:block rtl:translate-x-1/2">{ bar.display }</span>
					</div>
				) ) }
			</div>
			<div className="mt-1 flex gap-1.5 text-[10px] text-fg-3">
				{ bars.map( ( bar, index ) => (
					<span key={ bar.label } className="flex-1 text-center">{ index % 2 === 0 ? bar.label : '' }</span>
				) ) }
			</div>
			<details className="mt-2 text-xs">
				<summary className="cursor-pointer text-primary">{ tableLabel }</summary>
				<table className="mt-2 w-full text-xs">
					<tbody>
						{ bars.map( ( bar ) => (
							<tr key={ bar.label } className="border-b border-border">
								<td className="py-1">{ bar.label }</td>
								<td className="py-1 text-end">{ bar.display }</td>
							</tr>
						) ) }
					</tbody>
				</table>
			</details>
		</figure>
	);
}

export interface StepsProps {
	label: string;
	steps: { label: string; icon: string }[];
	current: number;
	onSelect?: ( index: number ) => void;
}

/** Numbered steps of the setup wizard. */
export function Steps( { label, steps, current, onSelect }: StepsProps ) {
	return (
		<ol aria-label={ label } className="m-0 flex list-none items-start p-0">
			{ steps.map( ( step, index ) => {
				const state = index < current ? 'done' : index === current ? 'current' : 'next';
				return (
					<li key={ step.label } className="relative flex flex-1 flex-col items-center gap-2 text-center after:absolute after:top-5 after:start-[calc(50%+24px)] after:h-0.5 after:w-[calc(100%-48px)] after:bg-border last:after:hidden data-[state=done]:after:bg-primary" data-state={ state }>
						<button
							type="button"
							onClick={ () => onSelect?.( index ) }
							aria-current={ state === 'current' ? 'step' : undefined }
							className={ cn(
								'grid size-10 place-items-center rounded-full border-2 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
								state === 'done' && 'border-primary bg-primary text-primary-foreground',
								state === 'current' && 'border-primary bg-primary-soft text-primary',
								state === 'next' && 'border-border bg-card text-fg-3'
							) }
						>
							<Icon name={ state === 'done' ? 'check' : step.icon } className="size-5" />
							<span className="sr-only">{ step.label }</span>
						</button>
						<strong className={ cn( 'text-xs max-sm:text-[10px] max-sm:font-medium', state === 'next' ? 'text-fg-3' : 'text-foreground' ) } aria-hidden="true">{ step.label }</strong>
					</li>
				);
			} ) }
		</ol>
	);
}
