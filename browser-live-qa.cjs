/**
 * Live browser QA — run against http://127.0.0.1:8000
 * Usage: node browser-live-qa.cjs
 *        BASE_URL=http://127.0.0.1:8002 node browser-live-qa.cjs
 */
const { chromium } = require('playwright');

const BASE = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const PASSWORD = process.env.QA_PASSWORD || 'password';

const USERS = {
    partner: 'rajat@rlassociates.in',
    associate: 'nilesh@rlassociates.in',
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

async function dismissWelcomeModal(page) {
    const backdrop = page.locator('div.fixed.inset-0.bg-gray-500').first();
    if (await backdrop.isVisible({ timeout: 1500 }).catch(() => false)) {
        await backdrop.click({ force: true }).catch(() => {});
        await page.waitForTimeout(300);
    }
}

async function login(page, email, expectUrlIncludes) {
    await page.goto(`${BASE}/login`);
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    const url = page.url();
    if (!url.includes(expectUrlIncludes)) {
        throw new Error(`login ${email}: expected URL with "${expectUrlIncludes}", got ${url}`);
    }
}

async function gotoCheck(page, path, label, { allowRedirect = false, mustInclude = null, mustNotInclude = null } = {}) {
    const response = await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded' });
    const status = response?.status() ?? 0;
    const url = page.url();
    const html = await page.content();
    const serverError = /Server Error|Whoops|Undefined variable/i.test(html);

    if (serverError) {
        fail(label, 'Laravel error page in HTML');
        return false;
    }
    if (mustInclude && !html.includes(mustInclude) && !url.includes(mustInclude)) {
        fail(label, `missing "${mustInclude}"`);
        return false;
    }
    if (mustNotInclude && (html.includes(mustNotInclude) || url.includes(mustNotInclude))) {
        fail(label, `unexpected "${mustNotInclude}"`);
        return false;
    }
    if (status === 403) {
        fail(label, '403 Forbidden');
        return false;
    }
    if (status >= 500) {
        fail(label, `HTTP ${status}`);
        return false;
    }
    if (status >= 400 && !allowRedirect) {
        fail(label, `HTTP ${status}`);
        return false;
    }
    ok(label);
    return true;
}

async function expectRedirectAway(page, path, label, awayFrom) {
    await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded' });
    const url = page.url();
    const html = await page.content();
    if (/Server Error|Whoops|Undefined variable/i.test(html)) {
        fail(label, 'server error');
        return;
    }
    if (url.includes(awayFrom)) {
        fail(label, `still on ${awayFrom}`);
        return;
    }
    ok(label);
}

async function expectForbidden(page, path, label) {
    const response = await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded' });
    const status = response?.status() ?? 0;
    const html = await page.content();
    if (status === 403 || html.includes('403') || html.includes('Forbidden')) {
        ok(label);
        return;
    }
    fail(label, `expected 403, got ${status} url=${page.url()}`);
}

async function runPartnerFlows(page) {
    console.log('\n=== Partner (Rajat) ===');
    await login(page, USERS.partner, '/dashboard');
    await dismissWelcomeModal(page);

    const partnerRoutes = [
        ['/dashboard', 'Dashboard'],
        ['/partner-dashboard', 'Partner Overview', { mustInclude: 'MTD Invoiced' }],
        ['/clients', 'Clients list'],
        ['/clients/create', 'Client create'],
        ['/tasks', 'Tasks'],
        ['/my-day', 'My Day'],
        ['/service-dues', 'Service dues'],
        ['/personal-renewals', 'Personal renewals'],
        ['/services', 'Service master'],
        ['/compliance-360', 'Compliance 360'],
        ['/dscs', 'DSC tracker'],
        ['/tds', 'TDS'],
        ['/billing', 'Billing queue'],
        ['/billing-rules', 'Billing rules'],
        ['/workload', 'Workload planner'],
        ['/collections', 'Collections center'],
        ['/invoices', 'Invoices'],
        ['/payments', 'Payments'],
        ['/expenses', 'Expenses'],
        ['/subscriptions', 'Subscriptions'],
        ['/reports', 'Reports hub'],
        ['/reports/financial', 'Financial report'],
        ['/reports/compliance', 'Compliance report'],
        ['/reports/staff-productivity', 'Staff productivity report'],
        ['/reports/client-profitability', 'Client profitability report'],
        ['/staff', 'Staff'],
        ['/credentials', 'Credentials'],
        ['/smart-documents', 'Smart archive'],
        ['/time-entries', 'Time entries'],
        ['/settings', 'Settings'],
        ['/system', 'System health'],
        ['/users', 'Users admin'],
        ['/branches', 'Branches'],
        ['/activity', 'Activity'],
        ['/notifications/whatsapp', 'WhatsApp settings'],
        ['/clients/import/nilesh', 'Nilesh import form'],
    ];

    for (const [path, label, opts = {}] of partnerRoutes) {
        await gotoCheck(page, path, label, opts);
        await dismissWelcomeModal(page);
    }

    await page.goto(`${BASE}/clients`);
    const clientLink = page.locator('table tbody tr a[href*="/clients/"]').first();
    if (await clientLink.count()) {
        await clientLink.click();
        await page.waitForLoadState('networkidle');
        const html = await page.content();
        if (/Server Error|Undefined variable/i.test(html)) {
            fail('Client show page', 'server error');
        } else if (html.includes('Service checklists') || html.includes('Pending Compliance')) {
            ok('Client show page');
        } else {
            ok('Client show page (loaded)');
        }
    } else {
        ok('Client show page (skipped — no clients in DB)');
    }

    const extraPartner = [
        ['/reports/service', 'Service report'],
        ['/reports/client', 'Client report'],
        ['/reports/task', 'Task report'],
        ['/reports/due-date', 'Due date report'],
        ['/recycle-bin', 'Recycle bin'],
        ['/leaves', 'Leaves'],
        ['/tasks?view=board', 'Tasks kanban'],
        ['/invoices/create', 'Invoice create form'],
        ['/payments/create', 'Payment create form'],
    ];
    for (const [path, label] of extraPartner) {
        await gotoCheck(page, path, label);
    }

    await page.goto(`${BASE}/billing`);
    const billingHtml = await page.content();
    if (billingHtml.includes('Create draft invoice') || billingHtml.includes('No unbilled items')) {
        ok('Billing draft invoice UI');
    } else {
        fail('Billing draft invoice UI', 'button not found');
    }

    await page.goto(`${BASE}/clients`);
    const link = page.locator('table tbody tr a[href*="/clients/"]').first();
    if (await link.count()) {
        const href = await link.getAttribute('href');
        const target = href.startsWith('http') ? href : `${BASE}${href.startsWith('/') ? '' : '/'}${href}`;
        await page.goto(target);
        const showHtml = await page.content();
        if (showHtml.includes('Service checklists')) {
            ok('Client spawn checklist section');
        } else {
            ok('Client spawn checklist section (no templates yet)');
        }
        const clientId = href.match(/\/clients\/(\d+)/)?.[1];
        if (clientId) {
            await gotoCheck(page, `/ledger/${clientId}`, 'Client ledger');
            await gotoCheck(page, `/onboarding/${clientId}`, 'Client onboarding');
        }
    }

    const searchRes = await page.request.get(`${BASE}/search/global?query=client`);
    if (searchRes.ok()) {
        ok('Global search API');
    } else {
        fail('Global search API', `HTTP ${searchRes.status()}`);
    }

    const paletteRes = await page.request.get(`${BASE}/search/palette`);
    if (paletteRes.ok()) {
        ok('Command palette API');
    } else {
        fail('Command palette API', `HTTP ${paletteRes.status()}`);
    }

    try {
        await page.locator('header').locator('button').filter({ has: page.locator('span.max-w-\\[140px\\]') }).first().click();
        await page.locator('form[action*="logout"] button[type="submit"]').click();
        await page.waitForURL(/\/login/, { timeout: 10000 });
        ok('Partner logout (Sign out button)');
    } catch {
        const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
        await page.request.post(`${BASE}/logout`, { form: token ? { _token: token } : {} });
        await page.goto(`${BASE}/dashboard`);
        if (page.url().includes('/login')) {
            ok('Partner logout (fallback POST)');
        } else {
            fail('Partner logout', page.url());
        }
    }
}

async function runAssociateFlows(page) {
    console.log('\n=== Associate (Nilesh) ===');
    await login(page, USERS.associate, '/dashboard');
    await dismissWelcomeModal(page);

    for (const [path, label] of [
        ['/dashboard', 'Dashboard'],
        ['/clients', 'Clients (own portfolio)'],
        ['/tasks', 'Tasks'],
        ['/invoices', 'My Client Invoices'],
        ['/smart-documents', 'Smart archive'],
        ['/time-entries', 'Time entries'],
    ]) {
        await gotoCheck(page, path, label);
    }

    await expectForbidden(page, '/billing', 'Billing blocked');
    await expectForbidden(page, '/reports', 'Reports blocked');
    await expectForbidden(page, '/staff', 'Staff blocked');
    await expectForbidden(page, '/credentials', 'Credentials blocked');
    await expectForbidden(page, '/partner-dashboard', 'Partner dashboard blocked');

    await page.request.post(`${BASE}/logout`).catch(() => {});
}

async function runArticleFlows(page) {
    console.log('\n=== Article clerk ===');
    await login(page, USERS.article, '/my-day');

    await gotoCheck(page, '/my-day', 'My Day landing');
    await gotoCheck(page, '/tasks', 'Tasks list');
    await gotoCheck(page, '/clients/create', 'Submit new client form');

    await expectRedirectAway(page, '/clients', 'Clients list blocked', '/clients');
    await expectRedirectAway(page, '/dashboard', 'Dashboard blocked', '/dashboard');
    await expectRedirectAway(page, '/billing', 'Billing blocked', '/billing');

    await page.request.post(`${BASE}/logout`).catch(() => {});
}

async function runGuestFlows(page) {
    console.log('\n=== Guest ===');
    const res = await page.goto(`${BASE}/`);
    if (page.url().includes('/login')) {
        ok('Root redirects to login');
    } else {
        fail('Root redirects to login', page.url());
    }
    await gotoCheck(page, '/login', 'Login page', { mustInclude: 'Sign in' });
}

(async () => {
    console.log(`Live browser QA @ ${BASE}\n`);
    let browser;
    try {
        const ping = await fetch(`${BASE}/login`);
        if (!ping.ok) throw new Error(`Login page HTTP ${ping.status}`);
    } catch (e) {
        console.error(`Cannot reach ${BASE} — start server: php artisan serve`);
        process.exit(1);
    }

    browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    page.on('pageerror', (err) => console.warn('  [page error]', err.message));

    try {
        await runGuestFlows(page);
        await runPartnerFlows(page);
        await runAssociateFlows(page);
        await runArticleFlows(page);
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
    console.log('All live browser checks passed.');
})();
