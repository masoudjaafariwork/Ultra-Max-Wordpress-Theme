/**
 * PHP components write their markup through sprintf(), where a literal
 * percent sign is `%%`. Tailwind reads the PHP source as it is, so a class
 * such as `w-[68%%]` (in the browser: `w-[68%]`) becomes a rule nobody
 * matches with a value no browser accepts. `%%` is never valid CSS, so the
 * compiled CSS turns it back into one sign: `\%\%` in selectors, `%%` in
 * values.
 *
 * @param {string} css Compiled CSS.
 * @return {string} CSS with single percent signs.
 */
export function printfPercent( css ) {
	return css.replace( /\\%\\%/g, '\\%' ).replace( /%%/g, '%' );
}
