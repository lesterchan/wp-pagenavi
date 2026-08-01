<?php
/**
 * The release invariants, asserted from the source and from the stored rows.
 *
 * Everything §7.2 asks of all nineteen plugins now lives in
 * Plugin_Metadata_TestCase. What is left here is what only WP-PageNavi can
 * say: the version it ships, its class prefix, the breaks its Upgrade Notice
 * has to cover, and the rules that have no home in the shared base -- the four
 * aria-labels borrowed from core's catalogue, the readme link and tag hygiene,
 * and the pre-3.0.0 option row no LIKE over wp_pagenavi_% can see.
 *
 * @package WP-PageNavi
 */

/**
 * WP-PageNavi's half of the shared metadata contract.
 *
 * @coversNothing
 */
class WP_PageNavi_Metadata_Test extends Plugin_Metadata_TestCase {

	/**
	 * The version this release ships.
	 *
	 * @return string
	 */
	protected function expected_version() {
		return '3.0.0';
	}

	/**
	 * The prefix every class the plugin declares carries.
	 *
	 * @return string
	 */
	protected function class_prefix() {
		return 'WP_PageNavi';
	}

	/**
	 * What a site owner updating from the released 2.94.6 would notice.
	 *
	 * Two things make this list longer than most. Every class was prefixed and
	 * one of them renamed as well, so custom PHP reaching into them breaks; and
	 * the bundled SCB framework is gone, which took a set of global functions
	 * other plugins may have been relying on WP-PageNavi to supply.
	 *
	 * @return string[]
	 */
	protected function upgrade_notice_subjects() {
		return array(
			'WordPress 6.8',
			'PHP 8.2',
			'`pagenavi_options`',
			'`wp_pagenavi_options`',
			'options-general.php?page=wp-pagenavi',
			'`pagenavi-css.css`',
			'css/wp-pagenavi.css',
			'`PageNavi_Options`',
			'`WP_PageNavi_Options`',
			'`PageNavi_Call`',
			'`PageNavi_Core`',
			'`PageNavi_Admin`',
			'`WP_PageNavi_Settings`',
			'`PageNavi_Options_Page`',
			'`PageNavi_Core::$options`',
			'SCB framework',
			'`scb_init()`',
			'`html()`',
		);
	}

	/**
	 * Seed the rows uninstall has to remove.
	 *
	 * @return void
	 */
	protected function seed_option_rows() {
		WP_PageNavi_Options::update( WP_PageNavi_Options::get_defaults() );
		WP_PageNavi_Options::maybe_upgrade();
	}

	/**
	 * Write the wp_pagenavi_version marker row.
	 *
	 * @return void
	 */
	protected function write_version_row() {
		WP_PageNavi_Options::maybe_upgrade();
	}

	/**
	 * Round-trip the settings sanitiser.
	 *
	 * @param array $input What the settings form is pretending to have posted.
	 * @return array
	 */
	protected function sanitize_settings( array $input ) {
		return (array) WP_PageNavi_Options::sanitize( $input );
	}

	/**
	 * A real settings key beside the poison, so the sanitiser actually runs.
	 *
	 * @return array
	 */
	protected function settings_fixture() {
		return array( 'num_pages' => 5 );
	}

	/**
	 * Register the front-end stylesheet.
	 *
	 * It is only enqueued when the plugin's own CSS is switched on, so the
	 * defaults have to be written before the shared RTL test has a handle to
	 * look at. There is no script to register: this plugin ships none.
	 *
	 * @return void
	 */
	protected function register_plugin_assets() {
		WP_PageNavi_Options::update( WP_PageNavi_Options::get_defaults() );

		WP_PageNavi_Core::stylesheets();
	}

	/**
	 * Five tags, because wordpress.org shows five and ignores the rest.
	 */
	public function test_the_readme_lists_exactly_five_tags() {
		preg_match( '/^Tags:\s*(.+?)\s*$/m', $this->readme(), $matches );

		$this->assertNotEmpty( $matches, 'The readme must carry a Tags line.' );
		$this->assertCount( 5, explode( ',', $matches[1] ), 'Exactly five tags, no more and no fewer.' );
	}

	/**
	 * The copyright block says what the header says.
	 *
	 * The header declares "GPLv2 or later", so a version-2-only block below it
	 * would contradict both the header and the GPL-2.0-or-later in
	 * composer.json.
	 */
	public function test_the_copyright_block_is_the_or_later_variant() {
		$this->assertSame( 'GPLv2 or later', $this->header_field( 'License' ) );
		$this->assertStringContainsString(
			'either version 2 of the License, or',
			$this->plugin_file()
		);
		$this->assertStringContainsString( '(at your option) any later version.', $this->plugin_file() );
		$this->assertStringNotContainsString( 'version 2, as', $this->plugin_file() );
	}

	/**
	 * Every translation call carries a text domain, and only two are allowed.
	 *
	 * The plugin's own is wp-pagenavi. The four navigation aria-labels reuse
	 * strings WordPress core already defines, so they name 'default' instead
	 * and arrive translated in every locale core supports.
	 */
	public function test_every_translation_call_uses_the_plugin_text_domain() {
		preg_match_all( '/(?:__|_n|_x)\((.*?)\);/s', wp_pagenavi_test_source_code(), $calls );

		$this->assertNotEmpty( $calls[1], 'The plugin makes at least one translation call.' );

		foreach ( $calls[1] as $arguments ) {
			$this->assertMatchesRegularExpression(
				"/'(wp-pagenavi|default)'/",
				$arguments,
				"A translation call is missing the text domain: {$arguments}"
			);
		}
	}

	/**
	 * The four labels that borrow core's catalogue, frozen at core's spelling.
	 *
	 * A msgid is a byte-for-byte lookup key, and core is not consistent about
	 * the casing: it spells these 'First page' and 'Last page' with a lowercase
	 * p but 'Previous Page' and 'Next Page' with a capital one. Capitalising
	 * the first two silently drops back to English in every locale, which no
	 * test that only checks the domain would catch.
	 */
	public function test_the_borrowed_aria_labels_keep_cores_spelling() {
		$code = wp_pagenavi_test_source_code();

		foreach ( array( 'First page', 'Previous Page', 'Next Page', 'Last page' ) as $label ) {
			$this->assertStringContainsString(
				"__( '{$label}', 'default' )",
				$code,
				"The '{$label}' label must match core's own spelling exactly."
			);
		}
	}

	/**
	 * The Donations note is the wording shared by all nineteen plugins.
	 *
	 * It is the last h3 of the description, and the two variants the family
	 * used to carry -- a stray bullet, and a clause about a school allowance
	 * that stopped being true a long time ago -- are gone.
	 */
	public function test_the_donations_note_is_the_shared_wording() {
		$readme      = $this->readme();
		$from        = (int) strpos( $readme, '## Description' );
		$description = substr( $readme, $from, (int) strpos( $readme, '## Usage' ) - $from );

		$this->assertStringContainsString( "### Donations\n", $description );
		$this->assertStringContainsString(
			'I spent most of my free time creating, updating, maintaining and supporting these plugins, if you really love my plugins and could spare me a couple of bucks, I will really appreciate it. If not feel free to use it without any obligations.',
			$description
		);

		$this->assertStringNotContainsString( '* I spent most of my free time', $description );
		$this->assertStringNotContainsString( 'school allowance', $description );
	}

	/**
	 * The old forums.lesterchan.net is gone, and the rest had drifted to http.
	 *
	 * Code spans are exempt: they document input rather than link anywhere.
	 */
	public function test_no_insecure_or_dead_links_remain() {
		$readme = (string) preg_replace( '/`[^`]*`/', '', $this->readme() );

		$this->assertSame( 0, preg_match( '#http://#', $readme ), 'Every readme link must use https.' );
		$this->assertSame( 0, preg_match( '#http://#', $this->plugin_file() ) );
		$this->assertStringNotContainsString( 'forums.lesterchan.net', $readme );
	}

	/**
	 * The settings row holds the plugin's own keys and nothing else.
	 *
	 * Stronger than the shared marker assertion, and only expressible here:
	 * the allowed set is exactly what get_defaults() returns, so a key the
	 * sanitiser lets through that no default names shows up as a difference.
	 */
	public function test_the_settings_row_holds_only_the_plugins_own_keys() {
		$clean = WP_PageNavi_Options::sanitize(
			array(
				'num_pages' => 5,
				'plugin'    => '3.0.0',
				'db'        => '1',
			)
		);

		WP_PageNavi_Options::update( $clean );
		WP_PageNavi_Options::maybe_upgrade();

		$this->assertSame(
			array_keys( WP_PageNavi_Options::get_defaults() ),
			array_keys( (array) get_option( WP_PageNavi_Options::OPTION ) ),
			"The settings row holds the plugin's own keys and nothing else."
		);
	}

	/**
	 * The pre-3.0.0 settings row goes too, and no LIKE would find it.
	 *
	 * The legacy name does not begin with the plugin's own option prefix, so
	 * the shared uninstall test -- which walks wp_options for wp_pagenavi_% --
	 * cannot see it. Deleting it is the whole reason the uninstaller names
	 * three rows rather than two.
	 */
	public function test_uninstall_removes_the_pre_3_0_0_settings_row() {
		update_option( WP_PageNavi_Options::LEGACY_OPTION, array( 'num_pages' => 5 ) );

		$this->assertNotFalse(
			get_option( WP_PageNavi_Options::LEGACY_OPTION ),
			'There should be a legacy row to remove, or this proves nothing.'
		);

		$this->run_uninstall();

		wp_cache_flush();

		$this->assertFalse(
			get_option( WP_PageNavi_Options::LEGACY_OPTION ),
			'Uninstalling left the pre-3.0.0 pagenavi_options row behind.'
		);
	}
}
