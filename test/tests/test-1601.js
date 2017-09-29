let BaseTest = require('./base-test').BaseTest;

class Test1601 extends BaseTest {

    passEnvPage() {
        return new Promise(resolve => resolve());
    }

    async waitForCreateDBButton() {
        let tries = 5;
        while (tries--) {
            try {
                await this.page.waitForSelector('#btCreateDB', {timeout: 1000});
                await this.page.waitFor(1000);
                return;
            } catch (e) {}
            await this.page._waitForVisibleAndClick('#btTestDB');
        }
        throw new Error('Create database button never appeared!');
    }

    async installModule() {
        console.log('Going to modules page');
        await this.gotoModulesPage();

        console.log('Uploading module');
        await this.page._waitForVisible('#desc-module-new', {timeout: 120000});
        await this.page.$eval('#desc-module-new', e => e.click());
        await this.page.waitForSelector('#module_install form');
        const fileUpload = await this.page.$('#file');
        await fileUpload.uploadFile(this.getModuleFileName());
        await this.page.click('#module_install form button[type="submit"]');

        console.log('Enabling module');
        await this.enableModule();
        await this.page._waitForVisible('[name="shop_id"]');
    }

    async gotoModulesPage() {
        await this.page.click('.icon-AdminParentModules');
    }

    async enableModule() {
        await this.page._waitForVisibleAndClick('.btn-success[href*=webwinkelkeur]');
    }

    gotoModuleConfiguration() {}
}

exports.TestCase = Test1601;
