<?php
/**
 * Tests for WP_PageNavi_Call, the per-invocation helper.
 *
 * @package WP-PageNavi
 */

/**
 * Covers pagination maths and link building.
 */
class WP_PageNavi_Call_Test extends WP_PageNavi_TestCase {

	/**
	 * Build a call instance.
	 *
	 * @param array $args Arguments.
	 * @return WP_PageNavi_Call
	 */
	protected function call( $args = array() ) {
		return new WP_PageNavi_Call( wp_parse_args( $args, array( 'type' => 'posts' ) ) );
	}

	/**
	 * Arguments are readable as properties.
	 *
	 * @return void
	 */
	public function test_arguments_are_readable_as_properties() {
		$call = $this->call( array( 'type' => 'users' ) );

		$this->assertSame( 'users', $call->type );
	}

	/**
	 * An argument that was never supplied reads as null rather than raising a
	 * notice, which is what the pre-3.0.0 version did on PHP 8.
	 *
	 * @return void
	 */
	public function test_unknown_argument_is_null() {
		$this->assertNull( $this->call()->no_such_argument );
	}

	/**
	 * Pagination figures for a posts query are integers.
	 *
	 * @return void
	 */
	public function test_posts_pagination_args_are_integers() {
		self::factory()->post->create_many( 12 );

		$query = new WP_Query(
			array(
				'post_type'      => 'post',
				'posts_per_page' => 5,
				'paged'          => 2,
			)
		);

		list( $per_page, $paged, $total ) = $this->call( array( 'query' => $query ) )->get_pagination_args();

		$this->assertSame( 5, $per_page );
		$this->assertSame( 2, $paged );
		$this->assertSame( 3, $total );
	}

	/**
	 * The user-query branch derives its figures with floor()/ceil(), which return
	 * floats. They must come back as integers or every strict comparison in the
	 * renderer silently stops matching.
	 *
	 * @return void
	 */
	public function test_user_pagination_args_are_integers_not_floats() {
		self::factory()->user->create_many( 8 );

		$users = new WP_User_Query(
			array(
				'number' => 2,
				'offset' => 4,
			)
		);

		list( $per_page, $paged, $total ) = $this->call(
			array(
				'type'  => 'users',
				'query' => $users,
			)
		)->get_pagination_args();

		$this->assertIsInt( $per_page );
		$this->assertIsInt( $paged );
		$this->assertIsInt( $total );
		$this->assertSame( 3, $paged );
	}

	/**
	 * A query with no results still reports at least one page.
	 *
	 * @return void
	 */
	public function test_empty_query_reports_one_page() {
		$query = new WP_Query(
			array(
				'post_type'      => 'post',
				'posts_per_page' => 5,
				'paged'          => 1,
				'post__in'       => array( 0 ),
			)
		);

		list( , $paged, $total ) = $this->call( array( 'query' => $query ) )->get_pagination_args();

		$this->assertSame( 1, $paged );
		$this->assertSame( 1, $total );
	}

	/**
	 * A blank link text produces no anchor at all, which is how a blanked setting
	 * hides that part of the navigation.
	 *
	 * @return void
	 */
	public function test_get_single_is_empty_without_text() {
		$this->assertSame( '', $this->call()->get_single( 2, '', array() ) );
		$this->assertSame( '', $this->call()->get_single( 2, null, array() ) );
	}

	/**
	 * Attributes are emitted in the order given, with the href last, and are
	 * escaped.
	 *
	 * @return void
	 */
	public function test_get_single_builds_an_escaped_anchor() {
		$html = $this->call()->get_single(
			3,
			'Page %PAGE_NUMBER%',
			array(
				'class' => 'page smaller',
				'title' => 'A "quoted" title',
			)
		);

		$this->assertStringStartsWith( '<a class="page smaller" title=', $html );
		$this->assertStringContainsString( '&quot;quoted&quot;', $html );
		$this->assertStringContainsString( '>Page 3</a>', $html );

		// href is appended after the caller's attributes.
		$this->assertGreaterThan( strpos( $html, 'title=' ), strpos( $html, 'href=' ) );
	}

	/**
	 * A boolean attribute repeats its name as the value, and false omits the
	 * attribute entirely. This mirrors the scb html() helper the anchor builder
	 * replaced, so third-party callers of get_single() keep working.
	 *
	 * @return void
	 */
	public function test_boolean_attributes() {
		$html = $this->call()->get_single(
			2,
			'x',
			array(
				'data-on'  => true,
				'data-off' => false,
				'class'    => 'c',
			)
		);

		$this->assertStringContainsString( 'data-on="data-on"', $html );
		$this->assertStringNotContainsString( 'data-off', $html );
		$this->assertStringContainsString( 'class="c"', $html );
	}

	/**
	 * The token replaced in the link text is configurable, which is how the first
	 * and last links substitute the page total instead of the page number.
	 *
	 * @return void
	 */
	public function test_custom_replacement_token() {
		$html = $this->call()->get_single( 9, 'Last of %TOTAL_PAGES%', array(), '%TOTAL_PAGES%' );

		$this->assertStringContainsString( '>Last of 9</a>', $html );
	}

	/**
	 * Posts URLs come from get_pagenum_link(); multipart URLs from
	 * get_multipage_link().
	 *
	 * @return void
	 */
	public function test_get_url_switches_on_type() {
		$this->assertSame( get_pagenum_link( 3 ), $this->call()->get_url( 3 ) );

		$post_id         = self::factory()->post->create( array( 'post_content' => 'A<!--nextpage-->B' ) );
		$GLOBALS['post'] = get_post( $post_id );

		$this->assertSame(
			get_multipage_link( 2 ),
			$this->call( array( 'type' => 'multipart' ) )->get_url( 2 )
		);
	}

	/**
	 * Page one of a multipart post is the bare permalink, and later pages hang a
	 * page argument off it when the site has no permalink structure.
	 *
	 * @return void
	 */
	public function test_get_multipage_link() {
		$post_id         = self::factory()->post->create( array( 'post_content' => 'A<!--nextpage-->B' ) );
		$GLOBALS['post'] = get_post( $post_id );

		$this->assertSame( get_permalink( $post_id ), get_multipage_link( 1 ) );
		$this->assertStringContainsString( 'page=2', get_multipage_link( 2 ) );
	}

	/**
	 * A draft always uses the query-argument form, because it has no pretty
	 * permalink yet.
	 *
	 * @return void
	 */
	public function test_get_multipage_link_for_a_draft() {
		update_option( 'permalink_structure', '/%postname%/' );

		$post_id         = self::factory()->post->create(
			array(
				'post_status'  => 'draft',
				'post_content' => 'A<!--nextpage-->B',
			)
		);
		$GLOBALS['post'] = get_post( $post_id );

		$this->assertStringContainsString( 'page=2', get_multipage_link( 2 ) );

		update_option( 'permalink_structure', '' );
	}
}
