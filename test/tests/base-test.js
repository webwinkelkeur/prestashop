const childProcess = require('child_process');

class BaseTest {

    constructor(params, page) {

        let pageExtensions = {

            _doAndWaitForNavigation: async function (actions, opts) {
                let navigationPromise = this.waitForNavigation(opts);
                await actions();
                return navigationPromise;
            },

            _waitForVisible: async function (selector, opts) {
                console.log('--- Waiting for: ' + selector);
                opts = Object.assign({}, opts, {visible: true});
                return this.waitForSelector(selector, opts);
            },

            _waitForVisibleAndClick: async function (selector, opts) {
                await this._waitForVisible(selector, opts);
                return this.click(selector);
            },

            _waitForAndClickInPage: async function (selector, opts) {
                console.log('--- Waiting for DOM: ' + selector);
                await this.waitForSelector(selector, opts);
                return this.$eval(selector, e => e.click());
            },

            _pointAndType: async function (selector, text) {
                console.log('--- Typing "' + text + '" in ' + selector);
                await this.focus(selector);
                await this.type(text);
            },

            _focusAndReplace: async function (selector, text) {
                console.log('--- Replacing with "' + text + '" in ' + selector);
                await this.click(selector, {clickCount: 2, delay: 200});
                await this.type(text);
            }
        };

        this.params = params;
        this.page = Object.assign(page, pageExtensions);
    }

    async install() {
        console.log('Going to instalation page');
        await this.page.goto(this.params['root-url'] + '/install1');

        console.log('Going to next page');
        await this.page._waitForVisibleAndClick('#btNext');

        console.log('Agreeing with terms');
        await this.page._waitForVisibleAndClick('#set_license');
        await this.page._waitForVisibleAndClick('#btNext');

        await this.passEnvPage();

        console.log('Filling shop details form');
        await this.page._waitForVisible('#infosShop');
        await this.page._pointAndType('#infosShop', this.params['shop-name']);
        await this.page._pointAndType('#infosFirstname', this.params['admin-first-name']);
        await this.page._pointAndType('#infosName', this.params['admin-last-name']);
        await this.page._pointAndType('#infosEmail', this.params['admin-user']);
        await this.page._pointAndType('#infosPassword', this.params['admin-pass']);
        await this.page._pointAndType('#infosPasswordRepeat', this.params['admin-pass']);
        await this.page._waitForVisibleAndClick('#btNext');

        console.log('Filling DB info page');
        await this.page._waitForVisible('#dbServer');
        await this.page.click('#dbServer', {clickCount: 2, delay: 150});
        await this.page.type(this.params['db-server']);
        await this.page._pointAndType('#dbPassword', this.params['db-pass']);

        console.log('Testing DB connection');
        await this.page._waitForVisibleAndClick('#btTestDB');

        await this.waitForCreateDBButton();

        console.log('Create DB');
        await this.page._waitForVisibleAndClick('#btCreateDB');
        await this.page._waitForVisible('#dbResultCheck.okBlock');
        await this.page.waitFor(2000);

        console.log('Going to next page');
        await this.page.click('#btNext');

        console.log('Waiting for installation');
        let failExpectation = this.page._waitForVisible('#error_process', {timeout: 120000});
        failExpectation.then(async () => {
            console.log('Encountered error. Trying again.');
            await this.page.waitFor(2000);
            this.page.click('#error_process a');
        }).catch(() => {});

        await this.page._waitForVisible('#install_process_success', {timeout: 120000});
    }

    async gotoAdmin() {
        const adminUrl = this.params['root-url'] + '/admin1';
        console.log('Going to ' + adminUrl);
        await this.page.goto(adminUrl, {timeout: 60000});
    }

    async login() {
        console.log('Logging in');
        await this.page.focus('#email');
        await this.page.type(this.params['admin-user']);
        await this.page.focus('#passwd');
        await this.page.type(this.params['admin-pass']);
        await this.page._doAndWaitForNavigation(
            () => this.page.click('button[name="submitLogin"]'),
            {timeout: 90000}
        );
    }

    async configureModule(shopId, shopKey) {
        console.log('Configuring module');
        await this.page._focusAndReplace('[name="shop_id"]', shopId);
        await this.page._focusAndReplace('[name="api_key"]', shopKey);
        await this.page.click('#webwinkelkeur-invite-on');
        await this.page.click('#content form [type="submit"]');
        await this.page._waitForVisible('.module_confirmation.conf.confirm.alert.alert-success')
    }

    async setOrdersToUninvited() {
        console.log('Setting orders to not invited');
        await this.execMysql('update ps_orders set webwinkelkeur_invite_sent = 0');
    }

    async gotoOrdersPage() {
        console.log('Going to orders page');
        await this.page.click('#nav-sidebar a[href*=AdminOrder]');
    }

    async checkIfAPIWasCalled() {
        console.log('Checking if API was called');
        const dbResults = await this.execMysql('select * from ps_webwinkelkeur_invite_error');
        if (dbResults === '') {
            throw new Error('An error for the API call was not found in the DB');
        }
        console.log('It was called');
    }

    async logout() {
        console.log('Logging out');
        await this.page.click('#employee_infos > a[data-toggle="dropdown"]');
        await this.page._waitForVisible('#header_logout');
        await this.page.click('#header_logout');
        await this.page._waitForVisible('#login-panel');
    }

    async checkBanner() {
        console.log('Going to home this.page: ' + this.params['root-url']);
        await this.page.goto(this.params['root-url']);

        console.log('Waiting for sidebar to load');
        await this.page.waitForSelector('.wwk--sidebar', {visible: true});
    }

    async configureModuleForStore2() {
        console.log('Configuring module for store 2');
        await this.page.waitFor(1000);
        await this.page._waitForVisibleAndClick('a[href*="controller=AdminModules"]');
        await this.page._waitForVisibleAndClick('a[href*="module_name=webwinkelkeur"]');
        await this.page._doAndWaitForNavigation(() => this.selectShopToConfigure());
        await this.configureModule(this.params['shop2-id'], this.params['shop2-key']);
    }

    async checkModuleConfigurationForStore2() {
        console.log('Checking configuration for store 2');
        await this.page._waitForVisible('[name="shop_id"]');
        let actualId = await this.page.$eval('[name="shop_id"]', e => e.value);
        let actualApiKey = await this.page.$eval('[name="api_key"]', e => e.value);
        if (actualId !== this.params['shop2-id']) {
            throw new Error('Actual shop2 id wrong. It was: ' + actualId);
        }
        if (actualApiKey !== this.params['shop2-key']) {
            throw new Error('Actual shop2 API key wrong. It was: ' + actualApiKey);
        }
        console.log('OK');
    }

    async gotoShop2() {
        let url = this.params['root-url'] + '/' + this.params['shop2-virtual-url'];
        console.log('Going to shop2 url: ' + url);
        await this.page.goto(url);
    }

    async checkShop2BannerCode() {
        console.log('Checking banner code for shop 2');
        let html = await this.page.content();
        let id = html.match(/_webwinkelkeur_id\s*=\s*(\d+);/)[1];
        if (id !== this.params['shop2-id']) {
            throw new Error('ID in code did not match. It was: ' + id);
        }
        console.log('OK');
    }

    passEnvPage() {
        throw new Error('BaseTest::passEnvPage() not implemented');
    }

    waitForCreateDBButton() {}

    configureMultistore() {
        throw new Error('BaseTest::configureMultistore() not implemented');
    }

    installModule() {
        throw new Error('BaseTest::installModule() not implemented');
    }

    gotoModuleConfiguration() {
        throw new Error('BaseTest::gotoModuleConfiguration() not implemented');
    }

    async finishTestOrder() {
        throw new Error('BaseTest::finishTestOrder() not implemented');
    }

    selectShopToConfigure() {
        throw new Error('BaseTest::selectShopToConfigure() not implemented');
    }

    getModuleFileName() {
        return this.params['module-dir'] + this.params['module-file'];
    }

    async run() {
        await this.install();
        await this.gotoAdmin();
        await this.login();
        await this.installModule();
        await this.gotoModuleConfiguration();
        await this.configureModule(this.params['shop-id'], this.params['shop-key']);
        await this.gotoOrdersPage();
        await this.setOrdersToUninvited();
        await this.finishTestOrder();
        await this.checkIfAPIWasCalled();
        await this.logout();
        await this.checkBanner();
        await this.gotoAdmin();
        await this.login();
        await this.configureMultistore();
        await this.configureModuleForStore2();
        await this.checkModuleConfigurationForStore2();
        await this.logout();
        await this.checkBanner();
        await this.gotoShop2();
        await this.checkShop2BannerCode();
    }

    async execMysql(query) {
        console.log('Executing query: ' + query);
        const cmd = `mysql -h ${this.params['db-server']} -u root -p${this.params['db-pass']} prestashop -e "${query}"`;
        console.log('Executing command: ' + cmd);
        return new Promise((resolve) => childProcess.exec(
            cmd, (error, stdout) => resolve(stdout)
        ));
    }

    async dropInREPL() {
        const repl = require('repl');
        const r = repl.start('REPL> ');
        r.context.test = this;
        return new Promise((resolve) => {
            r.on('exit', () => resolve());
        });
    }
}

exports.BaseTest = BaseTest;
