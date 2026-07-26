<?php
/**
 * Uninstaller: removes everything the plugin stored.
 *
 * @package WP-PageNavi
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Delete the plugin's options for the current site.
 *
 * @return void
 */
function wp_pagenavi_uninstall_site() {
	delete_option( 'pagenavi_options' );
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $site_id ) {
		switch_to_blog( (int) $site_id );
		wp_pagenavi_uninstall_site();
		restore_current_blog();
	}
} else {
	wp_pagenavi_uninstall_site();
}
