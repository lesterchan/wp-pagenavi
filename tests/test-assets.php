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

		$this->assertStringEndsWith( '/wp-pagenavi/css/wp-pagenavi.css', $src );
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
	 * A copy of wp-pagenavi.css in the active theme wins over the plugin's own,
	 * which is the documented way to restyle the navigation without losing the
	 * changes on upgrade.
	 *
	 * @return void
	 */
	public function test_theme_copy_overrides_the_plugin_stylesheet() {
		$this->set_option( 'use_pagenavi_css', 1 );

		$theme_dir = get_temp_dir() . 'pagenavi-theme-' . wp_generate_password( 6, false );
		mkdir( $theme_dir );
		file_put_contents( $theme_dir . '/wp-pagenavi.css', '/* theme copy */' );

		add_filter(
			'stylesheet_directory',
			static function () use ( $theme_dir ) {
				return $theme_dir;
			}
		);
		add_filter(
			'stylesheet_directory_uri',
			static function () {
				return 'https://example.org/theme';
			}
		);

		PageNavi_Core::stylesheets();
		$src = $GLOBALS['wp_styles']->registered['wp-pagenavi']->src;

		unlink( $theme_dir . '/wp-pagenavi.css' );
		rmdir( $theme_dir );

		$this->assertSame( 'https://example.org/theme/wp-pagenavi.css', $src );
	}

	/**
	 * A parent theme copy is used when the child theme has none.
	 *
	 * @return void
	 */
	public function test_parent_theme_copy_is_used_as_a_fallback() {
		$this->set_option( 'use_pagenavi_css', 1 );

		$parent_dir = get_temp_dir() . 'pagenavi-parent-' . wp_generate_password( 6, false );
		mkdir( $parent_dir );
		file_put_contents( $parent_dir . '/wp-pagenavi.css', '/* parent copy */' );

		// The child theme deliberately has no copy.
		add_filter(
			'stylesheet_directory',
			static function () {
				return '/nonexistent-child-theme';
			}
		);
		add_filter(
			'template_directory',
			static function () use ( $parent_dir ) {
				return $parent_dir;
			}
		);
		add_filter(
			'template_directory_uri',
			static function () {
				return 'https://example.org/parent';
			}
		);

		PageNavi_Core::stylesheets();
		$src = $GLOBALS['wp_styles']->registered['wp-pagenavi']->src;

		unlink( $parent_dir . '/wp-pagenavi.css' );
		rmdir( $parent_dir );

		$this->assertSame( 'https://example.org/parent/wp-pagenavi.css', $src );
	}

	/**
	 * The init() method hooks the enqueue onto wp_enqueue_scripts.
	 *
	 * @return void
	 */
	public function test_init_registers_the_enqueue_hook() {
		PageNavi_Core::init();

		$this->assertNotFalse(
			has_action( 'wp_enqueue_scripts', array( 'PageNavi_Core', 'stylesheets' ) )
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
