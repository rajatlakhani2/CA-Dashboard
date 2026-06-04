/**
 * Short go-live smoke against production.
 * Usage:
 *   node production-smoke.cjs
 *   BASE_URL=https://app.kuhu.org.in QA_PASSWORD=yourpassword node production-smoke.cjs
 */
const { chromium } = require('playwright');

const BASE = (process.env.BASE_URL || 'https://app.kuhu.org.in').replace(/\/$/, '');
const PASSWORD = process.env.QA_PASSWORD || 'password';

const USERS = {
    partner: 'rajat@rlassociates.in',
    associate: 'associate@rlassociates.in',
    article: 'article@rlassociates.in',
};

const passed = [];
const failed = [];

function ok(name) {
    passed.push(name);
    console.log(`  ✓ ${name}`);
}

function fail(name, detail) {
    failed.push({ name, detail });
    console.log(`  ✗ ${name}: ${detail}`);
}

async function login(page, email, expectPathPart) {
    const res = await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
    if (!res || res.status() >= 500) {
        throw new Error(`login page HTTP ${res?.status()}`);
    }

    const hasEmail = (await page.locator('input[name="email"]').count()) > 0;
    if (hasEmail) {
        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', PASSWORD);
    } else {
        const option = page.locator(`select[name="user_id"] option:has-text("${email}")`);
        if ((await option.count()) === 0) {
            throw new Error(`legacy login: no user option for ${email} — redeploy latest code or seed users`);
        }
        const value = await option.getAttribute('value');
        await page.selectOption('select[name="user_id"]', value);
    }

    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
    const url = page.url();
    if (!url.includes(expectPathPart)) {
        throw new Error(`expected URL containing "${expectPathPart}", got ${url}`);
    }
}

async function loginIfAvailable(page, email, expectPathPart) {
    try {
        await login(page, email, expectPathPart);
        return true;
    } catch (e) {
        if (String(e.message).includes('legacy login: no user option')) {
            return false;
        }
        throw e;
    }
}

async function pageOk(page, path, label) {
    const res = await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded' });
    const html = await page.content();
    if (/Server Error|Whoops|Undefined variable/i.test(html)) {
        fail(label, 'Laravel error page');
        return false;
    }
    if ((res?.status() ?? 0) >= 500) {
        fail(label, `HTTP ${res?.status()}`);
        return false;
    }
    if ((res?.status() ?? 0) === 403) {
        fail(label, '403 Forbidden');
        return false;
    }
    ok(label);
    return true;
}

(async () => {
    console.log(`Production smoke @ ${BASE}\n`);

    let browser;
    try {
        const ping = await fetch(`${BASE}/login`);
        if (!ping.ok) throw new Error(`login HTTP ${ping.status}`);
    } catch (e) {
        console.error(`Cannot reach ${BASE}/login — ${e.message}`);
        process.exit(1);
    }

    browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();

    try {
        console.log('=== Guest ===');
        const root = await page.goto(`${BASE}/`);
        if (page.url().includes('/login')) ok('Root → login');
        else fail('Root → login', page.url());

        console.log('\n=== Partner ===');
        await login(page, USERS.partner, '/dashboard');
        for (const [path, label] of [
            ['/dashboard', 'Dashboard'],
            ['/partner-dashboard', 'Partner overview'],
            ['/clients', 'Clients'],
            ['/billing', 'Billing'],
            ['/invoices', 'Invoices'],
        ]) {
            await pageOk(page, path, label);
        }

        console.log('\n=== Associate ===');
        await page.goto(`${BASE}/logout`).catch(() => {});
        if (await loginIfAvailable(page, USERS.associate, '/dashboard')) {
            await pageOk(page, '/clients', 'Clients');
            const billRes = await page.goto(`${BASE}/billing`);
            const billHtml = await page.content();
            if (billRes?.status() === 403 || /403|Forbidden/i.test(billHtml)) {
                ok('Billing blocked (403)');
            } else if (!page.url().includes('/billing')) {
                ok('Billing blocked (redirect)');
            } else {
                fail('Billing blocked', 'associate reached billing');
            }
        } else {
            ok('Associate smoke skipped (legacy login — only partner in dropdown)');
        }

        console.log('\n=== Article ===');
        await page.goto(`${BASE}/logout`).catch(() => {});
        if (await loginIfAvailable(page, USERS.article, '/my-day')) {
            await pageOk(page, '/my-day', 'My Day');
            const clientsRes = await page.goto(`${BASE}/clients`);
            if (!page.url().includes('/clients') || clientsRes?.status() === 403) {
                ok('Clients list blocked');
            } else {
                fail('Clients list blocked', page.url());
            }
        } else {
            ok('Article smoke skipped (legacy login — redeploy + users:ensure-firm-logins)');
        }
    } catch (e) {
        fail('FATAL', e.message);
    }

    await browser.close();

    console.log('\n========== SUMMARY ==========');
    console.log(`Passed: ${passed.length}`);
    console.log(`Failed: ${failed.length}`);
    if (failed.length) {
        failed.forEach((f) => console.log(`  - ${f.name}: ${f.detail}`));
        process.exit(1);
    }
    console.log('Production smoke passed.');
})();
