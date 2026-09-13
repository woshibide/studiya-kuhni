# Updating Panel plugins

An already open Panel tab keeps its JavaScript runtime when navigating between pages.
Kirby's Fiber requests replace view data but do not reload plugin scripts.
After installing or changing plugins, an old tab can therefore show a missing field type or an empty custom view even though a freshly opened tab works.
This does not mean the saved content has been removed.

Before reloading an editor tab, retain any unfinished text or confirm that its intended changes have been saved.
Do not trigger an automatic save or forced reload as a recovery mechanism.
Once unfinished work is safe, reload the Panel document using the browser's normal reload action.
Opening another Panel tab can verify that the updated editor loads without disturbing the original tab.

The current document loads the official Vue exports, Kirby's plugin registry, the assembled plugin bundle, and then the Panel application.
Kirby versions the plugin JavaScript and CSS URLs with the newest plugin asset modification time.
The `studio-tiptap` field depends on `k-tiptap-field`; the help page depends on `k-studio-guide-view`.
Both registrations must exist in the browser runtime that receives the corresponding field or view data.
The section library requires `k-studio-library-field` and `k-studio-features-field`, which inherit Kirby’s native structure and multiselect components.
The `k-studio-tiptap-field-preview` alias uses the official Tiptap preview inside native structure tables.
After saving library changes, reopen the page editor to fetch new choices after preserving any unfinished page work.
The Locator request adapter also needs a document reload in tabs opened before its installation.
Search and sharing previews require `k-serp-preview-section`, `k-studio-search-preview-section` and `k-studio-sharing-preview-section`.
The SERP plugin must register before the site adapter.

Run `php tests/panel-assets.php` after plugin changes.
It executes the complete assembled JavaScript bundle through the shipped Kirby registry with real Vue exports, checks required registrations and order, and invokes the help renderer with the actual PHP response.
It also verifies the script order and native asset revision URLs.
Locator checks exercise the real Leaflet tile creation and the installed field's search method with a controlled Nominatim response.
An exception from an earlier plugin fails the checks instead of silently skipping later registrations.
The test uses limited DOM stubs for library initialization, so actual browser checks remain necessary for editor behavior, layout and navigation.

For release verification, check a fresh Panel document and a tab that was open before the update.
The older tab must be recoverable by an explicit reload after preserving its unfinished work.
