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

		// Activation does not fire on a plugin update, which is the single most
		// common reason a migration never runs.
		add_action( 'init', array( 'WP_PageNavi_Options', 'maybe_upgrade' ), 5 );

		WP_PageNavi_Core::init();

		if ( is_admin() ) {
			require_once WP_PAGENAVI_DIR . 'includes/class-wp-pagenavi-settings.php';

			WP_PageNavi_Settings::init();
		}
	}

	/**
	 * Activation: run the upgrade routine so the rows are in their current shape.
	 *
	 * That folds in the pre-3.0.0 settings row and stamps the version markers. The
	 * plugin works correctly with no settings row at all, because the options are
	 * merged over the defaults on every read, so this is about carrying an existing
	 * install forward rather than about seeding a new one.
	 *
	 * The network branch matters because the settings live per site. Without it a
	 * network activation upgrades only whichever site happened to be current, and
	 * every other site serves its front end with the defaults until its own next
	 * request runs the upgrade.
	 *
	 * @param bool $network_wide Whether the plugin is being activated network-wide.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			// 'number' => 0 lifts WP_Site_Query's default cap of 100, which would otherwise skip every site past the hundredth while reporting success.
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				WP_PageNavi_Options::maybe_upgrade();
				// Inside the loop: switch_to_blog() pushes onto a stack, so restoring once after the loop unwinds it by exactly one.
				restore_current_blog();
			}

			return;
		}

		WP_PageNavi_Options::maybe_upgrade();
	}
}
