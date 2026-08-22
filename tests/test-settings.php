<?php
/**
 * Settings screen tests: the sanitise callback the save runs, and the
 * rendering of the form it saves.
 *
 * @package WP-PageNavi
 */

/**
 * Covers WP_PageNavi_Settings, in particular the sanitize callback the Settings API
 * runs on save.
 */
class WP_PageNavi_Settings_Test extends WP_PageNavi_TestCase {

	/**
	 * Numeric settings are coerced to non-negative integers.
	 *
	 * @return void
	 */
	public function test_numeric_settings_are_absinted() {
		$clean = WP_PageNavi_Options::sanitize(
			array(
				'num_pages'                    => '5abc',
				'num_larger_page_numbers'      => '-3',
				'larger_page_numbers_multiple' => '10.9',
				'style'                        => '2',
			)
		);

		$this->assertSame( 5, $clean['num_pages'], 'A numeric setting is cast to a non-negative integer.' );
		$this->assertSame( 3, $clean['num_larger_page_numbers'], 'Every numeric setting, not only the first.' );
		$this->assertSame( 10, $clean['larger_page_numbers_multiple'], 'Including the multiple.' );
		$this->assertSame( 2, $clean['style'], 'And the style.' );
	}

	/**
	 * Toggles are stored as integers.
	 *
	 * @return void
	 */
	public function test_toggles_are_integers() {
		$clean = WP_PageNavi_Options::sanitize(
			array(
				'always_show'      => '1',
				'use_pagenavi_css' => '0',
			)
		);

		$this->assertSame( 1, $clean['always_show'], 'A ticked toggle stores as one.' );
		$this->assertSame( 0, $clean['use_pagenavi_css'], 'And an unticked one as zero, rather than as a string.' );
	}

	/**
	 * Text settings are filtered through kses but keep their tokens and their
	 * permitted markup.
	 *
	 * @return void
	 */
	public function test_text_settings_are_ksesed() {
		$clean = WP_PageNavi_Options::sanitize(
			array(
				'pages_text'   => 'Page %CURRENT_PAGE% of %TOTAL_PAGES% <script>bad()</script>',
				'current_text' => '<strong>%PAGE_NUMBER%</strong>',
			)
		);

		$this->assertStringNotContainsString( '<script>', $clean['pages_text'], 'A script is filtered out of a text setting.' );
		$this->assertStringContainsString( '%CURRENT_PAGE%', $clean['pages_text'], 'While the token it carries survives.' );
		$this->assertSame( '<strong>%PAGE_NUMBER%</strong>', $clean['current_text'], 'And the markup a site is allowed to use is kept exactly.' );
	}

	/**
	 * An empty text value is legitimate: it hides that part of the navigation.
	 *
	 * @return void
	 */
	public function test_empty_text_is_preserved() {
		$clean = WP_PageNavi_Options::sanitize(
			array(
				'prev_text' => '',
				'next_text' => '',
			)
		);

		$this->assertSame( '', $clean['prev_text'], 'An emptied text setting stays empty; blank is how a part is hidden.' );
		$this->assertSame( '', $clean['next_text'], 'For every text setting, not only the first.' );
	}

	/**
	 * Keys absent from the submission fall back to their defaults, and the
	 * sanitiser never reads the row it is about to replace.
	 *
	 * Every field on the screen posts on every save, so this only differs from
	 * the stored value for a hand-crafted request. Reading the stored row here is
	 * what made a sanitiser have to rescue the version markers out of it, which
	 * is the whole reason those markers now live in a row of their own.
	 *
	 * @return void
	 */
	public function test_missing_keys_fall_back_to_defaults() {
		$options              = WP_PageNavi_Options::get_defaults();
		$options['num_pages'] = 9;
		$options['prev_text'] = 'KEEPME';
		WP_PageNavi_Options::update( $options );

		$clean = WP_PageNavi_Options::sanitize( array( 'style' => '1' ) );

		$this->assertSame( 5, $clean['num_pages'], 'A key the submission omitted falls back to its default.' );
		$this->assertSame( '&laquo;', $clean['prev_text'], 'Including the text defaults.' );
	}

	/**
	 * A non-array submission does not fatal.
	 *
	 * @return void
	 */
	public function test_non_array_input_is_survivable() {
		$clean = WP_PageNavi_Options::sanitize( 'garbage' );

		$this->assertIsArray( $clean, 'A non-array posted value comes back an array rather than propagating.' );
		$this->assertSame( 5, $clean['num_pages'], 'A non-array posted value falls back to the defaults rather than propagating.' );
	}

	/**
	 * Keys the plugin does not define are dropped rather than stored forever.
	 *
	 * @return void
	 */
	public function test_unknown_keys_are_discarded() {
		$clean = WP_PageNavi_Options::sanitize(
			array(
				'style'    => '1',
				'evil_key' => 'x',
				'another'  => array( 1, 2 ),
			)
		);

		$this->assertArrayNotHasKey( 'evil_key', $clean, 'A key the sanitiser does not know is discarded.' );
		$this->assertArrayNotHasKey( 'another', $clean, 'Every unknown key is discarded, not only the first.' );
		$this->assertSame(
			array_keys( WP_PageNavi_Options::get_defaults() ),
			array_keys( $clean ),
			'Only the plugin\'s own option keys may be stored.'
		);
	}

	/**
	 * An array posted where a scalar belongs is handled without a PHP 8 notice.
	 *
	 * @return void
	 */
	public function test_array_values_do_not_raise_a_notice() {
		$raised = null;

		// Capturing the notice is the assertion here, not leftover debug code. The
		// shared phpcs.xml excuses set_error_handler() for tests/, so this needs no
		// suppression of its own.
		set_error_handler(
			static function ( $errno, $errstr ) use ( &$raised ) {
				$raised = $errstr;
				return true;
			}
		);

		$clean = WP_PageNavi_Options::sanitize(
			array(
				'prev_text' => array( 'a' => 'b' ),
				'num_pages' => array( 5 ),
			)
		);

		restore_error_handler();

		$this->assertNull( $raised, "Sanitising raised: {$raised}" );
		$this->assertSame( '', $clean['prev_text'], 'An array posted into a text setting becomes an empty string.' );
		$this->assertSame( 0, $clean['num_pages'], 'And into a numeric one, zero.' );
		$this->assertStringNotContainsString( 'Array', (string) $clean['prev_text'], 'Rather than the literal Array, which is what casting one would produce.' );
	}

	/**
	 * The settings page is registered under Settings, at the slug it has always
	 * used, so existing bookmarks keep working.
	 *
	 * @return void
	 */
	public function test_settings_page_is_registered_at_the_same_slug() {
		global $submenu;

		wp_set_current_user( $this->create_admin() );
		set_current_screen( 'dashboard' );

		WP_PageNavi_Settings::add_page();

		$slugs = wp_list_pluck( $submenu['options-general.php'], 2 );
		$this->assertContains( 'wp-pagenavi', $slugs, 'The screen is registered at the slug the plugin has always used.' );
	}

	/**
	 * The capability constant and its accessor agree on manage_options.
	 *
	 * @return void
	 */
	public function test_capability_defaults_to_manage_options() {
		$this->assertSame( 'manage_options', WP_PageNavi_Settings::CAPABILITY, 'The capability constant is manage_options.' );
		$this->assertSame( 'manage_options', WP_PageNavi_Settings::capability(), 'And the accessor answers with it, so the two cannot drift.' );
	}

	/**
	 * Every capability check goes through one filter, which is handed the
	 * context it is being asked about.
	 *
	 * @return void
	 */
	public function test_capability_filter_is_honoured() {
		$seen = null;

		$replace = static function ( $capability, $context ) use ( &$seen ) {
			$seen = $context;
			return 'edit_pages';
		};
		add_filter( 'wp_pagenavi_capability', $replace, 10, 2 );

		$capability = WP_PageNavi_Settings::capability();

		remove_filter( 'wp_pagenavi_capability', $replace, 10 );

		$this->assertSame( 'edit_pages', $capability, 'A filter can replace the capability the screen requires.' );
		$this->assertSame( 'settings', $seen, 'the filter was not told which context it was being asked about.' );
	}

	/**
	 * A Settings link is added to the plugin row, and a non-array input from
	 * another plugin's bad filter does not break it.
	 *
	 * @return void
	 */
	public function test_action_links() {
		$links = WP_PageNavi_Settings::action_links( array( '<a href="#">Deactivate</a>' ) );
		$this->assertCount( 2, $links, 'The Settings link is added to the link passed in, not instead of it.' );
		$this->assertStringContainsString( 'page=wp-pagenavi', $links[0], 'And the Settings link points at it.' );

		$this->assertIsArray( WP_PageNavi_Settings::action_links( null ), 'A null links list is survivable rather than fatal.' );
	}
}

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- the screen tests share the file with the sanitise tests they mirror.

/**
 * Covers the markup and the Settings API wiring of Settings -> PageNavi --
 * the half of WP_PageNavi_Settings that draws the form, where
 * WP_PageNavi_Settings_Test above covers the sanitise callback.
 */
class WP_PageNavi_Settings_Screen_Test extends WP_PageNavi_TestCase {

	/**
	 * Register the settings and act as an administrator.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( $this->create_admin() );
		WP_PageNavi_Settings::register_settings();
	}

	/**
	 * Render the settings page and return its markup.
	 *
	 * @return string
	 */
	protected function render_page() {
		ob_start();
		WP_PageNavi_Settings::render_page();
		return ob_get_clean();
	}

	/**
	 * Render just the fields of the page.
	 *
	 * @return string
	 */
	protected function render_fields() {
		ob_start();
		do_settings_sections( WP_PageNavi_Settings::PAGE );
		return ob_get_clean();
	}

	/**
	 * The form posts to options.php and carries the Settings API nonce fields.
	 *
	 * @return void
	 */
	public function test_form_targets_options_php_with_nonce() {
		$html = $this->render_page();

		$this->assertStringContainsString( 'action="options.php"', $html, 'The form posts to options.php, so core handles the save.' );
		$this->assertStringContainsString( 'method="post"', $html, 'By POST.' );
		$this->assertStringContainsString( WP_PageNavi_Settings::GROUP, $html, 'Naming the settings group.' );
		$this->assertStringContainsString( 'name="_wpnonce"', $html, 'With a nonce.' );
		// Core emits this one with single quotes.
		$this->assertStringContainsString( "name='option_page'", $html, 'And the option_page field core checks it against.' );
		$this->assertStringContainsString( 'name="action" value="update"', $html, 'And the update action, without which core ignores the post.' );
	}

	/**
	 * The page has a heading and a submit button.
	 *
	 * @return void
	 */
	public function test_page_chrome() {
		$html = $this->render_page();

		$this->assertStringContainsString( 'PageNavi Settings', $html, 'The screen is headed.' );
		$this->assertStringContainsString( 'type="submit"', $html, 'Carries a submit button.' );
		$this->assertStringContainsString( 'class="wrap"', $html, 'And uses the core page wrapper.' );
	}

	/**
	 * Both sections are rendered, with the text section's intro copy.
	 *
	 * @return void
	 */
	public function test_sections_render() {
		$html = $this->render_fields();

		$this->assertStringContainsString( 'Page Navigation Text', $html, 'The text section is drawn.' );
		$this->assertStringContainsString( 'Page Navigation Options', $html, 'And the options section.' );
		$this->assertStringContainsString( 'Leaving a field blank will hide that part of the navigation.', $html, 'With the note explaining what a blank field does.' );
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

		$this->assertStringContainsString( 'for="wp-pagenavi-pages_text"', $html, 'The label points at a control.' );
		$this->assertStringContainsString( 'id="wp-pagenavi-pages_text"', $html, 'Which exists under that id, so clicking the label focuses the field.' );
		$this->assertStringContainsString( 'Text For Number Of Pages', $html, 'The text field is labelled.' );
		$this->assertStringContainsString( 'Number Of Pages To Show', $html, 'And so is the numeric one.' );
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

		$this->assertStringContainsString( 'value="Mine &quot;quoted&quot; &amp; &lt;b&gt;bold&lt;/b&gt;"', $html, 'A stored value is escaped for an attribute.' );
		$this->assertStringNotContainsString( 'value="Mine "quoted"', $html, 'Rather than closing it early, which would end the input.' );
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

		$this->assertStringContainsString( '<code>%CURRENT_PAGE%</code>', $html, 'The current page token is shown as code.' );
		$this->assertStringContainsString( '<code>%TOTAL_PAGES%</code>', $html, 'And the total pages token.' );
		$this->assertStringContainsString( '<code>%PAGE_NUMBER%</code>', $html, 'And the page number token.' );
		$this->assertStringNotContainsString( '%1$', $html, 'With no printf specifier left in the hint, which would be consumed rather than shown.' );
	}

	/**
	 * Number fields are real number inputs that cannot go negative.
	 *
	 * @return void
	 */
	public function test_number_fields() {
		$html = $this->render_fields();

		$this->assertMatchesRegularExpression(
			'/<input type="number" min="0" step="1" id="wp-pagenavi-num_pages"[^>]*value="5"/',
			$html,
			'The stored number is the value the field renders with.'
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
			'/<input type="radio" id="[^"]*" name="wp_pagenavi_options\[use_pagenavi_css\]" value="(\d)"([^>]*)/',
			$html,
			$matches,
			PREG_SET_ORDER
		);

		$this->assertCount( 2, $matches, 'Both radios of the pair are rendered, or the loop below asserts nothing.' );
		foreach ( $matches as $match ) {
			if ( '0' === $match[1] ) {
				$this->assertStringContainsString( 'checked', $match[2], 'The radio matching the stored value is the one marked checked.' );
			} else {
				$this->assertStringNotContainsString( 'checked', $match[2], 'Only the radio matching the stored value is marked checked.' );
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

		$this->assertStringContainsString( '<option value="2" selected', $html, 'The stored style is the option marked selected.' );
		$this->assertStringNotContainsString( '<option value="1" selected', $html, 'And it is the only one, so the select has one initial value.' );
		$this->assertStringContainsString( 'Drop-down List', $html, 'With the styles named rather than numbered.' );
	}

	/**
	 * Help notes are printed under the fields that have them.
	 *
	 * @return void
	 */
	public function test_help_notes_are_rendered() {
		$html = $this->render_fields();

		$this->assertStringContainsString( 'Enter 0 to disable.', $html, 'The note explaining the disable value is rendered.' );
		$this->assertStringContainsString( 'Show navigation even if there', $html, 'And the note on the always-show toggle.' );
	}

	/**
	 * A user without manage_options cannot view the screen.
	 *
	 * @return void
	 */
	public function test_render_page_requires_capability() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->expectException( WPDieException::class );
		WP_PageNavi_Settings::render_page();
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

		$this->assertSame( 7, $stored['num_pages'], 'A save through options.php runs the sanitiser, so the number is cast.' );
		$this->assertStringNotContainsString( '<script>', $stored['pages_text'], 'And the text is filtered.' );
		$this->assertStringContainsString( 'hello', $stored['pages_text'], 'While the safe part of it survives.' );
	}

	/**
	 * The setting is registered against the group the form posts.
	 *
	 * @return void
	 */
	public function test_setting_is_registered_in_the_expected_group() {
		$registered = get_registered_settings();

		$this->assertArrayHasKey( WP_PageNavi_Options::OPTION, $registered, 'The settings row is registered, so its sanitise callback is attached.' );
		$this->assertSame(
			WP_PageNavi_Settings::GROUP,
			$registered[ WP_PageNavi_Options::OPTION ]['group'],
			'The setting is registered in the group the form posts, or the save is rejected.'
		);
	}

	/**
	 * The init() method hooks the screen up.
	 *
	 * @return void
	 */
	public function test_init_registers_hooks() {
		WP_PageNavi_Settings::init();

		$this->assertNotFalse( has_action( 'admin_menu', array( 'WP_PageNavi_Settings', 'add_page' ) ), 'The settings page is hooked onto admin_menu.' );
		$this->assertNotFalse( has_action( 'admin_init', array( 'WP_PageNavi_Settings', 'register_settings' ) ), 'The settings registration is hooked onto admin_init.' );
		$this->assertNotFalse(
			has_action( 'admin_init', array( 'WP_PageNavi_Options', 'maybe_upgrade' ) ),
			'The upgrade routine must run on admin_init, because an update never fires the activation hook.'
		);
		$this->assertNotFalse(
			has_filter(
				'plugin_action_links_' . plugin_basename( WP_PAGENAVI_MAIN_FILE ),
				array( 'WP_PageNavi_Settings', 'action_links' )
			),
			'The Settings action link is hooked onto this plugin basename.'
		);
	}

	/**
	 * Every registered field is drawn by a method of its own, named after the
	 * option key, and the set of fields is exactly the set of options.
	 *
	 * @return void
	 */
	public function test_every_registered_field_has_a_callback_method_of_its_own() {
		$fields = WP_PageNavi_Settings::fields();

		// Compared as sets, not sequences. The two orders are deliberately
		// different: get_defaults() groups keys by what they store, while
		// fields() orders them the way the screen reads top to bottom. What the
		// standard requires is that neither list has a member the other lacks.
		$expected = array_keys( WP_PageNavi_Options::get_defaults() );
		$actual   = array_keys( $fields );
		sort( $expected );
		sort( $actual );

		$this->assertSame(
			$expected,
			$actual,
			'Every option needs a field and every field needs an option.'
		);

		foreach ( array_keys( $fields ) as $name ) {
			$this->assertTrue(
				method_exists( 'WP_PageNavi_Settings', 'field_' . $name ),
				"The '{$name}' field has no field_{$name}() callback."
			);
		}
	}

	/**
	 * Both sections are registered against the page, keyed by the constants
	 * rather than by a loose string.
	 *
	 * @return void
	 */
	public function test_the_sections_are_registered_under_the_page_slug() {
		global $wp_settings_sections;

		$this->assertArrayHasKey( WP_PageNavi_Settings::PAGE, $wp_settings_sections, 'The sections are registered under this screen slug, not the option group.' );

		$this->assertSame(
			array( WP_PageNavi_Settings::SECTION_TEXT, WP_PageNavi_Settings::SECTION_DISPLAY ),
			array_keys( $wp_settings_sections[ WP_PageNavi_Settings::PAGE ] ),
			'Both sections are registered under the page slug, in order.'
		);
	}

	/**
	 * The screen hands its markup to the Settings API rather than writing any of
	 * its own: do_settings_sections() emits the form table, so the plugin must
	 * not, and nothing carries a presentational attribute.
	 *
	 * Asserted against the source rather than the rendered page, because the
	 * table in the output is core's and is supposed to be there.
	 *
	 * @return void
	 */
	public function test_the_screen_writes_no_table_markup_or_inline_presentation() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-wp-pagenavi-settings.php' );

		$this->assertStringNotContainsString( '<table', $source, 'do_settings_sections() emits the form table.' );
		$this->assertStringNotContainsString( 'form-table', $source, 'The screen writes no table markup of its own; the Settings API draws it.' );
		$this->assertStringNotContainsString( '<tr', $source, 'Not a row either, which is what a hand-written form leaves behind.' );

		foreach ( array( 'style="', 'width="', 'valign', 'align="' ) as $attribute ) {
			$this->assertStringNotContainsString(
				$attribute,
				$source,
				"The admin markup must carry no inline {$attribute} attribute."
			);
		}
	}
}
