<?php
/**
 * Stylesheet enqueue tests.
 *
 * @package WP-PageNavi
 */

/**
 * Covers PageNavi_Core::stylesheets().
 */
class Test_PageNavi_Assets extends WP_UnitTestCase {

	/**
	 * Start each test with a clean style queue and no stored options.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		delete_option( PageNavi_Options::OPTION_NAME );

		$GLOBALS['wp_styles'] = new WP_Styles();
	}

	/**
	 * Store a single option value.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Option value.
	 * @return void
	 */
	protected function set_option( $key, $value ) {
		$options         = PageNavi_Options::get_defaults();
		$options[ $key ] = $value;
		PageNavi_Options::update( $options );
	}

	/**
	 * The stylesheet is enqueued when the option is on.
	 *
	 * @return void
	 */
	public function test_enqueued_when_enabled() {
		$this->set_option( 'use_pagenavi_css', 1 );

		PageNavi_Core::stylesheets();

		$this->assertTrue( wp_style_is( 'wp-pagenavi', 'enqueued' ) );
	}

	/**
	 * Nothing is enqueued when the option is off, whether it is stored as an int
	 * or as the boolean the defaults use.
	 *
	 * @return void
	 */
	public function test_not_enqueued_when_disabled() {
		$this->set_option( 'use_pagenavi_css', 0 );
		PageNavi_Core::stylesheets();
		$this->assertFalse( wp_style_is( 'wp-pagenavi', 'enqueued' ) );

		$GLOBALS['wp_styles'] = new WP_Styles();
		$this->set_option( 'use_pagenavi_css', false );
		PageNavi_Core::stylesheets();
		$this->assertFalse( wp_style_is( 'wp-pagenavi', 'enqueued' ) );
	}

	/**
	 * The stylesheet URL resolves against the plugin root, not the includes
	 * directory the class itself lives in.
	 *
	 * @return void
	 */
	public function test_stylesheet_url_resolves_to_plugin_root() {
		$this->set_option( 'use_pagenavi_css', 1 );

		PageNavi_Core::stylesheets();

		$src = $GLOBALS['wp_styles']->registered['wp-pagenavi']->src;

		$this->assertStringEndsWith( '/wp-pagenavi/pagenavi-css.css', $src );
		$this->assertStringNotContainsString( '/includes/', $src );
	}

	/**
	 * The stylesheet is versioned with the plugin version, so an upgrade busts
	 * the cache.
	 *
	 * @return void
	 */
	public function test_stylesheet_is_versioned_with_the_plugin() {
		$this->set_option( 'use_pagenavi_css', 1 );

		PageNavi_Core::stylesheets();

		$this->assertSame(
			WP_PAGENAVI_VERSION,
			$GLOBALS['wp_styles']->registered['wp-pagenavi']->ver
		);
	}

	/**
	 * The version constant tracks the plugin header.
	 *
	 * @return void
	 */
	public function test_version_constant_matches_header() {
		$data = get_file_data( WP_PAGENAVI_MAIN_FILE, array( 'Version' => 'Version' ) );

		$this->assertSame( $data['Version'], WP_PAGENAVI_VERSION );
	}
}
