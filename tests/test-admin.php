<?php
/**
 * Settings screen tests.
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

		$this->assertSame( 5, $clean['num_pages'] );
		$this->assertSame( 3, $clean['num_larger_page_numbers'] );
		$this->assertSame( 10, $clean['larger_page_numbers_multiple'] );
		$this->assertSame( 2, $clean['style'] );
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

		$this->assertSame( 1, $clean['always_show'] );
		$this->assertSame( 0, $clean['use_pagenavi_css'] );
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

		$this->assertStringNotContainsString( '<script>', $clean['pages_text'] );
		$this->assertStringContainsString( '%CURRENT_PAGE%', $clean['pages_text'] );
		$this->assertSame( '<strong>%PAGE_NUMBER%</strong>', $clean['current_text'] );
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

		$this->assertSame( '', $clean['prev_text'] );
		$this->assertSame( '', $clean['next_text'] );
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

		$this->assertSame( 5, $clean['num_pages'] );
		$this->assertSame( '&laquo;', $clean['prev_text'] );
	}

	/**
	 * A non-array submission does not fatal.
	 *
	 * @return void
	 */
	public function test_non_array_input_is_survivable() {
		$clean = WP_PageNavi_Options::sanitize( 'garbage' );

		$this->assertIsArray( $clean );
		$this->assertSame( 5, $clean['num_pages'] );
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

		$this->assertArrayNotHasKey( 'evil_key', $clean );
		$this->assertArrayNotHasKey( 'another', $clean );
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
		$this->assertSame( '', $clean['prev_text'] );
		$this->assertSame( 0, $clean['num_pages'] );
		$this->assertStringNotContainsString( 'Array', (string) $clean['prev_text'] );
	}

	/**
	 * The settings page is registered under Settings, at the slug it has always
	 * used, so existing bookmarks keep working.
	 *
	 * @return void
	 */
	public function test_settings_page_is_registered_at_the_same_slug() {
		global $submenu;

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		set_current_screen( 'dashboard' );

		WP_PageNavi_Settings::add_page();

		$slugs = wp_list_pluck( $submenu['options-general.php'], 2 );
		$this->assertContains( 'wp-pagenavi', $slugs );
	}

	/**
	 * A Settings link is added to the plugin row, and a non-array input from
	 * another plugin's bad filter does not break it.
	 *
	 * @return void
	 */
	public function test_action_links() {
		$links = WP_PageNavi_Settings::action_links( array( '<a href="#">Deactivate</a>' ) );
		$this->assertCount( 2, $links );
		$this->assertStringContainsString( 'page=wp-pagenavi', $links[0] );

		$this->assertIsArray( WP_PageNavi_Settings::action_links( null ) );
	}
}
