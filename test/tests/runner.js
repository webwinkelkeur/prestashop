const puppeteer = require('puppeteer');

function getTestCaseForVersion(version) {
    return require(getModulePathForVersion(version)).TestCase;
}

function getModulePathForVersion(version) {
    let versionParts = version.split('.').map((v) => parseInt(v));
    if (versionParts[1] === 7) {
        return './test-17';
    }
    if (versionParts[1] === 6 && versionParts[2] === 0) {
        if (versionParts[3] < 3) {
            return './test-1601';
        }
        if (versionParts[3] === 3) {
            return './test-1603';
        }
        if (versionParts[3] < 7) {
            return './test-1604';
        }
        return './test-1607';
    }
    if (versionParts[1] === 6) {
        return './test-1610';
    }
    throw new Error('Unknown version: ' + version);
}

async function run(params) {
    console.log('Starting test');
    const browser = await puppeteer.launch({
        headless: params.headless === 'true',
        slowMo: parseInt(params['slow-mo']),
        args: ['--window-size=' + [params.width, params.height].join(',')]
    });
    const page = await browser.newPage();
    await page.setViewport({
        width: params.width,
        height: params.height
    });
    await page.setExtraHTTPHeaders({
        'Accept-Language': 'en-US,en;q=0.8'
    });

    const testCase = getTestCaseForVersion(params.version);
    const test = new testCase(params, page);

    let status;

    try {
        await test.run();
        console.log('Success!');
        status = true;
    } catch (e) {
        console.error('Error: ' + e.message);
        const now = new Date();
        const filename = 'error-' + now.toISOString() + '-' + params.version + '.jpg';
        const fullFilename = params['error-image-dir'] + '/' + filename;
        await page.screenshot({path: fullFilename, fullPage: true, quality: 100});
        console.log('Screenshot saved in ' + fullFilename);
        status = false;
    }

    if (params.close !== 'true') {
        return test.dropInREPL();
    }

    browser.close();
    return status;
}

exports.run = run;
exports.defaultParams = {
    'root-url': 'http://localhost:8081',
    'db-server': 'localhost',
    'db-pass': 'admin',
    'shop-name': 'Test shop',
    'shop2-name': 'Test shop 2',
    'shop2-virtual-url': 'test-shop-2',
    'admin-first-name': 'Jon',
    'admin-last-name': 'Doe',
    'admin-user': 'autotester@kiboit.com',
    'admin-pass': 'tester123',
    'module-dir': __dirname + '/../../dist/',
    'module-file': 'prestashop-webwinkelkeur.zip',
    'error-image-dir': __dirname + '/../error-images/',
    'headless': 'true',
    'slow-mo': 100,
    'width': 1920,
    'height': 1080,
    'shop-id': '1',
    'shop-key': '1234',
    'shop2-id': '2',
    'shop2-key': '5678',
    'version': 'latest',
    'close': 'true',
};
