# Runtime and future publication

## Current behavior

This repository targets PHP 8.5, the current stable branch, with the latest available security patch installed at deployment time.
The old Luxor website stays on its existing PHP-FPM service until its own upgrade is tested.
Nothing in this change deploys a website or alters the server.

Set `STUDIO_ENV` to `local`, `staging`, or `production` through the process environment.
Missing or invalid values fall back to a non-indexable local environment.
Set `STUDIO_PRODUCTION_URL` to the exact HTTPS origin of the eventual main domain, without a path, query, credentials, or trailing slash.
The application does not load `.env` files automatically; `.env.example` documents the supported values.
Keep environment secrets outside all document roots and source control.

Only `production` requests on the configured main hostname are eligible for indexing.
Every `design.*` hostname remains non-indexable, even if someone accidentally sets `STUDIO_ENV=production`.
The application sends a robots meta tag and `X-Robots-Tag` for non-production HTML, denies crawling through `robots.txt`, and returns 404 for the staging sitemap.
The Apache staging host must also send `X-Robots-Tag` on every response, including directly served media and errors.
HTTP authentication protects the complete staging host; robots directives alone are not access control and cannot guarantee that an already indexed URL immediately disappears.
Do not link the design hostname from public navigation or the production sitemap.

Page-level `seo_noindex` can further restrict indexing but cannot override environment restrictions.
The production sitemap includes published pages, excluding drafts, draft descendants, error pages, page-level noindex pages, and configured unpublished paths.
`studio.unpublishedPaths` currently excludes `archive`, matching the existing launch decision in its template and navigation.
Remove that entry when the archive is ready.
Kirby draft status controls public visibility; authenticated draft preview remains an editorial action.

## Separate hosts and storage

The existing Luxor host serves `/var/www`.
Place both new environments outside that tree, under `/srv/kuhni`, so the old domain cannot expose the new project through its document root.

| Resource | Main | Design |
| --- | --- | --- |
| Host | `DOMAIN` | `design.DOMAIN` |
| Apache document root | `/srv/kuhni/production/current` | `/srv/kuhni/staging/current` |
| PHP-FPM service version | PHP 8.5 | PHP 8.5 |
| Dedicated pool/socket | `kuhni-production` | `kuhni-staging` |
| Unix worker account | `kuhni-production` | `kuhni-staging` |
| Indexing | Exact configured main host only | Never |
| Public access | Allowed | HTTP authentication |
| Callback delivery | Explicit SMTP configuration | Disabled |

Give each environment separate `content`, `media`, `site/accounts`, `site/sessions`, `site/cache`, logs, and runtime secrets.
Use host-only cookies and distinct session names; never set a parent-domain session cookie.
Do not share writable content, accounts, or sessions between environments.
Keep the existing `/run/php/php8.3-fpm.sock` mapping unchanged.
Install PHP 8.5 alongside 8.3 and map only the new virtual hosts to their dedicated 8.5 sockets.

The inspected server has about 2 GB RAM and no swap.
Start the new pools with `pm=ondemand`, one worker each, `pm.max_requests=300`, and a 256 MB memory limit, then measure concurrent Panel image processing and frontend requests before increasing limits.
Use `php_admin_value[memory_limit]` to enforce the pool limit.
Set matching upload/post limits suitable for approved images, for example 16 MB and 20 MB, without changing existing pools.
Enable OPcache and required Kirby extensions in PHP 8.5 itself; PHP 8.3 extension availability does not prove 8.5 readiness.

## Future Panel promotion

Default assumption: promotion publishes the reviewed complete website snapshot, including code, blueprints, content, and selected media.
This is recorded as an architectural choice, not an implemented publish button.
Production editorial writes should be disabled when this workflow is introduced; otherwise a whole-site promotion could overwrite independent edits.

The future staging Panel action must be admin-only, CSRF-protected, and explicitly identify the target domain and immutable release ID.
The Panel should request a narrowly scoped deployment worker job, not execute arbitrary shell commands with web-server privileges.
The worker must hold a publication lock and use configured allowlisted paths and hosts.

Local Kirby MCP is a development dependency.
After a development Composer install, run `vendor/bin/kirby-mcp install` to generate its ignored local CLI commands.
The runtime tooling is not part of a production artifact.

1. Freeze code and editorial state into an immutable release candidate.
2. Exclude environment config, accounts, sessions, logs, caches, local tooling, and development dependencies.
3. Record source revision and content/media checksums in a release manifest.
4. Preview that exact candidate on the protected design host and run the checks below.
5. Promote the same candidate into a new production release; inject production-only runtime configuration separately.
6. Atomically switch `production/current` only after validation succeeds.
7. Check main-domain health and both existing websites; switch back to the previous release if health fails.
8. Record actor, time, release ID, result, and rollback target; show job status in the Panel.

Do not copy the staging environment variables into production.
Do not mutate a currently served release in place.
Regenerate media and caches per release/environment; preserve old release files long enough for rollback and in-flight asset requests.
Pin a single candidate while review or promotion is in progress so concurrent Panel edits cannot change what is being published.
Retention and rollback must include content and media, not only Git code.

## Checks before future deployment

- Run `composer install --no-dev --optimize-autoloader`, platform checks, dependency audit, and the project test suite on the candidate using PHP 8.5.
- Run `npm ci --prefix assets/js` to install the pinned Embla and Leaflet runtime files used by the current frontend.
- Exclude `site/commands/mcp`, `tests`, `tools`, local secrets, `.git`, and `static` from deployment artifacts.
- Verify Apache syntax, PHP-FPM pool syntax, separate directory permissions, backups, and a restored test snapshot before changing any live symlink.
- Provision certificates for main and design hostnames; redirect HTTP and the chosen main-domain alias to their canonical HTTPS hosts.
- Verify design HTML, media, errors, Panel, and API require authentication and always return noindex headers.
- Verify main canonical URLs, sitemap, robots rules, 404s, draft exclusion, callback delivery, and HTTP-to-HTTPS redirects.
- Confirm `/vendor`, `/site`, `/content`, `/kirby`, `/tests`, `/tools`, `/static`, dotfiles, Composer manifests, and test PHP entrypoints cannot expose source or execute maintenance actions over HTTP.
- Confirm `luxor-kmv.ru` and `zpcalc.ru` still serve their baseline responses after any new virtual-host or FPM changes.

Apache templates in `ops/` are inactive examples and must not be enabled before domains, authentication files, certificates, paths, and pool users exist.
Static export and GitHub Pages publishing have been removed.

## References

- [PHP supported versions](https://www.php.net/supported-versions.php)
- [Apache name-based virtual hosts](https://httpd.apache.org/docs/2.4/vhosts/name-based.html)
- [PHP-FPM configuration](https://www.php.net/manual/en/install.fpm.configuration.php)
- [Google noindex guidance](https://developers.google.com/search/docs/crawling-indexing/block-indexing)
