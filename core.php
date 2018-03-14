<?php

/**
 * Template tag: Boxed Style Paging
 *
 * @param array $args:
 *  'before': (string)
 *  'after': (string)
 *  'options': (string|array) Used to overwrite options set in WP-Admin -> Settings -> PageNavi
 *
 * @return void|string
 */
function wp_pagenavi( $args = array() ) {
	if ( !is_array( $args ) ) {
		$argv = func_get_args();

		$args = array();
		foreach ( array( 'before', 'after', 'options' ) as $i => $key ) {
			$args[ $key ] = isset( $argv[ $i ]) ? $argv[ $i ] : '';
		}
	}

	$args = wp_parse_args( $args, array(
		'before' => '',
		'after' => '',
		'wrapper_tag' => 'div',
		'wrapper_class' => 'wp-pagenavi',
		'options' => array(),
		'query' => $GLOBALS['wp_query'],
		'type' => 'posts',
		'echo' => true
	) );

	extract( $args, EXTR_SKIP );

	$options = wp_parse_args( $options, PageNavi_Core::$options->get() );

	$instance = new PageNavi_Call( $args );

	list( $posts_per_page, $paged, $total_pages ) = $instance->get_pagination_args();

	if ( 1 == $total_pages && !$options['always_show'] )
		return;

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

	if ( $start_page < 1 )
		$start_page = 1;

	// Support for filters to change class names
	$class_names = array(
		'pages' => apply_filters( 'wp_pagenavi_class_pages', 'pages'),
		'first' => apply_filters( 'wp_pagenavi_class_first', 'first' ),
		'previouspostslink' => apply_filters( 'wp_pagenavi_class_previouspostslink', 'previouspostslink' ),
		'extend' => apply_filters( 'wp_pagenavi_class_extend', 'extend' ),
		'smaller' => apply_filters( 'wp_pagenavi_class_smaller', 'smaller' ),
		'page' => apply_filters( 'wp_pagenavi_class_page', 'page' ),
		'current' => apply_filters( 'wp_pagenavi_class_current', 'current'),
		'larger' => apply_filters( 'wp_pagenavi_class_larger', 'larger' ),
		'nextpostslink' => apply_filters( 'wp_pagenavi_class_nextpostslink', 'nextpostslink'),
		'last' => apply_filters( 'wp_pagenavi_class_last', 'last'),
	);

	$out = '';
	switch ( intval( $options['style'] ) ) {
		// Normal
		case 1:
			// Text
			if ( !empty( $options['pages_text'] ) ) {
				$pages_text = str_replace(
					array( "%CURRENT_PAGE%", "%TOTAL_PAGES%" ),
					array( number_format_i18n( $paged ), number_format_i18n( $total_pages ) ),
					__( $options['pages_text'], 'wp-pagenavi' ) );
				$out .= "<span class='{$class_names['pages']}'>$pages_text</span>";
			}

			if ( $start_page >= 2 && $pages_to_show < $total_pages ) {
				// First
				$first_text = str_replace( '%TOTAL_PAGES%', number_format_i18n( $total_pages ), __( $options['first_text'], 'wp-pagenavi' ) );
				$out .= $instance->get_single( 1, $first_text, array(
					'class' => $class_names['first']
				), '%TOTAL_PAGES%' );
			}

			// Previous
			if ( $paged > 1 && !empty( $options['prev_text'] ) ) {
				$out .= $instance->get_single( $paged - 1, $options['prev_text'], array(
					'class' => $class_names['previouspostslink'],
					'rel'   => 'prev'
				) );
			}

			if ( $start_page >= 2 && $pages_to_show < $total_pages ) {
				if ( !empty( $options['dotleft_text'] ) )
					$out .= "<span class='{$class_names['extend']}'>{$options['dotleft_text']}</span>";
			}

			// Smaller pages
			$larger_pages_array = array();
			if ( $larger_page_multiple )
				for ( $i = $larger_page_multiple; $i <= $total_pages; $i+= $larger_page_multiple )
					$larger_pages_array[] = $i;

			$larger_page_start = 0;
			foreach ( $larger_pages_array as $larger_page ) {
				if ( $larger_page < ($start_page - $half_page_start) && $larger_page_start < $larger_page_to_show ) {
					$out .= $instance->get_single( $larger_page, $options['page_text'], array(
						'class' => "{$class_names['smaller']} {$class_names['page']}",
						'title' => sprintf( __( 'Page %s', 'wp-pagenavi' ), number_format_i18n( $larger_page ) ),
					) );
					$larger_page_start++;
				}
			}

			if ( $larger_page_start )
				$out .= "<span class='{$class_names['extend']}'>{$options['dotleft_text']}</span>";

			// Page numbers
			$timeline = 'smaller';
			foreach ( range( $start_page, $end_page ) as $i ) {
				if ( $i == $paged && !empty( $options['current_text'] ) ) {
					$current_page_text = str_replace( '%PAGE_NUMBER%', number_format_i18n( $i ), $options['current_text'] );
					$out .= "<span aria-current='page' class='{$class_names['current']}'>$current_page_text</span>";
					$timeline = 'larger';
				} else {
					$out .= $instance->get_single( $i, $options['page_text'], array(
						'class' => "{$class_names['page']} {$class_names[$timeline]}",
						'title' => sprintf( __( 'Page %s', 'wp-pagenavi' ), number_format_i18n( $i ) ),
					) );
				}
			}

			// Large pages
			$larger_page_end = 0;
			$larger_page_out = '';
			foreach ( $larger_pages_array as $larger_page ) {
				if ( $larger_page > ($end_page + $half_page_end) && $larger_page_end < $larger_page_to_show ) {
					$larger_page_out .= $instance->get_single( $larger_page, $options['page_text'], array(
						'class' => "{$class_names['larger']} {$class_names['page']}",
						'title' => sprintf( __( 'Page %s', 'wp-pagenavi' ), number_format_i18n( $larger_page ) ),
					) );
					$larger_page_end++;
				}
			}

			if ( $larger_page_out ) {
				$out .= "<span class='{$class_names['extend']}'>{$options['dotright_text']}</span>";
			}
			$out .= $larger_page_out;

			if ( $end_page < $total_pages ) {
				if ( !empty( $options['dotright_text'] ) )
					$out .= "<span class='{$class_names['extend']}'>{$options['dotright_text']}</span>";
			}

			// Next
			if ( $paged < $total_pages && !empty( $options['next_text'] ) ) {
				$out .= $instance->get_single( $paged + 1, $options['next_text'], array(
					'class' => $class_names['nextpostslink'],
					'rel'   => 'next'
				) );
			}

			if ( $end_page < $total_pages ) {
				// Last
				$out .= $instance->get_single( $total_pages, __( $options['last_text'], 'wp-pagenavi' ), array(
					'class' => $class_names['last'],
				), '%TOTAL_PAGES%' );
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
					$out .= '<option value="'.esc_url( $instance->get_url( $page_num ) ).'" selected="selected" class="'.$class_names['current'].'">'.$current_page_text."</option>\n";
				} else {
					$page_text = str_replace( '%PAGE_NUMBER%', number_format_i18n( $i ), $options['page_text'] );
					$out .= '<option value="'.esc_url( $instance->get_url( $page_num ) ).'">'.$page_text."</option>\n";
				}
			}

			$out .= "</select>\n";
			$out .= "</form>\n";
			break;
	}
	$out = $before . "<" . $wrapper_tag . " class='" . $wrapper_class . "' role='navigation'>\n$out\n</" . $wrapper_tag . ">" . $after;

	$out = apply_filters( 'wp_pagenavi', $out );

	if ( !$echo )
		return $out;

	echo $out;
}


class PageNavi_Call {

	protected $args;

	function __construct( $args ) {
		$this->args = $args;
	}

	function __get( $key ) {
		return $this->args[ $key ];
	}

	function get_pagination_args() {
		global $numpages;

		$query = $this->query;

		switch( $this->type ) {
			case 'multipart':
				// Multipart page
				$posts_per_page = 1;
				$paged = max( 1, absint( get_query_var( 'page' ) ) );
				$total_pages = max( 1, $numpages );
				break;
			case 'users':
				// WP_User_Query
				$posts_per_page = $query->query_vars['number'];
				$paged = max( 1, floor( $query->query_vars['offset'] / $posts_per_page ) + 1 );
				$total_pages = max( 1, ceil( $query->total_users / $posts_per_page ) );
				break;
			default:
				// WP_Query
				$posts_per_page = intval( $query->get( 'posts_per_page' ) );
				$paged = max( 1, absint( $query->get( 'paged' ) ) );
				$total_pages = max( 1, absint( $query->max_num_pages ) );
				break;
		}

		return array( $posts_per_page, $paged, $total_pages );
	}

	function get_single( $page, $raw_text, $attr, $format = '%PAGE_NUMBER%' ) {
		if ( empty( $raw_text ) )
			return '';

		$text = str_replace( $format, number_format_i18n( $page ), $raw_text );

		$attr['href'] = $this->get_url( $page );

		return html( 'a', $attr, $text );
	}

	function get_url( $page ) {
		return ( 'multipart' == $this->type ) ? get_multipage_link( $page ) : get_pagenum_link( $page );
	}
}

# http://core.trac.wordpress.org/ticket/16973
if ( !function_exists( 'get_multipage_link' ) ) :
	function get_multipage_link( $page = 1 ) {
		global $post, $wp_rewrite;

		if ( 1 == $page ) {
			$url = get_permalink();
		} else {
			if ( '' == get_option('permalink_structure') || in_array( $post->post_status, array( 'draft', 'pending') ) )
				$url = add_query_arg( 'page', $page, get_permalink() );
			elseif ( 'page' == get_option( 'show_on_front' ) && get_option('page_on_front') == $post->ID )
				$url = trailingslashit( get_permalink() ) . user_trailingslashit( $wp_rewrite->pagination_base . "/$page", 'single_paged' );
			else
				$url = trailingslashit( get_permalink() ) . user_trailingslashit( $page, 'single_paged' );
		}

		return $url;
	}
endif;

// Template tag: Drop Down Menu (Deprecated)
function wp_pagenavi_dropdown() {
	wp_pagenavi();
}


class PageNavi_Core {
	static $options;

	static function init( $options ) {
		self::$options = $options;

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'stylesheets' ) );
	}

	static function stylesheets() {
		if ( !self::$options->use_pagenavi_css )
			return;

		if ( @file_exists( get_stylesheet_directory() . '/pagenavi-css.css' ) )
			$css_file = get_stylesheet_directory_uri() . '/pagenavi-css.css';
		elseif ( @file_exists( get_template_directory() . '/pagenavi-css.css' ) )
			$css_file = get_template_directory_uri() . '/pagenavi-css.css';
		else
			$css_file = plugins_url( 'pagenavi-css.css', __FILE__ );

		wp_enqueue_style( 'wp-pagenavi', $css_file, false, '2.70' );
	}
}

