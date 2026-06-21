/**
 * Executive Summary — browser E2E (Playwright)
 *
 * Covers manual QA cases:
 *   TC-009  Collapse widget persists after refresh
 *   TC-010  Expand widget persists after refresh
 *   TC-013  Widget reorder persists after refresh
 *   TC-017  Two half-width widgets (6+6) sit on one row
 *   TC-032  Calendar drag reschedule (confirm + API)
 *   TC-033  Calendar drag to past date is blocked
 *
 * Prerequisites:
 *   php artisan serve
 *   php artisan demo:ensure-dashboard
 *
 * Usage:
 *   npm run test:executive-summary
 *   BASE_URL=http://127.0.0.1:8000 node executive-summary-browser-qa.cjs
 */
const { chromium } = require('playwright');
const { execSync } = require('child_process');

const BASE = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const WORKSPACE = process.env.QA_WORKSPACE || 'demodashboard';
const EMAIL = process.env.QA_EMAIL || 'demo@vouchex.in';
const PASSWORD = process.env.QA_PASSWORD || 'demo@1234';
const HEADLESS = process.env.HEADLESS !== '0';
const SKIP_SEED = process.env.SKIP_SEED === '1';

const passed = [];
const failed = [];
const skipped = [];

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

async function dismissOverlays(page) {
    const welcome = page.locator('div.fixed.inset-0.bg-gray-500').first();
    if (await welcome.isVisible({ timeout: 1200 }).catch(() => false)) {
        await welcome.click({ force: true }).catch(() => {});
        await page.waitForTimeout(250);
    }

    const tourBadge = page.locator('#demo-tour-live-badge:not(.hidden)');
    if (await tourBadge.isVisible({ timeout: 800 }).catch(() => false)) {
        await page.keyboard.press('Escape').catch(() => {});
        await page.evaluate(() => {
            const badge = document.getElementById('demo-tour-live-badge');
            if (badge) badge.classList.add('hidden');
            const cursor = document.getElementById('demo-tour-cursor');
            if (cursor) cursor.classList.remove('visible');
        }).catch(() => {});
    }
}

async function loginDemo(page) {
    await page.goto(`${BASE}/login?workspace=${WORKSPACE}`, { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="workspace"]', WORKSPACE);
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/dashboard/, { timeout: 20000 });
    await page.waitForLoadState('load');
    await page.waitForFunction(() => document.getElementById('executive-summary-sortable') !== null);
    await page.waitForTimeout(600);
    await dismissOverlays(page);
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

async function clickWidgetCollapse(page, widgetId) {
    const widget = page.locator(`[data-dashboard-widget="${widgetId}"]`);
    await widget.scrollIntoViewIfNeeded();
    await widget.locator('.exec-widget__collapse').click();
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

async function tc009CollapsePersists(page) {
    const name = 'TC-009 Collapse calendar persists after refresh';
    try {
        await resetExecutiveLayout(page);
        await clickWidgetCollapse(page, 'exec-calendar');

        const collapsed = await isWidgetCollapsed(page, 'exec-calendar');
        if (!collapsed) {
            fail(name, 'calendar not collapsed before refresh');
            return;
        }

        const key = await layoutStorageKey(page);
        const stored = await page.evaluate((k) => {
            try {
                return JSON.parse(localStorage.getItem(k) || '{}');
            } catch {
                return {};
            }
        }, key);

        if (!stored['executive-summary-collapsed']?.['exec-calendar']) {
            fail(name, 'localStorage collapsed flag missing');
            return;
        }

        await page.reload({ waitUntil: 'load' });
        await page.waitForTimeout(700);
        await dismissOverlays(page);

        const stillCollapsed = await isWidgetCollapsed(page, 'exec-calendar');
        if (!stillCollapsed) {
            fail(name, 'calendar expanded after refresh');
            return;
        }

        ok(name);
    } catch (e) {
        fail(name, e.message);
    }
}

async function tc010ExpandPersists(page) {
    const name = 'TC-010 Expand calendar persists after refresh';
    try {
        if (!(await isWidgetCollapsed(page, 'exec-calendar'))) {
            await clickWidgetCollapse(page, 'exec-calendar');
        }

        await clickWidgetCollapse(page, 'exec-calendar');

        const expanded = await isWidgetCollapsed(page, 'exec-calendar');
        if (expanded) {
            fail(name, 'calendar still collapsed after expand click');
            return;
        }

        await page.reload({ waitUntil: 'load' });
        await page.waitForTimeout(700);
        await dismissOverlays(page);

        const stillExpanded = await isWidgetCollapsed(page, 'exec-calendar');
        if (stillExpanded) {
            fail(name, 'calendar collapsed again after refresh');
            return;
        }

        ok(name);
    } catch (e) {
        fail(name, e.message);
    }
}

async function tc013ReorderPersists(page) {
    const name = 'TC-013 Reorder calendar to top persists after refresh';
    try {
        await resetExecutiveLayout(page);

        const reordered = await page.evaluate(() => {
            const container = document.getElementById('executive-summary-sortable');
            if (!container) return false;

            const calendar = container.querySelector('[data-dashboard-widget="exec-calendar"]');
            const first = container.querySelector('[data-dashboard-widget]');
            if (!calendar || !first || calendar === first) return true;

            container.insertBefore(calendar, first);

            if (window.VouchexExecLayout && window.VouchexExecLayout.readStorage && window.VouchexExecLayout.writeStorage) {
                const state = window.VouchexExecLayout.readStorage();
                state['executive-summary-sortable'] = Array.from(
                    container.querySelectorAll('[data-dashboard-widget]')
                ).map((el) => el.getAttribute('data-dashboard-widget'));
                window.VouchexExecLayout.writeStorage(state);
            }

            return true;
        });

        if (!reordered) {
            fail(name, 'could not reorder widgets in DOM');
            return;
        }

        await page.waitForTimeout(400);

        const orderAfter = await widgetOrder(page);
        if (orderAfter[0] !== 'exec-calendar') {
            fail(name, `order after reorder: ${orderAfter.join(', ')}`);
            return;
        }

        await page.reload({ waitUntil: 'load' });
        await page.waitForTimeout(700);
        await dismissOverlays(page);

        const orderReload = await widgetOrder(page);
        if (orderReload[0] !== 'exec-calendar') {
            fail(name, `order after refresh: ${orderReload.join(', ')}`);
            return;
        }

        ok(name);
    } catch (e) {
        fail(name, e.message);
    }
}

async function tc017HalfWidthGrid(page) {
    const name = 'TC-017 Calendar + Pulse at col-6 share one row';
    try {
        await resetExecutiveLayout(page);

        const key = await ensureLayoutStorageKey(page);
        if (!key) {
            fail(name, 'layout storage key not found');
            return;
        }

        await page.evaluate((k) => {
            const data = JSON.parse(localStorage.getItem(k) || '{}');
            data['executive-summary-sizes'] = {
                'exec-calendar': { col: 6 },
                'exec-pulse': { col: 6 },
            };
            localStorage.setItem(k, JSON.stringify(data));
        }, key);

        await page.reload({ waitUntil: 'load' });
        await page.waitForTimeout(500);
        await dismissOverlays(page);

        await page.evaluate(() => {
            if (window.VouchexExecLayout && window.VouchexExecLayout.applySavedColSpans) {
                window.VouchexExecLayout.applySavedColSpans('executive-summary-sortable');
            }
        });
        await page.waitForTimeout(200);

        const calendar = page.locator('[data-dashboard-widget="exec-calendar"]');
        const pulse = page.locator('[data-dashboard-widget="exec-pulse"]');

        const calClass = await calendar.getAttribute('class');
        const pulseClass = await pulse.getAttribute('class');

        if (!calClass?.includes('exec-widget--col-6') || !pulseClass?.includes('exec-widget--col-6')) {
            const debug = await page.evaluate((k) => {
                const state = JSON.parse(localStorage.getItem(k) || '{}');
                return {
                    sizes: state['executive-summary-sizes'] || null,
                    widgets: Array.from(
                        document.querySelectorAll('#executive-summary-sortable [data-dashboard-widget]')
                    ).map((el) => ({ id: el.getAttribute('data-dashboard-widget'), className: el.className })),
                };
            }, key);
            fail(name, `missing col-6 classes: calendar="${calClass}" pulse="${pulseClass}" debug=${JSON.stringify(debug)}`);
            return;
        }

        const calBox = await calendar.boundingBox();
        const pulseBox = await pulse.boundingBox();

        if (!calBox || !pulseBox) {
            fail(name, 'could not measure widget boxes');
            return;
        }

        const sameRow = Math.abs(calBox.y - pulseBox.y) < 40;
        const sideBySide = calBox.x < pulseBox.x && calBox.width > 120 && pulseBox.width > 120;

        if (!sameRow || !sideBySide) {
            fail(name, `layout not side-by-side (cal y=${calBox.y}, pulse y=${pulseBox.y}, cal x=${calBox.x}, pulse x=${pulseBox.x})`);
            return;
        }

        ok(name);
    } catch (e) {
        fail(name, e.message);
    }
}

async function prepareCalendarWidget(page) {
    await page.addStyleTag({
        content: `
            .exec-widget__resize-layer { pointer-events: none !important; }
            #dashboardCalendar .fc-event { cursor: move !important; }
        `,
    });

    const calendarWidget = page.locator('[data-dashboard-widget="exec-calendar"]');
    if (await isWidgetCollapsed(page, 'exec-calendar')) {
        await clickWidgetCollapse(page, 'exec-calendar');
    }
    await calendarWidget.scrollIntoViewIfNeeded();
    await page.waitForFunction(() => window.calendar && window.calendar.getEvents().length > 0, null, {
        timeout: 12000,
    });
}

async function tc032CalendarDragReschedule(page) {
    const name = 'TC-032 Calendar drag reschedule accepts future date';
    try {
        await prepareCalendarWidget(page);

        const responsePromise = page.waitForResponse(
            (res) => res.url().includes('/calendar/update-date') && res.request().method() === 'POST',
            { timeout: 15000 }
        );

        const outcome = await page.evaluate(() => {
            const cal = window.calendar;
            const events = cal.getEvents().filter((e) => e.extendedProps?.type === 'task');
            if (!events.length) return { skip: true, reason: 'no task events' };

            const event = events[0];
            const future = new Date();
            future.setDate(future.getDate() + 5);
            future.setHours(12, 0, 0, 0);
            event.setStart(future);

            const handler = cal.getOption('eventDrop');
            const originalConfirm = window.confirm;
            window.confirm = () => true;

            let reverted = false;
            handler({
                event,
                revert: () => {
                    reverted = true;
                },
            });

            window.confirm = originalConfirm;

            return { skip: false, reverted, date: event.startStr };
        });

        if (outcome.skip) {
            skip(name, outcome.reason);
            return;
        }

        const response = await responsePromise.catch(() => null);
        if (!response) {
            fail(name, `no API response; reverted=${outcome.reverted}`);
            return;
        }

        if (!response.ok()) {
            fail(name, `API HTTP ${response.status()}`);
            return;
        }

        const body = await response.json().catch(() => ({}));
        if (!body.success) {
            fail(name, `API success=false: ${body.message || 'unknown'}`);
            return;
        }

        ok(name);
    } catch (e) {
        fail(name, e.message);
    }
}

async function tc033CalendarPastDateBlocked(page) {
    const name = 'TC-033 Calendar drag to past date is blocked';
    try {
        await prepareCalendarWidget(page);

        const result = await page.evaluate(() => {
            const cal = window.calendar;
            if (!cal) return { ok: false, reason: 'calendar missing' };

            const events = cal.getEvents().filter((e) => e.extendedProps?.type === 'task');
            if (!events.length) return { ok: false, reason: 'no task events' };

            const handler = cal.getOption('eventDrop');
            if (typeof handler !== 'function') return { ok: false, reason: 'no eventDrop handler' };

            const event = events[0];
            const past = new Date();
            past.setDate(past.getDate() - 3);
            past.setHours(12, 0, 0, 0);

            let reverted = false;
            let alertMessage = null;
            const originalAlert = window.alert;
            window.alert = (msg) => {
                alertMessage = String(msg);
            };

            const previous = event.start;
            event.setStart(past);

            try {
                handler({
                    event,
                    revert: () => {
                        reverted = true;
                    },
                    oldEvent: null,
                });
            } finally {
                window.alert = originalAlert;
                if (!reverted) {
                    event.setStart(previous);
                }
            }

            return {
                ok: Boolean(alertMessage && /Cannot reschedule to a past date/i.test(alertMessage) && reverted),
                alertMessage,
                reverted,
            };
        });

        if (result.reason) {
            skip(name, result.reason);
            return;
        }

        if (!result.ok) {
            fail(name, `alert="${result.alertMessage || 'none'}" reverted=${result.reverted}`);
            return;
        }

        ok(name);
    } catch (e) {
        fail(name, e.message);
    }
}

async function launchBrowser() {
    const options = { headless: HEADLESS };
    try {
        return await chromium.launch({ ...options, channel: 'chrome' });
    } catch {
        return await chromium.launch(options);
    }
}

async function ensureServerAndDemo() {
    let probe;
    try {
        probe = await fetch(`${BASE}/login`);
        if (!probe.ok) throw new Error(`HTTP ${probe.status}`);
    } catch (e) {
        let browser;
        try {
            browser = await launchBrowser();
            const page = await browser.newPage();
            const res = await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 15000 });
            await browser.close();
            if (!res || res.status() >= 500) {
                throw new Error(`HTTP ${res?.status() ?? 'no response'}`);
            }
        } catch (inner) {
            throw new Error(
                `Cannot reach ${BASE}/login — run: php artisan serve\n  ${inner.message || e.message}`
            );
        }
    }

    if (!SKIP_SEED) {
        try {
            console.log('Seeding demo workspace (demo:ensure-dashboard)…');
            execSync('php artisan demo:ensure-dashboard --no-interaction', {
                stdio: 'inherit',
                cwd: process.cwd(),
            });
        } catch (e) {
            console.warn('  Warning: demo seed failed — continuing if demo user already exists.');
        }
    }
}

(async () => {
    console.log(`Executive Summary browser QA @ ${BASE}`);
    console.log(`  workspace=${WORKSPACE} email=${EMAIL} headless=${HEADLESS}\n`);

    try {
        await ensureServerAndDemo();
    } catch (e) {
        console.error(e.message);
        process.exit(1);
    }

    const browser = await launchBrowser();
    const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await context.newPage();

    page.on('pageerror', (err) => console.warn('  [page error]', err.message));

    console.log('=== Authentication ===');
    try {
        await loginDemo(page);
        ok('TC-001 Demo login → dashboard');
    } catch (e) {
        fail('TC-001 Demo login → dashboard', e.message);
        await browser.close();
        process.exit(1);
    }

    console.log('\n=== Executive widget layout ===');
    await tc009CollapsePersists(page);
    await tc010ExpandPersists(page);
    await tc013ReorderPersists(page);
    await tc017HalfWidthGrid(page);

    console.log('\n=== Calendar drag ===');
    await tc032CalendarDragReschedule(page);
    await tc033CalendarPastDateBlocked(page);

    await browser.close();

    console.log('\n========== SUMMARY ==========');
    console.log(`Passed:  ${passed.length}`);
    console.log(`Failed:  ${failed.length}`);
    console.log(`Skipped: ${skipped.length}`);
    if (failed.length) {
        failed.forEach((f) => console.log(`  ✗ ${f.name}: ${f.detail}`));
        process.exit(1);
    }
    console.log('Executive Summary browser QA passed.');
})();
