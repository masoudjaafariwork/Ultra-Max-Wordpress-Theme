/**
 * Colour helpers for the live preview. Same rules as
 * packages/foundation/src/Color.php; tests/unit/color.test.ts keeps both
 * in step.
 */

const RAMP: [ number, string, number ][] = [
	[ 50, '#ffffff', 8 ],
	[ 100, '#ffffff', 16 ],
	[ 200, '#ffffff', 32 ],
	[ 400, '#ffffff', 70 ],
	[ 500, '#ffffff', 85 ],
	[ 600, '#ffffff', 100 ],
	[ 700, '#000000', 80 ],
	[ 800, '#000000', 62 ],
];

/**
 * Normalises #abc or #AABBCC to lowercase #aabbcc, or null.
 *
 * @param hex Colour.
 */
export function normalize( hex: string ): string | null {
	const value = hex.trim().toLowerCase();
	const short = /^#([0-9a-f])([0-9a-f])([0-9a-f])$/.exec( value );
	if ( short ) {
		return `#${ short[ 1 ] }${ short[ 1 ] }${ short[ 2 ] }${ short[ 2 ] }${ short[ 3 ] }${ short[ 3 ] }`;
	}
	return /^#[0-9a-f]{6}$/.test( value ) ? value : null;
}

const channels = ( hex: string ): [ number, number, number ] => [
	parseInt( hex.slice( 1, 3 ), 16 ),
	parseInt( hex.slice( 3, 5 ), 16 ),
	parseInt( hex.slice( 5, 7 ), 16 ),
];

/**
 * Mixes two colours in sRGB, like CSS color-mix.
 *
 * @param a       First colour.
 * @param percent Share of the first colour.
 * @param b       Second colour.
 */
export function mix( a: string, percent: number, b: string ): string {
	const ca = channels( normalize( a ) ?? '#000000' );
	const cb = channels( normalize( b ) ?? '#000000' );
	// PHP's round() rounds halves away from zero; Math.round rounds them up. Both are positive here.
	return '#' + ca.map( ( value, i ) => Math.round( ( value * percent + ( cb[ i ] ?? 0 ) * ( 100 - percent ) ) / 100 ).toString( 16 ).padStart( 2, '0' ) ).join( '' );
}

/**
 * The brand ramp from one colour.
 *
 * @param hex Brand colour.
 */
export function ramp( hex: string ): Record<number, string> {
	const base = normalize( hex ) ?? '#4c42d3';
	return Object.fromEntries( RAMP.map( ( [ step, withColor, share ] ) => [ step, mix( base, share, withColor ) ] ) );
}

/**
 * WCAG 2 relative luminance.
 *
 * @param hex Colour.
 */
export function luminance( hex: string ): number {
	const weights = [ 0.2126, 0.7152, 0.0722 ];
	return channels( normalize( hex ) ?? '#000000' ).reduce( ( sum, channel, i ) => {
		const c = channel / 255;
		return sum + ( weights[ i ] ?? 0 ) * ( c <= 0.03928 ? c / 12.92 : ( ( c + 0.055 ) / 1.055 ) ** 2.4 );
	}, 0 );
}

/**
 * WCAG 2 contrast ratio, 1 to 21.
 *
 * @param a First colour.
 * @param b Second colour.
 */
export function contrast( a: string, b: string ): number {
	const la = luminance( a );
	const lb = luminance( b );
	return ( Math.max( la, lb ) + 0.05 ) / ( Math.min( la, lb ) + 0.05 );
}

/**
 * Text colour on a background: white at 4.5:1 or more, else near black.
 *
 * @param background Background colour.
 */
export function on( background: string ): string {
	return contrast( background, '#ffffff' ) >= 4.5 ? '#ffffff' : '#15161c';
}
