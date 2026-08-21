<?php
/**
 * Shared base class for the WP-PageNavi test cases.
 *
 * @package WP-PageNavi
 */

/**
 * Clears the plugin's stored rows and builds the fixtures the suite shares.
 */
abstract class WP_PageNavi_TestCase extends WP_UnitTestCase {

	/**
	 * Creates a user who may actually reach the plugin's screens.
	 *
	 * The settings screen takes `manage_options`, which core's map_meta_cap()
	 * does not touch under multisite, so no grant_super_admin() here: a site
	 * administrator holds it on a network exactly as on a single site. Granting
	 * anyway would make the fixture stop representing the operator this plugin
	 * actually has and hide the very class of bug §7.2.2 is about.
	 *
	 * Every administrator the suite creates goes through this, so the network
	 * question is answered in one place rather than at each call site. Tests
	 * that assert the *unprivileged* path set their own subscriber or editor
	 * explicitly and must not be routed through here.
	 *
	 * @return int The new user's ID.
	 */
	protected function create_admin() {
		return self::factory()->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Start every test from a fresh install: no settings, no version markers and
	 * no leftover pre-3.0.0 row.
	 *
	 * The options are merged over the defaults on every read, so a row left
	 * behind by one test is invisible until it changes an assertion in another,
	 * which is the worst way to find it.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		delete_option( WP_PageNavi_Options::OPTION );
		delete_option( WP_PageNavi_Options::VERSION );
		delete_option( WP_PageNavi_Options::LEGACY_OPTION );
	}

	/**
	 * Store the defaults with some keys overridden.
	 *
	 * @param array $overrides Values to change.
	 * @return void
	 */
	protected function set_options( array $overrides = array() ) {
		WP_PageNavi_Options::update( array_merge( WP_PageNavi_Options::get_defaults(), $overrides ) );
	}

	/**
	 * A posts query at a given page.
	 *
	 * @param int $paged    Page number.
	 * @param int $per_page Posts per page.
	 * @return WP_Query
	 */
	protected function query( $paged, $per_page = 5 ) {
		return new WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'paged'          => $paged,
			)
		);
	}

	/**
	 * Run the uninstaller, however many times a suite asks for it.
	 *
	 * The uninstaller does its work in the file body, and PHP will not run a
	 * file body twice -- so the first caller in a process gets the real thing
	 * and any later one would silently get nothing at all. The require is
	 * therefore only there to guarantee the function exists, and the fan-out is
	 * driven from here: the same loop the file itself runs, with the same
	 * arguments.
	 *
	 * @return void
	 */
	protected function run_uninstall() {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'wp-pagenavi/wp-pagenavi.php' );
		}

		require_once dirname( __DIR__ ) . '/uninstall.php';

		if ( is_multisite() ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				wp_pagenavi_uninstall_site();
				restore_current_blog();
			}

			return;
		}

		wp_pagenavi_uninstall_site();
	}

	/**
	 * Every option row the plugin owns, read straight from the table.
	 *
	 * There is no API for "which rows exist", and asking the table is the point:
	 * a row added later and forgotten in uninstall.php is exactly the failure
	 * this is here to catch.
	 *
	 * @return string[]
	 */
	protected function stored_option_names() {
		global $wpdb;

		return (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( 'wp_pagenavi_' ) . '%'
			)
		);
	}
}
