<?php
/**
 * Rendering tests for the wp_pagenavi() template tag.
 *
 * @package WP-PageNavi
 */

/**
 * Covers the markup wp_pagenavi() produces.
 */
class Test_PageNavi_Render extends WP_UnitTestCase {

	/**
	 * IDs of the posts created for the suite.
	 *
	 * @var array
	 */
	protected static $post_ids = array();

	/**
	 * Create 47 posts once, giving 10 pages at 5 per page.
	 *
	 * @param WP_UnitTest_Factory $factory Fixture factory.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$post_ids = $factory->post->create_many( 47 );
	}

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
	 * Build a posts query at a given page.
	 *
	 * @param int $paged    Page number.
	 * @param int $per_page Posts per page.
	 * @return WP_Query
	 */
	protected function query( $paged, $per_page = 5 ) {
		return new WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'paged'          => $paged,
			)
		);
	}

	/**
	 * Render without echoing.
	 *
	 * @param array $args Template tag arguments.
	 * @return string|null
	 */
	protected function render( $args = array() ) {
		$args['echo'] = false;
		return wp_pagenavi( $args );
	}

	/**
	 * The wrapper carries the default tag, class and role.
	 *
	 * @return void
	 */
	public function test_wrapper_defaults() {
		$out = $this->render( array( 'query' => $this->query( 3 ) ) );

		$this->assertStringStartsWith( "<div class='wp-pagenavi' role='navigation'>", $out );
		$this->assertStringEndsWith( '</div>', $out );
	}

	/**
	 * The wrapper tag and class are configurable, and the tag is escaped.
	 *
	 * @return void
	 */
	public function test_wrapper_is_configurable_and_escaped() {
		$out = $this->render(
			array(
				'query'         => $this->query( 3 ),
				'wrapper_tag'   => 'nav',
				'wrapper_class' => 'my-nav',
			)
		);
		$this->assertStringStartsWith( "<nav class='my-nav' role='navigation'>", $out );

		$out = $this->render(
			array(
				'query'         => $this->query( 3 ),
				'wrapper_class' => 'x" onclick="alert(1)',
			)
		);
		$this->assertStringNotContainsString( 'onclick="alert(1)"', $out );
	}

	/**
	 * The current page is marked with aria-current and the current class, and is
	 * not a link.
	 *
	 * @return void
	 */
	public function test_current_page_is_marked() {
		$out = $this->render( array( 'query' => $this->query( 5 ) ) );

		$this->assertStringContainsString( "<span aria-current='page' class='current'>5</span>", $out );
	}

	/**
	 * Previous and next links carry the rel hints search engines look for.
	 *
	 * @return void
	 */
	public function test_prev_and_next_rel_attributes() {
		$out = $this->render( array( 'query' => $this->query( 5 ) ) );

		$this->assertStringContainsString( 'rel="prev"', $out );
		$this->assertStringContainsString( 'rel="next"', $out );
	}

	/**
	 * The first page has no previous link and the last page has no next link.
	 *
	 * @return void
	 */
	public function test_no_prev_on_first_page_and_no_next_on_last() {
		$first = $this->render( array( 'query' => $this->query( 1 ) ) );
		$this->assertStringNotContainsString( 'rel="prev"', $first );
		$this->assertStringContainsString( 'rel="next"', $first );

		$last = $this->render( array( 'query' => $this->query( 10 ) ) );
		$this->assertStringContainsString( 'rel="prev"', $last );
		$this->assertStringNotContainsString( 'rel="next"', $last );
	}

	/**
	 * A single page renders nothing unless always_show is set.
	 *
	 * @return void
	 */
	public function test_single_page_is_hidden_unless_always_show() {
		$this->assertNull( $this->render( array( 'query' => $this->query( 1, 100 ) ) ) );

		$out = $this->render(
			array(
				'query'   => $this->query( 1, 100 ),
				'options' => array( 'always_show' => true ),
			)
		);
		$this->assertStringContainsString( 'wp-pagenavi', $out );
	}

	/**
	 * The num_pages option controls how many numbered links appear.
	 *
	 * @return void
	 */
	public function test_num_pages_controls_window_size() {
		foreach ( array( 3, 5, 7 ) as $window ) {
			$out = $this->render(
				array(
					'query'   => $this->query( 5 ),
					'options' => array(
						'num_pages'                    => $window,
						'larger_page_numbers_multiple' => 0,
					),
				)
			);

			// The window is made of links plus the current-page span.
			$links = substr_count( $out, "class='current'" ) + preg_match_all( '/class="page /', $out );
			$this->assertSame( $window, $links, "Expected {$window} page slots." );
		}
	}

	/**
	 * Larger page numbers appear in the configured multiples.
	 *
	 * @return void
	 */
	public function test_larger_page_numbers() {
		$out = $this->render(
			array(
				'query'   => $this->query( 25, 1 ),
				'options' => array(
					'num_larger_page_numbers'      => 3,
					'larger_page_numbers_multiple' => 10,
				),
			)
		);

		$this->assertStringContainsString( '>10</a>', $out );
		$this->assertStringContainsString( '>40</a>', $out );

		// Disabling the multiple removes them.
		$out = $this->render(
			array(
				'query'   => $this->query( 25, 1 ),
				'options' => array( 'larger_page_numbers_multiple' => 0 ),
			)
		);
		$this->assertStringNotContainsString( 'smaller page', $out );
	}

	/**
	 * Blanking a text option hides that part of the navigation.
	 *
	 * @return void
	 */
	public function test_blank_text_hides_that_part() {
		$out = $this->render(
			array(
				'query'   => $this->query( 5 ),
				'options' => array(
					'prev_text' => '',
					'next_text' => '',
				),
			)
		);

		$this->assertStringNotContainsString( 'previouspostslink', $out );
		$this->assertStringNotContainsString( 'nextpostslink', $out );
	}

	/**
	 * Tokens in the text options are replaced.
	 *
	 * @return void
	 */
	public function test_tokens_are_replaced() {
		$out = $this->render(
			array(
				'query'   => $this->query( 5 ),
				'options' => array( 'pages_text' => 'Showing %CURRENT_PAGE% of %TOTAL_PAGES%' ),
			)
		);

		$this->assertStringContainsString( 'Showing 5 of 10', $out );
		$this->assertStringNotContainsString( '%CURRENT_PAGE%', $out );
	}

	/**
	 * Text options are run through kses, including when written straight to the
	 * option row rather than submitted through the settings form.
	 *
	 * @return void
	 */
	public function test_text_options_are_ksesed_on_output() {
		$out = $this->render(
			array(
				'query'   => $this->query( 5 ),
				'options' => array( 'pages_text' => '<script>alert(1)</script>safe' ),
			)
		);
		$this->assertStringNotContainsString( '<script>', $out );
		$this->assertStringContainsString( 'safe', $out );

		update_option(
			PageNavi_Options::OPTION_NAME,
			array( 'pages_text' => '<script>alert(1)</script>fromdb' )
		);
		$out = $this->render( array( 'query' => $this->query( 5 ) ) );
		$this->assertStringNotContainsString( '<script>', $out );
		$this->assertStringContainsString( 'fromdb', $out );
	}

	/**
	 * The class-name filters are applied.
	 *
	 * @return void
	 */
	public function test_class_name_filters() {
		add_filter(
			'wp_pagenavi_class_current',
			static function () {
				return 'my-current';
			}
		);

		$out = $this->render( array( 'query' => $this->query( 5 ) ) );

		$this->assertStringContainsString( "class='my-current'", $out );
	}

	/**
	 * The wp_pagenavi filter receives the output and the parsed arguments.
	 *
	 * @return void
	 */
	public function test_output_filter_receives_args() {
		$captured = null;

		add_filter(
			'wp_pagenavi',
			static function ( $out, $args ) use ( &$captured ) {
				$captured = $args;
				return '<!--f-->' . $out;
			},
			10,
			2
		);

		$out = $this->render( array( 'query' => $this->query( 3 ) ) );

		$this->assertStringStartsWith( '<!--f-->', $out );
		$this->assertIsArray( $captured );
		$this->assertArrayHasKey( 'wrapper_tag', $captured );
		$this->assertArrayHasKey( 'type', $captured );
		$this->assertSame( 'posts', $captured['type'] );
	}

	/**
	 * The drop-down style renders a select with one option per page.
	 *
	 * @return void
	 */
	public function test_dropdown_style() {
		$out = $this->render(
			array(
				'query'   => $this->query( 3 ),
				'options' => array( 'style' => 2 ),
			)
		);

		$this->assertStringContainsString( '<select', $out );
		$this->assertSame( 10, substr_count( $out, '<option ' ) );
		$this->assertStringContainsString( 'selected="selected"', $out );
	}

	/**
	 * The echo argument is honoured in both directions.
	 *
	 * @return void
	 */
	public function test_echo_argument() {
		$GLOBALS['wp_query'] = $this->query( 4 );

		ob_start();
		wp_pagenavi();
		$echoed = ob_get_clean();

		$this->assertStringContainsString( 'wp-pagenavi', $echoed );

		ob_start();
		$returned = wp_pagenavi( array( 'echo' => true ) );
		ob_end_clean();

		$this->assertNull( $returned );
	}

	/**
	 * The legacy positional argument form still works.
	 *
	 * @return void
	 */
	public function test_positional_arguments() {
		$GLOBALS['wp_query'] = $this->query( 4 );

		ob_start();
		wp_pagenavi( '<i>B</i>', '<i>A</i>' );
		$out = ob_get_clean();

		$this->assertStringStartsWith( '<i>B</i>', $out );
		$this->assertStringEndsWith( '<i>A</i>', $out );
	}

	/**
	 * The deprecated dropdown tag still renders.
	 *
	 * @return void
	 */
	public function test_deprecated_dropdown_tag() {
		$GLOBALS['wp_query'] = $this->query( 2 );

		ob_start();
		wp_pagenavi_dropdown();
		$out = ob_get_clean();

		$this->assertStringContainsString( 'wp-pagenavi', $out );
	}

	/**
	 * Page numbers derived from a WP_User_Query are integers, so the current page
	 * is still marked. Left as the floats floor()/ceil() return, the aria-current
	 * span disappears entirely.
	 *
	 * @return void
	 */
	public function test_user_query_marks_current_page() {
		self::factory()->user->create_many( 8 );

		$users = new WP_User_Query(
			array(
				'number' => 2,
				'offset' => 2,
			)
		);

		$out = wp_pagenavi(
			array(
				'type'  => 'users',
				'query' => $users,
				'echo'  => false,
			)
		);

		$this->assertStringContainsString( "aria-current='page'", $out );
		$this->assertStringContainsString( "<span aria-current='page' class='current'>2</span>", $out );
	}

	/**
	 * Multipart posts paginate through get_multipage_link().
	 *
	 * @return void
	 */
	public function test_multipart_pagination() {
		global $numpages, $post;

		$post_id = self::factory()->post->create(
			array( 'post_content' => 'A<!--nextpage-->B<!--nextpage-->C<!--nextpage-->D' )
		);

		$post = get_post( $post_id );
		setup_postdata( $post );
		$numpages = 4;
		set_query_var( 'page', 2 );

		$out = wp_pagenavi(
			array(
				'type' => 'multipart',
				'echo' => false,
			)
		);

		wp_reset_postdata();
		set_query_var( 'page', 0 );

		$this->assertStringContainsString( "<span aria-current='page' class='current'>2</span>", $out );
		$this->assertStringContainsString( 'page=3', $out );
	}
}
