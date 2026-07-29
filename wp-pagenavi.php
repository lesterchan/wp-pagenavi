<?php
/**
 * Plugin Name: WP-PageNavi
 * Plugin URI: https://lesterchan.net/portfolio/programming/php/
 * Description: Adds a more advanced paging navigation to your WordPress blog
 * Version: 3.0.0
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Author: Lester 'GaMerZ' Chan
 * Author URI: https://lesterchan.net
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-pagenavi
 * Domain Path: /languages
 *
 * @package WP-PageNavi
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

defined( 'ABSPATH' ) || exit;

/**
 * WP-PageNavi version. The last-run value is kept in the wp_pagenavi_version row.
 */
define( 'WP_PAGENAVI_VERSION', '3.0.0' );

/**
 * Schema counter. Bumped only when the stored rows need reshaping.
 */
define( 'WP_PAGENAVI_DB_VERSION', '1' );

/**
 * WP-PageNavi slug, which is also the text domain.
 */
define( 'WP_PAGENAVI_SLUG', 'wp-pagenavi' );

/**
 * WP-PageNavi main file.
 */
define( 'WP_PAGENAVI_MAIN_FILE', __FILE__ );

/**
 * WP-PageNavi directory, with a trailing slash.
 */
define( 'WP_PAGENAVI_DIR', plugin_dir_path( __FILE__ ) );

/**
 * WP-PageNavi URL, with a trailing slash.
 */
define( 'WP_PAGENAVI_URL', plugin_dir_url( __FILE__ ) );

require_once WP_PAGENAVI_DIR . 'includes/class-wp-pagenavi.php';

WP_PageNavi::init();
