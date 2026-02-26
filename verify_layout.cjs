const { chromium } = require('playwright');

(async () => {
    console.log('Launching browser...');
    const browser = await chromium.launch();
    const page = await browser.newPage();

    console.log('Navigating to Dashboard...');
    // Need to login? The dashboard might be protected.
    // Actually, the previous test worked on /tasks without login? 
    // Let's check middleware. It likely redirects to login if not auth.
    // The User factory created a user but we didn't log in.
    // However, the `tasks` index page seemed accessible in the previous log?
    // Wait, the previous log said "Navigating to Tasks page..." then "Header Text: TITLE".
    // If it was redirected to login, the header likely wouldn't be the table header.
    // Let's assume for local dev auth might be disabled or previously cached? 
    // No, `php artisan serve` is stateless.
    // If I access /tasks and gets redirected to login, the table header selector would fail.
    // BUT the previous verification SUCCESS implies it found the table header.
    // Does the `TasksController` have `middleware('auth')`?
    // Let's check.

    await page.goto('http://127.0.0.1:8000/tasks');

    // Check if we are on login page
    const url = page.url();
    if (url.includes('login')) {
        console.log('Redirected to login. Logging in...');
        await page.fill('input[name="email"]', 'test@example.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForNavigation();
        console.log('Logged in. Current URL:', page.url());
    }

    console.log('Checking Sidebar...');
    const sidebar = page.locator('#sidebar');

    // Check visibility
    if (await sidebar.isVisible()) {
        console.log('Sidebar is visible.');
    } else {
        console.log('FAILURE: Sidebar is NOT visible.');
    }

    // Check CSS
    const box = await sidebar.boundingBox();
    const computed = await sidebar.evaluate(el => {
        const style = window.getComputedStyle(el);
        return {
            position: style.position,
            left: style.left,
            width: style.width
        };
    });

    console.log('Sidebar Computed Styles:', computed);

    // Validation
    const validPosition = computed.position === 'fixed';
    const validLeft = computed.left === '0px';
    const validWidth = computed.width === '256px'; // w-64

    if (validPosition && validLeft && validWidth) {
        console.log('SUCCESS: Sidebar layout is correct.');
    } else {
        console.log('FAILURE: Sidebar layout incorrect.');
    }

    await browser.close();
})();
