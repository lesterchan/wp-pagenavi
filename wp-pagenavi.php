<?php
/*
Plugin Name: WP-PageNavi
Plugin URI: http://lesterchan.net/portfolio/programming/php/
Description: Adds a more advanced paging navigation to your WordPress blog
Version: 2.91
Author: Lester 'GaMerZ' Chan
Author URI: http://lesterchan.net
Text Domain: wp-pagenavi
*/

require_once dirname( __FILE__ ) . '/scb/load.php';

function _pagenavi_init() {
	load_plugin_textdomain( 'wp-pagenavi' );

	require_once dirname( __FILE__ ) . '/core.php';

	$options = new scbOptions( 'pagenavi_options', __FILE__, array(
		'pages_text'    => __( 'Page %CURRENT_PAGE% of %TOTAL_PAGES%', 'wp-pagenavi' ),
		'current_text'  => '%PAGE_NUMBER%',
		'page_text'     => '%PAGE_NUMBER%',
		'first_text'    => __( '&laquo; First', 'wp-pagenavi' ),
		'last_text'     => __( 'Last &raquo;', 'wp-pagenavi' ),
		'prev_text'     => __( '&laquo;', 'wp-pagenavi' ),
		'next_text'     => __( '&raquo;', 'wp-pagenavi' ),
		'dotleft_text'  => __( '...', 'wp-pagenavi' ),
		'dotright_text' => __( '...', 'wp-pagenavi' ),
		'num_pages' => 5,
		'num_larger_page_numbers' => 3,
		'larger_page_numbers_multiple' => 10,
		'always_show' => false,
		'use_pagenavi_css' => true,
		'style' => 1,
	) );

	PageNavi_Core::init( $options );

	if ( is_admin() ) {
		require_once dirname( __FILE__ ) . '/admin.php';
		new PageNavi_Options_Page( __FILE__, $options );
	}
}
scb_init( '_pagenavi_init' );

