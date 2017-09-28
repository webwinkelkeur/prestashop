class BaseTest {

    constructor(params, page) {

        let pageExtensions = {
            _waitForVisible: async function (selector, opts) {
                console.log('Waiting for: ' + selector);
                opts = Object.assign({}, opts, {visible: true});
                return this.waitForSelector(selector, opts);
            },

            _waitForVisibleAndClick: async function (selector, opts) {
                await this._waitForVisible(selector, opts);
                return this.click(selector);
            },

            _pointAndType: async function (selector, text) {
                console.log('Typing "' + text + '" in ' + selector);
                await this.focus(selector);
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

        await this.afterDatabaseTest();

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
        await this.page.goto(adminUrl);
    }

    async login() {
        console.log('Logging in');
        await this.page.focus('#email');
        await this.page.type(this.params['admin-user']);
        await this.page.focus('#passwd');
        await this.page.type(this.params['admin-pass']);
        await this.page.click('button[name="submitLogin"]');
        await this.page.waitForNavigation();

        console.log('Canceling onboarding');
        try {
            await this.page.click('.onboarding-button-shut-down');
        } catch (e) {
            console.log('There was no onboarding')
        }
    }

    async configureModule() {
        console.log('Configuring module');
        await this.page.focus('[name="shop_id"]');
        await this.page.type(this.params['shop-id']);
        await this.page.focus('[name="api_key"]');
        await this.page.type(this.params['shop-key']);
        await this.page.click('#content form [type="submit"]');
        await this.page._waitForVisible('.module_confirmation.conf.confirm.alert.alert-success')
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

    passEnvPage() {
        throw new Error('BaseTest::passEnvPage() not implemented');
    }

    afterDatabaseTest() {}

    installModule() {
        throw new Error('BaseTest::installModule() not implemented');
    }

    gotoModuleConfiguration() {
        throw new Error('BaseTest::gotoModuleConfiguration() not implemented');
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
        await this.configureModule();
        await this.logout();
        await this.checkBanner();
    }
}

exports.BaseTest = BaseTest;
