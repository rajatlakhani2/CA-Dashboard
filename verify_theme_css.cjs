const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage();
    await page.goto('http://127.0.0.1:8000/tasks');

    const bodyDiv = page.locator('.min-h-full.bg-bg-body');

    const computed = await bodyDiv.evaluate(el => {
        const style = window.getComputedStyle(el);
        return {
            backgroundColor: style.backgroundColor,
            color: style.color
        };
    });

    console.log('Computed Styles:', computed);

    // Executive Expected:
    // BG: rgb(255, 255, 255)
    // Text: rgb(0, 0, 0)

    if (computed.color === 'rgb(0, 0, 0)') {
        console.log('Text Color confirms Executive Theme.');
    } else {
        console.log('Text Color mismatch. Likely Modern/Default.');
    }

    await browser.close();
})();
