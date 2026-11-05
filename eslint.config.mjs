import js from '@eslint/js';
import globals from 'globals';

export default [
  { ignores: ['**/build/**', 'dist/**', 'node_modules/**'] },
  js.configs.recommended,
  { files: ['scripts/**/*.mjs', '*.mjs'], languageOptions: { globals: globals.node } },
  { files: ['themes/**/src/**/*.js', 'plugins/**/src/**/*.js'], languageOptions: { globals: globals.browser } },
];
