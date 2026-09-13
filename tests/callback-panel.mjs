import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawn, execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const { chromium } = await import(process.env.STUDIO_PLAYWRIGHT_MODULE || 'playwright');
const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const temporary = fs.mkdtempSync(path.join(fs.realpathSync(os.tmpdir()), 'studio-callback-panel-'));
const port = Number(process.env.STUDIO_CALLBACK_TEST_PORT || 8019);
const env = { ...process.env, STUDIO_CALLBACK_TEST_ROOT: temporary, STUDIO_CALLBACK_TEST_PORT: String(port) };
const origin = `http://127.0.0.1:${port}`;
const output = process.env.STUDIO_CALLBACK_SCREENSHOTS || temporary;
let server;
let browser;
let page;
let serverOutput = '';
const errors = [];
const browserRequest = (target, url, options = {}) => target.evaluate(async ({ url, options }) => {
    const response = await fetch(url, options);
    return { status: response.status, body: await response.text(), headers: Object.fromEntries(response.headers) };
}, { url, options });
try {
    execFileSync('php', ['tests/callback-panel-router.php'], { cwd: root, env });
    server = spawn('php', ['-S', `127.0.0.1:${port}`, 'tests/callback-panel-router.php'], { cwd: root, env });
    server.stderr.on('data', (data) => { serverOutput += data.toString(); });
    for (let attempt = 0; attempt < 50; attempt++) {
        if (server.exitCode !== null) throw new Error(serverOutput);
        try { await fetch(`${origin}/panel/login`); break; } catch {}
        await new Promise((resolve) => setTimeout(resolve, 100));
    }
    browser = await chromium.launch({ headless: true, ...(process.env.STUDIO_CHROMIUM ? { executablePath: process.env.STUDIO_CHROMIUM } : {}) });
    page = await browser.newPage({ viewport: { width: 1440, height: 1050 } });
    page.on('pageerror', (error) => errors.push(error.message));
    const login = async (target, role) => {
        await target.goto(`${origin}/panel/login`);
        await target.locator('input[type=email]').fill(`${role}@example.test`);
        await target.locator('input[type=password]').fill('local-callback-fixture-password');
        await target.locator('button[type=submit]').click();
        await target.waitForURL((url) => !url.pathname.includes('login')).catch(async (error) => {
            console.error(role, await target.locator('body').innerText());
            throw error;
        });
    };
    await login(page, 'admin');
    await page.getByRole('link', { name: 'Заявки', exact: true }).click();
    await page.getByText('В этом разделе заявок нет.').waitFor();

    const visitor = await browser.newPage();
    await visitor.goto(`${origin}/contacts`);
    const form = visitor.locator('#callback-form');
    await form.locator('[name=name]').fill('Клиент <img src=x onerror=alert(1)>');
    await form.locator('[name=telephone]').fill('+7 999 123-45-67');
    await form.locator('[name=email]').fill('client@example.test');
    await form.locator('[name=consent]').check();
    assert(await form.locator('[type=submit]').isEnabled(), 'Simulated production form is enabled');
    const submitted = visitor.waitForResponse((response) => response.url().endsWith('/callback'));
    await form.locator('[type=submit]').click();
    assert.equal((await submitted).status(), 200);
    await form.getByText('Заявка отправлена.', { exact: false }).waitFor();
    await page.getByRole('button', { name: 'Обновить', exact: true }).click();
    await page.getByRole('link', { name: 'Клиент <img src=x onerror=alert(1)>', exact: true }).click();
    await page.waitForURL(/\/panel\/studio-callback\/[a-f0-9]{32}$/);
    const detailUrl = page.url();
    const privateResponse = await browserRequest(page, detailUrl + '?_json=1');
    assert.equal(privateResponse.status, 200);
    assert.match(privateResponse.headers['cache-control'], /no-store/);
    const field = (name) => page.locator(`.k-field-name-${name}`).locator('input, textarea').first();
    assert.equal(await field('telephone').inputValue(), '+7 999 123-45-67');
    assert.equal(await field('email_status').inputValue(), 'Отправлено');
    assert.equal(await page.locator('img[src=x]').count(), 0, 'User text never becomes HTML');
    await page.getByRole('button', { name: 'Статус и заметка', exact: true }).click();
    const dialog = page.getByRole('dialog');
    await dialog.locator('select').selectOption('progress');
    await dialog.locator('textarea').fill('Позвонить завтра после 15:00.');
    await dialog.getByRole('button', { name: 'Сохранить', exact: true }).click();
    await dialog.waitFor({ state: 'hidden' });
    await page.waitForFunction(() => document.querySelector('.k-field-name-status input')?.value === 'В работе');
    assert.equal(await field('status').inputValue(), 'В работе');
    await page.reload();
    assert.equal(await field('notes').inputValue(), 'Позвонить завтра после 15:00.');
    console.log('Real form -> private inbox -> native status/notes dialog -> persistence passed.');

    fs.mkdirSync(output, { recursive: true });
    await page.screenshot({ path: path.join(output, 'callback-detail-desktop.png'), fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Mobile detail must not overflow');
    await page.screenshot({ path: path.join(output, 'callback-detail-mobile.png'), fullPage: true });
    await page.getByRole('link', { name: 'К заявкам', exact: true }).click();
    await page.getByRole('link', { name: 'В работе · 1', exact: true }).click();
    await page.getByRole('link', { name: 'Клиент <img src=x onerror=alert(1)>', exact: true }).waitFor();
    assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Mobile filters must not overflow');
    await page.screenshot({ path: path.join(output, 'callback-inbox-mobile.png'), fullPage: true });
    await page.setViewportSize({ width: 1440, height: 1050 });
    await page.screenshot({ path: path.join(output, 'callback-inbox-desktop.png'), fullPage: true });

    const manager = await browser.newPage();
    await login(manager, 'callback-manager');
    const managerDetail = await manager.goto(detailUrl);
    assert.equal(managerDetail.status(), 200, await manager.locator('body').innerText());
    await manager.getByText('Данные заявки', { exact: true }).waitFor().catch(async (error) => {
        console.error('Manager detail', manager.url(), await manager.locator('body').innerText());
        throw error;
    });
    assert.equal(await manager.locator('.k-field-name-telephone input').inputValue(), '+7 999 123-45-67');
    const editor = await browser.newPage();
    await login(editor, 'callback-editor');
    assert.equal(await editor.getByRole('link', { name: 'Заявки', exact: true }).count(), 0);
    for (const target of [editor, visitor]) {
        const response = await browserRequest(target, detailUrl + '?_json=1');
        assert(!response.body.includes('123-45-67'), 'Unauthorized direct detail response must contain no personal data');
        const csrf = target === editor ? await editor.evaluate(() => window.panel.system.csrf) : 'invalid';
        const id = new URL(detailUrl).pathname.split('/').pop();
        for (const suffix of ['/update', '/delete/2']) {
            const url = `${origin}/panel/dialogs/studio-callback/${id}${suffix}`;
            const loaded = await browserRequest(target, url, { headers: { 'X-Fiber': 'true' } });
            assert(!loaded.body.includes('123-45-67'), 'Unauthorized dialogs do not expose personal data');
            const denied = await browserRequest(target, url, { method: 'POST', headers: { 'X-Fiber': 'true', 'X-CSRF': csrf, 'Content-Type': 'application/json' }, body: JSON.stringify({ status: 'spam', notes: 'Forbidden', revision: 2 }) });
            if (target === editor) {
                assert.equal(denied.status, 403, 'Direct mutation requires callback permission: ' + denied.body);
            } else {
                // Kirby does not register custom area routes before login.
                assert.equal(JSON.parse(denied.body).code, 404, 'Anonymous mutation route does not exist');
                assert(denied.status >= 400, 'Anonymous mutation fails');
            }
        }
    }
    const csrfDenied = await browserRequest(page, `${origin}/panel/dialogs/studio-callback/${new URL(detailUrl).pathname.split('/').pop()}/update`, { method: 'POST', headers: { 'X-Fiber': 'true', 'Content-Type': 'application/json' }, body: JSON.stringify({ status: 'spam', notes: 'Missing CSRF', revision: 2 }) });
    assert.equal(csrfDenied.status, 403, 'Authorized browser without CSRF cannot mutate');
    await manager.reload();
    assert.equal(await manager.locator('.k-field-name-notes textarea').inputValue(), 'Позвонить завтра после 15:00.');
    console.log('Manager allowed; ordinary editor and anonymous visitors denied. Mobile layouts passed.');

    fs.writeFileSync(path.join(temporary, 'fail-email'), '1');
    await visitor.goto(`${origin}/contacts`);
    await form.locator('[name=name]').fill('Сохранено без SMTP');
    await form.locator('[name=telephone]').fill('+7 999 765-43-21');
    await form.locator('[name=consent]').check();
    await form.locator('[type=submit]').click();
    await form.getByText('Заявка отправлена.', { exact: false }).waitFor();
    await page.goto(`${origin}/panel/studio-callback`);
    await page.getByRole('link', { name: 'Сохранено без SMTP', exact: true }).click();
    assert.match(await field('email_status').inputValue(), /Не отправлено/);
    await page.getByRole('button', { name: 'Удалить', exact: true }).click();
    await page.getByRole('dialog').getByRole('button', { name: 'Удалить', exact: true }).click();
    await page.waitForURL('**/panel/studio-callback');
    await page.getByText('В этом разделе заявок нет.').waitFor();
    console.log('SMTP failure keeps accepted request; confirmed deletion removes it.');
    fs.writeFileSync(path.join(temporary, 'fail-storage'), '1');
    await visitor.goto(`${origin}/contacts`);
    await form.locator('[name=name]').fill('Не сохранять');
    await form.locator('[name=telephone]').fill('+7 999 765-43-21');
    await form.locator('[name=consent]').check();
    const storageFailure = visitor.waitForResponse((response) => response.url().endsWith('/callback'));
    await form.locator('[type=submit]').click();
    const failed = await storageFailure;
    assert.equal(failed.status(), 503);
    assert(!(await failed.text()).includes(temporary), 'Storage failure does not expose private path');
    fs.unlinkSync(path.join(temporary, 'fail-storage'));
    await page.reload();
    await page.getByText('В этом разделе заявок нет.').waitFor();
    console.log('Storage failure fails closed with no false success or exposed path.');
    assert.deepEqual(errors, [], 'No browser runtime errors');
} catch (error) {
    if (page) {
        await page.screenshot({ path: path.join(os.tmpdir(), 'studio-callback-failure.png'), fullPage: true }).catch(() => {});
        console.error(await page.locator('body').innerText().catch(() => ''));
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
