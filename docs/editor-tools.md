# Frontend editor tools

[Loop](https://moinfra.me/docs/moinframe-loop) 1.1.1 and [Admin Bar](https://github.com/Pechente/kirby-admin-bar) 2.3.0 are installed through Composer.
Sign in at `/panel`, then open the website in the same browser.
Admin Bar appears above the site navigation and links to the current page's editor.
Its account dropdown provides the Panel menu on smaller screens.
Loop appears at the bottom: choose **Comment**, click a page element, and submit feedback.
Saved feedback remains available after reloading and in the Panel's **Обратная связь** menu.

Both tools require a signed-in Kirby user.
Guest feedback is disabled.
The tools are omitted from command-line rendering, the static generator endpoint, and Kirby's `_preview` view.
Page caching remains disabled so personalized controls and CSRF tokens cannot enter shared cached HTML.

Loop stores feedback in `site/logs/loop/comments.sqlite` by default.
Keep this directory writable, private, persistent across deployments, and included in backups; do not treat it as disposable log output.
The existing `/site/logs/*` ignore rule excludes it from Git.
Both `ext-sqlite3` and `ext-pdo_sqlite` are required by Composer.

Two site-owned snippet overrides address upstream rendering errors without changing installed package files.
`site/snippets/loop/app.php` scopes Loop's translation helper to each render so rendering multiple pages in one PHP process does not redeclare a function.
`site/snippets/panel-icon.php` resolves Kirby icon aliases without Admin Bar's undefined `icon()` helper.
Review these overrides against upstream snippets when upgrading either plugin.
`assets/css/editor-tools.css` separates the toolbars from navigation, account controls, and the cookie notice.

Run normal checks with `composer test` and `composer check-platform-reqs`.
Browser verification uses an isolated content copy, account, sessions, media directory, and feedback database:

```sh
node tests/editor-tools.mjs
```

Set `STUDIO_PLAYWRIGHT_MODULE` and `STUDIO_CHROMIUM` if Playwright or Chromium use custom installation paths.
Set `STUDIO_EDITOR_TOOLS_SCREENSHOTS` to retain screenshots from the 1440, 768, and 390 pixel checks.
The browser test verifies guest exclusion, the current-page edit link, feedback creation and persistence, the Panel feedback view, and toolbar geometry.
