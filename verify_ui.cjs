const { chromium } = require('playwright');

(async () => {
    console.log('Launching browser...');
    const browser = await chromium.launch();
    const page = await browser.newPage();

    console.log('Navigating to Tasks page...');
    await page.goto('http://127.0.0.1:8000/tasks');

    console.log('Checking headers...');
    // Select the first header cell in the table
    const header = page.locator('table thead th').first();
    const headerText = await header.innerText();

    // Get computed style
    const color = await header.evaluate(el => {
        return window.getComputedStyle(el).color;
    });

    console.log(`Header Text: "${headerText}"`);
    console.log(`Computed Color: ${color}`);

    // Check against expected color for --c-text-main (#1e293b -> rgb(30, 41, 59))
    if (color === 'rgb(30, 41, 59)') {
        console.log('SUCCESS: Color matches correct theme variable.');
    } else {
        console.log('FAILURE: Color does not match. Expected rgb(30, 41, 59).');
    }

    await browser.close();
})();
