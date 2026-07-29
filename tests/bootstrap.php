<?php
/**
 * PHPUnit bootstrap: loads the plugin into the WordPress test suite.
 *
 * @package WP-PageNavi
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	// Where wp-env mounts the WordPress test library.
	$_tests_dir = '/wordpress-phpunit';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find the WordPress test library at {$_tests_dir}." . PHP_EOL;
	echo 'Run the suite through bin/test.sh, or set WP_TESTS_DIR.' . PHP_EOL;
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Load the plugin, and its admin half, which the plugin itself only loads on
 * admin requests.
 *
 * @return void
 */
function _wp_pagenavi_manually_load_plugin() {
	require dirname( __DIR__ ) . '/wp-pagenavi.php';
	require_once dirname( __DIR__ ) . '/includes/class-wp-pagenavi-admin.php';
}
tests_add_filter( 'muplugins_loaded', '_wp_pagenavi_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

// After the test library, not before: the base class extends WP_UnitTestCase,
// which does not exist until the bootstrap above has run.
require_once __DIR__ . '/helper-source.php';
require_once __DIR__ . '/helper-testcase.php';
