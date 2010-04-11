=== WP-PageNavi ===
Contributors: GamerZ, scribu
Donate link: http://lesterchan.net/wordpress
Tags: navigation, pagination, paging, pages
Requires at least: 2.8
Tested up to: 3.0
Stable tag: 2.61

Adds a more advanced paging navigation to your WordPress site.

== Description ==

Adds a more advanced paging navigation to your WordPress site.

Example:

	Pages (17): [1] 2 3 4 » ... Last »


<br>
[Demo](http://lesterchan.net/wordpress/) | [Translations](http://dev.wp-plugins.org/browser/wp-pagenavi/i18n/)

= Credits =
* Right-to-left language support by [Kambiz R. Khojasteh](http://persian-programming.com/)
* Maintenance by [scribu](http://scribu.net)

== Installation ==

You can either install it automatically from the WordPress admin, or do it manually:

1. Unzip the archive and put the `wp-pagenavi` folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins menu.

= Usage =

1. Open `wp-content/themes/your-theme-name/footer.php`
2. Add anywhere: `<?php wp_pagenavi(); ?>`
3. Go to *WP-Admin -> Settings -> PageNavi* to configure WP-PageNavi.

= Changing the CSS =

If you need to configure the CSS style of WP-PageNavi, you can copy the `pagenavi-css.css` file from the plugin directory to your theme's directory and make your modifications there. This way, you won't lose your changes when you update the plugin.

Alternatively, you can uncheck the "Use pagenavi.css?" option from the settings page and add the styles to your theme's style.css file directly.


== Screenshots ==

1. Default appearance
2. Options page

== Frequently Asked Questions ==

= "Parse error: syntax error, unexpected..." Help! =

Make sure your host is running PHP 5. Add this line to wp-config.php to check:

`var_dump(PHP_VERSION);`

= Doesn't work with query_posts() =

Read [The Right Way To Use query_posts()](http://scribu.net/wordpress/right-way-to-use-query_posts.html)

== Changelog ==

= 2.70 (2010-X-X) =
* better default CSS
* fixed issue with slashed quotes in settings
* let WordPress handle uninstallation
* [more info](http://scribu.net/wordpress/wp-pagenavi/wpn-2-70.html)

= 2.61 (2010-02-07) =
* fixed: memory limit error

= 2.60 (2010-02-07) =
* new: Compatible With WordPress 2.9
* new: Added "previouspostslink" and "nextpostslink" CSS classes for styling next/previous posts link by Joost de Valk
* new: Added option to include pagenavi-css.css In WP-Admin -> Settings -> PageNavi
* fixed: check for pagenavi-css.css in the child theme, first
* fixed: cleaner options page

= 2.50 (2009-06-01) =
* new: Compatible With WordPress 2.8
* new: Added Larger Page Number Pagination
* new: Added "first", "page" and "last" CSS Name To Link
* fixed: Removed "&#8201;" Entity
* fixed: Uses $_SERVER['PHP_SELF'] With plugin_basename(__FILE__) Instead Of Just $_SERVER['REQUEST_URI']

= 2.40 (2008-12-12) =
* new: Compatible With WordPress 2.7 Only
* new: Right To Left Language Support by Kambiz R. Khojasteh
* new: Called pagenavi_textdomain() In pagenavi_init() by Kambiz R. Khojasteh

= 2.31 (2008-07-16) =
* new: Compatible With WordPress 2.6

= 2.30 (2008-06-01) =
* new: WP-PageNavi Will Load 'pagenavi-css.css' Inside Your Theme Directory If It Exists. If Not, It Will Just Load The Default 'pagenavi-css.css' By WP-PageNavi
* new: Uses /wp-pagenavi/ Folder Instead Of /pagenavi/
* new: Uses wp-pagenavi.php Instead Of pagenavi.php
* new: Added "wp-pagenavi a:visited" Style In pagenavi-css.css
* new: Added $before And $after Function Arguments To wp_pagenavi();
* fixed: Rearranged CSS Classes In pagenavi-css.css
* fixed: "First" Text Does Not Always Appear If Page 1 Is Not Shown

= 2.20 (2007-10-01) =
* new: Supports query_posts(); Variables
* new: Ability To Uninstall WP-PageNavi

= 2.11 (2007-06-01) =
* new: Page Navigation Now Is Customizable Via 'WP-Admin -> Options -> PageNavi' And pagenavi-css.css
* new: Default Style Navigation Is Now Boxed Navigation (Similar To Digg.com) =
* fixed: Fix For Ultimate Tag Warrior By Oliver Kastler & Stephan (Netconcepts)

= 2.10 (2007-02-01) =
* new: Compatible With WordPress 2.1 Only
* new: Move pagenavi.php To pagenavi Folder

= 2.03 (2006-10-01) =
* fixed: Now Compatible With WordPress 2.1

= 2.02 (2006-06-01) =
* new: Added Drop Down Menu Style Of Page Navigation

= 2.01 (2006-03-01) =
* fixed: Paging Show If There Is Only 1 Page

= 2.00 (2006-01-01) =
* new: Compatible With WordPress 2.0
* fixed: Space Issues
