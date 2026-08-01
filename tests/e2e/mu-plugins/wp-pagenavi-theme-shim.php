<?php
/**
 * Plugin Name: WP-PageNavi E2E theme shim
 * Description: Calls wp_pagenavi() where a theme would, so the browser suite has something to look at. Loaded only in the wp-env tests environment.
 *
 * WP-PageNavi is a template tag. It hooks nothing into the front end by design:
 * a theme calls wp_pagenavi() in place of the_posts_navigation(), and if no
 * theme calls it the plugin renders nothing at all -- correctly.
 *
 * That makes the theme the integration point, and a bundled theme does not call
 * it. So this file plays the part of a theme that does. Without it every
 * front-end assertion in the suite would be checking that an empty page is
 * still empty, and would pass whatever the plugin did.
 *
 * It is a fixture, not a shipped file: it lives under tests/ and is mapped into
 * wp-content/mu-plugins for the tests environment only, by .wp-env.json.
 *
 * @package WP-PageNavi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Web requests only.
 *
 * wp-env maps this directory into the tests environment, and PHPUnit runs in
 * that same environment -- so without this guard the fixture below is loaded by
 * the unit suite as well, and a filter forcing comment paging on made
 * test_paging_disabled_renders_nothing fail while the plugin was behaving
 * perfectly. A browser fixture has no business being visible to a test that is
 * not driving a browser.
 */
if ( 'cli' === PHP_SAPI ) {
	return;
}

/**
 * Print the navigation after the main loop, as a theme's index.php would.
 *
 * The loop_end hook fires for every WP_Query, including the secondary ones a
 * theme runs for sidebars and related posts, so this is confined to the main
 * query on a listing page. Printing it for each of them would put three
 * navigations on the page and make "the pagination" an ambiguous thing to
 * select.
 *
 * @param WP_Query $query The query that has just finished looping.
 * @return void
 */
function wp_pagenavi_e2e_after_loop( $query ) {
	if ( ! $query->is_main_query() || is_singular() ) {
		return;
	}

	echo '<div id="wp-pagenavi-e2e">';
	wp_pagenavi();
	echo '</div>';
}
add_action( 'loop_end', 'wp_pagenavi_e2e_after_loop' );

/**
 * A route the suite calls to put the settings back.
 *
 * Deleting the row rather than writing the defaults into it: the plugin merges
 * whatever is stored over its own defaults on read, so "absent" is the true
 * starting state, and a copy of the defaults kept in a test file is a second
 * place holding one fact -- which is how a test ends up asserting last year's
 * defaults.
 *
 * The plugin's own option is not exposed through the REST API, correctly: it is
 * a settings row for an admin screen, not content. This route exists only in the
 * tests environment, and requires the same capability the settings screen does.
 *
 * @return void
 */
function wp_pagenavi_e2e_register_reset_route() {
	register_rest_route(
		'wp-pagenavi-e2e/v1',
		'/reset',
		array(
			'methods'             => 'POST',
			'callback'            => static function () {
				delete_option( 'wp_pagenavi_options' );

				return array( 'reset' => true );
			},
			'permission_callback' => static function () {
				return current_user_can( 'manage_options' );
			},
		)
	);
}
add_action( 'rest_api_init', 'wp_pagenavi_e2e_register_reset_route' );
