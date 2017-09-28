let BaseTest = require('./base-test').BaseTest;

class Test16 extends BaseTest {

    passEnvPage() {
        return new Promise(resolve => resolve());
    }

    async afterDatabaseTest() {
        await this.page.waitFor(2000);
        await this.page._waitForVisibleAndClick('#btTestDB');
        await this.page.waitFor(5000);
        return this.page._waitForVisibleAndClick('#btTestDB');
    }

    async installModule() {
        console.log('Going to modules page');
        await this.page.hover('#maintab-AdminParentModules');
        await this.page.click('#subtab-AdminModules a');

        console.log('Uploading module');
        await this.page._waitForVisibleAndClick('#desc-module-new', {timeout: 60000});
        await this.page.waitForSelector('#module_install form');
        const fileUpload = await this.page.$('#file');
        await fileUpload.uploadFile(this.getModuleFileName());
        await this.page.click('#module_install form button[type="submit"]');

        console.log('Installing module');
        await this.page._waitForVisibleAndClick('a[data-module-name="webwinkelkeur"]');
        await this.page._waitForVisibleAndClick('#proceed-install-anyway');
        await this.page._waitForVisible('[name="shop_id"]');
    }

    gotoModuleConfiguration() {
        return;
    }
}

exports.TestCase = Test16;
