<?php
/**
 * Plugin options.
 *
 * @package WP-PageNavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the plugin's single option row.
 *
 * Replaces the scbOptions object the plugin used before 3.0.0. The option key is
 * deliberately unchanged, so existing installs carry over without a migration.
 */
class PageNavi_Options {

	/**
	 * The option key. Unchanged since the plugin's first release.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'pagenavi_options';

	/**
	 * Option keys whose values are rendered as HTML and so must pass through kses.
	 *
	 * @return array
	 */
	public static function text_keys() {
		return array(
			'pages_text',
			'current_text',
			'page_text',
			'first_text',
			'last_text',
			'prev_text',
			'next_text',
			'dotleft_text',
			'dotright_text',
		);
	}

	/**
	 * Option keys holding a non-negative integer.
	 *
	 * @return array
	 */
	public static function int_keys() {
		return array(
			'style',
			'num_pages',
			'num_larger_page_numbers',
			'larger_page_numbers_multiple',
		);
	}

	/**
	 * Option keys holding a yes/no toggle.
	 *
	 * @return array
	 */
	public static function bool_keys() {
		return array(
			'use_pagenavi_css',
			'always_show',
		);
	}

	/**
	 * The default option values.
	 *
	 * Translated on demand rather than at load time, so this must not be called
	 * before `init` fires.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'pages_text'                   => __( 'Page %CURRENT_PAGE% of %TOTAL_PAGES%', 'wp-pagenavi' ),
			'current_text'                 => '%PAGE_NUMBER%',
			'page_text'                    => '%PAGE_NUMBER%',
			'first_text'                   => __( '&laquo; First', 'wp-pagenavi' ),
			'last_text'                    => __( 'Last &raquo;', 'wp-pagenavi' ),
			'prev_text'                    => __( '&laquo;', 'wp-pagenavi' ),
			'next_text'                    => __( '&raquo;', 'wp-pagenavi' ),
			'dotleft_text'                 => __( '...', 'wp-pagenavi' ),
			'dotright_text'                => __( '...', 'wp-pagenavi' ),
			'num_pages'                    => 5,
			'num_larger_page_numbers'      => 3,
			'larger_page_numbers_multiple' => 10,
			'always_show'                  => false,
			'use_pagenavi_css'             => true,
			'style'                        => 1,
		);
	}

	/**
	 * Get the stored options, merged over the defaults.
	 *
	 * Merging on read is what lets an install upgraded from an older version pick
	 * up keys that did not exist when its row was written.
	 *
	 * @param string|null $key Optional single key to return.
	 * @return mixed The full option array, or one value, or null for an unknown key.
	 */
	public static function get( $key = null ) {
		$stored  = get_option( self::OPTION_NAME, array() );
		$options = wp_parse_args( is_array( $stored ) ? $stored : array(), self::get_defaults() );

		if ( null === $key ) {
			return $options;
		}

		return isset( $options[ $key ] ) ? $options[ $key ] : null;
	}

	/**
	 * Replace the stored options.
	 *
	 * @param array $options Option values.
	 * @return bool Whether the option row changed.
	 */
	public static function update( array $options ) {
		return update_option( self::OPTION_NAME, $options );
	}
}
