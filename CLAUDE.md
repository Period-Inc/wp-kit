# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer install          # install dependencies
composer test             # run all tests (alias: php vendor/bin/phpunit)
php vendor/bin/phpunit tests/Support/UrlTest.php   # run a single test file
```

No linting toolchain is configured.

## Architecture

**WP-Kit** is a WordPress utility library. All classes use `declare(strict_types=1)` and PSR-4 under the `Period\WpKit` namespace.

### Layer structure

- **`src/Support/`** — WordPress-agnostic utilities (string, array, URL, HTTP, date, encoding, HTML template). No WordPress function calls here.
- **`src/Infrastructure/WordPress/`** — WordPress-specific wrappers. Each class guards all WordPress calls with `function_exists()` / `class_exists()` so the code loads and tests pass without WordPress.
- **`src/View/`** — HTML generation (`Element`, `RawHtml`, `Renderer`).
- **`src/Application.php`** — Entry point; coordinates `ScriptStyleRegistrar`, `PostTypeRegistrar`, shortcodes, and class enhancers. Accessed via the `pwk()` singleton defined in `bootstrap.php`.

### Key classes

**`HtmlTemplate`** — mustache-style string templating with explicit escape modes:
- `{{ key }}` → HTML-escape; `{{{ key }}}` → raw; `{{ attr: key }}` → attribute-escape; `{{ url: key }}` → URL-sanitize; `{{ html: key }}` → strip tags
- Supports dot-notation keys: `{{ user.profile.name }}`

**`Element`** — programmatic HTML builder with auto-escaping. `Element::el($tag, $attrs, $content)` for normal tags, `Element::void($tag, $attrs)` for void tags. Static factory shortcuts: `Element::div()`, `Element::a()`, `Element::img()`, etc. Wrap unescaped HTML in `Element::raw()` / `RawHtml`. `class` attribute accepts arrays and deduplicates. `data-*` attributes accept arrays/objects (JSON-encoded). Boolean `true` renders as a boolean attribute. `null`/`false` attrs are omitted.

**`PostTypeRegistrar`** — fluent API: chain `.register()` → `.metaBox()` → `.registerTaxonomy()` → `.boot()`. Calling `.boot()` hooks everything into WordPress `init`. `.metaBox()` without `post_type` inherits the last `.register()` call.

**`MetaBox`** — field types: `text`, `textarea`, `checkbox`, `select`, `hidden`, `image`, `media`, `gallery` (JSON array of attachment IDs), `repeater` (JSON array of groups, supports nested fields with `min`/`max`/collapsible headers).

**`ScriptStyleRegistrar`** — registers and enqueues scripts/styles. Pass `path` for file-based version hashing via `filemtime`.

### WordPress-free testing

All `Infrastructure/WordPress/` classes skip registration silently when WordPress is absent. Tests assert on return values and generated HTML rather than side effects. Use Symfony DomCrawler (already a dependency) to inspect HTML output in tests.
