<?php
/**
 * Plugin options.
 *
 * @package WP-PageNavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the plugin's two option rows.
 *
 * Replaces the scbOptions object the plugin used before 3.0.0. Settings live in
 * one row and the version markers in another, so the settings screen and the
 * upgrade routine can never overwrite each other's work.
 */
class WP_PageNavi_Options {

	/**
	 * The option row holding every setting, as a nested array.
	 *
	 * @var string
	 */
	const OPTION = 'wp_pagenavi_options';

	/**
	 * The option row holding the 'plugin' and 'db' version markers.
	 *
	 * @var string
	 */
	const VERSION = 'wp_pagenavi_version';

	/**
	 * The settings row every release up to 2.94.6 wrote.
	 *
	 * The rename happens in 3.0.0, so this is seen on every install upgrading
	 * from a shipped version as well as on one that ran a 3.0.0 development
	 * build.
	 *
	 * @var string
	 */
	const LEGACY_OPTION = 'pagenavi_options';

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
	 * empty, and WP_PageNavi_Call::get_single() drops a link whose text is empty. The
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
		$stored  = get_option( self::OPTION, array() );
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
		return update_option( self::OPTION, $options );
	}

	/**
	 * Get the version markers.
	 *
	 * @return array The 'plugin' and 'db' markers, each an empty string when unset.
	 */
	public static function get_versions() {
		$stored = get_option( self::VERSION, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array(
			'plugin' => isset( $stored['plugin'] ) ? (string) $stored['plugin'] : '',
			'db'     => isset( $stored['db'] ) ? (string) $stored['db'] : '',
		);
	}

	/**
	 * Clean a full set of submitted options.
	 *
	 * Also used as register_setting()'s sanitize_callback, which receives the whole
	 * nested array in one go. It reads nothing back out of the database: the version
	 * markers live in their own row, so there is nothing here to rescue and nothing
	 * this can corrupt.
	 *
	 * A key missing from the input falls back to its default rather than to the
	 * stored value. Every field on the settings screen posts on every save --
	 * the two toggles are radio pairs, not checkboxes -- so the two only differ
	 * for a hand-crafted request, and a sanitiser that reads the row it is about
	 * to replace is exactly what §2.1 of the house standard forbids.
	 *
	 * @param mixed $input Raw submitted values.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$options = wp_parse_args( is_array( $input ) ? $input : array(), self::get_defaults() );

		// Keep only keys the plugin actually defines. Without this a hand-crafted
		// post to options.php would have its extra keys stored in the option row
		// forever; the framework used before 3.0.0 dropped them for the same reason.
		$options = array_intersect_key( $options, self::get_defaults() );

		foreach ( self::int_keys() as $key ) {
			$value           = isset( $options[ $key ] ) && is_scalar( $options[ $key ] ) ? $options[ $key ] : 0;
			$options[ $key ] = absint( $value );
		}

		foreach ( self::bool_keys() as $key ) {
			$value           = isset( $options[ $key ] ) && is_scalar( $options[ $key ] ) ? $options[ $key ] : 0;
			$options[ $key ] = intval( $value );
		}

		// The same allow-list the renderer uses, so an SVG arrow typed into the
		// settings screen survives exactly as one passed through the 'options'
		// argument does.
		foreach ( self::text_keys() as $key ) {
			$options[ $key ] = self::kses( isset( $options[ $key ] ) ? $options[ $key ] : '' );
		}

		return $options;
	}

	/**
	 * Bring the stored rows up to date with the running code.
	 *
	 * Runs on activation and on every admin load, because activation hooks do not
	 * fire when a plugin is updated -- which is the usual reason a migration never
	 * runs. Idempotent.
	 *
	 * Both markers are written together in one update_option() at the very end, so
	 * a half-finished upgrade never records itself as complete.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		$versions = self::get_versions();

		if ( WP_PAGENAVI_VERSION === $versions['plugin'] && WP_PAGENAVI_DB_VERSION === $versions['db'] ) {
			return;
		}

		self::migrate();

		update_option(
			self::VERSION,
			array(
				'plugin' => WP_PAGENAVI_VERSION,
				'db'     => WP_PAGENAVI_DB_VERSION,
			)
		);
	}

	/**
	 * Write the settings row from inside an upgrade.
	 *
	 * `update_option()` declines to write a value equal to the one
	 * `get_option()` would return. Where `register_setting()` is passed a
	 * `default` that becomes a trap: the `default_option_*` filter it installs
	 * answers with the shipped defaults for a row that does not exist, so on an
	 * admin request -- the path every real update takes, because activation
	 * hooks do not fire on an update -- a migration whose result happens to
	 * equal the defaults writes nothing at all, while the legacy rows it read
	 * are deleted anyway.
	 *
	 * **This plugin passes no `default`** -- WP_PageNavi_Settings::register_settings()
	 * passes `type` and `sanitize_callback` only -- so no
	 * `default_option_wp_pagenavi_options` filter exists here and the trap is
	 * not armed. The helper is written this way regardless, so that adding one
	 * later cannot quietly break the migration. Do not read the paragraph above
	 * as a description of this plugin's `register_setting()` call.
	 *
	 * Passing an explicit default to `get_option()` defeats a registered one,
	 * because `filter_default_option()` returns early when a default was passed.
	 * That is what lets an absent row be told from a defaulted one and added
	 * outright. `add_option()` runs the sanitize callback exactly as
	 * `update_option()` does, so nothing else about the write changes.
	 *
	 * §7.6.1 has the plugins this shape has already bitten, and wp-dbmanager --
	 * where the same helper sat beside a migration that read the row bare -- is
	 * why the two halves have to agree rather than merely both exist.
	 *
	 * @param array $options The settings to store.
	 * @return void
	 */
	private static function write( array $options ) {
		if ( false === get_option( self::OPTION, false ) ) {
			add_option( self::OPTION, $options );

			return;
		}

		update_option( self::OPTION, $options );
	}

	/**
	 * Fold the pre-3.0.0 row into the current one.
	 *
	 * The settings row was named after the plugin without its wp_ prefix for
	 * twenty years. It is read once, folded in, and deleted; re-running finds
	 * nothing left to do.
	 *
	 * Settings are re-sanitised on the way through, so an upgrade cleans a row that
	 * an older, laxer version wrote just as thoroughly as a save would.
	 *
	 * @return void
	 */
	protected static function migrate() {
		$legacy = get_option( self::LEGACY_OPTION );

		if ( false !== $legacy ) {
			/*
			 * The raw row, never one register_setting() can synthesise. A plugin
			 * that passes a `default` installs a `default_option_*` filter with it,
			 * and a bare get_option() then answers with that defaults array instead
			 * of false for a row which does not exist -- so this branch is skipped
			 * and the legacy row is deleted a few lines below regardless, taking the
			 * settings with it. Passing an explicit default defeats the registered
			 * one: filter_default_option() returns early when a default was passed.
			 *
			 * It bites only on an admin request. Activation and WP-CLI never run
			 * register_setting(), which is why reactivating repairs it and why every
			 * test that goes through activation passes.
			 */
			if ( false === get_option( self::OPTION, false ) ) {
				self::write( self::sanitize( $legacy ) );
			}

			delete_option( self::LEGACY_OPTION );
		}

		$stored = get_option( self::OPTION, false );

		if ( false !== $stored ) {
			self::write( self::sanitize( $stored ) );
		}
	}
}
