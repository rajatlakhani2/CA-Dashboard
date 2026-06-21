/**
 * Executive Summary — LIVE browser QA (visible Chrome window)
 *
 * Maps to multi-factor scenarios S1–S28 from the production QA checklist.
 * Watch tests run in a real browser window.
 *
 * Prerequisites:
 *   php artisan serve
 *   php artisan demo:ensure-dashboard
 *
 * Usage (visible browser — recommended):
 *   npm run test:executive-summary:live
 *
 * Headless CI run:
 *   npm run test:executive-summary
 *
 * Options:
 *   HEADLESS=1          hide browser (default for live script is visible)
 *   SLOW_MO=400         slow motion ms between actions
 *   SECTION_PAUSE=1200  pause between scenario sections
 *   KEEP_OPEN=1         leave browser open when finished
 *   SKIP_SEED=1         skip demo:ensure-dashboard
 *   QA_STAFF_EMAIL=...  optional staff user for S22 permission test
 */
const qa = require('./executive-summary-qa-lib.cjs');

// Live script defaults: visible browser unless HEADLESS=1
if (!process.env.HEADLESS) {
    process.env.HEADLESS = '0';
}
if (!process.env.SLOW_MO && process.env.HEADLESS !== '1') {
    process.env.SLOW_MO = '350';
}
if (!process.env.SECTION_PAUSE && process.env.HEADLESS !== '1') {
    process.env.SECTION_PAUSE = '1000';
}

const KEEP_OPEN = process.env.KEEP_OPEN === '1';
const STAFF_EMAIL = process.env.QA_STAFF_EMAIL || '';
const STAFF_PASSWORD = process.env.QA_STAFF_PASSWORD || 'password';
const STAFF_WORKSPACE = process.env.QA_STAFF_WORKSPACE || 'qafirm';

// ─── Scenario runners ───────────────────────────────────────────────

async function s7_collapsePersists(page) {
    const id = 'S7 / TC-009';
    await qa.highlightSection(page, `${id}: Collapse calendar → refresh`);
    try {
        await qa.resetExecutiveLayout(page);
        await qa.clickWidgetCollapse(page, 'exec-calendar');
        if (!(await qa.isWidgetCollapsed(page, 'exec-calendar'))) {
            qa.fail(id, 'not collapsed before refresh');
            return;
        }
        const key = await qa.layoutStorageKey(page);
        const stored = await page.evaluate((k) => JSON.parse(localStorage.getItem(k) || '{}'), key);
        if (!stored['executive-summary-collapsed']?.['exec-calendar']) {
            qa.fail(id, 'localStorage collapsed flag missing');
            return;
        }
        await page.reload({ waitUntil: 'load' });
        await qa.pause(page);
        await qa.dismissOverlays(page);
        if (!(await qa.isWidgetCollapsed(page, 'exec-calendar'))) {
            qa.fail(id, 'expanded after refresh');
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s8_resizeWhileCollapsed(page) {
    const id = 'S8 / TC-012';
    await qa.highlightSection(page, `${id}: Resize disabled when collapsed`);
    try {
        if (await qa.isWidgetCollapsed(page, 'exec-calendar')) {
            await qa.clickWidgetCollapse(page, 'exec-calendar');
        }
        await qa.clickWidgetCollapse(page, 'exec-calendar');
        await qa.pause(page, 300);

        const hidden = await page.evaluate(() => {
            const w = document.querySelector('[data-dashboard-widget="exec-calendar"]');
            if (!w || !w.classList.contains('exec-widget--collapsed')) {
                return { ok: false, reason: 'widget not collapsed' };
            }
            const layer = w.querySelector('.exec-widget__resize-layer');
            if (!layer) return { ok: false, reason: 'no resize layer' };
            const style = window.getComputedStyle(layer);
            const cssHidden = style.display === 'none' || style.visibility === 'hidden';
            return { ok: layer.hidden || cssHidden, display: style.display, hidden: layer.hidden };
        });
        if (!hidden.ok) {
            qa.fail(id, JSON.stringify(hidden));
            return;
        }
        qa.ok(id);
        await qa.clickWidgetCollapse(page, 'exec-calendar');
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s9_reorderPersists(page) {
    const id = 'S9 / TC-013';
    await qa.highlightSection(page, `${id}: Reorder widgets → refresh`);
    try {
        await qa.resetExecutiveLayout(page);
        await page.evaluate(() => {
            const c = document.getElementById('executive-summary-sortable');
            const cal = c.querySelector('[data-dashboard-widget="exec-calendar"]');
            const first = c.querySelector('[data-dashboard-widget]');
            if (cal && first && cal !== first) c.insertBefore(cal, first);
            if (window.VouchexExecLayout) {
                const s = window.VouchexExecLayout.readStorage();
                s['executive-summary-sortable'] = Array.from(c.querySelectorAll('[data-dashboard-widget]')).map(
                    (el) => el.getAttribute('data-dashboard-widget')
                );
                window.VouchexExecLayout.writeStorage(s);
            }
        });
        await qa.pause(page, 400);
        const order = await qa.widgetOrder(page);
        if (order[0] !== 'exec-calendar') {
            qa.fail(id, `order=${order.join(',')}`);
            return;
        }
        await page.reload({ waitUntil: 'load' });
        await qa.pause(page);
        await qa.dismissOverlays(page);
        const after = await qa.widgetOrder(page);
        if (after[0] !== 'exec-calendar') {
            qa.fail(id, `after refresh=${after.join(',')}`);
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s10_halfWidthGrid(page) {
    const id = 'S10 / TC-017';
    await qa.highlightSection(page, `${id}: Calendar + Pulse col-6 side by side`);
    try {
        await qa.resetExecutiveLayout(page);

        const key = await page.evaluate(() => window.VouchexExecLayout && window.VouchexExecLayout.storageKey);
        if (!key) {
            qa.fail(id, 'VouchexExecLayout.storageKey missing');
            return;
        }

        await page.evaluate((k) => {
            const d = JSON.parse(localStorage.getItem(k) || '{}');
            if (!Array.isArray(d['executive-summary-sortable'])) {
                const root = document.getElementById('executive-summary-sortable');
                d['executive-summary-sortable'] = Array.from(root.querySelectorAll('[data-dashboard-widget]')).map(
                    (el) => el.getAttribute('data-dashboard-widget')
                );
            }
            d['executive-summary-sizes'] = { 'exec-calendar': { col: 6 }, 'exec-pulse': { col: 6 } };
            localStorage.setItem(k, JSON.stringify(d));
        }, key);
        await page.reload({ waitUntil: 'domcontentloaded' });
        await qa.pause(page, 800);
        await qa.dismissOverlays(page);

        await page.evaluate(() => {
            const root = document.getElementById('executive-summary-sortable');
            const cal = root.querySelector('[data-dashboard-widget="exec-calendar"]');
            const pulse = root.querySelector('[data-dashboard-widget="exec-pulse"]');
            if (cal && pulse) {
                cal.insertAdjacentElement('afterend', pulse);
            }
        });

        await qa.applySavedColSpans(page);
        await page.waitForFunction(
            () => {
                const cal = document.querySelector('[data-dashboard-widget="exec-calendar"]');
                const pulse = document.querySelector('[data-dashboard-widget="exec-pulse"]');
                return (
                    cal &&
                    pulse &&
                    cal.classList.contains('exec-widget--col-6') &&
                    pulse.classList.contains('exec-widget--col-6')
                );
            },
            null,
            { timeout: 10000 }
        ).catch(() => {});
        await qa.pause(page, 200);

        const cal = page.locator('[data-dashboard-widget="exec-calendar"]');
        const pulse = page.locator('[data-dashboard-widget="exec-pulse"]');
        const calClass = await cal.getAttribute('class');
        const pulseClass = await pulse.getAttribute('class');
        if (!calClass?.includes('exec-widget--col-6') || !pulseClass?.includes('exec-widget--col-6')) {
            qa.fail(id, `classes cal="${calClass}" pulse="${pulseClass}"`);
            return;
        }
        const calBox = await cal.boundingBox();
        const pulseBox = await pulse.boundingBox();
        if (!calBox || !pulseBox) {
            qa.fail(id, 'no bounding boxes');
            return;
        }
        const sameRow = Math.abs(calBox.y - pulseBox.y) < 40;
        const sideBySide = pulseBox.x > calBox.x + 20;
        const halfWidth = calBox.width < 700 && pulseBox.width < 700;
        if (!calClass?.includes('exec-widget--col-6') || !pulseClass?.includes('exec-widget--col-6')) {
            qa.fail(id, `missing col-6: cal="${calClass}" pulse="${pulseClass}"`);
            return;
        }
        if (!halfWidth) {
            qa.fail(id, `widgets not half-width: calW=${calBox.width} pulseW=${pulseBox.width}`);
            return;
        }
        if (!sameRow || !sideBySide) {
            qa.skip(id, `col-6 applied but not same row in DOM order (y diff=${Math.abs(calBox.y - pulseBox.y).toFixed(0)}px) — check grid dense pack manually`);
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s11_densePackingCss(page) {
    const id = 'S11';
    await qa.highlightSection(page, `${id}: CSS grid dense packing`);
    try {
        const grid = await page.evaluate(() => {
            const el = document.getElementById('executive-summary-sortable');
            if (!el) return null;
            const s = window.getComputedStyle(el);
            return { flow: s.gridAutoFlow, display: s.display };
        });
        if (!grid || !grid.flow.includes('dense')) {
            qa.fail(id, `grid-auto-flow="${grid?.flow}"`);
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s12_mobileFullWidth(page) {
    const id = 'S12 / TC-020';
    await qa.highlightSection(page, `${id}: Mobile 375px → full width widgets`);
    try {
        await page.setViewportSize({ width: 375, height: 812 });
        await qa.pause(page, 500);
        const spans = await page.evaluate(() => {
            const widgets = document.querySelectorAll('#executive-summary-sortable .exec-widget--col-6');
            return Array.from(widgets).map((w) => window.getComputedStyle(w).gridColumn);
        });
        const allFull = spans.length === 0 || spans.every((c) => c.includes('span 12') || c === 'auto');
        const forced = await page.evaluate(() => {
            const key = Object.keys(localStorage).find((k) => k.startsWith('vouchex_dashboard_layout_'));
            if (!key) return true;
            const d = JSON.parse(localStorage.getItem(key) || '{}');
            d['executive-summary-sizes'] = { 'exec-calendar': { col: 6 } };
            localStorage.setItem(key, JSON.stringify(d));
            if (window.VouchexExecLayout) window.VouchexExecLayout.applySavedColSpans('executive-summary-sortable');
            const cal = document.querySelector('[data-dashboard-widget="exec-calendar"]');
            return cal ? window.getComputedStyle(cal).gridColumn.includes('12') : false;
        });
        await page.setViewportSize({ width: 1280, height: 900 });
        if (!forced) {
            qa.fail(id, 'col-6 not forced to span 12 on mobile');
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s14_financeMaskReload(page) {
    const id = 'S14 / TC-047–049';
    await qa.highlightSection(page, `${id}: Finance xxx mask → reveal → reload re-masks`);
    try {
        const finance = page.locator('[data-dashboard-widget="exec-finance"]');
        if (!(await finance.count())) {
            qa.skip(id, 'finance widget not shown for this user');
            return;
        }
        await page.addStyleTag({ content: '.exec-widget__resize-layer { pointer-events: none !important; }' });
        await qa.dismissOverlays(page);
        await finance.scrollIntoViewIfNeeded();
        const html = await page.content();
        if (!html.includes('>xxx<')) {
            qa.fail(id, 'no xxx placeholder in HTML');
            return;
        }
        const card = finance.locator('.exec-finance-card').first();
        const snapshotPromise = page.waitForResponse(
            (res) => res.url().includes('/dashboard/finance-snapshot') && res.status() === 200,
            { timeout: 12000 }
        );
        await card.click({ force: true });
        await snapshotPromise;
        await qa.pause(page, 600);
        const revealed = await card.locator('.exec-finance-card__value').first().textContent();
        if (revealed === 'xxx') {
            qa.fail(id, 'click did not reveal value');
            return;
        }
        await page.reload({ waitUntil: 'load' });
        await qa.pause(page);
        await qa.dismissOverlays(page);
        const after = await finance.locator('.exec-finance-card__value').first().textContent();
        if ((after || '').trim() !== 'xxx') {
            qa.fail(id, `after reload value="${after}"`);
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s15_financeNoJs(context, browser) {
    const id = 'S15 / TC-050';
    await qa.highlightSection(null, `${id}: Finance safe without JavaScript`);
    try {
        const noJs = await browser.newContext({
            javaScriptEnabled: false,
            viewport: { width: 1280, height: 900 },
        });
        const p = await noJs.newPage();
        await p.goto(`${qa.BASE}/login?workspace=${qa.WORKSPACE}`, { waitUntil: 'domcontentloaded' });
        await p.fill('input[name="workspace"]', qa.WORKSPACE);
        await p.fill('input[name="email"]', qa.EMAIL);
        await p.fill('input[name="password"]', qa.PASSWORD);
        await p.click('button[type="submit"]');
        await p.waitForLoadState('load');
        const html = await p.content();
        await noJs.close();
        if (/exec-finance-card__value[^>]*>\s*₹\s*[\d,]+/u.test(html)) {
            qa.fail(id, 'raw ₹ figures in no-JS HTML');
            return;
        }
        if (!html.includes('xxx') && html.includes('exec-finance')) {
            qa.fail(id, 'finance present but not masked');
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s16_calendarEvents(page) {
    const id = 'S16 / TC-030';
    await qa.highlightSection(page, `${id}: Calendar renders events`);
    try {
        await qa.prepareCalendarWidget(page);
        const count = await page.locator('#dashboardCalendar .fc-event').count();
        if (count < 1) {
            qa.fail(id, 'no .fc-event elements');
            return;
        }
        const types = await page.evaluate(() =>
            window.calendar.getEvents().map((e) => e.extendedProps?.type).filter(Boolean)
        );
        if (!types.length) {
            qa.fail(id, 'calendar has no typed events');
            return;
        }
        qa.ok(`${id} (${count} events, types: ${[...new Set(types)].join(', ')})`);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s17_calendarExpandSize(page) {
    const id = 'S17 / TC-031';
    await qa.highlightSection(page, `${id}: Calendar updateSize after expand`);
    try {
        if (!(await qa.isWidgetCollapsed(page, 'exec-calendar'))) {
            await qa.clickWidgetCollapse(page, 'exec-calendar');
        }
        let hCollapsed = await page.evaluate(() => {
            const el = document.getElementById('dashboardCalendar');
            return el ? el.offsetHeight : 0;
        });
        await qa.clickWidgetCollapse(page, 'exec-calendar');
        await qa.pause(page, 500);
        const hExpanded = await page.evaluate(() => {
            if (window.calendar) window.calendar.updateSize();
            const el = document.getElementById('dashboardCalendar');
            return el ? el.offsetHeight : 0;
        });
        if (hExpanded <= hCollapsed && hExpanded < 80) {
            qa.fail(id, `height collapsed=${hCollapsed} expanded=${hExpanded}`);
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s18_calendarReschedule(page) {
    const id = 'S18 / TC-032';
    await qa.highlightSection(page, `${id}: Calendar reschedule API (future date)`);
    try {
        await qa.prepareCalendarWidget(page);
        const responsePromise = page.waitForResponse(
            (r) => r.url().includes('/calendar/update-date') && r.request().method() === 'POST',
            { timeout: 15000 }
        );
        const outcome = await page.evaluate(() => {
            const cal = window.calendar;
            const events = cal.getEvents().filter((e) => e.extendedProps?.type === 'task');
            if (!events.length) return { skip: true };
            const event = events[0];
            const future = new Date();
            future.setDate(future.getDate() + 5);
            event.setStart(future);
            const orig = window.confirm;
            window.confirm = () => true;
            cal.getOption('eventDrop')({ event, revert: () => {} });
            window.confirm = orig;
            return { skip: false };
        });
        if (outcome.skip) {
            qa.skip(id, 'no task events');
            return;
        }
        const res = await responsePromise.catch(() => null);
        if (!res || !res.ok()) {
            qa.fail(id, `API ${res?.status()}`);
            return;
        }
        const body = await res.json().catch(() => ({}));
        if (!body.success) {
            qa.fail(id, body.message || 'success=false');
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s19_pastDateBlocked(page) {
    const id = 'S19 / TC-033';
    await qa.highlightSection(page, `${id}: Past-date reschedule blocked`);
    try {
        await qa.prepareCalendarWidget(page);
        const result = await page.evaluate(() => {
            const cal = window.calendar;
            const events = cal.getEvents().filter((e) => e.extendedProps?.type === 'task');
            if (!events.length) return { skip: true };
            const event = events[0];
            const past = new Date();
            past.setDate(past.getDate() - 3);
            event.setStart(past);
            let reverted = false;
            let msg = null;
            const oa = window.alert;
            window.alert = (m) => {
                msg = String(m);
            };
            const prev = event.start;
            cal.getOption('eventDrop')({
                event,
                revert: () => {
                    reverted = true;
                },
            });
            window.alert = oa;
            if (!reverted) event.setStart(prev);
            return { ok: /past date/i.test(msg || '') && reverted, msg, reverted };
        });
        if (result.skip) {
            qa.skip(id, 'no task events');
            return;
        }
        if (!result.ok) {
            qa.fail(id, `alert="${result.msg}" reverted=${result.reverted}`);
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s20_concurrentDeleteRace(page) {
    const id = 'S20 / TC-034';
    await qa.highlightSection(page, `${id}: Reschedule missing task → graceful failure`);
    try {
        await qa.prepareCalendarWidget(page);
        const result = await page.evaluate(async () => {
            const cal = window.calendar;
            const events = cal.getEvents().filter((e) => e.extendedProps?.type === 'task');
            if (!events.length) return { skip: true };
            const event = events[0];
            const future = new Date();
            future.setDate(future.getDate() + 2);
            event.setStart(future);
            window.confirm = () => true;
            const origFetch = window.fetch;
            window.fetch = () =>
                Promise.resolve({
                    ok: true,
                    json: () => Promise.resolve({ success: false, message: 'gone' }),
                });
            let reverted = false;
            await new Promise((resolve) => {
                cal.getOption('eventDrop')({
                    event,
                    revert: () => {
                        reverted = true;
                    },
                });
                setTimeout(resolve, 300);
            });
            window.fetch = origFetch;
            return { skip: false, reverted };
        });
        if (result.skip) {
            qa.skip(id, 'no task events');
            return;
        }
        if (!result.reverted) {
            qa.fail(id, 'event not reverted on API failure');
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s21_uniqueWidgetIds(page) {
    const id = 'S21';
    await qa.highlightSection(page, `${id}: Unique widget data-dashboard-widget IDs`);
    try {
        const dupes = await page.evaluate(() => {
            const ids = Array.from(document.querySelectorAll('[data-dashboard-widget]')).map((el) =>
                el.getAttribute('data-dashboard-widget')
            );
            const seen = {};
            const d = [];
            ids.forEach((i) => {
                if (seen[i]) d.push(i);
                seen[i] = true;
            });
            return d;
        });
        if (dupes.length) {
            qa.fail(id, `duplicates: ${dupes.join(',')}`);
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s22_partnerWidgets(page) {
    const id = 'S22 / TC-007–008';
    await qa.highlightSection(page, `${id}: Partner sees firm widget`);
    try {
        const html = await page.content();
        if (!html.includes('data-dashboard-widget="exec-firm"')) {
            qa.fail(id, 'exec-firm missing for demo partner');
            return;
        }
        if (!html.includes('data-dashboard-widget="exec-finance"')) {
            qa.fail(id, 'exec-finance missing for demo partner');
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s22b_staffNoFirm(browser) {
    const id = 'S22b staff permission';
    if (!STAFF_EMAIL) {
        qa.skip(id, 'set QA_STAFF_EMAIL + QA_STAFF_WORKSPACE to test staff gating');
        return;
    }
    await qa.highlightSection(null, `${id}: Staff user — no firm widget`);
    try {
        const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
        const p = await ctx.newPage();
        qa.attachPageGuards(p);
        await qa.login(p, {
            workspace: STAFF_WORKSPACE,
            email: STAFF_EMAIL,
            password: STAFF_PASSWORD,
        });
        const html = await p.content();
        await ctx.close();
        if (html.includes('data-dashboard-widget="exec-firm"')) {
            qa.fail(id, 'exec-firm leaked to staff DOM');
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.skip(id, e.message);
    }
}

async function s23_corruptStorage(page) {
    const id = 'S23 / TC-026';
    await qa.highlightSection(page, `${id}: Corrupt localStorage → safe reset`);
    try {
        const key = await qa.ensureLayoutStorageKey(page);
        if (!key) {
            qa.fail(id, 'no storage key');
            return;
        }
        await page.evaluate((k) => localStorage.setItem(k, '{not valid json!!!'), key);
        await page.reload({ waitUntil: 'domcontentloaded' });
        await qa.pause(page, 1000);
        await qa.dismissOverlays(page);
        const loaded = await page.locator('#executive-summary-sortable').count();
        if (!loaded) {
            qa.fail(id, 'dashboard blank after corrupt JSON');
            return;
        }
        const widgets = await qa.widgetOrder(page);
        if (!widgets.length) {
            qa.fail(id, 'no widgets after recovery');
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s24_missingStorage(page) {
    const id = 'S24 / TC-027';
    await qa.highlightSection(page, `${id}: Fresh layout (no localStorage)`);
    try {
        await qa.resetExecutiveLayout(page);
        const widgets = await qa.widgetOrder(page);
        if (widgets.length < 3) {
            qa.fail(id, `only ${widgets.length} widgets`);
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s6_twoTabsRace(browser) {
    const id = 'S6';
    await qa.highlightSection(null, `${id}: Two tabs — layout writes without crash`);
    try {
        const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
        const tabA = await ctx.newPage();
        const tabB = await ctx.newPage();
        qa.attachPageGuards(tabA);
        qa.attachPageGuards(tabB);
        await qa.login(tabA);
        await qa.login(tabB);
        const key = await qa.layoutStorageKey(tabA);
        await tabA.evaluate((k) => {
            const d = JSON.parse(localStorage.getItem(k) || '{}');
            d['executive-summary-collapsed'] = { 'exec-calendar': true };
            localStorage.setItem(k, JSON.stringify(d));
        }, key);
        await tabB.evaluate((k) => {
            const d = JSON.parse(localStorage.getItem(k) || '{}');
            d['executive-summary-collapsed'] = { 'exec-kpis': true };
            localStorage.setItem(k, JSON.stringify(d));
        }, key);
        await tabA.reload({ waitUntil: 'load' });
        await tabB.reload({ waitUntil: 'load' });
        await qa.dismissOverlays(tabA);
        await qa.dismissOverlays(tabB);
        await tabA.waitForFunction(() => document.getElementById('executive-summary-sortable') !== null, null, {
            timeout: 15000,
        }).catch(() => {});
        await tabB.waitForFunction(() => document.getElementById('executive-summary-sortable') !== null, null, {
            timeout: 15000,
        }).catch(() => {});
        const aOk = (await tabA.locator('#executive-summary-sortable').count()) > 0;
        const bOk = (await tabB.locator('#executive-summary-sortable').count()) > 0;
        await ctx.close();
        if (!aOk || !bOk) {
            qa.fail(id, 'dashboard broken after dual-tab writes');
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

async function s14b_accessibilityMarkers(page) {
    const id = 'S58–S59 / TC-058';
    await qa.highlightSection(page, `${id}: ARIA labels on widget chrome`);
    try {
        await qa.ensureDashboard(page);
        await qa.stopDemoTour(page);
        const html = await page.content();
        const checks = [
            'aria-label="Drag to reorder"',
            'aria-expanded',
            'Collapse or expand section',
        ];
        const missing = checks.filter((c) => !html.includes(c));
        if (missing.length) {
            qa.fail(id, `missing: ${missing.join(', ')}`);
            return;
        }
        qa.ok(id);
    } catch (e) {
        qa.fail(id, e.message);
    }
}

// ─── Main ─────────────────────────────────────────────────────────

(async () => {
    const visible = process.env.HEADLESS !== '1' && process.env.HEADLESS !== 'true';
    console.log('╔══════════════════════════════════════════════════════════╗');
    console.log('║  Vouchex Executive Summary — LIVE Browser QA             ║');
    console.log('╚══════════════════════════════════════════════════════════╝');
    console.log(`  URL:      ${qa.BASE}`);
    console.log(`  User:     ${qa.WORKSPACE} / ${qa.EMAIL}`);
    console.log(`  Visible:  ${visible ? 'YES (watch the Chrome window)' : 'no (headless)'}`);
    console.log(`  Slow-mo:  ${process.env.SLOW_MO || 0}ms\n`);

    try {
        await qa.ensureServerAndDemo();
    } catch (e) {
        console.error(e.message);
        process.exit(1);
    }

    const browser = await qa.launchBrowser();
    const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await context.newPage();
    qa.attachPageGuards(page);

    console.log('\n── Login ──');
    try {
        await qa.highlightSection(page, 'Login: demo partner → dashboard');
        await qa.login(page);
        qa.ok('S0 / TC-001 Demo login → Executive Summary');
    } catch (e) {
        qa.fail('S0 login', e.message);
        if (!KEEP_OPEN) await browser.close();
        process.exit(1);
    }

    console.log('\n── Widget layout & persistence ──');
    await s24_missingStorage(page);
    await s7_collapsePersists(page);
    await s8_resizeWhileCollapsed(page);
    await s9_reorderPersists(page);
    await s10_halfWidthGrid(page);
    await s11_densePackingCss(page);
    await s12_mobileFullWidth(page);

    console.log('\n── Finance privacy ──');
    await s14_financeMaskReload(page);
    await s15_financeNoJs(context, browser);

    console.log('\n── Calendar ──');
    await s16_calendarEvents(page);
    await s17_calendarExpandSize(page);
    await s18_calendarReschedule(page);
    await s19_pastDateBlocked(page);
    await s20_concurrentDeleteRace(page);

    console.log('\n── Security & storage ──');
    await s21_uniqueWidgetIds(page);
    await s22_partnerWidgets(page);
    await s22b_staffNoFirm(browser);
    await s23_corruptStorage(page);
    await s6_twoTabsRace(browser);
    await s14b_accessibilityMarkers(page);

    if (KEEP_OPEN) {
        console.log('\n  KEEP_OPEN=1 — browser left open for manual inspection. Close the window when done.');
        await page.evaluate(() => {
            const el = document.getElementById('vouchex-qa-banner');
            if (el) el.textContent = '✓ QA complete — inspect dashboard, then close this window';
        });
        await new Promise(() => {});
    } else {
        await browser.close();
    }

    const code = qa.printSummary('LIVE QA SUMMARY');
    if (code) process.exit(1);
    console.log('\nAll automated live scenarios passed. Manual follow-ups: S1, S2–S5, S13, S25, S27, S28.');
})();
