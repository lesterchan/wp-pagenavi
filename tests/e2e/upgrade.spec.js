/**
 * The pre-3.0.0 migration, run the way a real site runs it.
 *
 * Activation does not fire when a plugin is merely updated -- a site that
 * updates from the Plugins screen never calls activate() -- so the migration
 * also hangs off admin_init. That is the hook every real upgrade actually goes
 * through, and loading an admin page in a browser is the only way to reach it.
 * A dashboard load rather than the settings screen, because the point is that
 * any admin request does it, not that somebody went looking for the plugin.
 *
 * Two things follow from that, and they are why this file exists at all when
 * tests/test-options.php covers the same routine:
 *
 *   1. On the admin path register_setting() has already run, so the sanitize
 *      callback is attached to the settings row and every write the migration
 *      makes goes through it. Under WP-CLI it is not attached at all, and a
 *      migration test that never registers the setting is testing WP-CLI.
 *   2. Every row here is read *raw*. WP_PageNavi_Options::get() merges the
 *      defaults over whatever is stored, so it cannot tell a row holding the
 *      defaults from no row at all -- and "no row at all, legacy row deleted"
 *      is precisely what §7.6.1 describes. Asking the plugin what it sees is
 *      how that defect stays invisible; asking the database is how it does not.
 *
 * The fixtures are therefore the *shipped* settings, not customised ones. A
 * customised row cannot see this shape: its migrated result differs from the
 * defaults, so the write lands whatever the read before it did.
 */

const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
const {
	DASHBOARD_URL,
	defaultOptions,
	deleteLegacyRow,
	deleteOptions,
	deleteVersionRow,
	ensurePluginActive,
	getLegacyRow,
	getStoredOptions,
	getVersionRow,
	installLegacyRow,
	reactivatePlugin,
	runningVersions,
	setOptions,
	setVersionRow,
	stockLegacyOptions,
} = require( './helpers.js' );

/**
 * Fifteen posts, five to a page, so three pages.
 *
 * More than one page is the whole precondition: on a single page the plugin
 * renders nothing at all, correctly, and every front-end assertion below would
 * pass with the migration deleted.
 */
const POSTS = 15;
const PER_PAGE = 5;
const PAGES = Math.ceil( POSTS / PER_PAGE );

/**
 * The navigation the fixture theme shim prints after the main loop.
 *
 * @param {import('@playwright/test').Page} page Page under test.
 * @return {import('@playwright/test').Locator} The wp-pagenavi wrapper.
 */
function nav( page ) {
	return page.locator( '#wp-pagenavi-e2e .wp-pagenavi' );
}

/**
 * What a stock legacy row looks like once the migration has cleaned it.
 *
 * The two toggles are the only difference, and it is worth pinning rather than
 * working around: they ship as PHP booleans and the sanitizer stores integers,
 * so a migrated stock install is *not* byte for byte the defaults array. A test
 * asserting otherwise would be describing the defaults rather than the write.
 *
 * @param {Object} defaults The shipped defaults, as the install reports them.
 * @return {Object} The row the migration should have written.
 */
function migratedStockRow( defaults ) {
	return { ...defaults, always_show: 0, use_pagenavi_css: 1 };
}

test.describe( 'The pre-3.0.0 upgrade', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();

		// posts_per_page is one of the few settings core exposes over REST. Set
		// here as well as in pagenavi.spec.js, so this file's fixture does not
		// depend on which order Playwright happened to run the two in.
		await requestUtils.updateSiteSettings( { posts_per_page: PER_PAGE } );

		// Order does not matter here -- nothing below asserts which post is on
		// which page, only how many pages there are -- so these go out together
		// rather than costing fifteen sequential round trips.
		await Promise.all(
			Array.from( { length: POSTS }, ( _, i ) =>
				requestUtils.createPost( {
					title: `Upgrade fixture post ${ String( i + 1 ).padStart( 2, '0' ) }`,
					content: 'Body.',
					status: 'publish',
				} ),
			),
		);
	} );

	test.afterEach( async () => {
		deleteOptions();
		deleteVersionRow();
		deleteLegacyRow();

		// This is the only file that ever switches the plugin off, and only for
		// as long as it takes to prove the activation hook runs. A failure part
		// way through would otherwise hand every later test a site with no
		// plugin, and the run would report a dozen symptoms of one cause.
		ensurePluginActive();
	} );

	test( 'the fixture really is more than one page', async ( { page } ) => {
		// The precondition every front-end assertion in this file leans on. One
		// page of posts is not a thing to paginate, and a plugin that renders
		// nothing there is behaving correctly.
		await page.goto( '/' );

		await expect( page.locator( 'article' ) ).toHaveCount( PER_PAGE );
		await expect( nav( page ) ).toBeVisible();
	} );

	test( 'a stock 2.94.5 row is folded in, written, deleted and stamped', async ( { page } ) => {
		installLegacyRow( stockLegacyOptions() );

		// The fixture really is a pre-3.0.0 install: old row present, new rows
		// absent. Without this the assertions below could be describing a site
		// that was already migrated, and would pass with migrate() deleted.
		expect( getLegacyRow() ).not.toBe( false );
		expect( getStoredOptions() ).toBe( false );
		expect( getVersionRow() ).toBe( false );

		await page.goto( DASHBOARD_URL );

		// The old row is gone rather than left to rot, so re-running finds
		// nothing to do and a later release has one row to think about.
		expect( getLegacyRow() ).toBe( false );

		// And the row it was folded into is genuinely on disk. This is the
		// assertion §7.6.1 is about: the settings survived here even though the
		// result of the migration is what the defaults would have answered
		// anyway, which is the one case a skipped write hides in.
		const stored = getStoredOptions();

		expect( stored ).not.toBe( false );
		expect( stored ).toEqual( migratedStockRow( defaultOptions() ) );

		// One write, both markers, matching the code that is running.
		expect( getVersionRow() ).toEqual( runningVersions() );

		// Present is not alive. The migrated settings have to be the ones the
		// front end acts on.
		await page.goto( '/' );

		await expect( nav( page ).locator( '.pages' ) ).toHaveText( `Page 1 of ${ PAGES }` );
	} );

	test( "a customised row keeps this site's wording, and is cleaned on the way through", async ( {
		page,
	} ) => {
		// Every release up to 2.94.5 stored the navigation text with no output
		// filtering at all, so a row can hold anything an administrator once
		// pasted into it -- and it was printed to every reader unescaped. The
		// migration re-sanitises what it folds in, which is the only chance the
		// plugin ever gets to clean a row written by a laxer build.
		installLegacyRow(
			stockLegacyOptions( {
				pages_text: 'Batch %CURRENT_PAGE% of %TOTAL_PAGES%',
				prev_text: '<script>window.wpPageNaviE2E = 1;</script>&laquo;',
				num_pages: 9,
			} ),
		);

		await page.goto( DASHBOARD_URL );

		const stored = getStoredOptions();

		expect( stored.pages_text ).toBe( 'Batch %CURRENT_PAGE% of %TOTAL_PAGES%' );
		expect( stored.num_pages ).toBe( 9 );
		expect( stored.prev_text ).not.toContain( '<script' );
		expect( stored.prev_text ).toContain( '&laquo;' );

		await page.goto( '/' );

		await expect( nav( page ).locator( '.pages' ) ).toHaveText( `Batch 1 of ${ PAGES }` );

		// Scoped to the plugin's own container: core prints unescaped values on
		// this same page -- post titles, the admin bar's greeting -- so a
		// page-wide sentinel would be answering for somebody else's markup.
		await expect( page.locator( '#wp-pagenavi-e2e script' ) ).toHaveCount( 0 );
	} );

	test( 'a legacy row never overwrites settings the owner has already saved', async ( {
		page,
	} ) => {
		// The shape an install lands in when it saved something through the new
		// screen and only then met the migration. The newer row is the one the
		// owner has actually seen, so the older one is folded away, not in.
		// Seeded in this order because installLegacyRow() clears the new row,
		// which is the state every other test here wants and this one does not.
		installLegacyRow( stockLegacyOptions( { num_pages: 9 } ) );
		setOptions( { ...defaultOptions(), num_pages: 7 } );

		await page.goto( DASHBOARD_URL );

		expect( getLegacyRow() ).toBe( false );
		expect( getStoredOptions().num_pages ).toBe( 7 );
	} );

	test( 'reactivating migrates the same row, without an admin page in sight', async ( {
		page,
	} ) => {
		installLegacyRow( stockLegacyOptions( { pages_text: 'Set %CURRENT_PAGE% of %TOTAL_PAGES%' } ) );

		// The other entry point, and the one an owner reaches for when something
		// looks wrong. It has to fold the same row in from the same state --
		// and it meets none of the filters register_setting() hangs on the row,
		// because activation never runs it.
		reactivatePlugin();

		expect( getLegacyRow() ).toBe( false );

		const stored = getStoredOptions();

		expect( stored ).not.toBe( false );
		expect( stored.pages_text ).toBe( 'Set %CURRENT_PAGE% of %TOTAL_PAGES%' );
		expect( getVersionRow() ).toEqual( runningVersions() );

		await page.goto( '/' );

		await expect( nav( page ).locator( '.pages' ) ).toHaveText( `Set 1 of ${ PAGES }` );
	} );

	test( 'a second activation, and the admin load after it, change nothing', async ( { page } ) => {
		installLegacyRow( stockLegacyOptions( { num_pages: 4 } ) );

		reactivatePlugin();

		const once = { options: getStoredOptions(), versions: getVersionRow() };

		// Owners deactivate and reactivate to fix things, sometimes twice. The
		// second pass has to be a bystander: the rows it finds are the rows it
		// leaves, and so is the admin_init pass that follows a real update.
		reactivatePlugin();

		expect( getStoredOptions() ).toEqual( once.options );
		expect( getVersionRow() ).toEqual( once.versions );

		await page.goto( DASHBOARD_URL );

		expect( getStoredOptions() ).toEqual( once.options );
		expect( getVersionRow() ).toEqual( once.versions );
	} );

	test( 'an install already on this version is left alone', async ( { page } ) => {
		// A row the sanitizer would rewrite if it ran -- a string where an
		// integer belongs, and keys it would fill in -- alongside markers saying
		// the upgrade has already happened. maybe_upgrade() returning early is
		// what keeps every admin request from being an option write, and the
		// proof it returned early is that this deliberately stale row survives.
		const stale = { pages_text: 'Stale %CURRENT_PAGE%', num_pages: '9' };

		setOptions( stale );
		setVersionRow( runningVersions() );

		await page.goto( DASHBOARD_URL );

		expect( getStoredOptions() ).toEqual( stale );
	} );
} );
