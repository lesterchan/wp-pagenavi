# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What it is

One template tag, `wp_pagenavi()`, replacing "« Older / Newer »" with numbered
page links, plus a settings screen under Settings for the twenty-odd text and
number options that shape the output. It paginates three different things:
`WP_Query` (the default), a `<!--nextpage-->` multipart post, and `WP_User_Query`.

WP-CommentNavi is its sibling and shares the same `Core` / `Call` / `Options` /
`Settings` shape — a change to one is usually a change to both.

## Data

Two option rows: `wp_pagenavi_options` for the settings and `wp_pagenavi_version`
for the `plugin` and `db` upgrade markers. **The markers are a row of their own
and must stay that way.** A marker kept inside the settings array has to be
rescued from the stored value on every save, because the settings form never
posts one.

`WP_PageNavi_Options::maybe_upgrade()` folds in `pagenavi_options`, the row every
release up to 2.94.5 wrote. That row is out in the world, so this migration runs
on real installs rather than on a rename nobody shipped.

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
* `README` `Contributors:` is `GamerZ` alone; `scribu` was dropped deliberately.
  Do not restore it.

## Migrations, and why they are tested through a browser

**Activation hooks do not fire when a plugin is updated.** A site that updates
from the Plugins screen never calls `activate()`, so `maybe_upgrade()` also
hangs off `admin_init` — the hook every real upgrade goes through.

That difference is what `tests/e2e/upgrade.spec.js` exists for, and it is worth
understanding before changing either the migration or that file:

* **On the admin path `register_setting()` has already run**, so the sanitize
  callback is attached to the settings row and every write the migration makes
  goes through it. Under WP-CLI it is not attached at all. **A migration test
  that never registers the setting is testing WP-CLI**, not the path real sites
  take.
* **Read the row raw when the question is "was it written".**
  `WP_PageNavi_Options::get()` merges the defaults over whatever is stored, so it
  answers identically for a row holding the defaults and for no row at all —
  which is exactly the state a migration that read, deleted and never wrote
  leaves behind.
* **Seed the shipped defaults, not customised values.** A customised fixture
  cannot see that failure: its migrated result differs from the defaults, so the
  write lands whatever the read before it did.
* **A migrated stock row is not the defaults array byte for byte.**
  `always_show` and `use_pagenavi_css` ship as PHP booleans and the sanitizer
  stores integers, so the test names that rather than asserting round it.
* `write()` passes an explicit default to `get_option()` so an absent row can be
  told from a defaulted one and `add_option()`ed. This plugin passes no
  `default` to `register_setting()` today, so the trap is not armed here; the
  helper is written that way so adding one later cannot quietly break the
  migration.

## Tests

`bin/test.sh` runs PHPUnit, `bin/test-multisite.sh` the network pass, and
`bin/test-e2e.sh` the Playwright suite. **Run them rather than trusting a note
about their last result** — CI is the authority, and this file cannot be.

`tests/test-kses.php` is the file to read first — ten tests over the SVG
allow-list, the `xlink:href` scoping and hostile input. `test-call.php` covers
the three pagination types, `test-upgrade.php` the `pagenavi_options` migration.

`tests/e2e/pagenavi.spec.js` needs its fixture to *be* more than one page, so it
carries an explicit "the fixture really is paginated" assertion — without it the
suite is vacuous. Fixtures page **five at a time**, not WordPress's default ten,
because pages are the only thing a pagination plugin is about. Navigate by
clicking the link the plugin rendered rather than typing a URL: `/page/2/` is
pagination on a site with a permalink structure and a 404 on one without, and
the link is the thing under test anyway.

**A capability test must assert both directions.** "An editor cannot reach the
settings screen" passes with the plugin deactivated, because then the screen
does not exist and core refuses everybody. Pair it with an administrator
reaching the same screen, so the pair can only pass when the plugin is present
*and* gating.
