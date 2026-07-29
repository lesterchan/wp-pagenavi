<?php
/**
 * WP-PageNavi bootstrap.
 *
 * @package WP-PageNavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads the plugin's components and hands each of them their hooks.
 *
 * Everything the plugin does hangs off this one entry point, so the main file
 * carries nothing but the header, the constants and a single call.
 */
class WP_PageNavi {

	/**
	 * Load the components and wire them up.
	 *
	 * @return void
	 */
	public static function init() {
		require_once WP_PAGENAVI_DIR . 'includes/class-wp-pagenavi-options.php';
		require_once WP_PAGENAVI_DIR . 'includes/class-wp-pagenavi-call.php';
		require_once WP_PAGENAVI_DIR . 'includes/class-wp-pagenavi-core.php';
		require_once WP_PAGENAVI_DIR . 'includes/template-tags.php';

		// Must be registered at file-load time, which is when this runs.
		register_activation_hook( WP_PAGENAVI_MAIN_FILE, array( __CLASS__, 'activate' ) );

		WP_PageNavi_Core::init();

		if ( is_admin() ) {
			require_once WP_PAGENAVI_DIR . 'includes/class-wp-pagenavi-settings.php';

			WP_PageNavi_Settings::init();
		}
	}

	/**
	 * Activation: run the upgrade routine so the version row is stamped.
	 *
	 * @return void
	 */
	public static function activate() {
		WP_PageNavi_Options::maybe_upgrade();
	}
}
