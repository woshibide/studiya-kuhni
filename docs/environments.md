# Publication and maintenance

## Hosts

`https://studiya-kuhni-kmv.ru` serves the standalone notice, with only the studio address.
The Cyrillic domain `студия-кухни-кмв.рф` and both `www` aliases permanently redirect to the main domain.
`https://design.studiya-kuhni-kmv.ru` serves the public Kirby preview without a shared password.
Design is always noindex; the public notice is indexable.
The main domains do not run Kirby.

The server uses Nginx and PHP 8.5 on Debian 13.
Luxor remains isolated under `/srv/luxor`; the archived zpcalc website is not part of this deployment.

## Initial server setup

Configure A records for both apex domains, both `www` aliases, and `design.studiya-kuhni-kmv.ru` to `80.76.60.110`, TTL 600.
Preserve existing mail records.
Use `xn-----flcfubncsg3bmne4a0n.xn--p1ai` when tools require the ASCII hostname.
Wait for public DNS resolution before requesting certificates.

Upload `ops/` to `/srv/kuhni/setup/ops`, then run:

```sh
ssh luxor 'bash /srv/kuhni/setup/ops/server/setup.sh'
./ops/deploy.sh notice
./ops/deploy.sh design --init
ssh -t luxor 'runuser -u kuhni-design -- php /usr/local/lib/kuhni/create-admin.php'
ssh luxor /usr/local/lib/kuhni/enable-https.sh
```

The administrator command asks for an email and hidden password; nothing is copied from local accounts.
The public Panel installer stays disabled.
Initial content can be seeded only once.
If an initial release fails after seeding, fix the code and use a normal design deployment; never repeat the content import.

Setup installs the missing SQLite extension and a dedicated `kuhni-design` PHP-FPM pool using the existing PHP 8.5 service.
It configures HTTP ACME routes and loopback-only health checks on port 8087.
Public application routes remain unavailable until HTTPS activation succeeds.
Certbot uses the existing `/var/lib/letsencrypt` webroot and renewal timer.
There is one certificate for the four main-domain names and one for design, with a dedicated Nginx reload hook.
HTTPS activation checks DNS, creates certificates, validates Nginx, checks both sites and Luxor, then tests certificate renewal.

## Deploy code

Commit reviewed changes first, then run:

```sh
./ops/deploy.sh design
./ops/deploy.sh notice
```

The script builds the committed revision in a temporary directory using locked dependencies.
It excludes development tools, local state and secrets, uploads the artifact through `ssh luxor`, and switches the target's `current` symlink atomically after platform validation.
A publication lock prevents concurrent switches.
Health checks verify the page, design Panel login, and Luxor; failures restore the previous code pointer.
Three successful code releases are retained.

Persistent design state lives in `/srv/kuhni/design/shared`.
Content, uploaded/generated media, accounts, sessions, storage, logs including Loop feedback, and licenses stay outside code releases.
Normal deployment never synchronizes or deletes this data.
After initialization, edit content on the server through the Panel.
Cache is runtime data and can be regenerated independently.

```sh
./ops/deploy.sh rollback design
./ops/deploy.sh rollback notice
```

Rollback switches code only and preserves current editorial data.
It cannot recover deleted content or reverse incompatible content migrations.
Automatic content backups will be added after editors populate the site; they are intentionally not installed for this first preview publication.

## Runtime and indexing

`STUDIO_SHARED_ROOT` selects an existing absolute persistent root; all expected directories must exist.
Leave it unset for normal local development.
The application does not automatically load `.env` files.
The dedicated FPM pool sets `STUDIO_ENV=staging`, `STUDIO_PRODUCTION_URL=https://studiya-kuhni-kmv.ru`, and disables callback delivery.
Secrets and licenses stay outside release artifacts.

Nginx sends `X-Robots-Tag: noindex, nofollow, noarchive` on design responses, including media and errors.
Kirby also supplies HTML noindex and refuses the staging sitemap.
An exact Nginx robots route allows crawlers to read these directives, overriding the application's blanket local/staging disallow.
The preview is public; noindex is not access control.
Do not link design from the public notice.

Nginx executes only the front controller and denies private source and maintenance paths.
The site has no Apache dependency.
Future main-domain Kirby publication, SMTP activation, automatic content backups, and any content migration require separate work.

## Verification

Run `composer test`, `composer audit`, `composer check-platform-reqs`, and `npm audit --prefix assets/js` before deployment.
Check Nginx and FPM syntax before reloading services.
Verify HTTPS and redirects, noindex on design HTML/media/errors, Panel login and editing, uploads, feedback, galleries and maps.
Use disposable fixtures to confirm code deployment and rollback preserve editorial state.
Check Luxor after server configuration changes.
