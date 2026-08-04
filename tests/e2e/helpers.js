/**
 * Shared fixtures for the WP-PageNavi end-to-end suite.
 *
 * Everything here writes to or reads from the two option rows directly, through
 * WP-CLI, because that is the only way to build the shapes these tests need: a
 * row as the released 2.94.5 wrote it, a row the sanitizer has never seen, and
 * an install with no version markers at all. None of the three can be produced
 * through the settings screen, and the migration tests are about precisely the
 * installs a person never typed.
 *
 * The rows are read *raw* on the way back. WP_PageNavi_Options::get() merges the
 * defaults over whatever is stored, so it answers the same array for a row
 * holding the defaults and for no row at all -- and "no row at all" is exactly
 * what a broken migration leaves behind. Ask the database, not the plugin.
 */

const { execFileSync } = require( 'child_process' );
const path = require( 'path' );

/** The plugin root, which is where wp-env reads .wp-env.json from. */
const PLUGIN_ROOT = path.join( __dirname, '../..' );

/** The settings screen, under Settings. */
const SETTINGS_URL = '/wp-admin/options-general.php?page=wp-pagenavi';

/** The Dashboard: an ordinary admin request, which is what an update goes through. */
const DASHBOARD_URL = '/wp-admin/index.php';

/** The option row every setting lives in. */
const OPTION = 'wp_pagenavi_options';

/** The option row holding the two upgrade markers. */
const VERSION_OPTION = 'wp_pagenavi_version';

/** The settings row every release up to 2.94.5 used. */
const LEGACY_OPTION = 'pagenavi_options';

/**
 * Run PHP inside the tests environment and hand back what it printed.
 *
 * The code is base64'd rather than passed as itself: a navigation label holding
 * quotes, angle brackets and a script tag is exactly the sort of string that
 * arrives at the other end subtly different, and a fixture that is not the
 * payload byte for byte proves nothing about sanitising it.
 *
 * @param {string} code PHP to evaluate, without an opening tag.
 * @return {string} Whatever the code echoed between its markers.
 */
function wpEval( code ) {
	const encoded = Buffer.from( code, 'utf8' ).toString( 'base64' );

	const output = execFileSync(
		'npx',
		[
			'--yes',
			'@wordpress/env',
			'run',
			'tests-cli',
			'wp',
			'eval',
			`eval( base64_decode( '${ encoded }' ) );`,
		],
		{ cwd: PLUGIN_ROOT, encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'pipe' ] },
	);

	// wp-env prints its own progress around the command's output, so the code
	// wraps what it wants to return in markers rather than the caller trying to
	// tell the two apart by position.
	const matched = output.match( /<<<([\s\S]*?)>>>/ );

	return matched ? matched[ 1 ] : '';
}

/**
 * Store a value in an option row exactly as given, sanitizer untouched.
 *
 * @param {string} option Option name.
 * @param {*}      value  Anything JSON can carry.
 * @return {void}
 */
function setRow( option, value ) {
	const data = Buffer.from( JSON.stringify( value ), 'utf8' ).toString( 'base64' );

	wpEval(
		`update_option( '${ option }', json_decode( base64_decode( '${ data }' ), true ) );
		echo '<<<done>>>';`,
	);
}

/**
 * An option row as the database holds it, with no defaults merged in.
 *
 * @param {string} option Option name.
 * @return {*} The stored value, or false when there is no row.
 */
function getRow( option ) {
	return JSON.parse( wpEval( `echo '<<<' . wp_json_encode( get_option( '${ option }' ) ) . '>>>';` ) );
}

/**
 * Write the settings row, going nowhere near the settings screen.
 *
 * @param {Object} options Option keys to store.
 * @return {void}
 */
function setOptions( options ) {
	setRow( OPTION, options );
}

/**
 * The settings row as the database holds it.
 *
 * @return {Object|false} The stored array, or false when there is no row.
 */
function getStoredOptions() {
	return getRow( OPTION );
}

/**
 * Remove the settings row, which is the state a fresh install is in.
 *
 * @return {void}
 */
function deleteOptions() {
	wpEval( `delete_option( '${ OPTION }' ); echo '<<<done>>>';` );
}

/**
 * The defaults the running code would fall back to.
 *
 * Asked of the install rather than transcribed, because a default copied into a
 * test file is a second place holding one fact -- which is how a suite ends up
 * asserting last year's defaults and passing.
 *
 * @return {Object} The default option array.
 */
function defaultOptions() {
	return JSON.parse(
		wpEval( "echo '<<<' . wp_json_encode( WP_PageNavi_Options::get_defaults() ) . '>>>';" ),
	);
}

/**
 * The settings row a stock 2.94.5 install carried.
 *
 * Every default has said the same thing since the scbOptions days, and the key
 * set is unchanged, so the shipped defaults *are* the untouched legacy row.
 * Derived from the running code rather than transcribed, so a default that
 * changes cannot leave a fixture behind describing a release nobody can diff
 * against any more.
 *
 * This is the fixture the §7.6.1 shape needs and a customised one cannot
 * replace: the migration's result equals the defaults here, which is the one
 * case where a write can be skipped and every reader still answers correctly
 * while the legacy row is deleted from under it.
 *
 * @param {Object} overrides Anything this particular site had changed.
 * @return {Object} A legacy settings row.
 */
function stockLegacyOptions( overrides = {} ) {
	return { ...defaultOptions(), ...overrides };
}

/**
 * Put the install back into the shape a pre-3.0.0 site is in.
 *
 * The prefixed rows go away entirely and the unprefixed one takes their place,
 * because that is what the migration has to meet: a row called
 * `pagenavi_options`, no version markers, and nothing else.
 *
 * @param {Object} legacy The legacy settings row, exactly as given.
 * @return {void}
 */
function installLegacyRow( legacy ) {
	const data = Buffer.from( JSON.stringify( legacy ), 'utf8' ).toString( 'base64' );

	wpEval(
		`delete_option( '${ OPTION }' );
		delete_option( '${ VERSION_OPTION }' );
		update_option( '${ LEGACY_OPTION }', json_decode( base64_decode( '${ data }' ), true ) );
		echo '<<<done>>>';`,
	);
}

/**
 * The pre-3.0.0 settings row, false once the migration has folded it in.
 *
 * @return {Object|false} The stored array, or false.
 */
function getLegacyRow() {
	return getRow( LEGACY_OPTION );
}

/**
 * Remove the legacy row without touching anything else.
 *
 * @return {void}
 */
function deleteLegacyRow() {
	wpEval( `delete_option( '${ LEGACY_OPTION }' ); echo '<<<done>>>';` );
}

/**
 * The upgrade markers, as the database holds them.
 *
 * @return {Object|false} The stored array, or false when there is no row.
 */
function getVersionRow() {
	return getRow( VERSION_OPTION );
}

/**
 * Stamp the upgrade markers.
 *
 * @param {Object} versions The two markers.
 * @return {void}
 */
function setVersionRow( versions ) {
	setRow( VERSION_OPTION, versions );
}

/**
 * Remove the version row, which is the state an unstamped install is in.
 *
 * @return {void}
 */
function deleteVersionRow() {
	wpEval( `delete_option( '${ VERSION_OPTION }' ); echo '<<<done>>>';` );
}

/**
 * The version numbers the running code expects to find stamped.
 *
 * @return {{plugin: string, db: string}} The two markers.
 */
function runningVersions() {
	return JSON.parse(
		wpEval(
			`echo '<<<' . wp_json_encode( array(
				'plugin' => WP_PAGENAVI_VERSION,
				'db'     => WP_PAGENAVI_DB_VERSION,
			) ) . '>>>';`,
		),
	);
}

/**
 * Deactivate and reactivate the plugin, which is the path that fires activate().
 *
 * Genuinely a different entry point from the admin_init one: updating through
 * the Plugins screen never fires the activation hook, and activation never runs
 * register_setting() -- so this path meets none of the filters the admin path
 * hangs on the settings row.
 *
 * @return {void}
 */
function reactivatePlugin() {
	wpEval(
		`require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( 'wp-pagenavi/wp-pagenavi.php' );
		activate_plugin( 'wp-pagenavi/wp-pagenavi.php' );
		echo '<<<done>>>';`,
	);
}

/**
 * Put the plugin back on, whatever a test left behind.
 *
 * @return {void}
 */
function ensurePluginActive() {
	wpEval(
		`require_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( ! is_plugin_active( 'wp-pagenavi/wp-pagenavi.php' ) ) {
			activate_plugin( 'wp-pagenavi/wp-pagenavi.php' );
		}
		echo '<<<done>>>';`,
	);
}

module.exports = {
	DASHBOARD_URL,
	LEGACY_OPTION,
	OPTION,
	SETTINGS_URL,
	VERSION_OPTION,
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
	wpEval,
};
