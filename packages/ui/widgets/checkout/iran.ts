/**
 * The Iranian checks of checkout values (CHK-04), the same rules as the
 * store's Checkout\Iran class, for an answer while the shopper types.
 */

/**
 * Latin digits only, without spaces, dashes or other marks.
 *
 * @param value Typed value.
 */
export function digits( value: string ): string {
	return value
		.replace( /[۰-۹]/g, ( digit ) => String( '۰۱۲۳۴۵۶۷۸۹'.indexOf( digit ) ) )
		.replace( /[٠-٩]/g, ( digit ) => String( '٠١٢٣٤٥٦٧٨٩'.indexOf( digit ) ) )
		.replace( /[^0-9+]/g, '' );
}

/**
 * A mobile number as 09xxxxxxxxx, or null.
 *
 * @param value Typed number.
 */
export function mobile( value: string ): string | null {
	let number = digits( value ).replace( /^(\+98|0098|98)(?=9\d{9}$)/, '' );
	if ( /^9\d{9}$/.test( number ) ) {
		number = `0${ number }`;
	}
	return /^09\d{9}$/.test( number ) ? number : null;
}

/**
 * Whether a value is a valid national code.
 *
 * @param value Typed code.
 */
export function nationalCode( value: string ): boolean {
	const code = digits( value );
	if ( ! /^\d{10}$/.test( code ) || /^(\d)\1{9}$/.test( code ) ) {
		return false;
	}
	let sum = 0;
	for ( let i = 0; i < 9; i++ ) {
		sum += Number( code[ i ] ) * ( 10 - i );
	}
	const rest = sum % 11;
	const check = Number( code[ 9 ] );
	return rest < 2 ? check === rest : check === 11 - rest;
}

/**
 * Whether a value is a valid national id of a company.
 *
 * @param value Typed id.
 */
export function companyId( value: string ): boolean {
	const id = digits( value );
	if ( ! /^\d{11}$/.test( id ) || /^(\d)\1{10}$/.test( id ) ) {
		return false;
	}
	const weights = [ 29, 27, 23, 19, 17 ];
	const plus = Number( id[ 9 ] ) + 2;
	let sum = 0;
	for ( let i = 0; i < 10; i++ ) {
		sum += ( Number( id[ i ] ) + plus ) * ( weights[ i % 5 ] ?? 0 );
	}
	const rest = sum % 11;
	return ( rest === 10 ? 0 : rest ) === Number( id[ 10 ] );
}

/**
 * Whether a value looks like a postal code.
 *
 * @param value Typed code.
 */
export function postcode( value: string ): boolean {
	return /^(?!(\d)\1{3})[13-9]{4}[1346-9][013-9]{5}$/.test( digits( value ) );
}

/**
 * Whether a value looks like an economic code.
 *
 * @param value Typed code.
 */
export function economicCode( value: string ): boolean {
	return /^\d{10,14}$/.test( digits( value ) );
}

/**
 * Whether a value passes a named rule; an empty value always does.
 *
 * @param rule  mobile, postcode, national_code, company_id, economic_code or digits.
 * @param value Value.
 */
export function passes( rule: string, value: string ): boolean {
	if ( value.trim() === '' ) {
		return true;
	}
	switch ( rule ) {
		case 'mobile':
			return mobile( value ) !== null;
		case 'postcode':
			return postcode( value );
		case 'national_code':
			return nationalCode( value );
		case 'company_id':
			return companyId( value );
		case 'economic_code':
			return economicCode( value );
		case 'digits':
			return /^\d{1,20}$/.test( digits( value ) );
		default:
			return true;
	}
}
