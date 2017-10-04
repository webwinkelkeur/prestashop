let Test1610 = require('./test-1610').TestCase;

class Test17 extends Test1610 {

    async passEnvPage() {
        console.log('Passing environment test page');
        await this.page._waitForVisible('.okBlock');
        return this.page._waitForVisibleAndClick('#btNext');
    }

    async login() {
        await super.login();
        console.log('Canceling onboarding');
        try {
            await this.page.click('.onboarding-button-shut-down');
            await this.page.click('.onboarding-navbar a');
        } catch (e) {
            console.log('There was no onboarding')
        }
    }

    async installModule() {
        console.log('Going to modules page');
        try {
            await this.page.click('#error-modal');
            await this.page.waitFor(1000);
        } catch(e) {}
        await this.navigateToModulesPage();

        console.log('Uploading module: ' + this.getModuleFileName());
        await this.page._waitForVisibleAndClick('#page-header-desc-configuration-add_module');
        await this.page.waitForSelector('#importDropzone input[type="file"]');
        const fileUpload = await this.page.$('#importDropzone input[type="file"]');
        await fileUpload.uploadFile(this.getModuleFileName());
    }

    async gotoModuleConfiguration() {
        console.log('Going to installed modules page');
        await this.page._waitForVisible('a.module-import-success-configure');
        await this.page._waitForVisibleAndClick('#module-modal-import-closing-cross');
        await this.navigateToConfigPage();
    }

    async configureModuleForStore2() {
        console.log('Configuring module for store 2');
        await this.navigateToModulesPage();
        await this.navigateToConfigPage();
        await this.page._doAndWaitForNavigation(() => this.selectShopToConfigure());
        await this.configureModule(this.params['shop2-id'], this.params['shop2-key']);
    }

    async navigateToModulesPage() {
        await this.page._waitForAndClickInPage('#subtab-AdminParentModulesSf > a');
    }

    async navigateToConfigPage() {
        await this.page._waitForVisibleAndClick('.page-head-tabs a.tab:nth-child(2)');

        console.log('Going to module configuration page');
        await this.page._waitForVisibleAndClick(
            '[data-tech-name="webwinkelkeur"] [data-confirm_modal="module-modal-confirm-webwinkelkeur-configure"]'
        );
        await this.page._waitForVisible('[name="shop_id"]');
    }
}

exports.TestCase = Test17;
