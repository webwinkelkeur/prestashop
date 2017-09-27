const puppeteer = require('puppeteer');

function getTestCaseForVersion(version) {
    let majorVersion = version.substr(0, 3);
    if (version === 'latest' || majorVersion === '1.7') {
        return require('./test-17').TestCase;
    }
    if (majorVersion === '1.6') {
        return require('./test-16').TestCase;
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

    const testCase = getTestCaseForVersion(params.version);
    const test = new testCase(params, page);

    try {
        await test.run();
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
}

exports.run = run;
exports.defaultParams = {
    'root-url': 'http://localhost:8081',
    'admin-user': 'autotester@kiboit.com',
    'admin-pass': 'tester',
    'module-dir': __dirname + '/../../dist/',
    'module-file': 'prestashop-webwinkelkeur.zip',
    'error-image-dir': __dirname + '/../error-images/',
    'headless': 'true',
    'slow-mo': 100,
    'width': 1920,
    'height': 1080,
    'shop-id': '1',
    'shop-key': '1234',
    'version': 'latest'
};
