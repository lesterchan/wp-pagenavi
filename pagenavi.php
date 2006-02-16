<?php
/*
Plugin Name: WP-PageNavi
Plugin URI: http://www.lesterchan.net/portfolio/programming.php
Description: Adds a more advanced page navigation to Wordpress.
Version: 2.01
Author: GaMerZ
Author URI: http://www.lesterchan.net
*/


/*  Copyright 2005  Lester Chan  (email : gamerz84@hotmail.com)

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


### Function: Page Navigation
function wp_pagenavi($before=' ', $after=' ', $prelabel='&laquo;', $nxtlabel='&raquo;') {
	global $request, $posts_per_page, $wpdb, $paged;
	if (!is_single()) {
		if (get_query_var('what_to_show') == 'posts') {
			preg_match('#FROM\s(.*)\sGROUP BY#siU', $request, $matches);
			$fromwhere = $matches[1];
			$numposts = $wpdb->get_var("SELECT COUNT(DISTINCT ID) FROM $fromwhere");
			$max_page = ceil($numposts /$posts_per_page);
		} else {
			$max_page = 999999;
		}
		if(empty($paged)) {
			$paged = 1;
		}
		if($max_page > 1) {
			echo "$before Pages ($max_page): <b>";
			if ($paged >= 4) {
				echo '<a href="'.get_pagenum_link().'">&laquo; First</a> ... ';
			}
			previous_posts_link($prelabel);
			for($i = $paged - 2 ; $i  <= $paged +2; $i++) {
				if ($i >= 1 && $i <= $max_page) {
					if($i == $paged) {
						echo "[$i]";
					} else {
						echo ' <a href="'.get_pagenum_link($i).'">'.$i.'</a> ';
					}
				}
			}
			next_posts_link($nxtlabel, $max_page);
			if (($paged+2) < ($max_page)) {
				echo ' ... <a href="'.get_pagenum_link($max_page).'">Last &raquo;</a>';
			}
			echo "$after</b>";
		}
	}
}
?>