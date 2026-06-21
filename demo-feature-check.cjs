/**

 * Quick live demo verification — all major modules + Executive Summary widgets.

 * Usage: node demo-feature-check.cjs

 */

const fs = require('fs');

const path = require('path');

const qa = require('./executive-summary-qa-lib.cjs');



const checks = [];



function pass(name) {

    checks.push({ status: 'PASS', name });

    console.log(`  ✓ ${name}`);

}



function fail(name, detail) {

    checks.push({ status: 'FAIL', name, detail });

    console.log(`  ✗ ${name}: ${detail}`);

}



(async () => {

    console.log(`Demo feature check @ ${qa.BASE}\n`);



    try {

        await qa.ensureServerAndDemo();

    } catch (e) {

        fail('Server', e.message);

        process.exit(1);

    }



    const browser = await qa.launchBrowser();

    const page = await browser.newPage({ viewport: { width: 1400, height: 900 } });

    qa.attachPageGuards(page);



    try {

        await qa.login(page);

        await qa.stopDemoTour(page);

        pass('Login → Dashboard');



        const html = await page.content();

        const widgets = [

            'exec-my-day',

            'exec-due-tomorrow',

            'exec-kpis',

            'exec-calendar',

            'exec-pulse',

            'exec-finance',

            'exec-firm',

        ];

        widgets.forEach((id) => {

            if (html.includes(`data-dashboard-widget="${id}"`)) pass(`Widget: ${id}`);

            else fail(`Widget: ${id}`, 'not in DOM');

        });



        if (html.includes('Executive Summary')) pass('Executive Summary header');

        else fail('Executive Summary header', 'missing');



        if (html.includes('>xxx<') && html.includes('x-cloak')) pass('Finance privacy mask (xxx + x-cloak)');

        else fail('Finance privacy mask', 'xxx/x-cloak missing');



        const eventCount = await page.locator('#dashboardCalendar .fc-event').count();

        if (eventCount > 0) pass(`Calendar events (${eventCount})`);

        else fail('Calendar events', 'none rendered');



        const myDayCount = await page.locator('[data-dashboard-widget="exec-my-day"] .rounded-xl').count();

        pass(`My Day panel (${myDayCount} task cards)`);



        const kpiCount = await page.locator('.exec-kpi-card').count();

        if (kpiCount >= 4) pass(`KPI cards (${kpiCount})`);

        else fail('KPI cards', `only ${kpiCount}`);



        await qa.stopDemoTour(page);

        const shotDir = path.join(process.cwd(), 'storage', 'app');

        fs.mkdirSync(shotDir, { recursive: true });

        await page.screenshot({ path: path.join(shotDir, 'demo-dashboard-check.png'), fullPage: true });

        pass('Screenshot → storage/app/demo-dashboard-check.png');



        const routes = [

            ['/clients', 'Clients'],

            ['/tasks', 'Tasks'],

            ['/my-day', 'My Day page'],

            ['/service-dues', 'Service dues'],

            ['/invoices', 'Invoices'],

            ['/billing', 'Billing'],

            ['/collections', 'Collections'],

            ['/reports', 'Reports'],

            ['/settings', 'Settings'],

            ['/staff', 'Staff'],

        ];



        console.log('\n── Module routes (partner demo) ──');

        for (const [route, label] of routes) {

            try {

                const res = await qa.visitRoute(page, route, label);

                const body = await page.content();

                const status = res ? res.status() : 0;

                if (status > 0 && status < 400 && !/Server Error|Whoops/i.test(body)) {

                    pass(`${label} (${route})`);

                } else {

                    fail(`${label} (${route})`, `HTTP ${status || 'unknown'}`);

                }

            } catch (e) {

                fail(`${label} (${route})`, e.message);

            }

        }

    } catch (e) {

        fail('FATAL', e.message);

    }



    await browser.close();



    const failed = checks.filter((c) => c.status === 'FAIL');

    console.log(`\n========== ${checks.length - failed.length}/${checks.length} passed ==========`);

    if (failed.length) {

        failed.forEach((f) => console.log(`  ✗ ${f.name}: ${f.detail}`));

        process.exit(1);

    }

})();


