<?php
/**
 * Option storage tests.
 *
 * @package WP-PageNavi
 */

/**
 * Covers WP_PageNavi_Options.
 */
class Test_PageNavi_Options extends WP_UnitTestCase {

	/**
	 * Reset the options between tests.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		delete_option( WP_PageNavi_Options::OPTION );
	}

	/**
	 * The option key must never change: it is what carries existing installs
	 * across the 3.0.0 upgrade.
	 *
	 * @return void
	 */
	public function test_option_key_is_unchanged() {
		$this->assertSame( 'pagenavi_options', WP_PageNavi_Options::OPTION );
	}

	/**
	 * With nothing stored, the defaults are returned.
	 *
	 * @return void
	 */
	public function test_defaults_when_nothing_stored() {
		$this->assertSame( WP_PageNavi_Options::get_defaults(), WP_PageNavi_Options::get() );
	}

	/**
	 * The default values themselves are the documented ones.
	 *
	 * @return void
	 */
	public function test_default_values() {
		$defaults = WP_PageNavi_Options::get_defaults();

		$this->assertSame( 5, $defaults['num_pages'] );
		$this->assertSame( 3, $defaults['num_larger_page_numbers'] );
		$this->assertSame( 10, $defaults['larger_page_numbers_multiple'] );
		$this->assertSame( 1, $defaults['style'] );
		$this->assertFalse( $defaults['always_show'] );
		$this->assertTrue( $defaults['use_pagenavi_css'] );
		$this->assertSame( '%PAGE_NUMBER%', $defaults['page_text'] );
	}

	/**
	 * A row written by an older version, missing keys added since, still yields a
	 * complete option set.
	 *
	 * @return void
	 */
	public function test_partial_row_falls_back_to_defaults() {
		update_option(
			WP_PageNavi_Options::OPTION,
			array(
				'style'     => 1,
				'num_pages' => 4,
			)
		);

		$options = WP_PageNavi_Options::get();

		$this->assertSame( 4, $options['num_pages'] );
		$this->assertSame( 10, $options['larger_page_numbers_multiple'] );
		$this->assertArrayHasKey( 'dotright_text', $options );
	}

	/**
	 * A single key can be read directly, and an unknown key gives null.
	 *
	 * @return void
	 */
	public function test_single_key_access() {
		$this->assertSame( 5, WP_PageNavi_Options::get( 'num_pages' ) );
		$this->assertNull( WP_PageNavi_Options::get( 'no_such_option' ) );
	}

	/**
	 * A corrupt (non-array) option row does not fatal.
	 *
	 * @return void
	 */
	public function test_non_array_option_row_is_survivable() {
		update_option( WP_PageNavi_Options::OPTION, 'not-an-array' );

		$this->assertSame( WP_PageNavi_Options::get_defaults(), WP_PageNavi_Options::get() );
	}

	/**
	 * Values round-trip through update().
	 *
	 * @return void
	 */
	public function test_update_round_trip() {
		$options              = WP_PageNavi_Options::get_defaults();
		$options['num_pages'] = 9;

		WP_PageNavi_Options::update( $options );

		$this->assertSame( 9, WP_PageNavi_Options::get( 'num_pages' ) );
	}
}
