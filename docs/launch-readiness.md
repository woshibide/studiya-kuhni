# Technical launch preparation

Review date: 12 September 2026.
This pass covers accessibility, application behavior, Kirby editing, dependencies, and the architecture needed for a protected staging site.
Editorial copy, photographs, final business locations, and production deployment remain separate work.

## Implemented

| Area | Result |
| --- | --- |
| Runtime | Kirby 5.5.3 with a PHP 8.5 requirement and updated locked dependencies. |
| Navigation | Keyboard operation, Escape, focus restoration, hidden-panel inertness, mobile focus containment, and readable scrolled navigation. |
| Galleries | Native dialogs, keyboard navigation, one close control, focus restoration, and accessible image counters. |
| Motion | System reduced-motion support and a persistent pause control across animated page features and maps. |
| Text contrast | Muted text uses a separate light-surface color with measured contrast of 5.96:1 on page white and 5.17:1 on silver; kitchen information has an opaque backing. |
| Page structure | Russian document language, skip links, main landmarks, one primary heading, and unique IDs. |
| Images | Explicit dimensions, responsive source selection, and editable image metadata with decorative-image support. |
| Enquiries | Shared accessible form, server validation, CSRF protection, consent, honeypot, throttling, and honest unavailable states until production SMTP is configured. |
| Kirby Panel | Russian task-based tabs and help, direct catalogue/contact navigation, factory parent links, environment labels, useful photo previews, scoped uploads, and singleton protection. |
| Rich text | Tiptap 1.3.1 for narrative fields, compatible legacy import without content migration, safe frontend rendering, and accessible editor labels. |
| Factory maps | Locator 2.1.0 in the Panel and local Leaflet 1.9.4 assets on the frontend, with lazy tiles, attribution, keyboard controls, failure fallbacks, scoped Panel referrers that resolve blocked OpenStreetMap tiles, and EOX 2016 satellite imagery on public maps. |
| Media Kit | Ordered uploads for each content section, editable download labels and descriptions, file type and size, and responsive visitor download links. |
| Indexing | Only the exact configured HTTPS production origin is eligible for indexing; design hosts remain excluded even under an incorrect production setting. |
| Publication | Separate environments and future immutable promotion are documented; deployment templates remain inactive. |

Four factories now have Locator values copied from their previous coordinates.
The migration preserves the original fields and is safe to repeat.
Aran Cucine and Aster Cucine currently share the same saved coordinates; confirm their real locations during the editorial review.
Mossman has no saved coordinates and displays an empty state.

## Verification

`composer test` exercises isolated local, staging, production, and incorrectly configured design-host requests.
It checks public visibility, robots rules, canonical behavior, sitemap exclusions, 404s, markup structure, blueprint forms, callback routes, location migration, and frontend script compatibility.
Tests copy content into temporary directories and use fake email delivery.

Kirby MCP runtime inspection confirms eight loaded plugin registrations, including Locator and Tiptap, and 44 blueprint IDs without reported errors.
The blueprint suite validates 32 YAML files, 48 editor models, 380 sections, and 117 image editors.
Panel checks cover native routes, sidebar highlights, access-aware shortcuts, environment labels, and Russian plugin translations.
Icon checks use Kirby's shipped SVG registry, and client asset checks execute the complete assembled plugin bundle to verify editor and guide registrations.
Rich-text checks cover legacy formatting, actual form saves, JSON rendering, portable internal links, inline structure, and frontend field bindings.
Composer and npm security audits report no known advisories for the installed dependency versions at review time.
PHP platform requirements and custom PHP syntax checks pass locally.

Browser checks cover desktop menus, gallery focus and image navigation, FAQ controls, motion controls, mobile navigation, and real factory-map tiles.
The 390-pixel mobile check verifies map marker and zoom controls with Space, popup close behavior, visible attribution, tile seams, gallery control placement, image navigation, and enquiry handoff.
HTTP checks confirm factory and inherited kitchen map markup and locally served Leaflet resources.
Panel browser checks cover the editing guide, factory and kitchen navigation, photo covers, named Tiptap controls, saving and reloading formatted text, native website preview, and a 390-pixel editor layout.
An existing browser tab reproduced missing editor types and an empty guide after the plugin installation; reloading that document restored both.
The [Panel update workflow](panel-updates.md) explains how to refresh older tabs while retaining unfinished work.
The final blueprint review also corrected invalid icons, clarified additional text pages, restored catalogue consultation controls, enabled native file links, and improved logo preview contrast.
The map follow-up reproduced the Panel's blocked tiles and confirmed real tiles after the request-policy fix.
Media Kit checks cover native file creation and validation, section selection, metadata, excluded files, public links, a byte-identical HTTP ZIP download, and desktop and 390-pixel visitor previews.
The [Media Kit editor guide](media-kit.md) explains uploads and publication within each section.
The disposable draft used for the editing round trip was removed; existing editorial text was not migrated or rewritten.
These local checks do not establish production-server behavior or a complete accessibility certification.

## First publication and later launch gates

The initial publication now follows the [Nginx deployment workflow](environments.md).
The design host is a public noindex preview; main domains serve a static notice with the studio address only.
This supersedes the earlier protected-staging and whole-site promotion assumptions above.

1. Finish initial DNS, HTTPS, administrator creation, and the deployment verification described in the environment guide.
2. Fill and review editorial content on the design server, including business details, photos, locations, and page metadata.
3. Add automatic content backups after the content is populated, before relying on it for the public launch.
4. Plan main-domain Kirby publication and activate the appropriate Kirby license.
5. Configure production SMTP and verify an authorized enquiry before enabling delivery.

A Panel promotion action is not part of this deployment.
Code releases and rollback preserve server editorial data; code rollback is not a content backup.
