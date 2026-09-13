# Callback form

Both contact forms submit to `POST /callback`.
JavaScript receives JSON; regular form submissions redirect with HTTP 303 to `/contacts#callback-form` and consume session feedback once.
Invalid fields remain populated for correction.
The sender receives success only after the request is saved in the private inbox.
Email delivery runs afterward; a failed email cannot discard an accepted request or tell the visitor to resubmit.

## Enable later in production

Delivery requires `studio.environment = production`, a request on the configured main origin, explicit enablement, a configured private storage directory, valid sender and recipient addresses, and an SMTP host.
Local and staging delivery always remain disabled even if production mail credentials are present.
The `design.*` host cannot send callbacks even if its environment is accidentally set to production.
Until configured, visitors see an unavailable notice and the existing telephone contact; the submit button is disabled.

Pass credentials through the production PHP-FPM environment or deployment secret store.
Never store them in content, Panel fields, tracked configuration, or a public `.env` file.

| Environment variable | Meaning | Default |
| --- | --- | --- |
| `STUDIO_CALLBACK_STORAGE_DIR` | Existing writable directory outside the public website root | Empty |
| `STUDIO_CALLBACK_ENABLED` | Explicit delivery enablement | `false` |
| `STUDIO_CALLBACK_FROM` | Verified sender address | Empty |
| `STUDIO_CALLBACK_TO` | Studio recipient address | Empty |
| `STUDIO_SMTP_HOST` | SMTP server hostname | Empty |
| `STUDIO_SMTP_PORT` | SMTP port | `587` |
| `STUDIO_SMTP_SECURITY` | Kirby SMTP security mode | `tls` |
| `STUDIO_SMTP_USERNAME` | SMTP account | Empty |
| `STUDIO_SMTP_PASSWORD` | SMTP password | Empty |

Equivalent explicit Kirby options `studio.callback.enabled`, `.from`, `.to`, `.storage`, and `.transport` override environment defaults.
SMTP authentication is enabled by default.
The transport option accepts Kirby's standard SMTP configuration array.
Set the verified sender as `from`; user-provided email is used only for `replyTo`.
The email contains the validated phone, name, optional email, consent acknowledgement, and server-resolved originating page title and URL.

## Operational behavior

CSRF validation, a hidden honeypot, field limits, and server-side consent validation run before delivery.
Five attempts per peer IP are allowed in a rolling 15-minute window after CSRF validation.
Rate-limit state uses an exclusive file lock and salted address hashes under `site/cache/studio-callback`; it retains no form values or raw IP addresses.
Expired rate-limit records are pruned on the next request.
The directory must remain writable and shared across production releases but separate from staging.
This implementation targets one PHP application host, with all FPM workers sharing the same directory.
If a reverse proxy is introduced, configure a trusted client-address boundary before changing the throttle; forwarded request headers are intentionally ignored.
Failure to write rate-limit state blocks delivery.
Storage failures show a generic failure message without exposing paths or database details.
SMTP failures are recorded in the inbox; an interrupted delivery or status write remains unconfirmed.
Email notifications are not automatically retried.
The existing email contains contact details, so access to the configured recipient mailbox must remain restricted.
HTML fallback temporarily stores failed form values in the visitor's session, expiring after 10 minutes or the next contact-page read.

## Verification

Run `php tests/callback.php` for isolated validation, environment-gating, fake delivery, and file-throttle tests.
Run `php tests/callback-route.php` for the real Kirby route, temporary storage, and a verified fake email component covering mail composition and the HTML redirect workflow.
No network connection or mail delivery occurs in these tests.
Before enabling production, verify delivery with the agreed recipient, confirm reply-to and kitchen context, and check consent wording against the final published privacy document.

## Panel inbox

Open **Заявки** in the Panel menu (`/panel/studio-callback`).
The default list shows new requests, newest first, with filters for **В работе**, **Завершённые**, **Спам**, and **Все**.
Lists paginate at 20 records and show status counts.
Use **Обновить** to fetch incoming requests.
Open a request, then **Статус и заметка** to record the next step or result of a call.
Original contact details, submission time, consent, and the server-resolved originating page are read-only.
Times are displayed in UTC.
Updates record the editor's user ID and reject stale saves instead of overwriting a colleague's changes.
Deletion uses Kirby's confirmation dialog and also rejects stale versions.

Administrators have access by default.
Assign the **Менеджер** role to staff who also edit website pages and files.
Its sidebar contains **Заявки**, **Страницы**, and **Помощь**; user administration, system access, and role changes are denied.
Assign the **Менеджер заявок** user role to staff who may handle personal data without editing website content or administering other accounts.
Other roles are denied unless their user blueprint explicitly grants `studio.callback.manage: true`; `access.panel` and `access.studio-callback` must also permit access.
Review any existing role with wildcard permissions because those explicitly grant plugin permissions too.
Hiding the menu is supplemented by permission checks on every view, dialog load, and submission.
Write requests require Kirby's session CSRF header.
The Panel supplies private, no-store responses.
Requests never become Kirby content pages, files, public API records, sitemap entries, or static exports.
There is no import of previously emailed requests.

## Private storage setup

PHP requires PDO SQLite (`pdo_sqlite`).
Provision a directory outside the public website root, owned by the PHP worker, with mode `0700`.
Set `STUDIO_CALLBACK_STORAGE_DIR` or `studio.callback.storage` to its absolute path.
The directory must already exist and remain writable across deployments.
The inbox rejects public-root paths and symlinks resolving inside the public root.
SQLite creates `callbacks.sqlite` with mode `0600`; callbacks must not use cache storage or be removed during cache cleanup.
Use a separate directory for each environment and never copy production records into development or staging.
Keep database backups private and apply the agreed retention policy to both the live database and backups.
Deleting a record removes it from the active inbox with SQLite secure deletion enabled; it does not delete previously sent emails or backups.
No automatic retention period is imposed.
This implementation supports one application host with local SQLite storage, not a database shared over a network filesystem.

Without storage configuration, authorized staff see a setup notice and the public form remains disabled.
The environment and SMTP enablement gates remain in place.
Check successful save, inbox access, and SMTP delivery before enabling production.

## Push notifications decision

The requested [Kirby Push Notifications plugin](https://github.com/philippoehrlein/Kirby-Push-Notifications) was reviewed at version 1.2.0 on 2026-09-13 and is not installed.
Its [public subscribe route](https://github.com/philippoehrlein/Kirby-Push-Notifications/blob/main/config/routes/subscribe.php) accepts a caller-selected channel, and the [subscription hook](https://github.com/philippoehrlein/Kirby-Push-Notifications/blob/main/config/hooks/subscribe.php) does not authorize access to private channels.
Its [Panel subscription endpoint](https://github.com/philippoehrlein/Kirby-Push-Notifications/blob/main/config/api/subscribe.php) accepts a caller-supplied `user_id` instead of always binding subscriptions to the authenticated account.
Its [send-to-many hook](https://github.com/philippoehrlein/Kirby-Push-Notifications/blob/main/config/hooks/send-to-many.php) sends to the whole channel when the user-ID list is empty.
These behaviors do not establish the required permission boundary for callback staff.
A generic notification containing no customer data could limit disclosure, but safe staff-only delivery would still require additional identity binding, private-channel authorization, and permission rechecks before sending.
Browser notifications can remain visible after logout, so sensitive data must never be included in their title, body, URL, or other payload fields.
Per the requested fallback, no push subscriptions, service worker, or notification dependency were introduced.

## Additional verification

Run `php tests/callback-inbox.php` for storage, pagination, permission denial, CSRF, escaped list text, and concurrent-edit checks.
Run `node tests/callback-panel.mjs` for isolated browser checks of real login, form submission, native Panel controls, permission denial, SMTP failure, deletion, and mobile layouts.
Set `STUDIO_PLAYWRIGHT_MODULE` and `STUDIO_CHROMIUM` if Playwright or Chromium are not on their usual paths.
Optionally set `STUDIO_CALLBACK_SCREENSHOTS` to retain browser screenshots.
The browser fixture uses temporary accounts and storage, fake SMTP, and a simulated HTTPS request boundary on loopback; it never sends email or modifies real callbacks.
