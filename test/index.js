const test = require('./tests/index');

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

test.run(params);

