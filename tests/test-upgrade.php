<?php
/**
 * Migration and version-marker tests.
 *
 * @package WP-PageNavi
 */

/**
 * Covers WP_PageNavi_Options::maybe_upgrade() and the row rename it performs.
 */
class WP_PageNavi_Upgrade_Test extends WP_PageNavi_TestCase {

	/**
	 * The row every release up to 2.94.6 wrote is folded into the prefixed one
	 * and then removed, so no install is left carrying both.
	 *
	 * @return void
	 */
	/**
	 * A stock legacy row still lands, even once a `default` is registered.
	 *
	 * This plugin passes no `default` to `register_setting()` today, so the
	 * §7.6.1 trap is not armed and a fixture equal to the defaults would prove
	 * nothing on its own -- with no `default_option_wp_pagenavi_options` filter,
	 * an absent row reads back as false and any write lands.
	 *
	 * write() is nonetheless written as though the filter existed, precisely so
	 * that adding one later cannot quietly break the migration. This registers
	 * the setting WITH a default -- the change a future release makes without
	 * thinking about migrations -- and requires the fold-in to land anyway.
	 *
	 * **It does not fail if write()'s add_option() branch is removed, and that
	 * is worth knowing rather than assuming.** WP-PageNavi is protected twice
	 * over: update_option() sanitises before it compares, WP_PageNavi_Options::sanitize()
	 * does not return the defaults unchanged, so the values differ and core
	 * reaches its own add_option() fallback. wp-commentnavi carries a migration
	 * identical to this one and its sanitiser *is* idempotent on the defaults,
	 * so there the same mutation goes red. Two plugins, one code path, different
	 * safety -- which is the argument for write() being explicit rather than
	 * leaning on whichever accident happens to hold.
	 *
	 * So what this pins is the outcome a site sees: a stock legacy row is folded
	 * in and lands. The legacy row is seeded equal to the shipped defaults on
	 * purpose -- a customised one cannot see this shape at all, because a result
	 * differing from the defaults is written whatever the read before it did.
	 *
	 * @return void
	 */
	public function test_a_stock_legacy_row_lands_even_with_a_registered_default() {
		delete_option( WP_PageNavi_Options::OPTION );
		delete_option( WP_PageNavi_Options::VERSION );

		register_setting(
			WP_PageNavi_Settings::GROUP,
			WP_PageNavi_Options::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'WP_PageNavi_Options', 'sanitize' ),
				'default'           => WP_PageNavi_Options::get_defaults(),
			)
		);

		update_option( WP_PageNavi_Options::LEGACY_OPTION, WP_PageNavi_Options::get_defaults() );

		$this->assertFalse( get_option( WP_PageNavi_Options::OPTION, false ), 'The fixture is only pre-migration if the new row is genuinely absent.' );

		WP_PageNavi_Options::maybe_upgrade();

		$this->assertIsArray( get_option( WP_PageNavi_Options::OPTION, false ), 'The migration must write the row even when its result equals the shipped defaults and a default is registered.' );
		$this->assertFalse( get_option( WP_PageNavi_Options::LEGACY_OPTION, false ), 'And the legacy row is deleted, having actually been folded in.' );

		unregister_setting( WP_PageNavi_Settings::GROUP, WP_PageNavi_Options::OPTION );
	}

	public function test_the_legacy_row_is_folded_in_and_deleted() {
		update_option(
			WP_PageNavi_Options::LEGACY_OPTION,
			array(
				'num_pages' => 9,
				'prev_text' => 'BACK',
			)
		);

		WP_PageNavi_Options::maybe_upgrade();

		$this->assertFalse(
			get_option( WP_PageNavi_Options::LEGACY_OPTION ),
			'pagenavi_options must not survive the upgrade.'
		);

		$stored = get_option( WP_PageNavi_Options::OPTION );

		$this->assertIsArray( $stored, 'wp_pagenavi_options must exist after the upgrade.' );
		$this->assertSame( 9, $stored['num_pages'], 'The stored window size was lost.' );
		$this->assertSame( 'BACK', $stored['prev_text'], 'The stored previous text was lost.' );
	}

	/**
	 * A row the old, laxer version wrote is re-sanitised on the way through, so
	 * an upgrade cleans it as thoroughly as a save would.
	 *
	 * @return void
	 */
	public function test_the_legacy_row_is_sanitised_as_it_is_folded_in() {
		update_option(
			WP_PageNavi_Options::LEGACY_OPTION,
			array(
				'num_pages'  => '-4',
				'pages_text' => '<script>bad()</script>Page %CURRENT_PAGE%',
				'evil_key'   => 'x',
			)
		);

		WP_PageNavi_Options::maybe_upgrade();

		$stored = get_option( WP_PageNavi_Options::OPTION );

		$this->assertSame( 4, $stored['num_pages'], 'A negative window size must be absinted.' );
		$this->assertStringNotContainsString( '<script>', $stored['pages_text'], 'The upgrade must run kses.' );
		$this->assertStringContainsString( '%CURRENT_PAGE%', $stored['pages_text'], 'The token must survive.' );
		$this->assertArrayNotHasKey( 'evil_key', $stored, 'Unknown keys must not be carried forward.' );
	}

	/**
	 * An install that already holds a prefixed row keeps it: the legacy row is
	 * dropped rather than allowed to overwrite newer settings.
	 *
	 * @return void
	 */
	public function test_an_existing_prefixed_row_wins_over_the_legacy_one() {
		$options              = WP_PageNavi_Options::get_defaults();
		$options['num_pages'] = 7;
		WP_PageNavi_Options::update( $options );

		update_option( WP_PageNavi_Options::LEGACY_OPTION, array( 'num_pages' => 2 ) );

		WP_PageNavi_Options::maybe_upgrade();

		$this->assertSame( 7, WP_PageNavi_Options::get( 'num_pages' ), 'The newer row must win.' );
		$this->assertFalse( get_option( WP_PageNavi_Options::LEGACY_OPTION ), 'The legacy row must still go.' );
	}

	/**
	 * Both markers are stamped together, in their own row.
	 *
	 * @return void
	 */
	public function test_the_version_row_is_stamped_with_both_markers() {
		WP_PageNavi_Options::maybe_upgrade();

		$this->assertSame(
			array(
				'plugin' => WP_PAGENAVI_VERSION,
				'db'     => WP_PAGENAVI_DB_VERSION,
			),
			get_option( WP_PageNavi_Options::VERSION ),
			'The version row is stamped with both markers, which is what stops the upgrade running again.'
		);
	}

	/**
	 * With nothing stored the markers read as empty strings rather than notices,
	 * and a corrupt row is treated the same way.
	 *
	 * @return void
	 */
	public function test_missing_and_corrupt_marker_rows_read_as_empty() {
		$this->assertSame(
			array(
				'plugin' => '',
				'db'     => '',
			),
			WP_PageNavi_Options::get_versions(),
			'A missing marker row reads as empty strings rather than null.'
		);

		update_option( WP_PageNavi_Options::VERSION, 'not-an-array' );

		$this->assertSame(
			array(
				'plugin' => '',
				'db'     => '',
			),
			WP_PageNavi_Options::get_versions(),
			'And a corrupt one, rather than propagating.'
		);
	}

	/**
	 * Running twice changes nothing, which is what lets it hang off admin_init.
	 *
	 * @return void
	 */
	public function test_the_upgrade_is_idempotent() {
		update_option( WP_PageNavi_Options::LEGACY_OPTION, array( 'num_pages' => 6 ) );

		WP_PageNavi_Options::maybe_upgrade();
		$first = get_option( WP_PageNavi_Options::OPTION );

		WP_PageNavi_Options::maybe_upgrade();

		$this->assertSame( $first, get_option( WP_PageNavi_Options::OPTION ), 'A second run must be a no-op.' );
	}

	/**
	 * An install already stamped at this version does no work at all, so the
	 * admin_init hook costs one option read on every request and nothing more.
	 *
	 * @return void
	 */
	public function test_an_up_to_date_install_does_no_work() {
		WP_PageNavi_Options::maybe_upgrade();

		update_option( WP_PageNavi_Options::LEGACY_OPTION, array( 'num_pages' => 3 ) );

		WP_PageNavi_Options::maybe_upgrade();

		$this->assertSame(
			array( 'num_pages' => 3 ),
			get_option( WP_PageNavi_Options::LEGACY_OPTION ),
			'The markers already match, so migrate() must not have run.'
		);
	}

	/**
	 * Activating the plugin stamps the version row, so a fresh install is never
	 * left looking like one that has never been upgraded.
	 *
	 * @return void
	 */
	public function test_activation_stamps_the_version_row() {
		WP_PageNavi::activate();

		$markers = get_option( WP_PageNavi_Options::VERSION );

		$this->assertIsArray( $markers, 'Activation stamps a version row, so an upgrade has somewhere to read from.' );
		$this->assertSame( WP_PAGENAVI_VERSION, $markers['plugin'], 'Activation stamps the plugin marker.' );
	}
}
