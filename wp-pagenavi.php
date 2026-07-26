<?php
/**
 * Plugin Name: WP-PageNavi
 * Plugin URI: https://lesterchan.net/portfolio/programming/php/
 * Description: Adds a more advanced paging navigation to your WordPress blog
 * Version: 3.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
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

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

// Plugin version.
define( 'WP_PAGENAVI_VERSION', '3.0.0' );

// Main plugin file, for resolving paths from the includes.
define( 'WP_PAGENAVI_MAIN_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-pagenavi-options.php';
require_once __DIR__ . '/includes/class-pagenavi-call.php';
require_once __DIR__ . '/includes/class-pagenavi-core.php';
require_once __DIR__ . '/includes/template-tags.php';

PageNavi_Core::init();

if ( is_admin() ) {
	require_once __DIR__ . '/includes/class-pagenavi-admin.php';
	PageNavi_Admin::init();
}
