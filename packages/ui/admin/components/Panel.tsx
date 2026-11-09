/**
 * Panel layout parts from design/admin: PageHeader (ump-head), Card,
 * SideNav (snav), SettingRow (sgroup), SaveBar and Tile.
 */
import type { CSSProperties, MouseEvent, ReactNode } from 'react';
import { cn } from 'cn';
import { Icon, LogoMark } from './Icon';

export interface PageHeaderProps {
	title: string;
	/** Line under the title, such as the version. */
	subtitle?: ReactNode;
	/** Shown between the title and the actions, such as a search box. */
	children?: ReactNode;
	actions?: ReactNode;
}

/** Top bar of every panel page. */
export function PageHeader( { title, subtitle, children, actions }: PageHeaderProps ) {
	return (
		<header className="flex flex-wrap items-center gap-4 rounded-xl border border-border bg-card px-5 py-4">
			<LogoMark />
			<div className="grid gap-0.5">
				<h1 className="m-0 p-0 text-lg font-bold leading-tight text-foreground">{ title }</h1>
				{ subtitle ? <p className="m-0 text-xs text-fg-3">{ subtitle }</p> : null }
			</div>
			{ children }
			{ actions ? <div className="ms-auto flex flex-wrap items-center gap-2">{ actions }</div> : null }
		</header>
	);
}

export interface CardProps {
	title?: ReactNode;
	icon?: string;
	/** Right side of the card head: a badge, link or feature ids. */
	aside?: ReactNode;
	children: ReactNode;
	/** A row of actions under a border at the bottom. */
	footer?: ReactNode;
	id?: string;
	/** Layout classes only. */
	className?: string;
}

/** A white panel with an optional head and footer. */
export function Card( { title, icon, aside, children, footer, id, className }: CardProps ) {
	return (
		<section id={ id } className={ cn( 'min-w-0 scroll-mt-12 rounded-xl border border-border bg-card p-5 text-card-foreground', className ) }>
			{ title || aside ? (
				<div className="mb-4 flex items-center justify-between gap-3">
					{ title ? (
						<h2 className="m-0 flex items-center gap-2 p-0 text-sm font-bold text-foreground">
							{ icon ? <Icon name={ icon } className="text-primary" /> : null }
							{ title }
						</h2>
					) : <span /> }
					{ aside }
				</div>
			) : null }
			{ children }
			{ footer ? <div className="-mx-5 -mb-5 mt-5 flex flex-wrap items-center gap-3 border-t border-border px-5 py-4 md:px-8">{ footer }</div> : null }
		</section>
	);
}

export interface SideNavItem {
	id: string;
	label: string;
	icon: string;
	/** Draws a separator before this item. */
	separated?: boolean;
}

export interface SideNavProps {
	label: string;
	items: SideNavItem[];
	current: string;
	onSelect: ( id: string ) => void;
}

/** Section list of the settings page; a scrolling row on narrow screens. */
export function SideNav( { label, items, current, onSelect }: SideNavProps ) {
	return (
		<nav aria-label={ label } className="sticky top-12 flex flex-col gap-0.5 rounded-xl border border-border bg-card p-2 max-xl:static max-xl:flex-row max-xl:overflow-x-auto">
			{ items.map( ( item ) => (
				<div key={ item.id } className="contents">
					{ item.separated ? <span className="my-1.5 h-px bg-border max-xl:hidden" /> : null }
					<a
						href={ `#${ item.id }` }
						aria-current={ item.id === current ? 'page' : undefined }
						onClick={ ( event ) => {
							event.preventDefault();
							onSelect( item.id );
						} }
						className={ cn(
							'flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] whitespace-nowrap text-fg-2 no-underline transition-colors hover:bg-surface-3 hover:text-foreground focus-visible:outline-2 focus-visible:outline-ring',
							item.id === current && 'bg-primary-soft font-bold text-primary hover:bg-primary-soft hover:text-primary'
						) }
					>
						<Icon name={ item.icon } className="size-4.5" />
						{ item.label }
					</a>
				</div>
			) ) }
		</nav>
	);
}

export interface SettingRowProps {
	/** Id of the control, for the label. */
	htmlFor?: string;
	label: string;
	help?: string;
	/** Design token the setting writes, shown for developers. */
	token?: string;
	children: ReactNode;
	id?: string;
	highlighted?: boolean;
	/** Whether the label is for a group of controls (fieldset). */
	group?: boolean;
}

/** One setting: label and help on one side, the control on the other. */
export function SettingRow( { htmlFor, label, help, token, children, id, highlighted, group }: SettingRowProps ) {
	const helpId = help && id ? `${ id }-help` : undefined;
	const head = (
		<>
			<span className="block text-[13px] font-bold text-foreground">{ label }</span>
			{ help ? <small id={ helpId } className="mt-1 block text-xs leading-relaxed text-fg-3">{ help }</small> : null }
			{ token ? <code className="mt-1 inline-block rounded-sm bg-surface-3 px-1 text-[10px] text-fg-3" dir="ltr">{ token }</code> : null }
		</>
	);
	const classes = cn(
		'grid scroll-mt-28 gap-6 border-b border-border py-5 first:pt-0 last:border-b-0 last:pb-0 xl:grid-cols-[260px_minmax(0,1fr)] max-xl:gap-3',
		highlighted && 'rounded-lg bg-accent-soft/50 outline-2 outline-offset-4 outline-accent-soft'
	);
	if ( group ) {
		return (
			<fieldset id={ id } className={ cn( classes, 'm-0 min-w-0 border-x-0 border-t-0 p-0' ) } aria-describedby={ helpId }>
				<legend className="float-start p-0">{ head }</legend>
				<div className="flex min-w-0 flex-col gap-3">{ children }</div>
			</fieldset>
		);
	}
	return (
		<div id={ id } className={ classes }>
			<label htmlFor={ htmlFor }>{ head }</label>
			<div className="flex min-w-0 flex-col gap-3">{ children }</div>
		</div>
	);
}

export interface SaveBarProps {
	message: ReactNode;
	children: ReactNode;
	dirty: boolean;
}

/** Sticky bar at the bottom of the settings page. */
export function SaveBar( { message, children, dirty }: SaveBarProps ) {
	return (
		<div className="sticky bottom-0 z-10 flex flex-wrap items-center gap-3 rounded-xl border border-border bg-card px-5 py-3 shadow-[0_-6px_20px_rgb(21_22_28/6%)]" role="region" aria-label={ typeof message === 'string' ? message : undefined }>
			<span className={ cn( 'inline-flex items-center gap-1.5 text-[13px]', dirty ? 'text-warning' : 'text-fg-3' ) } aria-live="polite">
				<Icon name={ dirty ? 'info' : 'check' } className="size-4" />
				{ message }
			</span>
			<span className="flex-1" />
			{ children }
		</div>
	);
}

export interface TileProps {
	label: string;
	value: ReactNode;
	note?: ReactNode;
}

/** A number with its label, as on the dashboard. */
export function Tile( { label, value, note }: TileProps ) {
	return (
		<div className="rounded-lg border border-border bg-surface-2 px-4 py-3">
			<small className="block text-xs text-fg-3">{ label }</small>
			<b className="text-2xl font-extrabold text-foreground">{ value }</b>
			{ note ? <small className="block text-xs text-fg-3">{ note }</small> : null }
		</div>
	);
}

export interface HeadingProps {
	level?: 2 | 3;
	size?: 'lg' | 'md' | 'sm';
	children: ReactNode;
	id?: string;
	/** Layout classes only. */
	className?: string;
}

/** A heading inside a page or card. */
export function Heading( { level = 2, size = 'lg', children, id, className }: HeadingProps ) {
	const Tag = level === 2 ? 'h2' : 'h3';
	const look = { lg: 'text-xl font-extrabold', md: 'text-base font-bold', sm: 'text-sm font-bold' }[ size ];
	return <Tag id={ id } className={ cn( 'm-0 p-0 leading-snug text-foreground', look, className ) }>{ children }</Tag>;
}

export interface TextProps {
	tone?: 'default' | 'muted' | 'faint';
	size?: 'md' | 'sm' | 'xs';
	children: ReactNode;
	/** Layout classes only. */
	className?: string;
}

/** A paragraph of panel text. */
export function Text( { tone = 'default', size = 'sm', children, className }: TextProps ) {
	const color = { default: 'text-foreground', muted: 'text-fg-2', faint: 'text-fg-3' }[ tone ];
	const fontSize = { md: 'text-sm', sm: 'text-[13px]', xs: 'text-xs' }[ size ];
	return <p className={ cn( 'm-0 leading-relaxed', color, fontSize, className ) }>{ children }</p>;
}

export interface TextLinkProps {
	href: string;
	children: ReactNode;
	onClick?: ( event: MouseEvent<HTMLAnchorElement> ) => void;
	icon?: string;
	/** Layout classes only. */
	className?: string;
}

/** An inline link in the brand colour. */
export function TextLink( { href, children, onClick, icon, className }: TextLinkProps ) {
	return (
		<a href={ href } onClick={ onClick } className={ cn( 'inline-flex items-center gap-1 text-[13px] font-medium text-primary no-underline hover:underline focus-visible:outline-2 focus-visible:outline-ring', className ) }>
			{ children }
			{ icon ? <Icon name={ icon } className="size-4" /> : null }
		</a>
	);
}

export interface KeyValueProps {
	items: { label: string; value: ReactNode; ltr?: boolean }[];
}

/** A two-column list of facts. */
export function KeyValue( { items }: KeyValueProps ) {
	return (
		<dl className="m-0 grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-[13px]">
			{ items.map( ( item ) => (
				<div key={ item.label } className="contents">
					<dt className="text-fg-3">{ item.label }</dt>
					<dd className="m-0 font-medium" dir={ item.ltr ? 'ltr' : undefined } style={ item.ltr ? { textAlign: 'end' } : undefined }>{ item.value }</dd>
				</div>
			) ) }
		</dl>
	);
}

export interface PreviewFrameProps {
	label: string;
	children: ReactNode;
	style?: CSSProperties;
}

/** A dashed frame around a live preview. */
export function PreviewFrame( { label, children, style }: PreviewFrameProps ) {
	return (
		<div className="rounded-xl border border-dashed border-border-strong bg-surface-2 p-4" style={ style } role="group" aria-label={ label }>
			<span className="mb-2.5 block text-xs text-fg-3">{ label }</span>
			{ children }
		</div>
	);
}
export interface DemoCardProps {
	name: string;
	description: string;
	image: string;
	/** Background and the two gradient colours of the preview art. */
	colors: [ string, string, string ];
	selected: boolean;
	onSelect: () => void;
}

/** A demo to choose in the setup wizard. */
export function DemoCard( { name, description, image, colors, selected, onSelect }: DemoCardProps ) {
	return (
		<button
			type="button"
			role="radio"
			aria-checked={ selected }
			onClick={ onSelect }
			className={ cn(
				'grid cursor-pointer overflow-hidden rounded-xl border-2 border-border bg-card p-0 text-start transition hover:shadow-um-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
				selected && 'border-primary shadow-[var(--um-focus)]'
			) }
		>
			<span className="flex h-36 flex-col gap-1.5 p-2.5" style={ { background: colors[ 0 ] } }>
				<span className="h-2.5 rounded-sm bg-white/85" />
				<span className="flex flex-1 items-center justify-end rounded-md px-2.5" style={ { background: `linear-gradient(120deg, ${ colors[ 1 ] }, ${ colors[ 2 ] })` } }>
					<img src={ image } alt="" width={ 64 } height={ 64 } className="h-16 w-auto" />
				</span>
				<span className="grid h-7 grid-cols-4 gap-1">
					<i className="rounded-sm bg-white/85" />
					<i className="rounded-sm bg-white/85" />
					<i className="rounded-sm bg-white/85" />
					<i className="rounded-sm bg-white/85" />
				</span>
			</span>
			<span className="grid gap-0.5 px-3 py-2.5">
				<b className="text-[13px] text-foreground">{ name }</b>
				<small className="text-xs text-fg-3">{ description }</small>
			</span>
		</button>
	);
}

export interface RichTextProps {
	/** Trusted HTML: our own help articles, filtered on the server. */
	html: string;
}

/** Typography for help articles. */
export function RichText( { html }: RichTextProps ) {
	return <div className="um-admin-prose" dangerouslySetInnerHTML={ { __html: html } } />;
}