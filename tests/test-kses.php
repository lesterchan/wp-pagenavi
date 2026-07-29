<?php
/**
 * Sanitisation of the navigation text options.
 *
 * Regression cover for the 2.94.6 bug where an inline SVG arrow caused the whole
 * previous/next link to disappear, plus the hostile-input battery that guards
 * the wider allow-list which fixes it.
 *
 * @package WP-PageNavi
 */

/**
 * Covers WP_PageNavi_Options::allowed_html() and ::kses().
 */
class Test_PageNavi_Kses extends WP_UnitTestCase {

	/**
	 * A representative inline SVG arrow.
	 *
	 * @var string
	 */
	const SVG = '<svg viewBox="0 0 16 16" width="16" height="16" class="icon" aria-hidden="true" focusable="false"><path d="M10 3L5 8l5 5" fill="none" stroke="currentColor" stroke-width="2"/></svg>';

	/**
	 * Reset options between tests.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		delete_option( WP_PageNavi_Options::OPTION );
	}

	/**
	 * The exact 2.94.6 regression: wp_kses_post() empties an inline SVG, and a
	 * link with empty text is dropped entirely, so the whole previous link
	 * vanished rather than merely losing its icon.
	 *
	 * @return void
	 */
	public function test_inline_svg_arrow_no_longer_deletes_the_link() {
		// The old behaviour, kept here so the regression stays legible.
		$this->assertSame( '', wp_kses_post( self::SVG ), 'wp_kses_post still empties an SVG.' );

		self::factory()->post->create_many( 12 );

		$out = wp_pagenavi(
			array(
				'echo'    => false,
				'query'   => new WP_Query(
					array(
						'post_type'      => 'post',
						'posts_per_page' => 5,
						'paged'          => 2,
					)
				),
				'options' => array( 'prev_text' => self::SVG ),
			)
		);

		$this->assertStringContainsString( 'previouspostslink', $out, 'The previous link was dropped.' );
		$this->assertStringContainsString( '<svg', $out );
		$this->assertStringContainsString( '<path', $out );
		$this->assertStringContainsString( 'd="M10 3L5 8l5 5"', $out );
	}

	/**
	 * The same value stored in the option row, rather than passed as an argument,
	 * also survives.
	 *
	 * @return void
	 */
	public function test_svg_arrow_stored_in_the_option_row() {
		self::factory()->post->create_many( 12 );

		$options              = WP_PageNavi_Options::get_defaults();
		$options['next_text'] = self::SVG;
		WP_PageNavi_Options::update( $options );

		$out = wp_pagenavi(
			array(
				'echo'  => false,
				'query' => new WP_Query(
					array(
						'post_type'      => 'post',
						'posts_per_page' => 5,
						'paged'          => 2,
					)
				),
			)
		);

		$this->assertStringContainsString( 'nextpostslink', $out );
		$this->assertStringContainsString( '<svg', $out );
	}

	/**
	 * An SVG typed into the settings screen survives the save, so the settings
	 * path and the template-tag path behave the same way.
	 *
	 * @return void
	 */
	public function test_svg_survives_the_settings_save() {
		$clean = WP_PageNavi_Admin::sanitize( array( 'prev_text' => self::SVG ) );

		$this->assertStringContainsString( '<svg', $clean['prev_text'] );
		$this->assertStringContainsString( '<path', $clean['prev_text'] );
	}

	/**
	 * Markup that already worked keeps working.
	 *
	 * @return void
	 */
	public function test_existing_arrow_markup_is_untouched() {
		$this->assertSame( '&laquo;', WP_PageNavi_Options::kses( '&laquo;' ) );
		$this->assertSame( '<i class="fa fa-chevron-left"></i>', WP_PageNavi_Options::kses( '<i class="fa fa-chevron-left"></i>' ) );
		$this->assertSame( '<span class="dashicons dashicons-arrow-left"></span>', WP_PageNavi_Options::kses( '<span class="dashicons dashicons-arrow-left"></span>' ) );
		$this->assertStringContainsString( '<img', WP_PageNavi_Options::kses( '<img src="/a.png" alt="prev">' ) );
		$this->assertSame( 'Page %CURRENT_PAGE%', WP_PageNavi_Options::kses( 'Page %CURRENT_PAGE%' ) );
	}

	/**
	 * Sprite-based icon sets reference their symbol with xlink:href, which is why
	 * the attribute is allowed rather than dropped.
	 *
	 * @return void
	 */
	public function test_sprite_syntax_is_supported() {
		$this->assertStringContainsString(
			'xlink:href="#chevron-left"',
			WP_PageNavi_Options::kses( '<svg><use xlink:href="#chevron-left"/></svg>' )
		);
		$this->assertStringContainsString(
			'href="#chevron-left"',
			WP_PageNavi_Options::kses( '<svg><use href="#chevron-left"/></svg>' )
		);
	}

	/**
	 * The xlink:href attribute is not in wp_kses_uri_attributes(), so allowing it
	 * naively would create a URI sink that skips wp_kses_bad_protocol() entirely. It
	 * must be protocol-filtered exactly like a plain href.
	 *
	 * @return void
	 */
	public function test_xlink_href_is_protocol_filtered() {
		$this->assertStringNotContainsString(
			'javascript:',
			WP_PageNavi_Options::kses( '<svg><use xlink:href="javascript:alert(1)"/></svg>' )
		);
		$this->assertStringNotContainsString(
			'javascript:',
			WP_PageNavi_Options::kses( '<svg><use href="javascript:alert(1)"/></svg>' )
		);
	}

	/**
	 * Widening the URI attribute list must not leak outside the one kses call.
	 *
	 * @return void
	 */
	public function test_uri_attribute_filter_is_scoped_to_the_call() {
		$this->assertNotContains( 'xlink:href', wp_kses_uri_attributes() );

		WP_PageNavi_Options::kses( '<svg><use xlink:href="#i"/></svg>' );

		$this->assertNotContains( 'xlink:href', wp_kses_uri_attributes() );
		$this->assertFalse( has_filter( 'wp_kses_uri_attributes' ), 'The scoped filter was left attached.' );
	}

	/**
	 * Nothing that could execute survives, for any of the shapes an attacker
	 * would reach for.
	 *
	 * @dataProvider hostile_inputs
	 *
	 * @param string $input Hostile markup.
	 * @return void
	 */
	public function test_hostile_input_is_neutralised( $input ) {
		$out = WP_PageNavi_Options::kses( $input );

		$this->assertStringNotContainsStringIgnoringCase( '<script', $out );
		$this->assertStringNotContainsStringIgnoringCase( 'javascript:', $out );
		$this->assertStringNotContainsStringIgnoringCase( '<iframe', $out );
		$this->assertDoesNotMatchRegularExpression( '/\son[a-z]+\s*=/i', $out );
	}

	/**
	 * Hostile markup, one shape per case.
	 *
	 * @return array
	 */
	public function hostile_inputs() {
		return array(
			'svg with onload'           => array( '<svg onload="alert(1)" viewBox="0 0 16 16"><path d="M1 1"/></svg>' ),
			'svg wrapping a script'     => array( '<svg><script>alert(1)</script><path d="M1 1"/></svg>' ),
			'bare script'               => array( '<script>alert(1)</script>' ),
			'img with onerror'          => array( '<img src=x onerror=alert(1)>' ),
			'anchor javascript url'     => array( '<a href="javascript:alert(1)">x</a>' ),
			'use href javascript'       => array( '<use href="javascript:alert(1)"/>' ),
			'use xlink:href javascript' => array( '<use xlink:href="javascript:alert(1)"/>' ),
			'foreignObject script'      => array( '<foreignObject><script>alert(1)</script></foreignObject>' ),
			'animate onbegin'           => array( '<animate onbegin="alert(1)"/>' ),
			'set targeting href'        => array( '<set attributeName="href" to="javascript:alert(1)"/>' ),
			'span onclick'              => array( '<span onclick="alert(1)">x</span>' ),
			'style with javascript url' => array( '<svg style="background:url(javascript:alert(1))"><path d="M1"/></svg>' ),
			'iframe'                    => array( '<iframe src="https://evil.test"></iframe>' ),
			'svg with onfocus'          => array( '<svg tabindex="1" onfocus="alert(1)"><path d="M1"/></svg>' ),
			'nested script in g'        => array( '<svg><g><script>alert(1)</script></g></svg>' ),
		);
	}

	/**
	 * The allow-list is filterable, so a site can narrow or widen it.
	 *
	 * @return void
	 */
	public function test_allowed_html_is_filterable() {
		$this->assertArrayHasKey( 'svg', WP_PageNavi_Options::allowed_html() );

		add_filter(
			'wp_pagenavi_allowed_html',
			static function ( $allowed ) {
				unset( $allowed['svg'] );
				return $allowed;
			}
		);

		$this->assertArrayNotHasKey( 'svg', WP_PageNavi_Options::allowed_html() );
		$this->assertStringNotContainsString( '<svg', WP_PageNavi_Options::kses( self::SVG ) );
	}

	/**
	 * The viewBox attribute is retained, which is what makes the icon scale.
	 *
	 * Which case it comes back in depends on the WordPress version: 6.0 keeps the
	 * author's `viewBox`, current versions lowercase it to `viewbox`. Either
	 * renders, because HTML parsers case-correct SVG attributes in foreign
	 * content, so the assertion deliberately ignores case rather than pinning one
	 * version's behaviour.
	 *
	 * @return void
	 */
	public function test_viewbox_is_retained_whatever_its_case() {
		$out = WP_PageNavi_Options::kses( '<svg viewBox="0 0 16 16"></svg>' );

		$this->assertMatchesRegularExpression( '/\sviewbox="0 0 16 16"/i', $out );
		$this->assertStringContainsString( '<svg', $out );
	}
}
