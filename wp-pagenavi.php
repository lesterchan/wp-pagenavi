<?php
/*
Plugin Name: WP-PageNavi
Plugin URI: https://lesterchan.net/portfolio/programming/php/
Description: Adds a more advanced paging navigation to your WordPress blog
Version: 2.94.6
Author: Lester 'GaMerZ' Chan
Author URI: https://lesterchan.net
Text Domain: wp-pagenavi
*/

/*
	Copyright 2026  Lester Chan  (email : lesterchan@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require_once __DIR__ . '/scb/load.php';

function _pagenavi_init() {
	require_once __DIR__ . '/core.php';

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
		require_once __DIR__ . '/admin.php';
		new PageNavi_Options_Page( __FILE__, $options );
	}
}
scb_init( '_pagenavi_init' );

