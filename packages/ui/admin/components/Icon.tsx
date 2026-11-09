/**
 * Admin Icon: the same hand-drawn set as the storefront Icon, decorative.
 */
import { ICON_SHAPES, type IconName } from '../../icons/shapes';
import { cn } from 'cn';

export type { IconName };

export interface IconProps {
	name: IconName | string;
	/** Size and colour classes; defaults to 20px. */
	className?: string;
	/** Mirrors the icon in left-to-right pages, for arrows that point forward. */
	directional?: boolean;
}

export function Icon( { name, className, directional }: IconProps ) {
	const shape = ( ICON_SHAPES as Record<string, string> )[ name ] ?? ICON_SHAPES.info;
	return (
		<svg
			className={ cn( 'size-5 shrink-0', directional && 'ltr:rotate-180', className ) }
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth={ 1.8 }
			strokeLinecap="round"
			strokeLinejoin="round"
			aria-hidden="true"
			focusable="false"
			dangerouslySetInnerHTML={ { __html: shape } }
		/>
	);
}

/**
 * The product mark, as in the storefront Logo.
 */
export function LogoMark( { size = 40 }: { size?: number } ) {
	return (
		<svg width={ size } height={ size } viewBox="0 0 40 40" aria-hidden="true" focusable="false" className="shrink-0">
			<defs>
				<linearGradient id="um-admin-mark" x1="0" y1="0" x2="1" y2="1">
					<stop offset="0" stopColor="var(--um-brand-500)" />
					<stop offset="1" stopColor="var(--um-brand-800)" />
				</linearGradient>
			</defs>
			<rect width="40" height="40" rx="12" fill="url(#um-admin-mark)" />
			<path d="M12 13v8a8 8 0 0 0 16 0v-8" fill="none" stroke="#fff" strokeWidth="4" strokeLinecap="round" />
			<circle cx="28" cy="29" r="3.2" fill="var(--um-accent-500)" />
		</svg>
	);
}
