# Callback form

Both contact forms submit to `POST /callback`.
JavaScript receives JSON; regular form submissions redirect with HTTP 303 to `/contacts#callback-form` and consume session feedback once.
Invalid fields remain populated for correction.
The sender receives success only after Kirby reports successful SMTP delivery.

## Enable later in production

Delivery requires `studio.environment = production`, a request on the configured main origin, explicit enablement, valid sender and recipient addresses, and an SMTP host.
Local and staging delivery always remain disabled even if production mail credentials are present.
The `design.*` host cannot send callbacks even if its environment is accidentally set to production.
Until configured, visitors see an unavailable notice and the existing telephone contact; the submit button is disabled.

Pass credentials through the production PHP-FPM environment or deployment secret store.
Never store them in content, Panel fields, tracked configuration, or a public `.env` file.

| Environment variable | Meaning | Default |
| --- | --- | --- |
| `STUDIO_CALLBACK_ENABLED` | Explicit delivery enablement | `false` |
| `STUDIO_CALLBACK_FROM` | Verified sender address | Empty |
| `STUDIO_CALLBACK_TO` | Studio recipient address | Empty |
| `STUDIO_SMTP_HOST` | SMTP server hostname | Empty |
| `STUDIO_SMTP_PORT` | SMTP port | `587` |
| `STUDIO_SMTP_SECURITY` | Kirby SMTP security mode | `tls` |
| `STUDIO_SMTP_USERNAME` | SMTP account | Empty |
| `STUDIO_SMTP_PASSWORD` | SMTP password | Empty |

Equivalent explicit Kirby options `studio.callback.enabled`, `.from`, `.to`, and `.transport` override environment defaults.
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
Delivery exceptions show a generic failure message without exposing SMTP details.
HTML fallback temporarily stores failed form values in the visitor's session, expiring after 10 minutes or the next contact-page read.

## Verification

Run `php tests/callback.php` for isolated validation, environment-gating, fake delivery, and file-throttle tests.
Run `php tests/callback-route.php` for the real Kirby route, temporary storage, and a verified fake email component covering mail composition and the HTML redirect workflow.
No network connection or mail delivery occurs in these tests.
Before enabling production, verify delivery with the agreed recipient, confirm reply-to and kitchen context, and check consent wording against the final published privacy document.
