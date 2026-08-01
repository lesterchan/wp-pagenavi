# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

WP-PageNavi follows `_standards/STANDARDS.md` in the parent folder, which is the
contract for all nineteen plugins in the collection. Where this file and that
one disagree, that one wins.

## What it is

One template tag, `wp_pagenavi()`, replacing "« Older / Newer »" with numbered
page links, plus a settings screen under Settings for the twenty-odd text and
number options that shape the output. It paginates three different things:
`WP_Query` (the default), a `<!--nextpage-->` multipart post, and `WP_User_Query`.

wp-commentnavi is its sibling and shares its `Core` / `Call` / `Options` /
`Settings` shape. Changes to one usually belong in the other.

## Data

`wp_pagenavi_options` and `wp_pagenavi_version`. The migration folds in
`pagenavi_options`, which the **released** 2.94.6 ships — so unlike most of the
collection this rename is user-facing rather than confined to an unreleased
major.

## Traps

* **The text options are `wp_kses()`'d on render as well as on save**
  (`WP_PageNavi_Core::render()`). That looks redundant and is not: the `options`
  argument to `wp_pagenavi()` lets a theme pass values that never went through
  the settings form, and the row can be written directly.
* **`WP_PageNavi_Options::allowed_html()` is `wp_kses_post()`'s list plus inline
  SVG.** Themes commonly use an inline SVG for the previous/next arrows, and the
  post list deletes the whole element — including the link wrapped around it.
  `test_inline_svg_arrow_no_longer_deletes_the_link` is the regression.
* **`viewBox` is listed as `viewbox`** because kses lowercases attribute names.
  `test_viewbox_is_retained_whatever_its_case` pins it.
* **`kses()` widens `wp_kses_uri_attributes` for the duration of one call, in a
  `try/finally`.** `wp_kses()` protocol-filters only the attributes on that
  list, and `xlink:href` is not on it — so allowing the attribute without this
  would let `xlink:href="javascript:…"` through the same filter that stops the
  identical payload in a plain `href`. The `finally` is load-bearing: the global
  list must never stay widened. `test_uri_attribute_filter_is_scoped_to_the_call`
  is the guard.
* **`get_pagination_args()` casts to int before returning.** The `users` branch
  derives its figures with `floor()`/`ceil()`, which return floats, and a float
  page number silently breaks every strict comparison downstream.
* **The theme override is looked up as `wp-pagenavi.css`, not `pagenavi-css.css`.**
  Stylesheet directory first, then template directory, then the plugin's own.
  Anyone who copied the old filename into their theme is silently ignored until
  they rename it — this is in the Upgrade Notice and is the most likely support
  question.
* **`wp_pagenavi()` still accepts three positional arguments** (`$before`,
  `$after`, `$options`) as well as an array. Themes in the wild call it both
  ways; the `func_get_args()` branch is not dead code.
* **The bundled SCB framework is gone**, and it defined global functions
  (`html()`, `html_link()`, `scb_init()`) that any plugin bundling it provided
  for everything else. Code relying on WP-PageNavi to supply them breaks — but
  possibly not until some *other* plugin also stops bundling it.
* `PageNavi_Admin` became `WP_PageNavi_Settings`, not `WP_PageNavi_Admin` — the
  rename and the prefix happened together (commit `8e60588`).
* `README` `Contributors:` is `GamerZ` alone; `scribu` was dropped per §3.2. Do
  not restore it.

## Tests

`tests/test-kses.php` is the file to read first — ten tests over the SVG
allow-list, the `xlink:href` scoping and hostile input. `test-call.php` covers
the three pagination types, `test-upgrade.php` the `pagenavi_options` migration.

`tests/e2e/pagenavi.spec.js` needs its fixture to *be* more than one page, so it
carries an explicit "the fixture really is paginated" assertion — without it the
suite is vacuous. Fixtures page **five at a time**, not WordPress's default ten,
because pages are the only thing a pagination plugin is about. The tests site
uses plain permalinks, so `/page/2/` is not a pagination URL; navigate by
clicking.

**Known gap** (`_standards/RESUME.md`): the capability test in this suite passes
with the plugin deactivated, because it cannot tell "capability works" from
"page missing". It needs a companion assertion that an admin *can* reach the
same screen. §7.5 now forbids the one-sided form.
