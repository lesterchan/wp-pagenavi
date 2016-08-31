<?php
/*
 * Uninstall plugin
 */
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

$option_names = array(
	'pagenavi_options'
);


if ( is_multisite() ) {
	$ms_sites = function_exists( 'get_sites' ) ? get_sites() : wp_get_sites();

	if( 0 < sizeof( $ms_sites ) ) {
		foreach ( $ms_sites as $ms_site ) {
			$blog_id = isset( $ms_site['blog_id'] ) ? $ms_site['blog_id'] : $ms_site->blog_id;
			switch_to_blog( $blog_id );
			if( sizeof( $option_names ) > 0 ) {
				foreach( $option_names as $option_name ) {
					delete_option( $option_name );
					plugin_uninstalled();
				}
			}
		}
	}

	restore_current_blog();
} else {
	if( sizeof( $option_names ) > 0 ) {
		foreach( $option_names as $option_name ) {
			delete_option( $option_name );
			plugin_uninstalled();
		}
	}
}

/**
 * Delete plugin table when uninstalled
 *
 * @access public
 * @return void
 */
function plugin_uninstalled() {
	global $wpdb;
}
