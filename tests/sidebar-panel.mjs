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
    const menuLabels = (target) => target.locator('.k-panel-menu a').allTextContents();
    const assertMenu = async (target, expected) => {
        await target.locator('.k-panel-menu a').first().waitFor({ timeout: 10000 });
        const labels = (await menuLabels(target)).map((label) => label.trim());
        assert.deepEqual(labels.filter((label) => !['Ваш аккаунт', 'Выйти', 'Купить лицензию'].includes(label)), expected);
    };
    fs.mkdirSync(output, { recursive: true });
    await login(page, 'admin');
    await assertMenu(page, ['Заявки', 'Страницы', 'Помощь', 'Пользователи', 'Система']);
    await page.goto(`${origin}/panel/pages/contacts`);
    assert.equal(await page.locator('.k-panel-menu a[aria-current]').innerText(), 'Страницы');
    await page.locator('.k-field-name-messengers tbody tr').first().locator('button').last().click();
    await page.getByRole('button', { name: 'Изменить', exact: true }).waitFor();
    assert.equal(await page.getByRole('button', { name: 'Настроить', exact: true }).count(), 0);
    await page.keyboard.press('Escape');
    await page.screenshot({ path: path.join(output, 'sidebar-admin-desktop.png'), fullPage: true });

    const manager = await browser.newPage({ viewport: { width: 1440, height: 1050 } });
    manager.on('pageerror', (error) => errors.push(error.message));
    page = manager;
    await login(manager, 'manager');
    assert(new URL(manager.url()).pathname === '/panel/studio-callback', 'Manager starts in requests');
    await assertMenu(manager, ['Заявки', 'Страницы', 'Помощь']);
    await manager.getByText('В этом разделе заявок нет.').waitFor();
    await manager.getByRole('link', { name: 'Помощь', exact: true }).click();
    await manager.getByRole('heading', { name: 'Как редактировать сайт', exact: true }).waitFor();
    await manager.getByRole('link', { name: 'Страницы', exact: true }).click();
    await manager.goto(`${origin}/panel/pages/contacts`);
    const phone = manager.locator('.k-field-name-phone input');
    await phone.fill('+7 999 000-00-00');
    await manager.getByRole('button', { name: 'Сохранить', exact: true }).click();
    await manager.getByRole('button', { name: 'Сохранить', exact: true }).waitFor({ state: 'hidden' });
    await manager.reload();
    assert.equal(await phone.inputValue(), '+7 999 000-00-00', 'Manager page edits persist');
    await manager.screenshot({ path: path.join(output, 'sidebar-manager-desktop.png'), fullPage: true });
    for (const route of ['users', 'system']) {
        const response = await browserRequest(manager, `${origin}/panel/${route}?_json=1`);
        const data = JSON.parse(response.body);
        assert.equal(data.$view?.code ?? data.code, 403, `Manager cannot access ${route}: ${response.body}`);
    }
    const csrf = await manager.evaluate(() => window.panel.system.csrf);
    const denied = await browserRequest(manager, `${origin}/api/account/role`, {
        method: 'PATCH', headers: { 'X-CSRF': csrf, 'Content-Type': 'application/json' }, body: JSON.stringify({ role: 'admin' }),
    });
    assert.equal(denied.status, 403, 'Manager cannot promote their own account');
    await manager.setViewportSize({ width: 390, height: 844 });
    assert(await manager.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Mobile page does not overflow');
    await manager.screenshot({ path: path.join(output, 'sidebar-manager-mobile.png'), fullPage: true });
    assert.deepEqual(errors, [], 'No browser runtime errors');
    console.log('Admin and manager menus, page editing, help, edit label, access restrictions and mobile layout passed.');
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
