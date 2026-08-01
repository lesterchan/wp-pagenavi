/**
 * WP-PageNavi on the front end and on its settings screen.
 *
 * A pagination plugin renders nothing until there is more content than fits on
 * one page, so a suite without fixtures would assert that an empty page is
 * still empty and pass whatever the plugin did. Everything here therefore runs
 * against a site with three pages' worth of posts, created once before the
 * first test.
 *
 * The other half of the fixture is tests/e2e/mu-plugins: this plugin is a
 * template tag, so a theme has to call it, and no bundled theme does.
 */

const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const SETTINGS_URL = '/wp-admin/options-general.php?page=wp-pagenavi';

/**
 * Five to a page rather than the WordPress default of ten, matching the demo
 * site. Smaller pages mean more of them for the same fixture, and the number of
 * pages is the only thing this plugin is about.
 *
 * Twenty-five posts is five pages, which is also the default width of the
 * plugin's window -- so every page number is on screen and a test can assert the
 * whole set rather than a slice of it.
 */
const POSTS = 25;
const PER_PAGE = 5;
const PAGES = Math.ceil( POSTS / PER_PAGE );

/**
 * The navigation the shim prints after the main loop.
 *
 * @param {import('@playwright/test').Page} page Page under test.
 * @return {import('@playwright/test').Locator} The wp-pagenavi wrapper.
 */
function nav( page ) {
	return page.locator( '#wp-pagenavi-e2e .wp-pagenavi' );
}

/**
 * Put the settings back, so one test's edit is not the next one's fixture.
 *
 * @param {Object} requestUtils The e2e request helper.
 * @return {Promise<void>} Resolves once the row is gone.
 */
async function resetOptions( requestUtils ) {
	// The route belongs to the fixture mu-plugin, not the plugin: this settings
	// row is for an admin screen and correctly has no REST route of its own.
	await requestUtils.rest( {
		method: 'POST',
		path: '/wp-pagenavi-e2e/v1/reset',
	} );
}

test.describe( 'WP-PageNavi', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();

		// posts_per_page is one of the few settings core does expose over REST,
		// so unlike the comment paging options this can be set from here.
		await requestUtils.updateSiteSettings( { posts_per_page: PER_PAGE } );

		// Five pages' worth. Created in parallel because 25 sequential REST
		// round trips is most of a minute for nothing.
		await Promise.all(
			Array.from( { length: POSTS }, ( _, i ) =>
				requestUtils.createPost( {
					title: `Paged post ${ String( i + 1 ).padStart( 2, '0' ) }`,
					content: 'Body.',
					status: 'publish',
				} ),
			),
		);
	} );

	test.afterEach( async ( { requestUtils } ) => {
		await resetOptions( requestUtils );
	} );

	test( 'the fixture really is more than one page', async ( { page } ) => {
		// The assertion the rest of the file leans on. If the site fits on one
		// page then a plugin that renders nothing is behaving correctly, and
		// every test below would pass without the plugin installed at all.
		await page.goto( '/' );

		const posts = await page.locator( 'article' ).count();

		expect( posts ).toBe( PER_PAGE );
		await expect( nav( page ) ).toBeVisible();
	} );

	test( 'shows a page number for every page, and marks the current one', async ( { page } ) => {
		await page.goto( '/' );

		const navigation = nav( page );

		await expect( navigation ).toBeVisible();

		// Three pages, so three numbers -- one of them the current page, which is
		// a span rather than a link because there is nowhere to go.
		await expect( navigation.locator( 'a.page, span.current' ) ).toHaveCount( PAGES );
		await expect( navigation.locator( 'span.current' ) ).toHaveText( '1' );

		// The current page is announced, not just coloured differently.
		await expect( navigation.locator( '[aria-current="page"]' ) ).toHaveCount( 1 );
	} );

	test( 'the pages text counts correctly', async ( { page } ) => {
		await page.goto( '/' );

		await expect( nav( page ).locator( '.pages' ) ).toHaveText(
			`Page 1 of ${ PAGES }`,
		);
	} );

	test( 'clicking a page number goes there and moves the current marker', async ( {
		page,
	} ) => {
		await page.goto( '/' );

		const firstTitle = await page.locator( 'article h2, article h1' ).first().innerText();

		await nav( page ).getByRole( 'link', { name: '2', exact: true } ).click();

		await expect( nav( page ).locator( 'span.current' ) ).toHaveText( '2' );

		// A different page of posts, not the same one with a different number.
		const secondTitle = await page.locator( 'article h2, article h1' ).first().innerText();
		expect( secondTitle ).not.toBe( firstTitle );
	} );

	test( 'previous and next appear only where they lead somewhere', async ( { page } ) => {
		await page.goto( '/' );

		// Page one has nothing before it.
		await expect( nav( page ).locator( '.previouspostslink' ) ).toHaveCount( 0 );
		await expect( nav( page ).locator( '.nextpostslink' ) ).toHaveCount( 1 );

		await nav( page ).locator( '.nextpostslink' ).click();

		await expect( nav( page ).locator( 'span.current' ) ).toHaveText( '2' );
		await expect( nav( page ).locator( '.previouspostslink' ) ).toHaveCount( 1 );

		// Walk to the end rather than clicking a fixed number of times: how many
		// pages there are is a function of the fixture and the site's per-page
		// setting, and a test that hardcodes two clicks quietly stops testing the
		// last page the moment either changes.
		for ( let i = 2; i < PAGES; i++ ) {
			await nav( page ).locator( '.nextpostslink' ).click();
			await expect( nav( page ).locator( 'span.current' ) ).toHaveText( String( i + 1 ) );
		}

		// And the last page has nothing after it.
		await expect( nav( page ).locator( 'span.current' ) ).toHaveText( String( PAGES ) );
		await expect( nav( page ).locator( '.nextpostslink' ) ).toHaveCount( 0 );
	} );

	test( 'first and last appear once the window cannot show every page', async ( {
		page,
		admin,
	} ) => {
		// With three pages and a window of five there is nothing to skip past, so
		// the plugin is right to omit them. Narrowing the window to one is what
		// makes First and Last mean something.
		await admin.visitAdminPage( 'options-general.php', 'page=wp-pagenavi' );
		await page.locator( '#wp-pagenavi-num_pages' ).fill( '1' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.notice-success, .settings-error' ).first() ).toBeVisible();

		// Reached by clicking, not by typing /page/2/: the tests site keeps the
		// plain permalink structure, so that path is not a pagination URL and
		// WordPress quietly serves page one. The navigation then reads "Page 1 of
		// 3" and the assertion below fails for a reason that has nothing to do
		// with the plugin.
		await page.goto( '/' );
		await nav( page ).locator( '.nextpostslink' ).click();

		await expect( nav( page ).locator( 'span.current' ) ).toHaveText( '2' );

		await expect( nav( page ).locator( '.first' ) ).toHaveCount( 1 );
		await expect( nav( page ).locator( '.last' ) ).toHaveCount( 1 );

		await nav( page ).locator( '.first' ).click();

		await expect( nav( page ).locator( 'span.current' ) ).toHaveText( '1' );
	} );

	test( 'the dropdown style renders a select instead of the numbers', async ( {
		page,
		admin,
	} ) => {
		// Not a separate template tag: wp_pagenavi_dropdown() is a back-compat
		// shim that calls wp_pagenavi(), and which of the two renderers runs is
		// the 'style' setting.
		await admin.visitAdminPage( 'options-general.php', 'page=wp-pagenavi' );
		await page.locator( '#wp-pagenavi-style' ).selectOption( '2' );
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.notice-success, .settings-error' ).first() ).toBeVisible();

		await page.goto( '/' );

		const dropdown = nav( page ).locator( 'select' );

		await expect( dropdown ).toBeVisible();
		await expect( dropdown.locator( 'option' ) ).toHaveCount( PAGES );

		// The current page is the selected one, so the control agrees with the
		// page around it rather than always reading "Page 1".
		await expect( dropdown.locator( 'option.current' ) ).toHaveText( '1' );

		await expect( nav( page ).locator( 'a.page' ) ).toHaveCount( 0 );
	} );

	test( 'the stylesheet is enqueued, and the setting turns it off', async ( {
		page,
		admin,
	} ) => {
		await page.goto( '/' );

		await expect( page.locator( 'link[rel="stylesheet"][href*="wp-pagenavi"]' ) ).toHaveCount(
			1,
		);

		await admin.visitAdminPage( 'options-general.php', 'page=wp-pagenavi' );

		await page.locator( '#wp-pagenavi-use_pagenavi_css-0' ).check();
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.notice-success, .settings-error' ).first() ).toBeVisible();

		await page.goto( '/' );

		// A theme that styles the navigation itself should be able to stop the
		// plugin's sheet loading at all, rather than override it.
		await expect( page.locator( 'link[rel="stylesheet"][href*="wp-pagenavi"]' ) ).toHaveCount(
			0,
		);
	} );

	test( 'nothing is rendered when there is only one page', async ( { page, requestUtils } ) => {
		// always_show is off by default: one page of posts is not a thing to
		// paginate, and a navigation reading "Page 1 of 1" is furniture.
		await requestUtils.deleteAllPosts();

		await requestUtils.createPost( {
			title: 'The only post',
			content: 'Body.',
			status: 'publish',
		} );

		await page.goto( '/' );

		await expect( nav( page ) ).toHaveCount( 0 );

		// Put the fixture back for whatever runs next -- clearing first, because
		// the single post above is still there and twenty-six posts is six pages,
		// not five. A later test then reads "of 6" and fails for a reason that
		// has nothing to do with it.
		await requestUtils.deleteAllPosts();

		await Promise.all(
			Array.from( { length: POSTS }, ( _, i ) =>
				requestUtils.createPost( {
					title: `Paged post ${ String( i + 1 ).padStart( 2, '0' ) }`,
					content: 'Body.',
					status: 'publish',
				} ),
			),
		);
	} );
} );

test.describe( 'The WP-PageNavi settings screen', () => {
	test.afterEach( async ( { requestUtils } ) => {
		await resetOptions( requestUtils );
	} );

	test( 'sits under Settings and shows the text and display sections', async ( {
		page,
		admin,
	} ) => {
		await admin.visitAdminPage( 'options-general.php' );

		const entry = page.locator( '#adminmenu a[href="options-general.php?page=wp-pagenavi"]' );
		await expect( entry ).toHaveCount( 1 );

		await entry.click();

		await expect( page.getByRole( 'heading', { name: 'PageNavi Settings' } ) ).toBeVisible();
		await expect( page.locator( '#wp-pagenavi-pages_text' ) ).toBeVisible();
		await expect( page.locator( '#wp-pagenavi-num_pages' ) ).toBeVisible();
	} );

	test( 'a changed text template shows up on the front end', async ( { page, admin } ) => {
		await admin.visitAdminPage( 'options-general.php', 'page=wp-pagenavi' );

		await page
			.locator( '#wp-pagenavi-pages_text' )
			.fill( 'Sheet %CURRENT_PAGE% of %TOTAL_PAGES%' );

		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.notice-success, .settings-error' ).first() ).toBeVisible();

		await page.goto( '/' );

		await expect( nav( page ).locator( '.pages' ) ).toHaveText( `Sheet 1 of ${ PAGES }` );
	} );

	test( 'always_show renders the navigation for a single page', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await admin.visitAdminPage( 'options-general.php', 'page=wp-pagenavi' );

		await page.locator( '#wp-pagenavi-always_show' ).check();
		await page.getByRole( 'button', { name: 'Save Changes' } ).click();
		await expect( page.locator( '.notice-success, .settings-error' ).first() ).toBeVisible();

		await requestUtils.deleteAllPosts();
		await requestUtils.createPost( {
			title: 'The only post',
			content: 'Body.',
			status: 'publish',
		} );

		await page.goto( '/' );

		await expect( nav( page ) ).toBeVisible();
		await expect( nav( page ).locator( '.pages' ) ).toHaveText( 'Page 1 of 1' );

		await requestUtils.deleteAllPosts();

		await Promise.all(
			Array.from( { length: POSTS }, ( _, i ) =>
				requestUtils.createPost( {
					title: `Paged post ${ String( i + 1 ).padStart( 2, '0' ) }`,
					content: 'Body.',
					status: 'publish',
				} ),
			),
		);
	} );

	test( 'a user without manage_options cannot reach the screen', async ( {
		browser,
		baseURL,
		requestUtils,
	} ) => {
		const username = 'pagenavi_editor';

		const existing = await requestUtils.rest( {
			path: '/wp/v2/users',
			params: { search: username, context: 'edit' },
		} );

		if ( ! existing.length ) {
			await requestUtils.rest( {
				method: 'POST',
				path: '/wp/v2/users',
				data: {
					username,
					email: `${ username }@example.com`,
					password: 'correct-horse-battery-staple',
					roles: [ 'editor' ],
				},
			} );
		}

		const context = await browser.newContext( { storageState: undefined } );
		const other = await context.newPage();

		await other.goto( `${ baseURL }/wp-login.php` );
		await other.locator( '#user_login' ).fill( username );
		await other.locator( '#user_pass' ).fill( 'correct-horse-battery-staple' );
		await other.locator( '#wp-submit' ).click();
		await expect( other.locator( '#wpadminbar' ) ).toBeVisible();

		await other.goto( `${ baseURL }${ SETTINGS_URL }` );

		await expect( other.locator( 'body' ) ).toContainText(
			/do not have sufficient permissions|not allowed to access this page/,
		);

		await context.close();
	} );
} );
