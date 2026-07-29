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
	delete_option( 'wp_pagenavi_options' );
	delete_option( 'wp_pagenavi_version' );

	// The settings row was named pagenavi_options up to 2.94.6. It is deleted by
	// the upgrade routine, so this only catches an install that never reached
	// wp-admin between updating and being removed.
	delete_option( 'pagenavi_options' );
}

if ( is_multisite() ) {
	// 'number' => 0 is required: WP_Site_Query defaults to 100, so without it a
	// network larger than that would leave the options behind on every site past
	// the hundredth.
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
} else {
	wp_pagenavi_uninstall_site();
}
