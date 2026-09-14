# Technical launch preparation

Review date: 12 September 2026.
This pass covers accessibility, application behavior, Kirby editing, dependencies, and the pre-publication environment architecture.
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
| Publication | Code-only deploy and rollback preserve server editorial data; main domains use a static notice. |

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

1. Create the first administrator through the protected Kirby setup screen described in the environment guide.
2. Fill and review editorial content on the design server, including business details, photos, locations, and page metadata.
3. Add automatic content backups after the content is populated, before relying on it for the public launch.
4. Plan main-domain Kirby publication and activate the appropriate Kirby license.
5. Configure production SMTP and verify an authorized enquiry before enabling delivery.

A Panel promotion action is not part of this deployment.
Code releases and rollback preserve server editorial data; code rollback is not a content backup.

## Deployment verification, 14 September 2026

Both code releases are installed under `/srv/kuhni`, with the PHP pool and Nginx loopback health routes active.
The static notice contains only the studio address.
Project tests, dependency audits, PHP platform checks, Nginx/FPM syntax checks, and desktop/mobile notice checks passed.
Browser checks against the deployed Nginx/FPM site passed for Panel login, draft editing, image upload, feedback, real map tiles, and gallery interaction.
Content/account checksums and feedback rows stayed unchanged through a second deployment and code rollback; an intentionally invalid notice release automatically restored the working release.
Disposable test data was removed afterward, and Luxor still returns HTTP 200.

The main notice is now public over HTTPS, including both domains and their `www` aliases.
Its certificate expires on 13 December 2026, and Certbot renewal plus the Nginx reload hook passed a dry run.
External checks confirm canonical redirects, static assets, address-only copy, private-path 404s, and Luxor health.

Design is now public at `https://design.studiya-kuhni-kmv.ru` after its DNS record propagated.
Its separate certificate expires on 13 December 2026, and both certificates passed renewal dry runs with the Nginx reload hook.
The existing Certbot renewal timer is enabled and active.
External checks passed for all five HTTPS hostnames, permanent redirects, design noindex metadata and headers on HTML/media/errors, crawlable robots rules, sitemap exclusion, and private/executable-path blocking.
Public browser checks passed for desktop/mobile layouts, real factory-map tiles, gallery navigation, and the notice at 1440, 390, and 320 pixels.
Nginx and PHP-FPM configuration checks passed, and Luxor remains healthy.

Kirby has no accounts and is ready for the owner's first-run browser setup.
A temporary password protects Panel/API routes, including alternate Kirby parameter paths; the public frontend remains password-free.
The first administrator creation removes the setup gate automatically.
The setup form was verified over public HTTPS without creating an account.
See the environment guide for setup access and maintenance commands.
