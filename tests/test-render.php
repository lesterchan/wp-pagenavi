<?php
/**
 * Rendering tests for the wp_pagenavi() template tag.
 *
 * @package WP-PageNavi
 */

/**
 * Covers the markup wp_pagenavi() produces.
 */
class WP_PageNavi_Render_Test extends WP_PageNavi_TestCase {

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

		$this->assertStringStartsWith( "<div class='wp-pagenavi' role='navigation'>", $out, 'The wrapper is a div carrying the plugin class and a navigation role.' );
		$this->assertStringEndsWith( '</div>', $out, 'And is closed.' );
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
		$this->assertStringStartsWith( "<nav class='my-nav' role='navigation'>", $out, 'The tag and class are configurable.' );

		$out = $this->render(
			array(
				'query'         => $this->query( 3 ),
				'wrapper_class' => 'x" onclick="alert(1)',
			)
		);
		$this->assertStringNotContainsString( 'onclick="alert(1)"', $out, 'And escaped, so neither can add an event handler.' );
	}

	/**
	 * The current page is marked with aria-current and the current class, and is
	 * not a link.
	 *
	 * @return void
	 */
	public function test_current_page_is_marked() {
		$out = $this->render( array( 'query' => $this->query( 5 ) ) );

		$this->assertStringContainsString( "<span aria-current='page' class='current'>5</span>", $out, 'The current page is marked for assistive technology as well as visually.' );
	}

	/**
	 * Previous and next links carry the rel hints search engines look for.
	 *
	 * @return void
	 */
	public function test_prev_and_next_rel_attributes() {
		$out = $this->render( array( 'query' => $this->query( 5 ) ) );

		$this->assertStringContainsString( 'rel="prev"', $out, 'The previous link declares its relation.' );
		$this->assertStringContainsString( 'rel="next"', $out, 'And the next link.' );
	}

	/**
	 * The navigation aria-labels are looked up in core's text domain, not this
	 * plugin's, so they arrive already translated in every locale core supports.
	 *
	 * @return void
	 */
	public function test_aria_labels_use_the_core_text_domain() {
		$seen   = array();
		$labels = array( 'First page', 'Previous Page', 'Next Page', 'Last page' );

		add_filter(
			'gettext',
			static function ( $translation, $text, $domain ) use ( &$seen, $labels ) {
				if ( in_array( $text, $labels, true ) ) {
					$seen[ $text ] = $domain;
				}
				return $translation;
			},
			10,
			3
		);

		// Page 5 of 10 renders all four links.
		$out = $this->render( array( 'query' => $this->query( 5 ) ) );

		foreach ( $labels as $label ) {
			$this->assertArrayHasKey( $label, $seen, "The '{$label}' label was never rendered." );
			$this->assertSame( 'default', $seen[ $label ], "The '{$label}' label must use core's text domain." );
			$this->assertStringContainsString( 'aria-label="' . $label . '"', $out, 'The ' . $label . ' link carries its aria-label in the output.' );
		}
	}

	/**
	 * Each aria-label must match core's own spelling exactly, or it silently
	 * resolves to nothing and ships untranslated. Core is inconsistent about the
	 * casing, so this pins the four strings against core's current catalogue
	 * rather than against a convention.
	 *
	 * @return void
	 */
	public function test_aria_labels_match_strings_core_actually_defines() {
		$labels = array( 'First page', 'Previous Page', 'Next Page', 'Last page' );

		$out = $this->render( array( 'query' => $this->query( 5 ) ) );

		foreach ( $labels as $label ) {
			$this->assertStringContainsString( 'aria-label="' . $label . '"', $out, 'The ' . $label . ' link carries its aria-label in the output.' );
		}

		// The capitalisations core does not define must not creep back in.
		$this->assertStringNotContainsString( 'aria-label="First Page"', $out, 'No aria-label is invented that core does not define, since it would go untranslated.' );
		$this->assertStringNotContainsString( 'aria-label="Last Page"', $out, 'For either end.' );
	}

	/**
	 * The first page has no previous link and the last page has no next link.
	 *
	 * @return void
	 */
	public function test_no_prev_on_first_page_and_no_next_on_last() {
		$first = $this->render( array( 'query' => $this->query( 1 ) ) );
		$this->assertStringNotContainsString( 'rel="prev"', $first, 'On the first page there is no previous link.' );
		$this->assertStringContainsString( 'rel="next"', $first, 'But there is a next one.' );

		$last = $this->render( array( 'query' => $this->query( 10 ) ) );
		$this->assertStringContainsString( 'rel="prev"', $last, 'On the last page there is a previous link.' );
		$this->assertStringNotContainsString( 'rel="next"', $last, 'And no next one.' );
	}

	/**
	 * A single page renders nothing unless always_show is set.
	 *
	 * @return void
	 */
	public function test_single_page_is_hidden_unless_always_show() {
		$this->assertNull( $this->render( array( 'query' => $this->query( 1, 100 ) ) ), 'A single page renders nothing at all unless always_show is set.' );

		$out = $this->render(
			array(
				'query'   => $this->query( 1, 100 ),
				'options' => array( 'always_show' => true ),
			)
		);
		$this->assertStringContainsString( 'wp-pagenavi', $out, 'always_show renders on a single page, which is what it is for.' );
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

		$this->assertStringContainsString( '>10</a>', $out, 'The larger numbers start at the first multiple.' );
		$this->assertStringContainsString( '>40</a>', $out, 'And continue to the last one inside the run.' );

		// Disabling the multiple removes them.
		$out = $this->render(
			array(
				'query'   => $this->query( 25, 1 ),
				'options' => array( 'larger_page_numbers_multiple' => 0 ),
			)
		);
		$this->assertStringNotContainsString( 'smaller page', $out, 'With no smaller numbers, since there is nothing before the window.' );
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

		$this->assertStringNotContainsString( 'previouspostslink', $out, 'A blanked previous text hides that link entirely.' );
		$this->assertStringNotContainsString( 'nextpostslink', $out, 'And a blanked next text.' );
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

		$this->assertStringContainsString( 'Showing 5 of 10', $out, 'Both tokens are substituted with the real numbers.' );
		$this->assertStringNotContainsString( '%CURRENT_PAGE%', $out, 'With no token left in the output.' );
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
		$this->assertStringNotContainsString( '<script>', $out, 'A script in a text option is filtered on output.' );
		$this->assertStringContainsString( 'safe', $out, 'While the safe part survives.' );

		update_option(
			WP_PageNavi_Options::OPTION,
			array( 'pages_text' => '<script>alert(1)</script>fromdb' )
		);
		$out = $this->render( array( 'query' => $this->query( 5 ) ) );
		$this->assertStringNotContainsString( '<script>', $out, 'And filtered again for a value that came from the database rather than the form.' );
		$this->assertStringContainsString( 'fromdb', $out, 'With its safe part surviving too.' );
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

		$this->assertStringContainsString( "class='my-current'", $out, 'A filter can replace the class the current page carries.' );
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

		$this->assertStringStartsWith( '<!--f-->', $out, 'The output filter can rewrite the markup.' );
		$this->assertIsArray( $captured, 'The output filter is handed the argument array.' );
		$this->assertArrayHasKey( 'wrapper_tag', $captured, 'The filtered arguments still carry wrapper_tag.' );
		$this->assertArrayHasKey( 'type', $captured, 'The filtered arguments still carry type.' );
		$this->assertSame( 'posts', $captured['type'], 'And is told which kind of query it is filtering.' );
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

		$this->assertStringContainsString( '<select', $out, 'The dropdown style renders a select.' );
		$this->assertSame( 10, substr_count( $out, '<option ' ), 'With one option per page.' );
		$this->assertStringContainsString( 'selected="selected"', $out, 'And the current page selected.' );
	}

	/**
	 * An unrecognised style renders the wrapper and nothing inside it, rather
	 * than falling back to a style or fatalling.
	 *
	 * @return void
	 */
	public function test_unknown_style_renders_an_empty_wrapper() {
		$out = $this->render(
			array(
				'query'   => $this->query( 3 ),
				'options' => array( 'style' => 7 ),
			)
		);

		$this->assertStringContainsString( "class='wp-pagenavi'", $out, 'An unknown style still renders the wrapper.' );
		$this->assertStringNotContainsString( '<a ', $out, 'With no links in it.' );
		$this->assertStringNotContainsString( '<select', $out, 'And no select, rather than falling back to a style the site did not ask for.' );
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

		$this->assertStringContainsString( 'wp-pagenavi', $echoed, 'The echo argument prints the markup rather than returning it.' );

		ob_start();
		$returned = wp_pagenavi( array( 'echo' => true ) );
		ob_end_clean();

		$this->assertNull( $returned, 'With echo on, nothing is returned; the markup went to the output buffer.' );
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

		$this->assertStringStartsWith( '<i>B</i>', $out, 'The positional form still puts before first.' );
		$this->assertStringEndsWith( '<i>A</i>', $out, 'And after last, so a theme calling it the old way is not broken.' );
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

		$this->assertStringContainsString( 'wp-pagenavi', $out, 'The deprecated dropdown tag still renders, so a theme calling it is not broken.' );
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

		$this->assertStringContainsString( "aria-current='page'", $out, 'A user query marks its current page for assistive technology.' );
		$this->assertStringContainsString( "<span aria-current='page' class='current'>2</span>", $out, 'And visually, on the page the query is actually on.' );
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

		$this->assertStringContainsString( "<span aria-current='page' class='current'>2</span>", $out, 'A multipart post marks its current page.' );
		$this->assertStringContainsString( 'page=3', $out, 'And links the others by page argument rather than by permalink.' );
	}
}
