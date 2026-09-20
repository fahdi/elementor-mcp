# Widget Builder data layer: query, shortcode, remote (September 2026)

Handoff and evaluation notes for the work done 2026-09-18 to 2026-09-20 on the Sandbox Widget Builder. Written for another agent or reviewer picking this up cold. Everything below is local and uncommitted until the two commits that carry this file; nothing has been released.

## What changed, in one paragraph

The Widget Builder DSL (a JSON spec that `EMCP_Tools_Widget_Generator` compiles into an Elementor `Widget_Base` class) could only render typed-in data. It can now render **live site data** (posts, products, terms, menus, site identity, breadcrumbs, the WooCommerce cart), **embed other plugins' output** through an allowlisted `shortcode` control (saved Elementor templates, Contact Form 7, WPForms, Gravity Forms, Fluent Forms, Forminator), and **read HTTPS JSON APIs** through a `remote` query source with presets (OpenWeather, Google Reviews, Yelp, generic JSON) whose keys live on the Connection tab, never in the widget. 85 widgets were built on top of this on the local test site `elementor-widgets.test` and verified in a browser.

## Where the code lives

| Area | File | Tier |
|---|---|---|
| Query runtime (all sources) | `pro/includes/class-widget-query.php` | Pro, runtime (`Pro_Loader::FILES`) |
| Shortcode allowlist + render | `pro/includes/class-shortcode-registry.php` | Pro, runtime |
| Remote fetch + cache | `pro/includes/remote/class-remote-source.php` | Pro, runtime |
| Remote preset contract | `pro/includes/remote/interface-remote-preset.php` | Pro, runtime |
| Presets | `pro/includes/remote/class-remote-preset-{openweather,google-reviews,yelp,json}.php` | Pro, runtime |
| API key storage (encrypted options, constants win) | `includes/class-remote-keys.php` | Free |
| Key UI ("Live data providers" on Connection tab) | `includes/admin/views/page-connection.php`, `includes/admin/trait-admin-settings.php` | Free |
| Spec validation + PHP generation | `pro/includes/class-widget-generator.php` | Pro |
| Template DSL compiler | `pro/includes/class-sandbox-template-compiler.php` | Pro |
| Live registry sync after create | `includes/class-widget-store.php` (`sync_live_registry()`) | Free |
| Loader lists and manifest | `includes/class-pro-loader.php` (`FILES`), `pro-manifest.txt` | Free |
| Agent skill doc | `pro/skills/emcp-skills/widget-builder.md` | Pro |
| Design specs | `docs/superpowers/specs/2026-09-18-widget-builder-query-source-design.md`, `docs/superpowers/specs/2026-09-20-widget-builder-shortcode-and-remote-design.md` (git-ignored docs folder, present locally) | |

Related, same period: `EMCP_Tools_Cloud_Sync::bulk_backup()` now skips artifacts already up to date (`is_up_to_date()`), so "Save all to Cloud" no longer re-uploads the whole library on every click (`includes/cloud/class-cloud-sync.php`, `includes/admin/trait-admin-cloud.php`, `assets/js/sandbox-cloud.js`).

## The contracts (what a widget author sees)

### `query` control

```json
{ "name": "posts", "type": "query", "source": "posts", "defaults": { "count": 6 } }
```

- `source`: `posts` | `products` | `terms` | `menu` | `site` | `breadcrumbs` | `cart` | `remote` (needs `preset`).
- The generator emits a Query panel of ordinary Elementor controls prefixed with the control name (`posts_count`, `posts_orderby`, ...). `defaults` presets them.
- Template: `{{#each posts}}...{{/each}}`, `{{#if posts}}`, `{{posts_found}}`, `{{posts_error}}`.
- Row fields are fixed per source and pinned by `WidgetQueryTest::test_row_field_contract_is_pinned`. The full list is returned by the `list-control-types` MCP tool.
- **Extras** (new 2026-09-20): a source can add root tokens beside `_found`/`_error`. Only `cart` does today: `{{cart_count}} {{cart_total}} {{cart_subtotal}} {{cart_total_html}} {{cart_currency}} {{cart_cart_url}} {{cart_checkout_url}} {{cart_shop_url}}` and `{{#if cart_is_empty}}`. `EMCP_Tools_Widget_Query::extras()` declares them (typed for the compiler), `extra_values()` produces them at render time, the generator merges them into `$settings` and reserves the names.

### `shortcode` control

```json
{ "name": "form", "type": "shortcode", "tag": "contact-form-7" }
```

- `tag` must be one of the registry's providers; the editor shows a live SELECT2 of that provider's items; the stored value is the item id; `{{form}}` renders it. Templates render through `get_builder_content_for_display( $id, true )`, everything else through `do_shortcode()` on a registry-built string. A spec never contains a shortcode string. Recursion is capped (`MAX_DEPTH`).

### `remote` source

```json
{ "name": "wx", "type": "query", "source": "remote", "preset": "openweather", "defaults": { "location": "London,GB" } }
```

- `EMCP_Tools_Remote_Source::run()` resolves the key from `EMCP_Tools_Remote_Keys`, builds the request via the preset, enforces `https://`, fetches with `wp_safe_remote_get` (private hosts refused, 5 s timeout, 512 KB cap), caches in a transient (preset TTL or the `ttl` control, clamped 1 min to 1 day) and serves the last good payload for a day when the provider fails. `{{name_error}}` carries a human instruction ("Add your OpenWeather API key under ...").
- The `json` preset is the escape hatch: `defaults.map` is `field=dot.path` per line; names ending `url|link|href|image|img|avatar|thumbnail|icon` escape as URLs.

### Structured defaults (generator fix, 2026-09-20)

`icon` (`{value, library}`), `url` (`{url}`), `media` (`{url, id}`) and a `multiple` `select2` (list) defaults now reach `add_control()` in Elementor's shape, at the root and inside repeater rows. Before this they were silently dropped, so a spec's default icon never showed. `WidgetGeneratorTest::test_array_defaults_reach_the_control_in_elementor_shape` pins it.

## Safety properties worth checking

- Runtime classes are in `EMCP_Tools_Pro_Loader::FILES`, not `MCP_FILES`. `MCP_FILES` is skipped on plain front-end views since 3.12.2, and a runtime class placed there renders zero rows on the front end while WP-CLI shows data (this was hit once and is the first thing to suspect if a widget is empty only on the front end).
- Query is read-only and capped (50 rows; terms 100; remote 50). `run()` never throws; failures go to the debug log and `{{name_error}}`.
- Shortcode: allowlist is strict (`allowed()`), ids are `ctype_digit`, only published items resolve, depth-guarded.
- Remote: keys are never in a spec, generated PHP, the cache key, or the browser; the log line keeps only the host.
- Row-field shadowing: a root control named like a row field of any query on the widget (`title`, `thumbnail`, `price`, ...) is refused by the validator, because inside `{{#each}}` the row value wins silently. Extras names are reserved the same way.

## Tests

- Pro suite: `phpunit` from the plugin root (config points at `pro/tests`). 2865 tests, 6 skipped (Windows symlink cases). Relevant files: `pro/tests/unit/sandbox/WidgetQueryTest.php`, `ShortcodeRegistryTest.php`, `RemoteSourceTest.php`, `pro/tests/unit/WidgetGeneratorTest.php`, `pro/tests/unit/blocks/SandboxTemplateCompilerTest.php`, `pro/tests/unit/Cloud/CloudSyncTest.php`.
- Public suite: `phpunit -c tests/phpunit.xml`. 232 tests.
- `pro/tests/bootstrap.php` gained the class-map entries for the new classes, a faithful `sanitize_text_field` stub, and a `do_shortcode` stub that records the last call and supports a re-entrancy hook.

## Live verification (not repeatable from tests)

Site: `F:\laragon\www\elementor-widgets` (https://elementor-widgets.test, Elementor 4.2.4 + Pro, WooCommerce 11.1.1, CF7). Plugin copy there is a premium-layout build of this tree at `wp-content/plugins/emcp-pro`; keep it in sync by copying changed files (the loader is dual-root, so the file paths match).

Showcase pages, all checked with Playwright for rendering and zero console errors:

| Page | Widgets |
|---|---|
| `/widget-showcase-2/` (132) | Form Styler, Template Carousel, Weather, Google Reviews, Yelp Reviews, News Ticker (posts, custom, JSON feed), Advanced Menu mega panels |
| `/widget-showcase-3/` (155) | Advanced Heading, Animated Text, Advanced Tabs, Feature List, Advanced Icon Box, Advanced Button, Table of Contents, Progress Bars, Data Table, Alert Box |
| `/widget-showcase-4/` (195) | Advanced Slider, Video Box, Sticky Video, Media Carousel, Image Swap, Image Accordion, Image Layers, Ken Burns Slideshow, Lottie Player, Animated Blob, Image Separator |
| `/widget-showcase-5/` (226) | Loop Tabs, World Clock, Tags Cloud, Preview Window, Unfold, Google Maps, Multi Scroll, Recent Posts Notification, WhatsApp Chat |
| `/widget-showcase-6/` (250) | Site Logo, Breadcrumbs, Mini Cart (real cart, live totals) |
| `/widget-showcase-7/` (276) | Call to Action, Dual Button, Newsletter Form, Social Share, Coupon Card, Business Hours, Team Grid, Process Steps |
| `/widget-showcase-8/` (316) | Event List, Tooltip Text, QR Code, Video Playlist, Text Path, Post Timeline, Scroll to Top, Floating Action Menu |

**Review pass of the documentation pages (2026-09-21).** A visual audit of all 429 demos (contact sheets per widget, then fixes) found two widget-level bugs now fixed in the specs and guarded in the plugin: (1) Content Toggle and Lightbox had a content control named `animation`, which Elementor treats as its entrance animation, so both widgets rendered `visibility: hidden` everywhere; the controls are renamed `anim_style` and `EMCP_Tools_Widget_Generator::RESERVED_CONTROL_NAMES` now refuses `animation` / `_animation` (test `test_validate_rejects_elementor_reserved_control_names`). (2) Advanced Tabs (pills), Loop Tabs (pills), Table of Contents (inline chips), Content Toggle (boxed) and Data Table (dark header) lost their filled-state text or background to a plain colour control default (the trap already listed in the skill); each got a dedicated control (`tab_color_a_fill`, `item_color_a_inline`, `sw_color_a_fill`, `th_bg_dark` / `th_color_dark`). Generator-side: demo settings drop every `_`-prefixed key (widths, animations) and the `animation` key; images are filled only into real image slots (never `logo`, `icon`, `badge`, `avatar`, `overlay`, `center`, `custom`), repeaters are seeded from spec defaults first and foreign / placeholder URLs are replaced; fixed layouts (alert bars and toasts, share sidebar and bar), auto-open triggers and the Maps JS mode are listed but not placed; API-key widgets (Weather, Google Reviews, Yelp) show one demo plus a note; the frosted-glass button gets a dark backdrop. Viewport-fixed widgets (Scroll to Top, Floating Action Menu, WhatsApp Chat, Recent Posts Notification, Reading Progress Bar, One Page Navigation, Sticky Video, Preview Window) are demoed inside a "stage" container carrying `transform: translateZ(0)`, which makes the container the containing block for `position: fixed` descendants, so every layout renders in its box instead of the browser corner. That stage also exposed One Page Navigation still reparenting its nav to `<body>` (trap 0), now removed from spec 109.

**Per-widget documentation pages (2026-09-21).** `/widgets/` (page 341, "Widget Library") indexes 100 child pages, one per widget at `/widgets/{slug}/`. Each page is generated from the stored spec plus the showcase instances: hero with counts, a setup guide (panel sections, primary layout control, style sections, query/shortcode notes, conditional options), one live demo per option of the primary layout control (plus secondary style/skin/shape series where a second variant control exists), leftover showcase variations with a settings caption, and a full options reference table (key, type, choices, default, notes) for every control. Fixed-position widgets get a single demo; the Lightbox auto-open triggers are listed but not demoed. Regenerate with the scratch script `docs-gen.php` (any agent can rebuild it: list widgets, `get_spec`, walk showcase `_elementor_data` for base settings, create page + `add-container` + `add-free-widget`, `html` widgets for the guide). 446 demos, 0 console errors, 0 broken images on the Playwright sweep.

Weather, Google Reviews and Yelp were verified only down to the empty-key hint; the generic JSON preset was verified live against a keyless public API. Google Maps runs keyless in embed mode; the JS API mode needs a browser key.

Widget specs are stored as `_emcp_spec` post meta on `emcp_widget` posts; export with `EMCP_Tools_Widget_Store::get_spec( $id )`, re-create anywhere with `::create( $spec, true )`.

## Traps found while building (read before touching this code)

0. **Do not reparent a widget's element to `<body>` from its JS** (tooltips, toasts, floating buttons). Elementor emits every control's CSS under the element id (`.elementor-element-xxxx .my-class`), so a node moved out of the wrapper loses all Style-tab settings. Keep it in place and use `position: fixed`; only an ancestor with a CSS transform breaks that, which Elementor containers do not have by default.

1. **The MCP stdio process runs the generator it loaded at start.** After changing `class-widget-generator.php`, widgets created through MCP still get the old code until the client reconnects. Regenerate from WP-CLI: `EMCP_Tools_Widget_Store::update( $id, EMCP_Tools_Widget_Store::get_spec( $id ) )`.
2. **`wp eval-file` silently ran nothing** (exit 0, no output) for one ~30 KB spec script, while `wp eval 'include "file";'` ran it. Prefer the include form for large scripts. Also, top-level variables in `eval-file` are not globals; helper functions must use `$GLOBALS`.
3. **Empty per-row CSS custom properties.** `style="--acc:{{color}}"` with an empty colour yields `--acc:` which makes `var(--acc, fallback)` invalid rather than falling back, and any JS `style.setProperty` on the same element breaks an attribute-selector workaround. Strip empty properties in the widget's JS.
4. **A control with a `default` outranks widget base CSS on the same element** because Elementor emits it under the element id. A state override (night-mode tile colour) therefore needs its own control with a default, not a CSS rule.
5. **Elementor caches per-post CSS files.** After changing a widget's controls, run `\Elementor\Plugin::$instance->files_manager->clear_cache()` and delete `_elementor_element_cache` on the page, or the old CSS and markup persist.
6. **Full-page Playwright screenshots miss `loading="lazy"` images** below the fold. Check the DOM before "fixing" an image that is actually fine.
7. **Section id equal to a control name** silently drops the control in Elementor; the validator now refuses it. Same for a root control named like a query row field.
8. `plain_price()` in the query class had a UTF-8 double-encoded NBSP literal in source; product prices carried a stray NBSP. It now uses `"\xC2\xA0"` escapes.

## Follow-ups not done

- Pagination and nested loops for `query` (design notes in the 2026-09-18 spec).
- OAuth-based feeds (Instagram, Facebook, TikTok, X) are out of scope by design; they need a connect-account flow, not a preset.
- Container extensions from the vendor lists (parallax, equal height, display conditions) are not widgets and are not covered by this builder.
- Marketplace copy for the 33 widgets built on 2026-09-20 (the first 29 have copy in `E:\MSR Builds\Products\EMCP\Promos\widget-marketplace-copy-2026-09-18.md`).
