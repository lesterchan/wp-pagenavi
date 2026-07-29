<?php
/**
 * Stylesheet enqueue tests.
 *
 * @package WP-PageNavi
 */

/**
 * Covers WP_PageNavi_Core::stylesheets().
 */
class Test_PageNavi_Assets extends WP_UnitTestCase {

	/**
	 * Start each test with a clean style queue and no stored options.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		delete_option( WP_PageNavi_Options::OPTION );

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
		$options         = WP_PageNavi_Options::get_defaults();
		$options[ $key ] = $value;
		WP_PageNavi_Options::update( $options );
	}

	/**
	 * The stylesheet is enqueued when the option is on.
	 *
	 * @return void
	 */
	public function test_enqueued_when_enabled() {
		$this->set_option( 'use_pagenavi_css', 1 );

		WP_PageNavi_Core::stylesheets();

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
		WP_PageNavi_Core::stylesheets();
		$this->assertFalse( wp_style_is( 'wp-pagenavi', 'enqueued' ) );

		$GLOBALS['wp_styles'] = new WP_Styles();
		$this->set_option( 'use_pagenavi_css', false );
		WP_PageNavi_Core::stylesheets();
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

		WP_PageNavi_Core::stylesheets();

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

		WP_PageNavi_Core::stylesheets();

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

		add_filter( 'stylesheet_directory', array( $this, 'filter_css_directory' ) );
		add_filter(
			'stylesheet_directory_uri',
			static function () {
				return 'https://example.org/theme';
			}
		);

		WP_PageNavi_Core::stylesheets();
		$src = $GLOBALS['wp_styles']->registered['wp-pagenavi']->src;

		$this->assertSame( 'https://example.org/theme/wp-pagenavi.css', $src );
	}

	/**
	 * A parent theme copy is used when the child theme has none.
	 *
	 * @return void
	 */
	public function test_parent_theme_copy_is_used_as_a_fallback() {
		$this->set_option( 'use_pagenavi_css', 1 );

		// The child theme deliberately has no copy.
		add_filter(
			'stylesheet_directory',
			static function () {
				return '/nonexistent-child-theme';
			}
		);
		add_filter( 'template_directory', array( $this, 'filter_css_directory' ) );
		add_filter(
			'template_directory_uri',
			static function () {
				return 'https://example.org/parent';
			}
		);

		WP_PageNavi_Core::stylesheets();
		$src = $GLOBALS['wp_styles']->registered['wp-pagenavi']->src;

		$this->assertSame( 'https://example.org/parent/wp-pagenavi.css', $src );
	}

	/**
	 * Point a theme directory at the plugin's own css/ folder.
	 *
	 * The lookup only asks whether wp-pagenavi.css exists in the directory, and
	 * css/ is a directory that certainly holds one. Standing a real theme up in
	 * the temp directory would mean creating and deleting files from a test, and
	 * a half-run test would then leave them behind.
	 *
	 * @return string
	 */
	public function filter_css_directory() {
		return untrailingslashit( dirname( __DIR__ ) . '/css' );
	}

	/**
	 * The init() method hooks the enqueue onto wp_enqueue_scripts.
	 *
	 * @return void
	 */
	public function test_init_registers_the_enqueue_hook() {
		WP_PageNavi_Core::init();

		$this->assertNotFalse(
			has_action( 'wp_enqueue_scripts', array( 'WP_PageNavi_Core', 'stylesheets' ) )
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

	/**
	 * The stylesheet, with its comment block removed.
	 *
	 * The comment names the properties the sheet promises not to use, so leaving
	 * it in would fail every assertion below for the wrong reason.
	 *
	 * @return string
	 */
	protected function stylesheet_rules() {
		$css = (string) file_get_contents( dirname( __DIR__ ) . '/css/wp-pagenavi.css' );

		return (string) preg_replace( '#/\*.*?\*/#s', '', $css );
	}

	/**
	 * One sheet serves both text directions, so nothing in it may be physical.
	 *
	 * This is what removes the need for a mirrored -rtl.css: a physical property
	 * here is the only thing that would ever make a second sheet necessary, so
	 * the rule is enforced at the source rather than by remembering it.
	 *
	 * @return void
	 */
	public function test_the_stylesheet_uses_no_physical_properties() {
		$rules = $this->stylesheet_rules();

		foreach ( array( 'margin-left', 'margin-right', 'padding-left', 'padding-right', 'border-left', 'border-right', 'float' ) as $property ) {
			$this->assertStringNotContainsString(
				$property . ':',
				$rules,
				"{$property} is physical; use its inline-start/inline-end equivalent."
			);
		}

		$this->assertDoesNotMatchRegularExpression(
			'/text-align:\s*(left|right)/',
			$rules,
			'text-align must be start or end, not left or right.'
		);

		$this->assertSame( array(), (array) glob( dirname( __DIR__ ) . '/css/*-rtl.css' ) );
	}

	/**
	 * The theme owns the typography and the text colour; the plugin borrows them.
	 *
	 * @return void
	 */
	public function test_the_stylesheet_leaves_typography_to_the_theme() {
		$rules = $this->stylesheet_rules();

		foreach ( array( 'font-family', 'font-size', 'background' ) as $property ) {
			$this->assertStringNotContainsString( $property . ':', $rules );
		}

		$this->assertDoesNotMatchRegularExpression(
			'/(^|[^-])color:\s*#/',
			$rules,
			'A colour must come from a custom property with a fallback, never a bare hex.'
		);

		$this->assertStringNotContainsString( '!important', $rules );
	}
}
