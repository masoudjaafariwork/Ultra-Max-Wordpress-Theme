import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import globals from 'globals';

/** Imports that only packages/ui may use, so the libraries stay replaceable. */
const libraryOnly = {
	patterns: [
		{ group: [ 'flowbite', 'flowbite/*' ], message: 'Only packages/ui behaviour modules may import Flowbite. Use the component API.' },
		{ group: [ 'radix-ui', '@radix-ui/*' ], message: 'Only packages/ui/admin may import Radix. Import from @ultra-max/ui/admin.' },
		{ group: [ '**/admin/components/ui/*', '@/components/ui/*' ], message: 'Do not import shadcn/ui directly. Import from @ultra-max/ui/admin.' },
	],
};

export default tseslint.config(
	{
		ignores: [ '**/node_modules/**', '**/vendor/**', '**/vendor-prefixed/**', 'build/**', '**/assets/build/**', 'dist/**', 'design/**', 'test-results/**', 'playwright-report/**', '.cache/**' ],
	},
	js.configs.recommended,
	...tseslint.configs.recommended,
	{
		languageOptions: {
			ecmaVersion: 2023,
			sourceType: 'module',
			globals: { ...globals.browser, ...globals.node },
		},
		rules: {
			'no-restricted-imports': [ 'error', libraryOnly ],
		},
	},
	{
		files: [ 'packages/ui/**' ],
		rules: { 'no-restricted-imports': 'off' },
	}
);
