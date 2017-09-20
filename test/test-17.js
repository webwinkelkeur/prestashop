const puppeteer = require('puppeteer');

(async () => {
    console.log('Starting test');
    const browser = await puppeteer.launch({headless: false, slowMo: 100});
    const page = await browser.newPage();
    await page.setViewport({width: 1920, height: 1080});

    try {
        console.log('Going to http://localhost:8081/admin1');
        await page.goto('http://localhost:8081/admin1');

        console.log('Logging in');
        await page.focus('#email');
        await page.type('autotester@kiboit.com');
        await page.focus('#passwd');
        await page.type('tester');
        await page.click('button[name="submitLogin"]');
        await page.waitForNavigation();

        console.log('Canceling onboarding');
        try {
            await page.click('.onboarding-popup .buttons .onboarding-button-shut-down');
        } catch (e) {
            console.log('There was no onboarding')
        }

        console.log('Going to modules page');
        await page.hover('#subtab-AdminParentModulesSf');
        await page.click('#subtab-AdminModulesSf a');
        await page.waitForNavigation();

        console.log('Uploading module');
        await page.click('#page-header-desc-configuration-add_module');
        await page.waitForSelector('#importDropzone input[type="file"]');
        const fileUpload = await page.$('#importDropzone input[type="file"]');
        await fileUpload.uploadFile('../dist/prestashop-webwinkelkeur-1.7.zip');

        console.log('Going to installed modules page');
        await page.waitForSelector('a.module-import-success-configure', {visible: true});
        await page.click('#module-modal-import-closing-cross');
        await page.click('.page-head-tabs a.tab:nth-child(2)');
        await page.waitForNavigation();

        console.log('Going to module configuration page');
        await page.click(
            '[data-tech-name="webwinkelkeur"] [data-confirm_modal="module-modal-confirm-webwinkelkeur-configure"]'
        );
        await page.waitForNavigation();

        console.log('Configuring module');
        await page.focus('[name="shop_id"]');
        await page.type('1');
        await page.focus('[name="api_key"]');
        await page.type('1234');
        await page.click('#content form [type="submit"]');
        await page.waitForNavigation();
        const successConfirmation = await page.$('.module_confirmation.conf.confirm.alert.alert-success');
        if (!successConfirmation) {
            throw new Error('Could not see success confirmation after configuring module!');
        }

        console.log('Logging out');
        await page.click('#employee_infos > a[data-toggle="dropdown"]');
        await page.waitForSelector('#header_logout', {visible: true});
        await page.click('#header_logout');
        await page.waitForNavigation();

        console.log('Going to home page: http://localhost:8081/');
        await page.goto('http://localhost:8081/');

        console.log('Waiting for sidebar to load');
        await page.waitForSelector('.wwk--sidebar', {visible: true});
    } catch (e) {
        console.error(e.message);
        await page.screenshot({path: 'error.jpg', fullPage: true, quality: 100});
        console.log('Screenshot saved in error.jpg')
    }
    browser.close();
})();

