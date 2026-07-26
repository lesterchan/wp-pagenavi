<?php
/**
 * Settings screen tests.
 *
 * @package WP-PageNavi
 */

/**
 * Covers PageNavi_Admin, in particular the sanitize callback the Settings API
 * runs on save.
 */
class Test_PageNavi_Admin extends WP_UnitTestCase {

	/**
	 * Reset the options between tests.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		delete_option( PageNavi_Options::OPTION_NAME );
	}

	/**
	 * Numeric settings are coerced to non-negative integers.
	 *
	 * @return void
	 */
	public function test_numeric_settings_are_absinted() {
		$clean = PageNavi_Admin::sanitize(
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
		$clean = PageNavi_Admin::sanitize(
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
		$clean = PageNavi_Admin::sanitize(
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
		$clean = PageNavi_Admin::sanitize(
			array(
				'prev_text' => '',
				'next_text' => '',
			)
		);

		$this->assertSame( '', $clean['prev_text'] );
		$this->assertSame( '', $clean['next_text'] );
	}

	/**
	 * Keys absent from the submission keep their stored value rather than being
	 * wiped.
	 *
	 * @return void
	 */
	public function test_missing_keys_keep_stored_values() {
		$options              = PageNavi_Options::get_defaults();
		$options['num_pages'] = 9;
		$options['prev_text'] = 'KEEPME';
		PageNavi_Options::update( $options );

		$clean = PageNavi_Admin::sanitize( array( 'style' => '1' ) );

		$this->assertSame( 9, $clean['num_pages'] );
		$this->assertSame( 'KEEPME', $clean['prev_text'] );
	}

	/**
	 * A non-array submission does not fatal.
	 *
	 * @return void
	 */
	public function test_non_array_input_is_survivable() {
		$clean = PageNavi_Admin::sanitize( 'garbage' );

		$this->assertIsArray( $clean );
		$this->assertSame( 5, $clean['num_pages'] );
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

		PageNavi_Admin::add_page();

		$slugs = wp_list_pluck( $submenu['options-general.php'], 2 );
		$this->assertContains( 'pagenavi', $slugs );
	}

	/**
	 * A Settings link is added to the plugin row, and a non-array input from
	 * another plugin's bad filter does not break it.
	 *
	 * @return void
	 */
	public function test_action_links() {
		$links = PageNavi_Admin::action_links( array( '<a href="#">Deactivate</a>' ) );
		$this->assertCount( 2, $links );
		$this->assertStringContainsString( 'page=pagenavi', $links[0] );

		$this->assertIsArray( PageNavi_Admin::action_links( null ) );
	}
}
