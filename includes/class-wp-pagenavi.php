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

		WP_PageNavi_Core::init();

		if ( is_admin() ) {
			require_once WP_PAGENAVI_DIR . 'includes/class-wp-pagenavi-admin.php';

			WP_PageNavi_Admin::init();
		}
	}
}
