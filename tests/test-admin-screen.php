<?php
/**
 * Settings screen rendering tests.
 *
 * These cover the half of WP_PageNavi_Admin that draws the form, as opposed to
 * test-admin.php which covers the sanitise callback.
 *
 * @package WP-PageNavi
 */

/**
 * Covers the markup and the Settings API wiring of Settings -> PageNavi.
 */
class Test_PageNavi_Admin_Screen extends WP_UnitTestCase {

	/**
	 * Register the settings and act as an administrator.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		delete_option( WP_PageNavi_Options::OPTION );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		WP_PageNavi_Admin::register_settings();
	}

	/**
	 * Render the settings page and return its markup.
	 *
	 * @return string
	 */
	protected function render_page() {
		ob_start();
		WP_PageNavi_Admin::render_page();
		return ob_get_clean();
	}

	/**
	 * Render just the fields of the page.
	 *
	 * @return string
	 */
	protected function render_fields() {
		ob_start();
		do_settings_sections( WP_PageNavi_Admin::PAGE );
		return ob_get_clean();
	}

	/**
	 * The form posts to options.php and carries the Settings API nonce fields.
	 *
	 * @return void
	 */
	public function test_form_targets_options_php_with_nonce() {
		$html = $this->render_page();

		$this->assertStringContainsString( 'action="options.php"', $html );
		$this->assertStringContainsString( 'method="post"', $html );
		$this->assertStringContainsString( WP_PageNavi_Admin::GROUP, $html );
		$this->assertStringContainsString( 'name="_wpnonce"', $html );
		// Core emits this one with single quotes.
		$this->assertStringContainsString( "name='option_page'", $html );
		$this->assertStringContainsString( 'name="action" value="update"', $html );
	}

	/**
	 * The page has a heading and a submit button.
	 *
	 * @return void
	 */
	public function test_page_chrome() {
		$html = $this->render_page();

		$this->assertStringContainsString( 'PageNavi Settings', $html );
		$this->assertStringContainsString( 'type="submit"', $html );
		$this->assertStringContainsString( 'class="wrap"', $html );
	}

	/**
	 * Both sections are rendered, with the text section's intro copy.
	 *
	 * @return void
	 */
	public function test_sections_render() {
		$html = $this->render_fields();

		$this->assertStringContainsString( 'Page Navigation Text', $html );
		$this->assertStringContainsString( 'Page Navigation Options', $html );
		$this->assertStringContainsString( 'Leaving a field blank will hide that part of the navigation.', $html );
	}

	/**
	 * Every option has a field, named so that it posts into the single option
	 * array the plugin has always used.
	 *
	 * @return void
	 */
	public function test_every_option_has_a_field() {
		$html = $this->render_fields();

		foreach ( array_keys( WP_PageNavi_Options::get_defaults() ) as $key ) {
			$this->assertStringContainsString(
				'name="' . WP_PageNavi_Options::OPTION . '[' . $key . ']"',
				$html,
				"Missing a form field for the '{$key}' option."
			);
		}
	}

	/**
	 * Each field is labelled, and the label points at the input.
	 *
	 * @return void
	 */
	public function test_fields_are_labelled() {
		$html = $this->render_fields();

		$this->assertStringContainsString( 'for="pagenavi-pages_text"', $html );
		$this->assertStringContainsString( 'id="pagenavi-pages_text"', $html );
		$this->assertStringContainsString( 'Text For Number Of Pages', $html );
		$this->assertStringContainsString( 'Number Of Pages To Show', $html );
	}

	/**
	 * The text fields show the stored values, escaped.
	 *
	 * @return void
	 */
	public function test_text_fields_show_stored_values_escaped() {
		$options               = WP_PageNavi_Options::get_defaults();
		$options['pages_text'] = 'Mine "quoted" & <b>bold</b>';
		WP_PageNavi_Options::update( $options );

		$html = $this->render_fields();

		$this->assertStringContainsString( 'value="Mine &quot;quoted&quot; &amp; &lt;b&gt;bold&lt;/b&gt;"', $html );
		$this->assertStringNotContainsString( 'value="Mine "quoted"', $html );
	}

	/**
	 * The token hints are emitted as code spans, not baked into the translatable
	 * label, so a formatting pass can never rewrite them into printf
	 * placeholders.
	 *
	 * @return void
	 */
	public function test_token_hints_are_code_spans() {
		$html = $this->render_fields();

		$this->assertStringContainsString( '<code>%CURRENT_PAGE%</code>', $html );
		$this->assertStringContainsString( '<code>%TOTAL_PAGES%</code>', $html );
		$this->assertStringContainsString( '<code>%PAGE_NUMBER%</code>', $html );
		$this->assertStringNotContainsString( '%1$', $html );
	}

	/**
	 * Number fields are real number inputs that cannot go negative.
	 *
	 * @return void
	 */
	public function test_number_fields() {
		$html = $this->render_fields();

		$this->assertMatchesRegularExpression(
			'/<input type="number" min="0" step="1" id="pagenavi-num_pages"[^>]*value="5"/',
			$html
		);
	}

	/**
	 * The radio fields mark the stored choice, and only that one.
	 *
	 * @return void
	 */
	public function test_radio_reflects_stored_value() {
		$options                     = WP_PageNavi_Options::get_defaults();
		$options['use_pagenavi_css'] = 0;
		WP_PageNavi_Options::update( $options );

		$html = $this->render_fields();

		preg_match_all(
			'/<input type="radio" name="pagenavi_options\[use_pagenavi_css\]" value="(\d)"([^>]*)/',
			$html,
			$matches,
			PREG_SET_ORDER
		);

		$this->assertCount( 2, $matches );
		foreach ( $matches as $match ) {
			if ( '0' === $match[1] ) {
				$this->assertStringContainsString( 'checked', $match[2] );
			} else {
				$this->assertStringNotContainsString( 'checked', $match[2] );
			}
		}
	}

	/**
	 * The style select marks the stored option.
	 *
	 * @return void
	 */
	public function test_select_reflects_stored_value() {
		$options          = WP_PageNavi_Options::get_defaults();
		$options['style'] = 2;
		WP_PageNavi_Options::update( $options );

		$html = $this->render_fields();

		$this->assertStringContainsString( '<option value="2" selected', $html );
		$this->assertStringNotContainsString( '<option value="1" selected', $html );
		$this->assertStringContainsString( 'Drop-down List', $html );
	}

	/**
	 * Help notes are printed under the fields that have them.
	 *
	 * @return void
	 */
	public function test_help_notes_are_rendered() {
		$html = $this->render_fields();

		$this->assertStringContainsString( 'Enter 0 to disable.', $html );
		$this->assertStringContainsString( 'Show navigation even if there', $html );
	}

	/**
	 * A user without manage_options cannot view the screen.
	 *
	 * @return void
	 */
	public function test_render_page_requires_capability() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->expectException( WPDieException::class );
		WP_PageNavi_Admin::render_page();
	}

	/**
	 * The register_setting() call wires the sanitise callback onto the option itself, so a
	 * plain update_option() is cleaned. This is what proves the Settings API
	 * plumbing is connected, rather than just that sanitize() works when called
	 * directly.
	 *
	 * @return void
	 */
	public function test_registered_setting_sanitizes_on_update() {
		update_option(
			WP_PageNavi_Options::OPTION,
			array(
				'num_pages'  => '7abc',
				'pages_text' => '<script>bad()</script>hello',
			)
		);

		$stored = get_option( WP_PageNavi_Options::OPTION );

		$this->assertSame( 7, $stored['num_pages'] );
		$this->assertStringNotContainsString( '<script>', $stored['pages_text'] );
		$this->assertStringContainsString( 'hello', $stored['pages_text'] );
	}

	/**
	 * The setting is registered against the group the form posts.
	 *
	 * @return void
	 */
	public function test_setting_is_registered_in_the_expected_group() {
		$registered = get_registered_settings();

		$this->assertArrayHasKey( WP_PageNavi_Options::OPTION, $registered );
		$this->assertSame(
			WP_PageNavi_Admin::GROUP,
			$registered[ WP_PageNavi_Options::OPTION ]['group']
		);
	}

	/**
	 * The init() method hooks the screen up.
	 *
	 * @return void
	 */
	public function test_init_registers_hooks() {
		WP_PageNavi_Admin::init();

		$this->assertNotFalse( has_action( 'admin_menu', array( 'WP_PageNavi_Admin', 'add_page' ) ) );
		$this->assertNotFalse( has_action( 'admin_init', array( 'WP_PageNavi_Admin', 'register_settings' ) ) );
		$this->assertNotFalse(
			has_filter(
				'plugin_action_links_' . plugin_basename( WP_PAGENAVI_MAIN_FILE ),
				array( 'WP_PageNavi_Admin', 'action_links' )
			)
		);
	}
}
