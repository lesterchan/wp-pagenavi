=== WP-PageNavi ===
Contributors: GamerZ, scribu
Donate link: http://lesterchan.net/wordpress
Tags: pagenavi, navi, navigation, wp-pagenavi, page
Requires at least: 2.8
Tested up to: 2.9.1
Stable tag: 2.60

Adds a more advanced paging navigation to your WordPress site.

== Description ==

Adds a more advanced paging navigation to your WordPress site.

Example:

	Pages (17): [1] 2 3 4 » ... Last »


<br>
[Demo](http://lesterchan.net/wordpress/) | [Translations](http://dev.wp-plugins.org/browser/wp-pagenavi/i18n/) | [Support Forums](http://forums.lesterchan.net/index.php?board=14.0)

= Credits =
* Right-to-left language support by [Kambiz R. Khojasteh](http://persian-programming.com/)
* Maintenance by [scribu](http://scribu.net)

= Donations =
I spent most of my free time creating, updating, maintaining and supporting these plugins, if you really love my plugins and could spare me a couple of bucks as my school allowance, I will really appericiate it. If not feel free to use it without any obligations. Thank You. My Paypal account is lesterchan@gmail.com.

== Installation ==

You can either install it automatically from the WordPress admin, or do it manually:

1. Unzip the archive and put the `wp-pagenavi` folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins menu.

= Usage =

1. Open `wp-content/themes/<YOUR THEME NAME>/footer.php`
2. Add Anywhere:

`<?php if(function_exists('wp_pagenavi')) { wp_pagenavi(); } ?>`

3. Go to *WP-Admin -> Settings -> PageNavi* to configure WP-PageNavi.

= Changing the CSS =

If you need to configure the CSS style of WP-PageNavi, you can copy the `pagenavi-css.css` file from the plugin directory to your theme's directory and make your modifications there. This way, you won't lose your changes when you update the plugin.

1. Copy the file from:

`/wp-content/plugins/wp-pagenavi/pagenavi-css.css`

to:

`/wp-content/themes/<YOUR THEME NAME>/pagenavi-css.css`

2. Make your modifications to the new file.

Alternatively, you can uncheck the "Use pagenavi.css?" option from the settings page and add the styles to your theme's style.css file directly.


== Screenshots ==

1. Default appearance
2. Options page

== Frequently Asked Questions ==

[WP-PageNavi Support Forums](http://forums.lesterchan.net/index.php?board=14.0 "WP-PageNavi Support Forums")

== Changelog ==

= 2.70 (2010-X-X) =
* optimized CSS
* let WordPress handle uninstallation

= 2.61 (2010-02-07) =
* FIXED: memory limit error
* NEW: Compatible With WordPress 2.9
* NEW: Added "previouspostslink" and "nextpostslink" CSS classes for styling next/previous posts link by Joost de Valk
* NEW: Added option to include pagenavi-css.css In WP-Admin -> Settings -> PageNavi
* FIXED: check for pagenavi-css.css in the child theme, first
* FIXED: cleaner options page

= 2.50 (2009-06-01) =
* NEW: Compatible With WordPress 2.8
* NEW: Added Larger Page Number Pagination
* NEW: Added "first", "page" and "last" CSS Name To Link
* FIXED: Removed "&#8201;" Entity
* FIXED: Uses $_SERVER['PHP_SELF'] With plugin_basename(__FILE__) Instead Of Just $_SERVER['REQUEST_URI']

= 2.40 (2008-12-12) =
* NEW: Compatible With WordPress 2.7 Only
* NEW: Right To Left Language Support by Kambiz R. Khojasteh
* NEW: Called pagenavi_textdomain() In pagenavi_init() by Kambiz R. Khojasteh

= 2.31 (2008-07-16) =
* NEW: Compatible With WordPress 2.6

= 2.30 (2008-06-01) =
* NEW: WP-PageNavi Will Load 'pagenavi-css.css' Inside Your Theme Directory If It Exists. If Not, It Will Just Load The Default 'pagenavi-css.css' By WP-PageNavi
* NEW: Uses /wp-pagenavi/ Folder Instead Of /pagenavi/
* NEW: Uses wp-pagenavi.php Instead Of pagenavi.php
* NEW: Added "wp-pagenavi a:visited" Style In pagenavi-css.css
* NEW: Added $before And $after Function Arguments To wp_pagenavi();
* FIXED: Rearranged CSS Classes In pagenavi-css.css
* FIXED: "First" Text Does Not Always Appear If Page 1 Is Not Shown

= 2.20 (2007-10-01) =
* NEW: Supports query_posts(); Variables
* NEW: Ability To Uninstall WP-PageNavi

= 2.11 (2007-06-01) =
* NEW: Page Navigation Now Is Customizable Via 'WP-Admin -> Options -> PageNavi' And pagenavi-css.css
* NEW: Default Style Navigation Is Now Boxed Navigation (Similar To Digg.com) =
* FIXED: Fix For Ultimate Tag Warrior By Oliver Kastler & Stephan (Netconcepts)

= 2.10 (2007-02-01) =
* NEW: Compatible With WordPress 2.1 Only
* NEW: Move pagenavi.php To pagenavi Folder

= 2.03 (2006-10-01) =
* FIXED: Now Compatible With WordPress 2.1

= 2.02 (2006-06-01) =
* NEW: Added Drop Down Menu Style Of Page Navigation

= 2.01 (2006-03-01) =
* FIXED: Paging Show If There Is Only 1 Page

= 2.00 (2006-01-01) =
* NEW: Compatible With WordPress 2.0
* FIXED: Space Issues

