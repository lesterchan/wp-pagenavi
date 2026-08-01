# WP-PageNavi
Contributors: GamerZ  
Donate link: https://lesterchan.net/site/donation/  
Tags: navigation, pagination, paging, pages, page links  
Requires at least: 6.8  
Tested up to: 7.0  
Stable tag: 3.0.0  
Requires PHP: 8.2  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a more advanced paging navigation interface.

## Description
Want to replace the old *&larr; Older posts | Newer posts &rarr;* links with some page links?

This plugin provides the `wp_pagenavi()` template tag which generates fancy pagination links.

Much of the 2.x line was the work of [scribu](https://scribu.net), whose write-ups on using it with secondary queries are still the ones the FAQ below points at. The plugin icon is by [SimpleIcon](https://www.simpleicon.com) from [Flaticon](https://www.flaticon.com).

### Donations
I spent most of my free time creating, updating, maintaining and supporting these plugins, if you really love my plugins and could spare me a couple of bucks, I will really appreciate it. If not feel free to use it without any obligations.

## Usage
In your theme, you need to find calls to next_posts_link() and previous_posts_link() and replace them.

In the Twentyten theme, it looks like this:

```php
<div class="nav-previous"><?php next_posts_link( __( '<span class="meta-nav">&larr;</span> Older posts', 'twentyten' ) ); ?></div>
<div class="nav-next"><?php previous_posts_link( __( 'Newer posts <span class="meta-nav">&rarr;</span>', 'twentyten' ) ); ?></div>
```

You would replace those two lines with this:

```php
<?php wp_pagenavi(); ?>
```

For multipart pages, you would look for code like this:

```php
<?php wp_link_pages( ... ); ?>
```

and replace it with this:

```php
<?php wp_pagenavi( array( 'type' => 'multipart' ) ); ?>
```

Go to *WP-Admin -> Settings -> PageNavi* for configuration.

### Changing the CSS

If you need to configure the CSS style of WP-PageNavi, you can copy the `css/wp-pagenavi.css` file from the plugin directory into your theme's directory as `wp-pagenavi.css` and make your modifications there. This way, you won't lose your changes when you update the plugin.

The stylesheet sets no font and no text colour, so it inherits both from your theme, and its two border colours are CSS custom properties. A small change needs no copy of the file at all:

```php
.wp-pagenavi {
	--wp-pagenavi-border-color: #d0d0d0;
	--wp-pagenavi-border-color-current: rebeccapurple;
}
```

Alternatively, you can set "Use wp-pagenavi.css" to No on the settings page and add the styles to your theme's style.css file directly.

### Changing Class Names

There are [filters](https://developer.wordpress.org/plugins/hooks/filters/) that can be used to change the default class names that are assigned to page navigation elements.

#### Filters

* `wp_pagenavi_class_pages`
* `wp_pagenavi_class_first`
* `wp_pagenavi_class_previouspostslink`
* `wp_pagenavi_class_extend`
* `wp_pagenavi_class_smaller`
* `wp_pagenavi_class_page`
* `wp_pagenavi_class_current`
* `wp_pagenavi_class_larger`
* `wp_pagenavi_class_nextpostslink`
* `wp_pagenavi_class_last`

#### Filter Usage

```php
// Simple Usage - 1 callback per filter
add_filter('wp_pagenavi_class_previouspostslink', 'theme_pagination_previouspostslink_class');
add_filter('wp_pagenavi_class_nextpostslink', 'theme_pagination_nextpostslink_class');
add_filter('wp_pagenavi_class_page', 'theme_pagination_page_class');

function theme_pagination_previouspostslink_class($class_name) {
  return 'pagination__control-link pagination__control-link--previous';
}

function theme_pagination_nextpostslink_class($class_name) {
  return 'pagination__control-link pagination__control-link--next';
}

function theme_pagination_page_class($class_name) {
  return 'pagination__current-page';
}


// More Concise Usage - 1 callback for all filters
add_filter('wp_pagenavi_class_previouspostslink', 'theme_pagination_class');
add_filter('wp_pagenavi_class_nextpostslink', 'theme_pagination_class');
add_filter('wp_pagenavi_class_page', 'theme_pagination_class');

function theme_pagination_class($class_name) {
  switch($class_name) {
    case 'previouspostslink':
      $class_name = 'pagination__control-link pagination__control-link--previous';
      break;
    case 'nextpostslink':
      $class_name = 'pagination__control-link pagination__control-link--next';
      break;
    case 'page':
      $class_name = 'pagination__current'
      break;
  }
  return $class_name;
}
```

## Frequently Asked Questions

### When I go to page 2, I see the same posts as on page 1!

You're using `query_posts()` wrong. See [The Right Way To use query_posts()](https://scribu.net/wordpress/wp-pagenavi/right-way-to-use-query_posts.html)

### Does PageNavi work with secondary WP_Query instances?

Yes; read [this tutorial](https://scribu.net/wordpress/wp-pagenavi/wpn-2-74.html)

### How do I ignore the options page?

If you are running a multi-language plugin, you will probably want to ignore the strings in the options page.

You can do that like so:

```php
<?php wp_pagenavi( array( 'options' => WP_PageNavi_Options::get_defaults() ) ); ?>
```

Before 3.0.0 this was written as `PageNavi_Core::$options->get_defaults()`. That property was backed by the bundled SCB framework, which 3.0.0 removes, so the old form no longer works and must be updated.

### Can I use an SVG arrow for the previous and next links?

Yes. The navigation text settings accept the inline SVG elements as well as everything `wp_kses_post()` allows, so an icon typed into the settings screen survives the save. That allow-list is the `wp_pagenavi_allowed_html` filter if you need to widen or narrow it.

## Screenshots

1. With Custom Styling
2. Admin - Options Page

## Changelog
### 3.0.0
* BREAKING: WP-PageNavi now requires WordPress 6.8 and PHP 8.2. A site running anything older will not be offered the update
* BREAKING: The settings are stored in `wp_pagenavi_options` instead of `pagenavi_options`. The old row is copied over and removed automatically
* BREAKING: The settings screen has moved from `options-general.php?page=pagenavi` to `options-general.php?page=wp-pagenavi`
* BREAKING: `pagenavi-css.css` is now `css/wp-pagenavi.css`, and a copy in your theme directory must be renamed to `wp-pagenavi.css` to keep overriding it
* BREAKING: Every class is now prefixed. `PageNavi_Options`, `PageNavi_Call` and `PageNavi_Core` are `WP_PageNavi_Options`, `WP_PageNavi_Call` and `WP_PageNavi_Core`, and `PageNavi_Admin` is now `WP_PageNavi_Settings`
* BREAKING: `PageNavi_Core::$options` has been removed. Use `WP_PageNavi_Options::get_defaults()` and `WP_PageNavi_Options::get()` instead. See the FAQ
* BREAKING: `PageNavi_Options_Page` has been removed and replaced by `WP_PageNavi_Settings`
* BREAKING: Dropping the SCB Framework also removes the global functions and `scb*` classes it defined, since they were loaded into WordPress by whichever plugin bundled it. If your theme or another plugin called `html()`, `html_link()`, `set_post_field()`, `scb_init()`, `scb_register_table()`, `scb_install_table()`, `scb_uninstall_table()`, `scb_admin_notice()`, `scb_get_query_flags()`, `scb_list_fold()` or `scb_list_group_by()`, it must now provide them itself. Note that another installed plugin may still be supplying them, so the breakage will only appear once nothing else on the site bundles SCB
* NEW: Removed the bundled WP SCB Framework. The plugin now runs entirely on WordPress core APIs and has no dependencies
* NEW: The settings page is built with the WordPress Settings API
* NEW: Restructured into `includes/` following the Plugin Handbook layout
* NEW: A `wp_pagenavi_capability` filter over the capability the settings screen requires
* NEW: A `wp_pagenavi_version` row holding the plugin and schema version markers, so a future upgrade has something to compare against
* CHANGED: The stylesheet's two colours are CSS custom properties with fallbacks rather than hardcoded hexes, and it now carries a dark colour scheme
* CHANGED: The stylesheet uses no physical CSS properties, so one file serves left-to-right and right-to-left sites alike
* FIXED: An inline SVG arrow no longer deletes the entire previous/next link. `wp_kses_post()` removes an SVG rather than cleaning it, which left the link text empty, and a link with empty text is dropped altogether. The navigation text is now filtered against `wp_kses_post()`'s list plus the inline SVG elements, exposed as the `wp_pagenavi_allowed_html` filter. Reported by Cal at toolshed (#73)
* FIXED: Multisite uninstall no longer stops at the hundredth site, so a large network has its options removed everywhere. Reported by Cal at toolshed (#73)
* FIXED: The navigation aria-labels now match the strings WordPress core defines, so they are translated in every locale core supports. Reported by Cal at toolshed (#73)
* FIXED: An array submitted where a text setting belongs no longer raises a PHP 8 notice, and option keys the plugin does not define are no longer stored. Reported by Cal at toolshed (#73)
* FIXED: Page links are escaped with `esc_url()` instead of `esc_attr()`
* FIXED: Page numbers derived from a `WP_User_Query` are cast to integers, so the current page is marked correctly
* FIXED: The stylesheet version now tracks the plugin version instead of being pinned to 2.70
* FIXED: The label on each Yes/No setting pointed at an element that did not exist, so clicking it did nothing
* NOTE: No hook and no template tag is renamed. The ten `wp_pagenavi_class_*` filters, the `wp_pagenavi` filter, `wp_pagenavi()` and `wp_pagenavi_dropdown()` all keep the names they shipped with

## Upgrade Notice
### 3.0.0
This is a large release. Read this before updating from 2.94.6.

**Your site must be on WordPress 6.8 or newer and PHP 8.2 or newer.** This is the change most likely to affect you: a site on an older stack simply will not be offered the update, and nothing else here matters until the stack is current. Ask your host if you are not sure which PHP version you are on.

**Your settings are kept, but they move.** The `pagenavi_options` row becomes `wp_pagenavi_options`. The plugin does this itself, on activation or on the first wp-admin page you load after updating, and deletes the old row once it has copied it. There is nothing to do by hand.

**The settings screen has a new address.** It was *Settings -> PageNavi* at `options-general.php?page=pagenavi` and it is still *Settings -> PageNavi*, now at `options-general.php?page=wp-pagenavi`. Update any bookmark you have.

**If you copied `pagenavi-css.css` into your theme, rename your copy to `wp-pagenavi.css`.** The plugin's own stylesheet has moved to `css/wp-pagenavi.css`, and the theme override is looked up under the new name. Until you rename it your customised copy is ignored and the plugin's default styling is used instead. Nothing inside your copy needs to change.

**Nothing a theme calls has been renamed.** `wp_pagenavi()`, `wp_pagenavi_dropdown()`, the `wp_pagenavi` filter and all ten `wp_pagenavi_class_*` filters behave exactly as before. If your theme only calls the template tag, that side of the upgrade is uneventful.

**Custom PHP that reached into the plugin's classes needs editing.** Every class is now prefixed with `WP_`: `PageNavi_Options` is `WP_PageNavi_Options`, `PageNavi_Call` is `WP_PageNavi_Call` and `PageNavi_Core` is `WP_PageNavi_Core`. The settings screen was renamed as well as prefixed: `PageNavi_Admin` is now `WP_PageNavi_Settings`. `PageNavi_Options_Page` and `PageNavi_Core::$options` are gone; use `WP_PageNavi_Options::get()` and `WP_PageNavi_Options::get_defaults()` instead.

**The bundled SCB framework is gone.** It defined a set of global functions and `scb*` classes that any plugin bundling it loaded into WordPress for everything else to use. If your theme or another plugin called `html()`, `html_link()`, `set_post_field()`, `scb_init()` or any of the `scb_*` helpers and relied on WP-PageNavi to provide them, it must now provide them itself. Another installed plugin may still be supplying them, in which case you will notice nothing until that one stops.
