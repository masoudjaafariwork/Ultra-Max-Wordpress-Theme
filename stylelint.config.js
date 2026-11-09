/** Stylelint: standard rules plus the at-rules Tailwind CSS 4 adds. */
export default {
	extends: [ 'stylelint-config-standard' ],
	ignoreFiles: [ '**/node_modules/**', '**/vendor/**', 'build/**', '**/assets/build/**', 'dist/**', 'design/**', 'test-results/**', 'playwright-report/**' ],
	rules: {
		'at-rule-no-unknown': [ true, { ignoreAtRules: [ 'theme', 'source', 'plugin', 'custom-variant', 'utility', 'variant', 'apply', 'reference', 'config' ] } ],
		'at-rule-no-deprecated': [ true, { ignoreAtRules: [ 'apply' ] } ],
		'import-notation': 'string',
		'selector-class-pattern': null,
		'custom-property-pattern': null,
		'keyframes-name-pattern': null,
		'declaration-property-value-no-unknown': null,
		'value-keyword-case': [ 'lower', { ignoreKeywords: [ 'currentColor' ], ignoreProperties: [ 'font-family', '/^--um-font/' ] } ],
		'custom-property-empty-line-before': null,
	},
};