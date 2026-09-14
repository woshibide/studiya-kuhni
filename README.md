# Студия Кухни

Kirby website for the kitchen showroom.
PHP 8.5 and Node.js are required for local development and verification.

## Local development

```sh
composer install
npm ci --prefix assets/js
composer start
```

Open `http://localhost:8000`.
Local and staging environments disable indexing and callback email delivery.
Environment variables are documented in `.env.example`; this project does not automatically load `.env` files.

```sh
composer test
composer audit
composer check-platform-reqs
```

Tests use temporary content copies and fake email delivery.
They do not send enquiries or change editorial source content.

## Kirby MCP

```sh
vendor/bin/kirby-mcp install
vendor/bin/kirby-mcp
```

MCP runs locally over stdio and is a development dependency.
Generated runtime commands are ignored and excluded from production artifacts.

## Editing

Open `/panel` for pages, the kitchen catalogue, contacts, and site settings.
Signed-in editors can edit the current page with Admin Bar and leave contextual feedback with Loop; see [editor tools](docs/editor-tools.md).
The Panel's “Помощь” section explains saving, preview, page status, inherited factory information, and the current environment.
Narrative fields use Tiptap 1.3.1 while existing content remains readable without a migration.
See [rich-text integration](docs/rich-text.md) for its storage format, compatibility adapter, and rendering rules.
See [Media Kit downloads](docs/media-kit.md) for uploading, labeling, and ordering visitor downloads.
After changing Panel plugins, follow the [Panel update workflow](docs/panel-updates.md) to refresh open tabs while preserving unfinished work.
Shared kitchen details and benefits are managed in the Panel's “Фабрики → Библиотека” tab; see [section library](docs/section-library.md) for selection, page-specific text, and migration.
The “Поиск и соцсети” tab previews search results and social cards, with automatic OG covers when needed; see [metadata editing](docs/seo.md).

## Runtime and publication

See [technical readiness](docs/launch-readiness.md), [environment and promotion architecture](docs/environments.md), [callback configuration](docs/callback.md), and [factory maps](docs/maps.md).
Deploy with `./ops/deploy.sh design` or `./ops/deploy.sh notice` after initial server setup.
The public design hostname is non-indexable, with persistent editorial storage and a dedicated PHP-FPM pool.
Main domains serve a standalone notice; Luxor retains its existing configuration.
See the environment guide for initial setup, HTTPS, rollback, and deferred content backups.

The website runs on Kirby/PHP; static export and GitHub Pages publishing have been removed.
