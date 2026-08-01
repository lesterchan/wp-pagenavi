<?php
/**
 * The release invariants, asserted from the source and from the stored rows.
 *
 * These are the house rules every plugin in this family shares, and every one
 * of them has been broken by an ordinary edit at some point: a header field
 * that drifted out of the canonical order, a new directory shipped without its
 * silence guard, a version bumped in one file of three, a readme header line
 * that lost the two trailing spaces holding it apart from the next.
 *
 * They are the things a restructuring quietly breaks and nothing notices until
 * a release fails its pre-flight months later, so catching them here is far
 * cheaper than catching them there.
 *
 * @package WP-PageNavi
 */

/**
 * @coversNothing
 */
class WP_PageNavi_Metadata_Test extends WP_PageNavi_TestCase {

	const VERSION = '3.0.0';

	/**
	 * The main plugin file.
	 *
	 * @return string
	 */
	protected function plugin_file() {
		return wp_pagenavi_test_read( 'wp-pagenavi.php' );
	}

	/**
	 * The readme.
	 *
	 * @return string
	 */
	protected function readme() {
		return wp_pagenavi_test_read( 'README.md' );
	}

	/**
	 * A field from the main plugin file's header docblock.
	 *
	 * @param string $field Field name.
	 * @return string
	 */
	protected function header_field( $field ) {
		$data = get_file_data( dirname( __DIR__ ) . '/wp-pagenavi.php', array( $field => $field ) );

		return $data[ $field ];
	}

	/**
	 * A field from the readme's header block.
	 *
	 * @param string $field Field name.
	 * @return string
	 */
	protected function readme_field( $field ) {
		preg_match( '/^' . preg_quote( $field, '/' ) . ':\s*(.+?)\s*$/m', $this->readme(), $matches );

		return isset( $matches[1] ) ? $matches[1] : '';
	}

	/**
	 * Every directory in the repo that holds at least one PHP file.
	 *
	 * @return string[] Absolute paths, plugin root included.
	 */
	protected function php_directories() {
		$root  = dirname( __DIR__ );
		$found = array();

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			$path = $file->getPathname();

			// vendor/ and node_modules/ are not ours and never ship, and
			// artifacts/ is Playwright output: gitignored, never deployed, and
			// recreated on any failing run.
			if ( false !== strpos( $path, '/vendor/' )
				|| false !== strpos( $path, '/node_modules/' )
				|| false !== strpos( $path, '/artifacts/' ) ) {
				continue;
			}

			if ( 'php' === strtolower( $file->getExtension() ) ) {
				$found[ dirname( $path ) ] = true;
			}
		}

		return array_keys( $found );
	}

	public function test_version_matches_everywhere() {
		$this->assertStringContainsString( ' * Version: ' . self::VERSION, $this->plugin_file() );
		$this->assertStringContainsString( "define( 'WP_PAGENAVI_VERSION', '" . self::VERSION . "' );", $this->plugin_file() );
		$this->assertStringContainsString( 'Stable tag: ' . self::VERSION, $this->readme() );
	}

	public function test_the_changelog_has_a_section_for_this_version() {
		$this->assertStringContainsString( '### ' . self::VERSION . "\n", $this->readme() );
	}

	/**
	 * The order is neither alphabetical nor intuitive -- Requires at least and
	 * Requires PHP sit before Author -- so it is copied, never composed.
	 */
	public function test_the_plugin_header_fields_are_in_the_canonical_order() {
		$expected = array(
			'Plugin Name',
			'Plugin URI',
			'Description',
			'Version',
			'Requires at least',
			'Requires PHP',
			'Author',
			'Author URI',
			'License',
			'License URI',
			'Text Domain',
			'Domain Path',
		);

		preg_match( '#^<\?php\s*/\*\*(.+?)\*/#s', $this->plugin_file(), $matches );
		$this->assertNotEmpty( $matches, 'The plugin file must open with a docblock header.' );

		preg_match_all( '/^\s*\*\s*([A-Z][A-Za-z ]*?):\s/m', $matches[1], $fields );

		$this->assertSame( $expected, $fields[1] );
	}

	/**
	 * The readme order differs from the PHP one on purpose: Requires PHP comes
	 * after Stable tag here. They are not to be harmonised.
	 */
	public function test_the_readme_header_fields_are_in_the_canonical_order() {
		$expected = array(
			'Contributors',
			'Donate link',
			'Tags',
			'Requires at least',
			'Tested up to',
			'Stable tag',
			'Requires PHP',
			'License',
			'License URI',
		);

		$header = substr( $this->readme(), 0, (int) strpos( $this->readme(), "\n\n" ) );

		preg_match_all( '/^([A-Z][A-Za-z ]*?):\s/m', $header, $fields );

		$this->assertSame( $expected, $fields[1] );
	}

	public function test_requires_headers_match_readme() {
		$this->assertStringContainsString( ' * Requires at least: 6.8', $this->plugin_file() );
		$this->assertStringContainsString( ' * Requires PHP: 8.2', $this->plugin_file() );
		$this->assertStringContainsString( 'Requires at least: 6.8', $this->readme() );
		$this->assertStringContainsString( 'Requires PHP: 8.2', $this->readme() );
	}

	/**
	 * Header lines need two trailing spaces to render as separate lines.
	 *
	 * Markdown joins consecutive lines into one paragraph unless each is ended
	 * with a hard line break, so a missing pair renders as
	 * "License: GPLv2 or later License URI: https://..." on GitHub. It is
	 * invisible in the source and in a diff, which is exactly why it wants a
	 * test. The last line needs none, having nothing after it to run into.
	 */
	public function test_every_readme_header_line_keeps_its_line_break() {
		$header = substr( $this->readme(), 0, (int) strpos( $this->readme(), "\n\n" ) );
		$lines  = explode( "\n", $header );

		// The first line is the "# WP-PageNavi" heading, not a header field.
		$fields = array_slice( $lines, 1 );
		$last   = array_pop( $fields );

		$this->assertCount( 8, $fields, 'Nine header fields, the ninth popped off above.' );

		foreach ( $fields as $line ) {
			$this->assertStringEndsWith(
				'  ',
				$line,
				"Needs two trailing spaces or it merges with the line below: {$line}"
			);
		}

		$this->assertStringStartsWith( 'License URI:', $last );
		$this->assertSame( rtrim( $last ), $last, 'The last header line must not have trailing spaces.' );
	}

	public function test_the_readme_lists_exactly_five_tags() {
		preg_match( '/^Tags:\s*(.+?)\s*$/m', $this->readme(), $matches );

		$this->assertNotEmpty( $matches, 'The readme must carry a Tags line.' );
		$this->assertCount( 5, explode( ',', $matches[1] ), 'Exactly five tags, no more and no fewer.' );
	}

	/**
	 * Bare versions: "### 3.0.0", never "### Version 3.0.0".
	 */
	public function test_every_changelog_heading_is_a_bare_version() {
		$this->assertSame( 0, preg_match( '/^### Version /m', $this->readme() ) );
	}

	/**
	 * The catalogue comes from translate.wordpress.org, and since WP 6.7 calling
	 * load_plugin_textdomain() this early trips _doing_it_wrong.
	 */
	public function test_the_plugin_does_not_load_its_own_textdomain() {
		$this->assertStringNotContainsString( 'load_plugin_textdomain', wp_pagenavi_test_source_code() );
	}

	/**
	 * The old forums.lesterchan.net is gone, and the rest of these had drifted
	 * to http over twenty years. Code spans are exempt: they document input.
	 */
	public function test_no_insecure_or_dead_links_remain() {
		$readme = preg_replace( '/`[^`]*`/', '', $this->readme() );

		$this->assertSame( 0, preg_match( '#http://#', $readme ), 'Every readme link must use https.' );
		$this->assertSame( 0, preg_match( '#http://#', $this->plugin_file() ) );
		$this->assertStringNotContainsString( 'forums.lesterchan.net', $readme );
	}

	public function test_every_directory_has_an_index_php() {
		foreach ( $this->php_directories() as $directory ) {
			$this->assertFileExists(
				$directory . '/index.php',
				"{$directory} ships PHP and so needs an index.php silence guard."
			);
		}
	}

	public function test_the_guards_use_the_docblock_form() {
		foreach ( $this->php_directories() as $directory ) {
			$guard = (string) file_get_contents( $directory . '/index.php' );

			// phpcbf cannot fix the one-line "// Silence is golden." form.
			$this->assertStringContainsString( '/**', $guard, "{$directory}/index.php must use the docblock form." );
			$this->assertStringContainsString( 'Silence is golden.', $guard );
		}
	}

	/**
	 * Every translation call must carry the plugin's own text domain, except the
	 * four navigation aria-labels, which reuse strings WordPress core already
	 * defines so they arrive translated in every locale core supports.
	 */
	public function test_every_translation_call_uses_the_plugin_text_domain() {
		$code = wp_pagenavi_test_source_code();

		preg_match_all( '/(?:__|_n|_x)\((.*?)\);/s', $code, $calls );

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
	 * p but 'Previous Page' and 'Next Page' with a capital one. Capitalising the
	 * first two silently drops back to English in every locale, which no test
	 * that only checks the domain would catch.
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

	public function test_the_gpl_licence_is_shipped() {
		$licence = wp_pagenavi_test_read( 'LICENSE' );

		$this->assertStringContainsString( 'GNU GENERAL PUBLIC LICENSE', $licence );
		$this->assertStringContainsString( 'Version 2, June 1991', $licence );
	}

	/**
	 * The header says "GPLv2 or later", so the copyright block below it must say
	 * the same. A v2-only block there contradicts both the header and the
	 * GPL-2.0-or-later in composer.json.
	 */
	public function test_the_copyright_block_is_the_or_later_variant() {
		$this->assertStringContainsString(
			'either version 2 of the License, or',
			$this->plugin_file()
		);
		$this->assertStringContainsString( '(at your option) any later version.', $this->plugin_file() );
		$this->assertStringNotContainsString( 'version 2, as', $this->plugin_file() );
	}

	/**
	 * The catalogue is built by translate.wordpress.org, and Travis has been
	 * dead for these repos for years.
	 */
	public function test_no_abandoned_build_or_translation_artefacts_ship() {
		$root = dirname( __DIR__ );

		$this->assertFileDoesNotExist( $root . '/.travis.yml' );
		$this->assertDirectoryDoesNotExist( $root . '/languages' );

		foreach ( array( 'pot', 'po', 'mo' ) as $extension ) {
			$this->assertSame(
				array(),
				(array) glob( $root . '/*.' . $extension ),
				"No .{$extension} files: translate.wordpress.org builds the catalogue."
			);
		}
	}

	public function test_canonical_lesterchan_urls() {
		$this->assertSame(
			'https://lesterchan.net/portfolio/programming/php/',
			$this->header_field( 'Plugin URI' )
		);
		$this->assertSame( 'https://lesterchan.net', $this->header_field( 'Author URI' ) );
		$this->assertSame( 'https://lesterchan.net/site/donation/', $this->readme_field( 'Donate link' ) );
		$this->assertSame(
			'https://www.gnu.org/licenses/gpl-2.0.html',
			$this->header_field( 'License URI' )
		);
		$this->assertSame( 'https://www.gnu.org/licenses/gpl-2.0.html', $this->readme_field( 'License URI' ) );
	}

	/**
	 * One name, in every plugin. A second contributor has to be added on
	 * wordpress.org as well, so a name here that is not on the listing silently
	 * does nothing -- which is what "GamerZ, scribu" was doing here until 3.0.0.
	 */
	public function test_contributors_is_gamerz_only() {
		$this->assertSame( 'GamerZ', $this->readme_field( 'Contributors' ) );
	}

	public function test_text_domain_is_the_plugin_slug() {
		$this->assertSame( 'wp-pagenavi', $this->header_field( 'Text Domain' ) );
		$this->assertSame( '/languages', $this->header_field( 'Domain Path' ) );
		$this->assertSame( 'wp-pagenavi', WP_PAGENAVI_SLUG );
	}

	/**
	 * The second-level headings are a closed set in a fixed order.
	 *
	 * Third-level ones are not: Donations, the usage subsections and every
	 * changelog version live below these.
	 */
	public function test_readme_sections_are_the_canonical_set() {
		preg_match_all( '/^## (.+?)\s*$/m', $this->readme(), $sections );

		$this->assertSame(
			array(
				'Description',
				'Usage',
				'Frequently Asked Questions',
				'Screenshots',
				'Changelog',
				'Upgrade Notice',
			),
			$sections[1]
		);
	}

	/**
	 * Donations is the last h3 of the description, with the wording shared by
	 * all nineteen plugins.
	 */
	public function test_the_donations_note_is_the_shared_wording() {
		$readme      = $this->readme();
		$description = substr( $readme, (int) strpos( $readme, '## Description' ), (int) strpos( $readme, '## Usage' ) - (int) strpos( $readme, '## Description' ) );

		$this->assertStringContainsString( "### Donations\n", $description );
		$this->assertStringContainsString(
			'I spent most of my free time creating, updating, maintaining and supporting these plugins, if you really love my plugins and could spare me a couple of bucks, I will really appreciate it. If not feel free to use it without any obligations.',
			$description
		);

		// The two variants the family carried before: a stray bullet, and a
		// clause about school that stopped being true a long time ago.
		$this->assertStringNotContainsString( '* I spent most of my free time', $description );
		$this->assertStringNotContainsString( 'school allowance', $description );
	}

	/**
	 * Five prefixes, and nothing else.
	 *
	 * The listing on wordpress.org renders the changelog verbatim, so a stray
	 * "Important:" or a lowercase "New:" is visible to every reader of it.
	 */
	public function test_changelog_prefixes_are_canonical() {
		$readme    = $this->readme();
		$changelog = substr( $readme, (int) strpos( $readme, '## Changelog' ) );
		$changelog = substr( $changelog, 0, (int) strpos( $changelog, "\n## Upgrade Notice" ) );

		preg_match_all( '/^\* (.+?):/m', $changelog, $bullets );

		$this->assertNotEmpty( $bullets[1], 'The changelog must carry bullets.' );

		foreach ( $bullets[1] as $prefix ) {
			$this->assertContains(
				$prefix . ':',
				array( 'BREAKING:', 'NEW:', 'CHANGED:', 'FIXED:', 'NOTE:' ),
				"'{$prefix}:' is not one of the five allowed changelog prefixes."
			);
		}
	}

	/**
	 * Every break a site owner updating from the released 2.94.6 would notice
	 * has to be spelled out under Upgrade Notice, not only in the changelog.
	 */
	public function test_the_upgrade_notice_covers_the_gap_since_the_released_version() {
		$readme = $this->readme();
		$notice = substr( $readme, (int) strpos( $readme, '## Upgrade Notice' ) );

		foreach ( array( '6.8', '8.2', 'wp_pagenavi_options', 'page=wp-pagenavi', 'wp-pagenavi.css', 'WP_PageNavi_Options' ) as $break ) {
			$this->assertStringContainsString(
				$break,
				$notice,
				"The Upgrade Notice must tell a site owner about {$break}."
			);
		}
	}

	/**
	 * WP-PageNavi ships no JavaScript at all, which is the strongest form of the
	 * house rule: nothing to enqueue, so nothing to depend on jQuery.
	 */
	public function test_no_jquery_is_enqueued() {
		$code = wp_pagenavi_test_source_code();

		$this->assertStringNotContainsStringIgnoringCase( 'jquery', $code );
		$this->assertStringNotContainsString(
			'wp_enqueue_script(',
			$code,
			'The plugin registers no scripts, so it can declare no dependencies.'
		);
		$this->assertSame( array(), (array) glob( dirname( __DIR__ ) . '/js/*.js' ) );
	}

	/**
	 * No plugin in this family ships a second, mirrored stylesheet: the front
	 * end uses CSS logical properties instead, so one sheet serves both
	 * directions.
	 */
	public function test_no_rtl_stylesheet_is_registered() {
		$root = dirname( __DIR__ );

		$this->assertSame( array(), (array) glob( $root . '/*-rtl.css' ) );
		$this->assertSame( array(), (array) glob( $root . '/css/*-rtl.css' ) );
		$this->assertStringNotContainsString(
			'wp_style_add_data',
			wp_pagenavi_test_source_code(),
			"No plugin registers 'rtl' style data."
		);
	}

	/**
	 * The upgrade markers live in their own row, holding those two keys and no
	 * others. Anything else in here means a marker has drifted back into the
	 * settings array, which is the bug this shape exists to make impossible.
	 */
	public function test_version_row_holds_exactly_plugin_and_db() {
		WP_PageNavi_Options::maybe_upgrade();

		$markers = get_option( WP_PageNavi_Options::VERSION );

		$this->assertIsArray( $markers, 'wp_pagenavi_version must be an array.' );

		$keys = array_keys( $markers );
		sort( $keys );

		$this->assertSame( array( 'db', 'plugin' ), $keys );
		$this->assertSame( WP_PAGENAVI_VERSION, $markers['plugin'] );
		$this->assertSame( WP_PAGENAVI_DB_VERSION, $markers['db'] );
	}

	/**
	 * The regression guard for the wp-useronline bug: a sanitiser that has to
	 * rescue the version markers out of the value it is replacing will sooner or
	 * later fail to, and the upgrade then re-runs on every request. Keeping the
	 * markers in a row of their own makes that impossible, and this fails the
	 * moment someone moves one back.
	 */
	public function test_settings_sanitizer_never_stores_version_markers() {
		$clean = WP_PageNavi_Options::sanitize(
			array(
				'num_pages'  => 5,
				'version'    => '3.0.0',
				'db_version' => '1',
				'versions'   => array( 'plugin' => '3.0.0' ),
				'plugin'     => '3.0.0',
				'db'         => '1',
			)
		);

		foreach ( array( 'version', 'db_version', 'versions', 'plugin', 'db' ) as $key ) {
			$this->assertArrayNotHasKey(
				$key,
				$clean,
				"'{$key}' is a version marker and must never be stored in the settings array."
			);
		}

		WP_PageNavi_Options::update( $clean );
		WP_PageNavi_Options::maybe_upgrade();

		$this->assertSame(
			array_keys( WP_PageNavi_Options::get_defaults() ),
			array_keys( (array) get_option( WP_PageNavi_Options::OPTION ) ),
			'The settings row holds the plugin\'s own keys and nothing else.'
		);
	}

	/**
	 * Deleting the plugin leaves nothing behind.
	 *
	 * The assertion is deliberately a LIKE over wp_options rather than two
	 * delete_option() checks: a row added later and forgotten in uninstall.php
	 * is exactly the failure this is here to catch. The multisite config runs
	 * the same test through uninstall.php's get_sites() branch.
	 *
	 * This is the only test that runs the uninstaller. uninstall.php is included
	 * with require_once, so a second test doing the same would silently include
	 * nothing and assert against rows no one had removed.
	 */
	public function test_uninstall_removes_every_option_row() {
		WP_PageNavi_Options::update( WP_PageNavi_Options::get_defaults() );
		WP_PageNavi_Options::maybe_upgrade();

		$this->assertNotEmpty(
			$this->stored_option_names(),
			'There should be rows to remove before uninstall runs.'
		);

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'wp-pagenavi/wp-pagenavi.php' );
		}

		require_once dirname( __DIR__ ) . '/uninstall.php';

		wp_cache_flush();

		$this->assertSame(
			array(),
			$this->stored_option_names(),
			'uninstall.php must remove every wp_pagenavi_* row.'
		);
		$this->assertFalse(
			get_option( 'pagenavi_options' ),
			'The pre-3.0.0 row must go too, for an install that never reached wp-admin.'
		);
	}
}
