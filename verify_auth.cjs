const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage();
    await page.goto('http://127.0.0.1:8000/tasks');

    // Capture the debug text
    const debugText = await page.textContent('.fixed.bottom-0.right-0');
    console.log('DEBUG INFO:', debugText);

    await browser.close();
})();
