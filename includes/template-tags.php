<?php
/**
 * Public template tags.
 *
 * These are the plugin's API. Their names and signatures are relied on by themes
 * and must not change.
 *
 * @package WP-PageNavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Template tag: page navigation.
 *
 * Accepts either an array of arguments or, for backwards compatibility, up to
 * three positional arguments: $before, $after, $options.
 *
 * @param array|string $args {
 *     Optional. Arguments, or the 'before' string in the positional form.
 *
 *     @type string       $before        Markup to place before the navigation.
 *     @type string       $after         Markup to place after the navigation.
 *     @type string       $wrapper_tag   Tag name for the wrapper element. Default 'div'.
 *     @type string       $wrapper_class Class for the wrapper element. Default 'wp-pagenavi'.
 *     @type array        $options       Overrides for the settings saved in WP-Admin.
 *     @type object       $query         Query to paginate. Default the main query.
 *     @type string       $type          One of 'posts', 'multipart' or 'users'. Default 'posts'.
 *     @type bool         $echo          Whether to print the markup. Default true.
 * }
 * @return string|void The markup when 'echo' is false, otherwise nothing.
 */
function wp_pagenavi( $args = array() ) {
	if ( ! is_array( $args ) ) {
		$argv = func_get_args();

		$args = array();
		foreach ( array( 'before', 'after', 'options' ) as $i => $key ) {
			$args[ $key ] = isset( $argv[ $i ] ) ? $argv[ $i ] : '';
		}
	}

	return PageNavi_Core::render( $args );
}

/**
 * Template tag: drop down menu.
 *
 * @deprecated 2.80 Use wp_pagenavi() with the 'style' option set to 2.
 *
 * @return void
 */
function wp_pagenavi_dropdown() {
	wp_pagenavi();
}

if ( ! function_exists( 'get_multipage_link' ) ) :
	/**
	 * Get the URL for a page of a multipart post.
	 *
	 * @link https://core.trac.wordpress.org/ticket/16973
	 *
	 * @param int $page Page number.
	 * @return string
	 */
	function get_multipage_link( $page = 1 ) {
		global $post, $wp_rewrite;

		if ( 1 === (int) $page ) {
			return get_permalink();
		}

		if ( '' === get_option( 'permalink_structure' ) || in_array( $post->post_status, array( 'draft', 'pending' ), true ) ) {
			$url = add_query_arg( 'page', $page, get_permalink() );
		} elseif ( 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === (int) $post->ID ) {
			$url = trailingslashit( get_permalink() ) . user_trailingslashit( $wp_rewrite->pagination_base . "/$page", 'single_paged' );
		} else {
			$url = trailingslashit( get_permalink() ) . user_trailingslashit( $page, 'single_paged' );
		}

		return $url;
	}
endif;
