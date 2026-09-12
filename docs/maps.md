# Factory locations

Factory editors use the Locator field to search for an address, enter latitude and longitude, or move the marker.
The field stores `fabric_location` as YAML with `lat`, `lon`, and optionally `zoom`.
Kitchen pages use the saved location of their parent factory.
Empty or invalid locations render no interactive map.
Clearing a saved Locator field suppresses the legacy coordinates and hides the map.

The empty editor map has a world overview, not a default factory location.
Latitude and longitude must both be finite numbers within -90 to 90 and -180 to 180 respectively.
Zero is valid for either coordinate.
Invalid or absent zoom uses level 13; valid zoom is an integer from 1 to 19.
Panel searches use Nominatim only after an explicit search, with autocomplete disabled.
Panel tiles use OpenStreetMap.

The local Panel adapter uses the canonical `https://tile.openstreetmap.org/{z}/{x}/{y}.png` endpoint.
Locator tile images and explicit Nominatim searches send the site's origin as their cross-origin referrer using `strict-origin-when-cross-origin`.
This fixes the observed OpenStreetMap `403 Access blocked` tiles caused by missing referrers under Kirby's default Panel policy.
The Panel's global `same-origin` policy remains unchanged, and full editor paths are not sent to these services.
After updating the adapter, [reload older Panel tabs](panel-updates.md) once unfinished edits are safe.

## Provider configuration

Public factory and inherited kitchen maps use EOX Sentinel-2 cloudless 2016 imagery without an account or API key.
The HTTPS WMTS endpoint is `https://tiles.maps.eox.at/wmts/1.0.0/s2cloudless_3857/default/g/{z}/{y}/{x}.jpg`.
The layer identifier deliberately has no year suffix: EOX's [capabilities document](https://tiles.maps.eox.at/wmts/1.0.0/WMTSCapabilities.xml) identifies it as the 2016 Web Mercator layer under CC BY 4.0.
Do not substitute a newer annual layer without checking its different license.
Visible attribution includes EOxCloudless, EOX IT Services GmbH, modified Copernicus Sentinel data 2016, the license, and EOX::Maps.

The public map caps zoom at 14 to suit the imagery's approximately 10-metre resolution.
Saved factory coordinates and zoom values remain unchanged; the renderer clamps a larger saved zoom to the provider limit.
Tiles load only when the map becomes visible, and the external map link remains available if the free service fails or rate-limits requests.

Public maps use a white location marker with a dark center, restrained white controls, and the site's existing typography.
The marker and zoom buttons retain 44-pixel interaction targets, keyboard operation, and visible focus.
Panel Locator maps continue to use street tiles for address editing.

`studio.map.tileUrl`, `studio.map.attribution`, and `studio.map.maxZoom` can select another compatible raster tile provider through trusted server configuration.
Keep the provider's required attribution when changing the tile URL.
These configuration values are not editable content fields.
When JavaScript or tiles fail, the public page keeps a link to the saved coordinates in OpenStreetMap.

The Locator field explicitly uses `tiles: openstreetmap` and `geocoding: nominatim`.
`sylvainjule.locator.mapbox.id` is set to `mapbox/outdoors-v11` solely to avoid Locator 2.1.0 passing null to `array_key_exists()` on PHP 8.5.
This compatibility setting does not select Mapbox, send Mapbox requests, or require a Mapbox token.

Keep visible attribution and normal HTTP caching; do not add tile prefetching or offline downloads.
Public OpenStreetMap services have usage limits and no availability guarantee.
Nominatim permits at most one request per second across the application; keep searches manual and do not add autocomplete, background geocoding, or bulk queries.
Review the [tile policy](https://operations.osmfoundation.org/policies/tiles/) and [Nominatim policy](https://operations.osmfoundation.org/policies/nominatim/) before deployment and select a suitable provider if usage grows.

## Existing coordinates

Run `php tools/migrate-locations.php` to inspect the migration without writing files.
Run `php tools/migrate-locations.php --apply` to copy validated legacy latitude, longitude, and zoom into `fabric_location`.
The command preserves every existing content field and never overwrites a Locator field, including an explicitly empty one.
Running it again makes no changes.
Factories without valid existing coordinates remain unchanged.

Run this migration before editing existing factories with the new blueprint.
Until migration, a factory with no Locator field can still use its valid legacy coordinates on the frontend.
The old numeric values remain in content for migration compatibility; the editor uses the single Locator field.
No new business location is inferred or geocoded during migration.

## Verification

Run `php tests/maps.php` for coordinate validation, field precedence, clearing, legacy migration, idempotence, and Locator field decoding.
Tests use temporary content and never update the website content.
Run `php tests/panel-assets.php` to verify the assembled Locator adapter, canonical tile URL, per-image referrer policy, other provider URLs, and Nominatim request handling.
Browser verification reproduced the blocked tiles before the change, then confirmed rendered street tiles in a disposable search draft and the existing Aran Cucine editor.

The satellite follow-up verifies the 2016 layer, WMTS row/column order, attribution, zoom clamping, and rendered imagery in the browser.
