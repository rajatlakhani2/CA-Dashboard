/**
 * Shared helpers for Executive Summary Playwright QA scripts.
 */
const { chromium } = require('playwright');
const { execSync } = require('child_process');

const BASE = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const WORKSPACE = process.env.QA_WORKSPACE || 'demodashboard';
const EMAIL = process.env.QA_EMAIL || 'demo@vouchex.in';
const PASSWORD = process.env.QA_PASSWORD || 'demo@1234';
const HEADLESS = process.env.HEADLESS === '1' || process.env.HEADLESS === 'true';
const SKIP_SEED = process.env.SKIP_SEED === '1';
const SLOW_MO = parseInt(process.env.SLOW_MO || '0', 10) || 0;
const SECTION_PAUSE = parseInt(process.env.SECTION_PAUSE || '0', 10) || 0;

const passed = [];
const failed = [];
const skipped = [];
const pageErrors = [];

function ok(name) {
    passed.push(name);
    console.log(`  ✓ ${name}`);
}

function fail(name, detail) {
    failed.push({ name, detail });
    console.log(`  ✗ ${name}: ${detail}`);
}

function skip(name, reason) {
    skipped.push({ name, reason });
    console.log(`  ○ ${name} (skipped: ${reason})`);
}

async function pause(page, ms) {
    const wait = ms || SECTION_PAUSE;
    if (wait > 0 && page) {
        await page.waitForTimeout(wait);
    }
}

async function highlightSection(page, label) {
    if (!page || HEADLESS) return;
    console.log(`\n  ▶ ${label}`);
    await page.evaluate((text) => {
        let el = document.getElementById('vouchex-qa-banner');
        if (!el) {
            el = document.createElement('div');
            el.id = 'vouchex-qa-banner';
            el.style.cssText =
                'position:fixed;top:12px;left:50%;transform:translateX(-50%);z-index:99999;' +
                'background:#1e293b;color:#f8fafc;padding:10px 20px;border-radius:12px;' +
                'font:bold 14px system-ui,sans-serif;box-shadow:0 8px 32px rgba(0,0,0,.35);pointer-events:none;';
            document.body.appendChild(el);
        }
        el.textContent = text;
    }, label);
    await pause(page, Math.max(SECTION_PAUSE, 800));
}

async function stopDemoTour(page) {
    await page.evaluate(() => {
        sessionStorage.removeItem('vouchex_demo_tour_active');
        sessionStorage.removeItem('vouchex_demo_tour_step');
        document.body.classList.remove('demo-tour-autoplay');
        if (window.driver && typeof window.driver.destroy === 'function') {
            try {
                window.driver.destroy();
            } catch {
                /* ignore */
            }
        }
        document.querySelectorAll('.driver-popover, #driver-popover-content, .driver-overlay').forEach((el) => el.remove());
        const badge = document.getElementById('demo-tour-live-badge');
        if (badge) badge.classList.add('hidden');
        const root = document.getElementById('demo-tour-root');
        if (root && root._x_dataStack && root._x_dataStack[0]) {
            const state = root._x_dataStack[0];
            state.welcomeOpen = false;
            state.modalOpen = false;
            state.loadingOpen = false;
            state.cinemaActive = false;
            if (typeof state.destroyDriver === 'function') state.destroyDriver();
            if (typeof state.clearAutoAdvance === 'function') state.clearAutoAdvance();
        }
    }).catch(() => {});

    for (const label of [/Skip — explore on my own/i, /^Skip tour$/i, /^Skip$/i]) {
        const btn = page.getByRole('button', { name: label }).first();
        if (await btn.isVisible({ timeout: 400 }).catch(() => false)) {
            await btn.click({ force: true }).catch(() => {});
            await page.waitForTimeout(200);
        }
    }

    await page.evaluate(async () => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            await fetch('/demo-tour/dismiss', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
        } catch {
            /* ignore */
        }
    }).catch(() => {});

    await page.waitForTimeout(250);
}

async function dismissOverlays(page) {
    const welcome = page.locator('div.fixed.inset-0.bg-gray-500').first();
    if (await welcome.isVisible({ timeout: 1200 }).catch(() => false)) {
        await welcome.click({ force: true }).catch(() => {});
        await page.waitForTimeout(250);
    }

    await stopDemoTour(page);

    const tourBadge = page.locator('#demo-tour-live-badge:not(.hidden)');
    if (await tourBadge.isVisible({ timeout: 800 }).catch(() => false)) {
        await page.keyboard.press('Escape').catch(() => {});
        await stopDemoTour(page);
    }
}

async function visitRoute(page, route, label) {
    await stopDemoTour(page);
    for (let attempt = 0; attempt < 3; attempt++) {
        try {
            const res = await page.goto(`${BASE}${route}`, { waitUntil: 'load', timeout: 25000 });
            await stopDemoTour(page);
            return res;
        } catch (e) {
            if (attempt === 2) throw e;
            await page.waitForTimeout(600);
            await stopDemoTour(page);
        }
    }
    return null;
}

async function login(page, opts = {}) {
    const workspace = opts.workspace || WORKSPACE;
    const email = opts.email || EMAIL;
    const password = opts.password || PASSWORD;
    const expectUrl = opts.expectUrl || /\/dashboard/;

    await page.goto(`${BASE}/login?workspace=${workspace}`, { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="workspace"]', workspace);
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForURL(expectUrl, { timeout: 25000 });
    await page.waitForLoadState('load');
    if (expectUrl.test('/dashboard')) {
        await page.waitForFunction(() => document.getElementById('executive-summary-sortable') !== null, null, {
            timeout: 15000,
        }).catch(() => {});
    }
    await pause(page);
    await dismissOverlays(page);
    await ensureDashboard(page);
}

async function layoutStorageKey(page) {
    return page.evaluate(() => {
        if (window.VouchexExecLayout && window.VouchexExecLayout.storageKey) {
            return window.VouchexExecLayout.storageKey;
        }
        return Object.keys(localStorage).find((k) => k.startsWith('vouchex_dashboard_layout_')) || null;
    });
}

async function ensureLayoutStorageKey(page) {
    let key = await layoutStorageKey(page);
    if (key) return key;
    await clickWidgetCollapse(page, 'exec-kpis');
    await clickWidgetCollapse(page, 'exec-kpis');
    await page.waitForTimeout(450);
    return layoutStorageKey(page);
}

async function widgetOrder(page) {
    return page.evaluate(() => {
        const root = document.getElementById('executive-summary-sortable');
        if (!root) return [];
        return Array.from(root.querySelectorAll('[data-dashboard-widget]')).map((el) =>
            el.getAttribute('data-dashboard-widget')
        );
    });
}

async function isWidgetCollapsed(page, widgetId) {
    return page.evaluate((id) => {
        const el = document.querySelector(`[data-dashboard-widget="${id}"]`);
        return el ? el.classList.contains('exec-widget--collapsed') : null;
    }, widgetId);
}

async function ensureDashboard(page) {
    if (!page.url().includes('/dashboard')) {
        await page.goto(`${BASE}/dashboard`, { waitUntil: 'domcontentloaded' });
        await page.waitForFunction(() => document.getElementById('executive-summary-sortable') !== null, null, {
            timeout: 15000,
        }).catch(() => {});
    }
    await dismissOverlays(page);
    await page
        .addStyleTag({ content: '.exec-widget__resize-layer { pointer-events: none !important; }' })
        .catch(() => {});
}

async function clickWidgetCollapse(page, widgetId) {
    await ensureDashboard(page);
    await page.evaluate((id) => {
        const widget = document.querySelector(`[data-dashboard-widget="${id}"]`);
        const btn = widget && widget.querySelector('.exec-widget__collapse');
        if (btn) btn.click();
    }, widgetId);
    await page.waitForTimeout(450);
}

async function resetExecutiveLayout(page) {
    const key = await layoutStorageKey(page);
    if (key) {
        await page.evaluate((k) => localStorage.removeItem(k), key);
    }
    await page.reload({ waitUntil: 'load' });
    await page.waitForTimeout(700);
    await dismissOverlays(page);
}

async function applySavedColSpans(page) {
    await page.evaluate(() => {
        if (window.VouchexExecLayout && window.VouchexExecLayout.applySavedColSpans) {
            window.VouchexExecLayout.applySavedColSpans('executive-summary-sortable');
        }
    });
}

async function prepareCalendarWidget(page) {
    await page.addStyleTag({
        content: '.exec-widget__resize-layer { pointer-events: none !important; }',
    });
    if (await isWidgetCollapsed(page, 'exec-calendar')) {
        await clickWidgetCollapse(page, 'exec-calendar');
    }
    await page.locator('[data-dashboard-widget="exec-calendar"]').scrollIntoViewIfNeeded();
    await page.waitForFunction(() => window.calendar && window.calendar.getEvents().length > 0, null, {
        timeout: 12000,
    });
}

async function launchBrowser() {
    const options = { headless: HEADLESS, slowMo: SLOW_MO };
    try {
        return await chromium.launch({ ...options, channel: 'chrome' });
    } catch {
        return await chromium.launch(options);
    }
}

async function ensureServerAndDemo() {
    try {
        const res = await fetch(`${BASE}/login`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
    } catch (e) {
        const browser = await launchBrowser();
        const page = await browser.newPage();
        const res = await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 15000 });
        await browser.close();
        if (!res || res.status() >= 500) {
            throw new Error(`Cannot reach ${BASE}/login — run: php artisan serve`);
        }
    }

        if (!SKIP_SEED) {
            try {
                console.log('Seeding demo workspace (demo:ensure-dashboard)…');
                execSync('php artisan demo:ensure-dashboard --no-interaction', {
                    stdio: 'inherit',
                    cwd: process.cwd(),
                    env: { ...process.env, QA_SKIP_TOUR: '1' },
                });
        } catch {
            console.warn('  Warning: demo seed failed — continuing if demo user exists.');
        }
    }
}

function attachPageGuards(page) {
    page.on('pageerror', (err) => {
        pageErrors.push(err.message);
        console.warn('  [page error]', err.message);
    });
}

function printSummary(title) {
    console.log(`\n========== ${title} ==========`);
    console.log(`Passed:  ${passed.length}`);
    console.log(`Failed:  ${failed.length}`);
    console.log(`Skipped: ${skipped.length}`);
    if (pageErrors.length) {
        console.log(`Page errors: ${pageErrors.length}`);
    }
    if (failed.length) {
        failed.forEach((f) => console.log(`  ✗ ${f.name}: ${f.detail}`));
        return 1;
    }
    return 0;
}

module.exports = {
    BASE,
    WORKSPACE,
    EMAIL,
    PASSWORD,
    HEADLESS,
    SLOW_MO,
    SECTION_PAUSE,
    passed,
    failed,
    skipped,
    pageErrors,
    ok,
    fail,
    skip,
    pause,
    highlightSection,
    dismissOverlays,
    stopDemoTour,
    visitRoute,
    login,
    layoutStorageKey,
    ensureLayoutStorageKey,
    widgetOrder,
    isWidgetCollapsed,
    ensureDashboard,
    clickWidgetCollapse,
    resetExecutiveLayout,
    applySavedColSpans,
    prepareCalendarWidget,
    launchBrowser,
    ensureServerAndDemo,
    attachPageGuards,
    printSummary,
};
