const { chromium } = require('playwright');

(async () => {
    console.log('Launching browser...');
    const browser = await chromium.launch();
    const page = await browser.newPage();

    // Login
    console.log('Logging in...');
    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', 'benny.mcdermott@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/');

    // Navigate to Tasks
    console.log('Navigating to Tasks page...');
    await page.goto('http://127.0.0.1:8000/tasks');
    await page.waitForSelector('table');

    // Check variables
    const computedStyle = await page.evaluate(() => {
        // border-b applies border-bottom to all rows
        const el = document.querySelector('tbody tr:first-child');

        // If no row, fall back to table (but this would fail the logic)
        if (!el) return { borderColor: 'No rows found', textColor: 'N/A' };

        const style = window.getComputedStyle(el);
        const textEl = document.querySelector('td .text-text-secondary');

        return {
            borderColor: style.borderBottomColor,
            textColor: window.getComputedStyle(textEl).color
        };
    });

    console.log('Computed Styles:', computedStyle);

    // Modern Theme Defaults:
    // --c-border: #e2e8f0 -> rgb(226, 232, 240)
    // --c-text-secondary: #64748b -> rgb(100, 116, 139)

    if (computedStyle.borderColor === 'rgb(226, 232, 240)' && computedStyle.textColor === 'rgb(100, 116, 139)') {
        console.log('SUCCESS: Theme variables correct for Modern theme.');
    } else {
        console.log('FAILURE: Theme variables mismatch.');
    }

    await browser.close();
})();
