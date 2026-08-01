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
	 * Store one option value over the defaults.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Option value.
	 * @return void
	 */
	protected function set_option( $key, $value ) {
		$options         = WP_PageNavi_Options::get_defaults();
		$options[ $key ] = $value;

		WP_PageNavi_Options::update( $options );
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
	 * The uninstaller declares a global function, so a second require would
	 * fatal on redeclare and a require_once that has already fired proves
	 * nothing. Calling the function directly once it exists is the repeatable
	 * form. Nothing here touches schema, so including the file is safe for the
	 * first caller.
	 *
	 * @return void
	 */
	protected function run_uninstall() {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'wp-pagenavi/wp-pagenavi.php' );
		}

		if ( function_exists( 'wp_pagenavi_uninstall_site' ) ) {
			wp_pagenavi_uninstall_site();

			return;
		}

		require dirname( __DIR__ ) . '/uninstall.php';
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
