<?php
/**
 * Uninstaller tests.
 *
 * The behavioural half of this -- run the uninstaller, assert nothing matching
 * wp_pagenavi_% survives -- lives in test-metadata.php, because uninstall.php is
 * pulled in with require_once and so can only ever execute once per process. A
 * second test file requiring it would silently include nothing and then assert
 * against rows that nobody had removed.
 *
 * What is left here is what that test cannot see: the shape of the file itself.
 *
 * @package WP-PageNavi
 */

/**
 * Covers uninstall.php.
 */
class WP_PageNavi_Uninstall_Test extends WP_PageNavi_TestCase {

	/**
	 * The uninstaller's source.
	 *
	 * @return string
	 */
	protected function uninstaller() {
		return wp_pagenavi_test_read( 'uninstall.php' );
	}

	/**
	 * The multisite branch must ask for every site, not the first hundred.
	 *
	 * WP_Site_Query defaults 'number' to 100, so a network larger than that would
	 * silently leave the options behind on every site past the hundredth. This is
	 * asserted against the source rather than by building a 101-site network,
	 * which the suite cannot do; the assertion exists so the argument cannot be
	 * dropped again without a failure.
	 *
	 * @return void
	 */
	public function test_multisite_uninstall_asks_for_every_site() {
		$this->assertMatchesRegularExpression(
			"/'number'\s*=>\s*0/",
			$this->uninstaller(),
			"uninstall.php must pass 'number' => 0 to get_sites(), or it stops at 100 sites."
		);
	}

	/**
	 * Every row the plugin can create is named in the uninstaller, including the
	 * pre-3.0.0 one an install that never reached wp-admin still holds.
	 *
	 * @return void
	 */
	public function test_the_uninstaller_names_every_row_the_plugin_creates() {
		$source = $this->uninstaller();

		foreach ( array( 'wp_pagenavi_options', 'wp_pagenavi_version', 'pagenavi_options' ) as $row ) {
			$this->assertStringContainsString(
				"delete_option( '{$row}' )",
				$source,
				"uninstall.php must delete the {$row} row."
			);
		}
	}

	/**
	 * Loading the file outside an uninstall must do nothing at all.
	 *
	 * @return void
	 */
	public function test_the_uninstaller_refuses_to_run_unless_wordpress_asked_for_it() {
		$this->assertStringContainsString(
			"defined( 'WP_UNINSTALL_PLUGIN' ) || exit;",
			$this->uninstaller(),
			'uninstall.php must be guarded, or a direct request would wipe the settings.'
		);
	}

	/**
	 * Switching site pushes onto a stack, so the restore has to happen inside
	 * the loop. Left outside it, every site but the last is uninstalled against
	 * the wrong tables.
	 *
	 * @return void
	 */
	public function test_the_site_loop_restores_the_blog_each_time_round() {
		$source = $this->uninstaller();
		$start  = (int) strpos( $source, 'foreach ( $site_ids' );
		$loop   = substr( $source, $start, (int) strpos( $source, '} else {' ) - $start );

		$this->assertStringContainsString( 'switch_to_blog(', $loop, 'The site loop switches per site.' );
		$this->assertStringContainsString( 'restore_current_blog();', $loop, 'And restores each time round, rather than once after the loop.' );
	}
}
