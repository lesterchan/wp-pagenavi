<?php

class PageNavi_Options_Page extends scbAdminPage {

	function setup() {
		$this->textdomain = 'wp-pagenavi';

		$this->args = array(
			'page_title' => __( 'PageNavi Settings', $this->textdomain ),
			'menu_title' => __( 'PageNavi', $this->textdomain ),
			'page_slug' => 'pagenavi',
		);
	}

	function validate( $new_data, $old_data ) {
		$options = wp_parse_args($new_data, $old_data);
		foreach ( array( 'style', 'num_pages', 'num_larger_page_numbers', 'larger_page_numbers_multiple' ) as $key )
			$options[$key] = absint( @$options[$key] );

		foreach ( array( 'use_pagenavi_css', 'always_show', 'use_extend_between_larger_pages', 'show_prev_next_links_before_after_first_last' ) as $key ) {
			$options[$key] = intval(@$options[$key]);
		}

		return $options;
	}

	function page_content() {
		$rows = array(
			array(
				'title' => __( 'Text For Number Of Pages', $this->textdomain ),
				'type' => 'text',
				'name' => 'pages_text',
				'extra' => 'size="50"',
				'desc' => '<br />
					%CURRENT_PAGE% - ' . __( 'The current page number.', $this->textdomain ) . '<br />
					%TOTAL_PAGES% - ' . __( 'The total number of pages.', $this->textdomain )
			),

			array(
				'title' => __( 'Text For Current Page', $this->textdomain ),
				'type' => 'text',
				'name' => 'current_text',
				'desc' => '<br />
					%PAGE_NUMBER% - ' . __( 'The page number.', $this->textdomain )
			),

			array(
				'title' => __( 'Text For Page', $this->textdomain ),
				'type' => 'text',
				'name' => 'page_text',
				'desc' => '<br />
					%PAGE_NUMBER% - ' . __( 'The page number.', $this->textdomain )
			),

			array(
				'title' => __( 'Text For First Page', $this->textdomain ),
				'type' => 'text',
				'name' => 'first_text',
				'desc' => '<br />
					%TOTAL_PAGES% - ' . __( 'The total number of pages.', $this->textdomain )
			),

			array(
				'title' => __( 'Text For Last Page', $this->textdomain ),
				'type' => 'text',
				'name' => 'last_text',
				'desc' => '<br />
					%TOTAL_PAGES% - ' . __( 'The total number of pages.', $this->textdomain )
			),

			array(
				'title' => __( 'Text For Previous Page', $this->textdomain ),
				'type' => 'text',
				'name' => 'prev_text',
				'extra' => 'id="prev_dashicon_pick"',
				'desc' => '<input type="button" data-target="#prev_dashicon_pick" class="button dashicons-picker" value="' . __( 'Or choose an icon', $this->textdomain ) . '" />'
			),

			array(
				'title' => __( 'Text For Next Page', $this->textdomain ),
				'type' => 'text',
				'name' => 'next_text',
				'extra' => 'id="next_dashicon_pick"',
				'desc' => '<input type="button" data-target="#next_dashicon_pick" class="button dashicons-picker" value="' . __( 'Or choose an icon', $this->textdomain ) . '" />'
			),

			array(
				'title' => __( 'Text For Previous ...', $this->textdomain ),
				'type' => 'text',
				'name' => 'dotleft_text',
			),

			array(
				'title' => __( 'Text For Next ...', $this->textdomain ),
				'type' => 'text',
				'name' => 'dotright_text',
			),
		);

		$out =
		 html( 'h3', __( 'Page Navigation Text', $this->textdomain ) )
		.html( 'p', __( 'Leaving a field blank will hide that part of the navigation.', $this->textdomain ) )
		.$this->table( $rows );


		$rows = array(
			array(
				'title' => __( 'Use pagenavi-css.css', $this->textdomain ),
				'type' => 'radio',
				'name' => 'use_pagenavi_css',
				'choices' => array( 1 => __( 'Yes', $this->textdomain ), 0 => __( 'No', $this->textdomain ) )
			),

			array(
				'title' => __( 'Page Navigation Style', $this->textdomain ),
				'type' => 'select',
				'name' => 'style',
				'values' => array( 1 => __( 'Normal', $this->textdomain ), 2 => __( 'Drop-down List', $this->textdomain ) ),
				'text' => false
			),

			array(
				'title' => __( 'Always Show Page Navigation', $this->textdomain ),
				'type' => 'radio',
				'name' => 'always_show',
				'choices' => array( 1 => __( 'Yes', $this->textdomain ), 0 => __( 'No', $this->textdomain ) ),
				'desc' => '<br />'.__( "Show navigation even if there's only one page.", $this->textdomain )
			),

			array(
				'title' => __( 'Number Of Pages To Show', $this->textdomain ),
				'type' => 'text',
				'name' => 'num_pages',
				'extra' => 'class="small-text"'
			),

			array(
				'title' => __( 'Number Of Larger Page Numbers To Show', $this->textdomain ),
				'type' => 'text',
				'name' => 'num_larger_page_numbers',
				'extra' => 'class="small-text"',
				'desc' =>
				'<br />' . __( 'Larger page numbers are in addition to the normal page numbers. They are useful when there are many pages of posts.', $this->textdomain ) .
				'<br />' . __( 'For example, WP-PageNavi will display: Pages 1, 2, 3, 4, 5, 10, 20, 30, 40, 50.', $this->textdomain ) .
				'<br />' . __( 'Enter 0 to disable.', $this->textdomain )
			),

			array(
				'title' => __( 'Show Larger Page Numbers In Multiples Of', $this->textdomain ),
				'type' => 'text',
				'name' => 'larger_page_numbers_multiple',
				'extra' => 'class="small-text"',
				'desc' =>
				'<br />' . __( 'For example, if mutiple is 5, it will show: 5, 10, 15, 20, 25', $this->textdomain )
			),

			array(
                'title' => __( 'Use extend text for Larger Pages', $this->textdomain ),
                'type' => 'radio',
                'name' => 'use_extend_between_larger_pages',
                'choices' => array( 1 => __( 'Yes', $this->textdomain ), 0 => __( 'No', $this->textdomain ) )
            ),

            array(
                'title' => __( 'Display previous link before first page and next link after last page', $this->textdomain ),
                'type' => 'radio',
                'name' => 'show_prev_next_links_before_after_first_last',
                'choices' => array( 1 => __( 'Yes', $this->textdomain ), 0 => __( 'No', $this->textdomain ) )
            )
		);

		$out .=
		 html( 'h3', __( 'Page Navigation Options', $this->textdomain ) )
		.$this->table( $rows );

		echo $this->form_wrap( $out );
	}
}

