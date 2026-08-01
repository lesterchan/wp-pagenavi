/**
 * WordPress JS coding standards for WP-PageNavi.
 *
 * "recommended-with-formatting" uses native ESLint formatting rules rather
 * than delegating to Prettier, so no Prettier install is needed.
 *
 * Excluded from the SVN deploy, so this never ships to users.
 */
import wordpress from '@wordpress/eslint-plugin';
import globals from 'globals';

export default [
	{
		ignores: [ '**/node_modules/**', '**/vendor/**', '**/*.min.js' ],
	},
	...wordpress.configs[ 'recommended-with-formatting' ],
	{
		languageOptions: {
			globals: {
				...globals.browser,
			},
		},
		settings: {
			react: { version: '18.0' },
		},
	},
	{
		// The Playwright suite is CommonJS and runs under Node, not in a page:
		// it requires its helpers and exports nothing to a browser.
		files: [ 'tests/e2e/**/*.js', 'playwright.config.js' ],
		languageOptions: {
			sourceType: 'commonjs',
			globals: {
				...globals.node,
			},
		},
	},
];
