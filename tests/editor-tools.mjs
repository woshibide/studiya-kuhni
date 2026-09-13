import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawn, execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const { chromium } = await import(process.env.STUDIO_PLAYWRIGHT_MODULE || 'playwright');
const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const temporary = fs.mkdtempSync(path.join(fs.realpathSync(os.tmpdir()), 'studio-editor-tools-'));
const env = { ...process.env, STUDIO_EDITOR_TOOLS_TEST_ROOT: temporary };
const port = Number(process.env.STUDIO_EDITOR_TOOLS_TEST_PORT || 8023);
const origin = `http://127.0.0.1:${port}`;
let server;
let browser;
let editorPage;
let serverOutput = '';
const errors = [];
try {
    execFileSync('php', ['tests/editor-tools-router.php'], { cwd: root, env });
    server = spawn('php', ['-S', `127.0.0.1:${port}`, 'tests/editor-tools-router.php'], { cwd: root, env });
    server.stderr.on('data', (data) => { serverOutput += data.toString(); });
    for (let attempt = 0; attempt < 50; attempt++) {
        if (server.exitCode !== null) throw new Error(serverOutput);
        try { await fetch(`${origin}/panel/login`); break; } catch {}
        await new Promise((resolve) => setTimeout(resolve, 100));
    }
    browser = await chromium.launch({ headless: true, ...(process.env.STUDIO_CHROMIUM ? { executablePath: process.env.STUDIO_CHROMIUM } : {}) });
    const page = await browser.newPage({ viewport: { width: 1440, height: 1100 } });
    editorPage = page;
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => { if (message.type() === 'error') errors.push(message.text()); });
    await page.goto(`${origin}/panel/login`);
    await page.locator('input[type=email]').fill('tools@example.test');
    await page.locator('input[type=password]').fill('local-editor-tools-password');
    await page.locator('button[type=submit]').click();
    await page.waitForURL('**/panel/site');
    const guest = await browser.newPage();
    await guest.goto(origin + '/', { waitUntil: 'domcontentloaded' });
    assert.equal(await guest.locator('.admin-bar, kirby-loop').count(), 0);
    assert.equal((await guest.request.get(origin + '/loop/comments/home')).status(), 403);
    await guest.close();

    await page.goto(origin + '/', { waitUntil: 'domcontentloaded' });
    const loop = page.locator('kirby-loop');
    await loop.getByRole('button', { name: 'Comment', exact: true }).waitFor();
    assert.equal(await page.locator('.admin-bar__link--highlight').getAttribute('href'), origin + '/panel/pages/home');
    const note = 'Please review this heading.';
    await loop.getByRole('button', { name: 'Comment', exact: true }).click();
    await page.locator('h1').first().click();
    await loop.locator('dialog[open] textarea').fill(note);
    const saved = page.waitForResponse((response) => response.url().endsWith('/loop/comment/new') && response.request().method() === 'POST');
    await loop.locator('dialog[open]').getByRole('button', { name: 'Submit', exact: true }).click();
    assert.equal((await (await saved).json()).status, 'ok');
    await page.reload({ waitUntil: 'domcontentloaded' });
    await loop.getByRole('button', { name: '1 unresolved comments' }).click();
    await loop.getByText(note, { exact: true }).waitFor();
    await page.keyboard.press('Escape');
    const output = process.env.STUDIO_EDITOR_TOOLS_SCREENSHOTS || temporary;
    fs.mkdirSync(output, { recursive: true });
    for (const width of [1440, 768, 390]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.evaluate(() => window.scrollTo(0, 0));
        const bar = await page.locator('.admin-bar').boundingBox();
        const navigation = await page.locator('[data-site-nav]').boundingBox();
        assert(navigation.y >= bar.y + bar.height, 'Navigation must clear the Admin Bar');
        const feedbackToggle = await loop.getByRole('button', { name: 'Open comments', exact: true }).boundingBox();
        assert(feedbackToggle.y >= navigation.y + navigation.height, `Feedback toggle must clear navigation and account controls: ${JSON.stringify({width, navigation, feedbackToggle})}`);
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'No horizontal overflow');
        const cookie = await page.locator('#cookie-banner').boundingBox();
        assert(cookie.x >= 0 && cookie.x + cookie.width <= width + 1, 'Cookie notice stays inside viewport');
        await page.screenshot({ path: path.join(output, `editor-tools-${width}.png`) });
    }
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.goto(origin + '/panel/loop');
    await page.getByText(note, { exact: true }).waitFor();
    assert(await page.getByRole('link', { name: 'Обратная связь', exact: true }).count() > 0);
    assert(fs.existsSync(path.join(temporary, 'logs/loop/comments.sqlite')));
    assert.deepEqual(errors, [], 'No browser runtime errors');
    console.log('Editor tools: guest access, authenticated editing, feedback persistence, Panel integration and three viewport sizes passed.');
} catch (error) {
    if (editorPage) {
        await editorPage.screenshot({ path: path.join(os.tmpdir(), 'studio-editor-tools-failure.png'), fullPage: true }).catch(() => {});
        console.error(await editorPage.locator('body').innerText().catch(() => ''));
        console.error(errors);
    }
    throw error;
} finally {
    await browser?.close();
    if (server && server.exitCode === null) {
        server.kill();
        await new Promise((resolve) => server.once('exit', resolve));
    }
    fs.rmSync(temporary, { recursive: true, force: true });
}
