<?php

// Template tag: Boxed Style Paging
function wp_pagenavi( $before = '', $after = '', $options = array() ) {
	global $wp_query;

	$options = wp_parse_args( $options, PageNavi_Core::$options->get() );

	$posts_per_page = intval( get_query_var( 'posts_per_page' ) );

	$paged = absint( get_query_var( 'paged' ) );
	if ( !$paged )
		$paged = 1;

	$total_pages = absint( $wp_query->max_num_pages );
	if ( !$total_pages )
		$total_pages = 1;

	if ( 1 == $total_pages && !$options['always_show'] )
		return;

	$request = $wp_query->request;
	$numposts = $wp_query->found_posts;

	$pages_to_show = absint( $options['num_pages'] );
	$larger_page_to_show = absint( $options['num_larger_page_numbers'] );
	$larger_page_multiple = absint( $options['larger_page_numbers_multiple'] );
	$pages_to_show_minus_1 = $pages_to_show - 1;
	$half_page_start = floor( $pages_to_show_minus_1/2 );
	$half_page_end = ceil( $pages_to_show_minus_1/2 );
	$start_page = $paged - $half_page_start;

	if ( $start_page <= 0 )
		$start_page = 1;

	$end_page = $paged + $half_page_end;

	if ( ( $end_page - $start_page ) != $pages_to_show_minus_1 )
		$end_page = $start_page + $pages_to_show_minus_1;

	if ( $end_page > $total_pages ) {
		$start_page = $total_pages - $pages_to_show_minus_1;
		$end_page = $total_pages;
	}

	if ( $start_page <= 0 )
		$start_page = 1;

	$out = '';
	switch ( intval( $options['style'] ) ) {
		// Normal
		case 1:
			if ( !empty( $options['pages_text'] ) ) {
				$pages_text = str_replace(
					array( "%CURRENT_PAGE%", "%TOTAL_PAGES%" ),
					array( number_format_i18n( $paged ), number_format_i18n( $total_pages ) ),
				$options['pages_text'] );
				$out .= "<span class='pages'>$pages_text</span>";
			}

			if ( $start_page >= 2 && $pages_to_show < $total_pages ) {
				$first_text = str_replace( '%TOTAL_PAGES%', number_format_i18n( $total_pages ), $options['first_text'] );
				$out .= _wp_pagenavi_single( 1, 'first', $first_text, '%TOTAL_PAGES%' );

				if ( !empty( $options['dotleft_text'] ) )
					$out .= "<span class='extend'>{$options['dotleft_text']}</span>";
			}

			$larger_pages_array = array();
			if ( $larger_page_multiple )
				for ( $i = $larger_page_multiple; $i <= $total_pages; $i+= $larger_page_multiple )
					$larger_pages_array[] = $i;

			$larger_page_start = 0;
			foreach ( $larger_pages_array as $larger_page ) {
				if ( $larger_page < $start_page && $larger_page_start < $larger_page_to_show ) {
					$out .= _wp_pagenavi_single( $larger_page, 'smaller page', $options['page_text'] );
					$larger_page_start++;
				}
			}

			if ( !empty( $options['prev_text'] ) )
				$out .= get_previous_posts_link( $options['prev_text'] );

			foreach ( range( $start_page, $end_page ) as $i ) {
				if ( $i == $paged && !empty( $options['current_text'] ) ) {
					$current_page_text = str_replace( '%PAGE_NUMBER%', number_format_i18n( $i ), $options['current_text'] );
					$out .= "<span class='current'>$current_page_text</span>";
				} else {
					$out .= _wp_pagenavi_single( $i, 'page', $options['page_text'] );
				}
			}

			if ( !empty( $options['next_text'] ) )
				$out .= get_next_posts_link( $options['next_text'], $total_pages );

			$larger_page_end = 0;
			foreach ( $larger_pages_array as $larger_page ) {
				if ( $larger_page > $end_page && $larger_page_end < $larger_page_to_show ) {
					$out .= _wp_pagenavi_single( $larger_page, 'larger page', $options['page_text'] );
					$larger_page_end++;
				}
			}

			if ( $end_page < $total_pages ) {
				if ( !empty( $options['dotright_text'] ) )
					$out .= "<span class='extend'>{$options['dotright_text']}</span>";

				$out .= _wp_pagenavi_single( $total_pages, 'last', $options['last_text'], '%TOTAL_PAGES%' );
			}
			break;

		// Dropdown
		case 2:
			$out .= '<form action="" method="get">'."\n";
			$out .= '<select size="1" onchange="document.location.href = this.options[this.selectedIndex].value;">'."\n";

			foreach ( range( 1, $total_pages ) as $i ) {
				$page_num = $i;
				if ( $page_num == 1 )
					$page_num = 0;

				if ( $i == $paged ) {
					$current_page_text = str_replace( '%PAGE_NUMBER%', number_format_i18n( $i ), $options['current_text'] );
					$out .= '<option value="'.esc_url( get_pagenum_link( $page_num ) ).'" selected="selected" class="current">'.$current_page_text."</option>\n";
				} else {
					$page_text = str_replace( '%PAGE_NUMBER%', number_format_i18n( $i ), $options['page_text'] );
					$out .= '<option value="'.esc_url( get_pagenum_link( $page_num ) ).'">'.$page_text."</option>\n";
				}
			}

			$out .= "</select>\n";
			$out .= "</form>\n";
			break;
	}
	$out = $before . "<div class='wp-pagenavi'>\n$out\n</div>" . $after;

	echo apply_filters( 'wp_pagenavi', $out );
}

function _wp_pagenavi_single( $page, $class, $raw_text, $format = '%PAGE_NUMBER%' ) {
	if ( empty( $raw_text ) )
		return '';

	$text = str_replace( $format, number_format_i18n( $page ), $raw_text );

	return "<a href='" . esc_url( get_pagenum_link( $page ) ) . "' class='$class'>$text</a>";
}


// Template tag: Drop Down Menu ( Deprecated )
function wp_pagenavi_dropdown() {
	wp_pagenavi();
}


class PageNavi_Core {
	static $options;

	function init( $options ) {
		self::$options = $options;

		add_action( 'wp_print_styles', array( __CLASS__, 'stylesheets' ) );

		add_filter( 'previous_posts_link_attributes', array( __CLASS__, 'previous_posts_link_attributes' ) );
		add_filter( 'next_posts_link_attributes', array( __CLASS__, 'next_posts_link_attributes' ) );
	}

	function stylesheets() {
		if ( !self::$options->use_pagenavi_css )
			return;

		if ( @file_exists( STYLESHEETPATH . '/pagenavi-css.css' ) )
			$css_file = get_stylesheet_directory_uri() . '/pagenavi-css.css';
		elseif ( @file_exists( TEMPLATEPATH . '/pagenavi-css.css' ) )
			$css_file = get_template_directory_uri() . '/pagenavi-css.css';
		else
			$css_file = plugins_url( 'pagenavi-css.css', __FILE__ );

		wp_enqueue_style( 'wp-pagenavi', $css_file, false, '2.70' );
	}

	function previous_posts_link_attributes() {
		return 'class="previouspostslink"';
	}

	function next_posts_link_attributes() {
		return 'class="nextpostslink"';
	}
}

