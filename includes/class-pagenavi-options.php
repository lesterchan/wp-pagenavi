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
	 * Tags and attributes permitted inside the navigation text options.
	 *
	 * This is wp_kses_post()'s list plus the inline SVG elements. Themes commonly
	 * use an inline SVG for the previous and next arrows, and wp_kses_post()
	 * removes an SVG completely rather than cleaning it — which leaves the text
	 * empty, and PageNavi_Call::get_single() drops a link whose text is empty. The
	 * result is that the whole link disappears from the page, not just the icon.
	 *
	 * Script tags, event-handler attributes and unsafe protocols are still
	 * removed, because only what is listed here survives.
	 *
	 * @return array A wp_kses() style array of allowed tags and attributes.
	 */
	public static function allowed_html() {
		$allowed = wp_kses_allowed_html( 'post' );

		// Presentation attributes shared by every SVG element below.
		$common = array(
			'class'             => true,
			'id'                => true,
			'style'             => true,
			'role'              => true,
			'aria-hidden'       => true,
			'aria-label'        => true,
			'focusable'         => true,
			'fill'              => true,
			'fill-rule'         => true,
			'fill-opacity'      => true,
			'clip-rule'         => true,
			'stroke'            => true,
			'stroke-width'      => true,
			'stroke-linecap'    => true,
			'stroke-linejoin'   => true,
			'stroke-miterlimit' => true,
			'stroke-dasharray'  => true,
			'stroke-dashoffset' => true,
			'stroke-opacity'    => true,
			'opacity'           => true,
			'transform'         => true,
			'vector-effect'     => true,
		);

		// kses lowercases attribute names, so viewBox has to be listed as viewbox.
		// It still renders: HTML parsers case-correct SVG attributes in foreign
		// content, so the attribute comes back off the element as viewBox.
		$allowed['svg']      = array_merge(
			$common,
			array(
				'xmlns'               => true,
				'width'               => true,
				'height'              => true,
				'viewbox'             => true,
				'preserveaspectratio' => true,
				'x'                   => true,
				'y'                   => true,
			)
		);
		$allowed['g']        = $common;
		$allowed['defs']     = $common;
		$allowed['desc']     = $common;
		$allowed['title']    = $common;
		$allowed['symbol']   = array_merge( $common, array( 'viewbox' => true ) );
		$allowed['path']     = array_merge(
			$common,
			array(
				'd'          => true,
				'pathlength' => true,
			)
		);
		$allowed['polygon']  = array_merge( $common, array( 'points' => true ) );
		$allowed['polyline'] = array_merge( $common, array( 'points' => true ) );
		$allowed['line']     = array_merge(
			$common,
			array(
				'x1' => true,
				'y1' => true,
				'x2' => true,
				'y2' => true,
			)
		);
		$allowed['rect']     = array_merge(
			$common,
			array(
				'x'      => true,
				'y'      => true,
				'width'  => true,
				'height' => true,
				'rx'     => true,
				'ry'     => true,
			)
		);
		$allowed['circle']   = array_merge(
			$common,
			array(
				'cx' => true,
				'cy' => true,
				'r'  => true,
			)
		);
		$allowed['ellipse']  = array_merge(
			$common,
			array(
				'cx' => true,
				'cy' => true,
				'rx' => true,
				'ry' => true,
			)
		);
		$allowed['use']      = array_merge(
			$common,
			array(
				'href'       => true,
				'xlink:href' => true,
				'x'          => true,
				'y'          => true,
				'width'      => true,
				'height'     => true,
			)
		);

		/**
		 * Filters the tags allowed inside the WP-PageNavi navigation text options.
		 *
		 * Note that wp_kses() only protocol-filters the attribute names returned by
		 * wp_kses_uri_attributes(). If you add an attribute here that carries a URL,
		 * add its name to that list too, or it becomes an unfiltered URI sink.
		 *
		 * @since 3.0.0
		 *
		 * @param array $allowed A wp_kses() style array of allowed tags and attributes.
		 */
		return apply_filters( 'wp_pagenavi_allowed_html', $allowed );
	}

	/**
	 * Clean a navigation text value.
	 *
	 * The wp_kses() call only protocol-filters the attribute names listed by
	 * wp_kses_uri_attributes(), and xlink:href is not one of them — so allowing
	 * that attribute without this would let xlink:href="javascript:…" through the
	 * filter that stops the identical payload in a plain href. The attribute is
	 * worth supporting because sprite-based icon sets still emit it, so instead of
	 * dropping it the URI list is widened for the duration of this one call.
	 *
	 * @param string $value Raw text option value.
	 * @return string
	 */
	public static function kses( $value ) {
		// A text option is always a string. Anything else can only arrive from a
		// hand-crafted request posting pagenavi_options[prev_text][] to options.php,
		// and casting an array to string is both meaningless and a PHP 8 notice.
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$add_xlink = static function ( $attributes ) {
			$attributes[] = 'xlink:href';
			return $attributes;
		};

		add_filter( 'wp_kses_uri_attributes', $add_xlink );

		try {
			return wp_kses( (string) $value, self::allowed_html() );
		} finally {
			// Always scoped to this call; the global list must not be widened.
			remove_filter( 'wp_kses_uri_attributes', $add_xlink );
		}
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
