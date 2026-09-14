# Search and sharing metadata

Page editors use the **Поиск и соцсети** tab.
The left column edits metadata; the right column previews a search result and a shared link while typing.
The site tab with the same name configures shared defaults.

## Editor behavior

- An empty page title uses the page name.
- Search results append ` | ` and the site's visitor-facing name; social cards use the page title alone.
- An empty description uses the site's shared description.
- **Изображение для ссылки** is the only image-mode selector.
- **Обложка с текстом** generates a cover using the supplied combined artwork and shows only cover text and styling controls.
- **Своё изображение** shows only the page image picker; an empty picker uses the shared cover until a file is selected.
- **Общая обложка** uses the site upload or supplied address artwork and hides page image controls.
- Switching modes preserves custom text, typography and the selected file so editors can return to them.
- Pages without an explicit mode retain their previous behavior, including separate saved and unsaved choices.
- New pages default to **Обложка с текстом**.
- **Текст на обложке** changes only the image text and preserves explicit line breaks.
- An empty cover text uses the SEO title or page name; kitchens without an SEO title use the factory and model on separate lines.
- The preview slider adjusts text from 48 to 144 pixels, with 112 pixels matching the supplied example.
- Drag the text in the preview, or focus it and use arrow keys (1 pixel) and Shift + arrow keys (10 pixels).
- **Сбросить** restores the default size and position without clearing custom cover text.
- Cover text, size and position follow the normal Panel save and discard workflow.
- **Скрыть страницу от поисковых систем** restricts indexing; drafts and environment restrictions still take priority.

The preview uses the configured production origin when available.
It describes the expected appearance; search engines and messaging apps may rewrite text or crop images differently.
Metadata changes become public when the editor saves in the current environment.
Third-party services can retain older cards until they refresh their caches.

## Implementation

[SERP Preview](https://github.com/johannschopplich/kirby-serp-preview) supplies the search-result component.
The `studio/seo` adapter supplies live titles, the exact site suffix, inherited descriptions and production paths.
Existing `seo_title`, `seo_description`, `seo_image`, `seo_generate_image` and `seo_noindex` content remains compatible.
The explicit `seo_image_mode` takes precedence; the native select adapter resolves legacy values for each Panel content version without changing files on read.
No content migration is required.

[OG Image](https://github.com/mauricerenck/og-image) renders automatic covers at 1200 × 630.
The plugin composes the supplied template; the adapter draws adjustable text using `assets/og/Suisse Int'l Medium.ttf`.
The default origin is (40, 72) in a 1200 × 630 image, with 112-pixel text and 110-pixel baseline spacing.
Text wraps by measured glyph width, preserves Unicode and explicit line breaks, and shrinks when needed to fit above the logo.
Placement is clamped to 24-pixel outer margins and a title bottom edge of 320 pixels.
Extremely long text truncates with an ellipsis at the minimum size and shows an editor warning.
The original logo pixels remain unchanged.
The integration captures upstream output without adding generated files to page content.
PHP GD with FreeType is required; Composer checks the GD extension.

`index.php` registers guarded `/og-image` routes before the plugin's default routes.
Custom entry points must likewise pass `Studio\Seo\OgImage::routes()` in the Kirby constructor's `routes` property.
Drafts, excluded archive pages, error pages and missing pages return 404 from these routes.
Uploaded images retain their actual file format instead of being served with a forced PNG content type.
The PHP runtime serves these endpoints.

Authenticated Panel previews accept unsaved text through `api/studio-seo/og-preview` and return a PNG data URL without saving content or adding cache entries.
Public generation keeps one cache entry per page under Kirby's cache root.
Cover text, font size, position, font file and template changes invalidate the image revision.
Increment `OgImage::VERSION` when changing rendering logic.

Convert `assets/og/og-image_OG combined.jpg` into the PNG template required by the plugin:

```sh
php tools/build-og-template.php
```

After installing these plugins, preserve unfinished work and reload existing Panel tabs as described in [Panel updates](panel-updates.md).

## Verification

```sh
composer test
composer check-platform-reqs
node tests/seo-panel.mjs
```

The browser test requires Playwright with Chromium.
`STUDIO_PLAYWRIGHT_MODULE` can point to an existing Playwright module; `STUDIO_CHROMIUM` can select an existing Chromium executable.
`STUDIO_SEO_SCREENSHOTS` optionally retains screenshots outside the test directory.
The test creates temporary copies of content, accounts, media, sessions and caches, then removes them on completion.
It checks live previews, cover typography, pointer dragging, keyboard movement, reset, upload and image precedence, save/reload, public metadata, identical PNG output, site defaults, long Cyrillic text, mobile overflow and dark mode.
