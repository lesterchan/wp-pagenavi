<?php
/**
 * Uninstaller tests.
 *
 * @package WP-PageNavi
 */

/**
 * Covers uninstall.php.
 */
class Test_PageNavi_Uninstall extends WP_UnitTestCase {

	/**
	 * Running the uninstaller removes both of the plugin's rows, and the
	 * pre-3.0.0 row an install that never reached wp-admin might still hold.
	 *
	 * @return void
	 */
	public function test_uninstall_deletes_every_row_the_plugin_owns() {
		WP_PageNavi_Options::update( WP_PageNavi_Options::get_defaults() );
		WP_PageNavi_Options::maybe_upgrade();
		update_option( WP_PageNavi_Options::LEGACY_OPTION, array( 'num_pages' => 5 ) );

		$this->assertIsArray( get_option( WP_PageNavi_Options::OPTION ) );
		$this->assertIsArray( get_option( WP_PageNavi_Options::VERSION ) );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'wp-pagenavi/wp-pagenavi.php' );
		}
		require_once dirname( __DIR__ ) . '/uninstall.php';

		$this->assertFalse( get_option( WP_PageNavi_Options::OPTION ) );
		$this->assertFalse( get_option( WP_PageNavi_Options::VERSION ) );
		$this->assertFalse( get_option( WP_PageNavi_Options::LEGACY_OPTION ) );
	}

	/**
	 * The multisite branch must ask for every site, not the first hundred.
	 *
	 * WP_Site_Query defaults 'number' to 100, so a network larger than that would
	 * silently leave the option behind on every site past the hundredth. This is
	 * asserted against the source rather than by building a 101-site network,
	 * which the single-site suite cannot do; the assertion exists so the argument
	 * cannot be dropped again without a failure.
	 *
	 * @return void
	 */
	public function test_multisite_uninstall_asks_for_every_site() {
		$source = file_get_contents( dirname( __DIR__ ) . '/uninstall.php' );

		$this->assertMatchesRegularExpression(
			"/'number'\s*=>\s*0/",
			$source,
			"uninstall.php must pass 'number' => 0 to get_sites(), or it stops at 100 sites."
		);
	}
}
