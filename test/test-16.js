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
        await page.hover('#maintab-AdminParentModules');
        await page.click('#subtab-AdminModules a');
        await page.waitForNavigation();

        console.log('Uploading module');
        await page.click('#desc-module-new');
        await page.waitForSelector('#module_install form');
        const fileUpload = await page.$('#file');
        await fileUpload.uploadFile('../dist/prestashop-webwinkelkeur-1.6.zip');
        await page.click('#module_install form button[type="submit"]');
        await page.waitForNavigation();

        console.log('Installing module');
        await page.click('a[data-module-name="webwinkelkeur"]');
        await page.waitForSelector('#proceed-install-anyway', {visible: true});
        await page.click('#proceed-install-anyway');
        await page.waitForNavigation();


        console.log('Configuring module');
        await page.focus('[name="shop_id"]');
        await page.type('1');
        await page.focus('[name="api_key"]');
        await page.type('1234');
        await page.click('#content form [type="submit"]');

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

