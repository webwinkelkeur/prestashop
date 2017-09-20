const puppeteer = require('puppeteer');

const params = {
    'root-url': 'http://localhost:8081',
    'admin-user': 'autotester@kiboit.com',
    'admin-pass': 'tester',
    'module-dir': __dirname + '/../dist/',
    'module-file': 'prestashop-webwinkelkeur.zip',
    'error-image-dir': __dirname + '/error-images/',
    'headless': 'true',
    'slow-mo': 100,
    'width': 1920,
    'height': 1080,
    'shop-id': '1',
    'shop-key': '1234',
    'version': 'latest'
};

const paramPattern = /--([a-z-]+)=([^\s]*)/;
for (let arg of process.argv) {
    let matches = arg.match(paramPattern);
    if (matches !== null && params[matches[1]] !== undefined) {
        params[matches[1]] = matches[2];
    }
}

(async () => {
    console.log('Starting test');
    const browser = await puppeteer.launch({headless: params.headless === 'true', slowMo: parseInt(params['slow-mo'])});
    const page = await browser.newPage();
    await page.setViewport({width: params.width, height: params.height});

    try {
        const adminUrl = params['root-url'] + '/admin1';
        console.log('Going to ' + adminUrl);
        await page.goto(adminUrl);

        console.log('Logging in');
        await page.focus('#email');
        await page.type(params['admin-user']);
        await page.focus('#passwd');
        await page.type(params['admin-pass']);
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

        const moduleFile = params['module-dir'] + params['module-file'];
        console.log('Uploading module: ' + moduleFile);
        await page.click('#page-header-desc-configuration-add_module');
        await page.waitForSelector('#importDropzone input[type="file"]');
        const fileUpload = await page.$('#importDropzone input[type="file"]');
        await fileUpload.uploadFile(moduleFile);

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
        await page.type(params['shop-id']);
        await page.focus('[name="api_key"]');
        await page.type(params['shop-key']);
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

        console.log('Going to home page: ' + params['root-url']);
        await page.goto(params['root-url']);

        console.log('Waiting for sidebar to load');
        await page.waitForSelector('.wwk--sidebar', {visible: true});

        console.log('Success!');
    } catch (e) {
        console.error('Error: ' + e.message);
        const now = new Date();
        const filename = 'error-' + params.version + '-' + now.toISOString() + '.jpg';
        const fullFilename = params['error-image-dir'] + '/' + filename;
        await page.screenshot({path: fullFilename, fullPage: true, quality: 100});
        console.log('Screenshot saved in ' + fullFilename);
    }
    browser.close();
})();

