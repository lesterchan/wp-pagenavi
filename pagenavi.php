<?php
/*
Plugin Name: WP-PageNavi
Plugin URI: http://www.lesterchan.net/portfolio/programming.php
Description: Adds a more advanced page navigation to Wordpress.
Version: 2.03
Author: GaMerZ
Author URI: http://www.lesterchan.net
*/


/*  Copyright 2006  Lester Chan  (email : gamerz84@hotmail.com)

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


### Function: Page Navigation: Normal Paging
function wp_pagenavi($before=' ', $after=' ', $prelabel='&laquo;', $nxtlabel='&raquo;') {
	global $request, $posts_per_page, $wpdb, $paged;
	$pages_to_show = 5;
	$half_pages_to_show = round($pages_to_show/2);
	if (!is_single()) {
		if (get_query_var('what_to_show') == 'posts') {
			preg_match('#FROM\s(.*)\sORDER BY#siU', $request, $matches);
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
			if ($paged >= ($pages_to_show-1)) {
				echo '<a href="'.get_pagenum_link().'">&laquo; First</a> ... ';
			}
			previous_posts_link($prelabel);
			for($i = $paged - $half_pages_to_show; $i  <= $paged + $half_pages_to_show; $i++) {
				if ($i >= 1 && $i <= $max_page) {
					if($i == $paged) {
						echo "[$i]";
					} else {
						echo ' <a href="'.get_pagenum_link($i).'">'.$i.'</a> ';
					}
				}
			}
			next_posts_link($nxtlabel, $max_page);
			if (($paged+$half_pages_to_show) < ($max_page)) {
				echo ' ... <a href="'.get_pagenum_link($max_page).'">Last &raquo;</a>';
			}
			echo "$after</b>";
		}
	}
}


### Function: Page Navigation: Drop Down Menu
function wp_pagenavi_dropdown() {
	global $request, $posts_per_page, $wpdb, $paged;
	if (!is_single()) {
		if (get_query_var('what_to_show') == 'posts') {
			preg_match('#FROM\s(.*)\sORDER BY#siU', $request, $matches);
			$fromwhere = $matches[1];
			$numposts = $wpdb->get_var("SELECT COUNT(DISTINCT ID) FROM $fromwhere");
			$max_page = ceil($numposts /$posts_per_page);
		} else {
			$max_page = 999999;
		}
		if(empty($paged)) {
			$paged = 1;
		}
		echo '<form action="'.htmlspecialchars($_SERVER['PHP_SELF']).'" method="get">'."\n";
		echo '<select size="1" onchange="document.location.href = this.options[this.selectedIndex].value;">'."\n";
		for($i = 1; $i  <= $max_page; $i++) {
			if($i == $paged) {
				echo "<option value=\"".get_pagenum_link($i)."\" selected=\"selected\">Page: $i</option>\n";
			} else {
				echo "<option value=\"".get_pagenum_link($i)."\">Page: $i</option>\n";
			}
		}
		echo "</select>\n";
		echo "</form>\n";
	}
}
?>