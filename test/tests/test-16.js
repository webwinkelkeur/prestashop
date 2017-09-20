let BaseTest = require('./base-test').BaseTest;

class Test16 extends BaseTest {

    async installModule() {
        console.log('Going to modules page');
        await this.page.hover('#maintab-AdminParentModules');
        await this.page.click('#subtab-AdminModules a');
        await this.page.waitForNavigation();

        console.log('Uploading module');
        await this.page.click('#desc-module-new');
        await this.page.waitForSelector('#module_install form');
        const fileUpload = await this.page.$('#file');
        await fileUpload.uploadFile(this.getModuleFileName());
        await this.page.click('#module_install form button[type="submit"]');
        await this.page.waitForNavigation();

        console.log('Installing module');
        await this.page.click('a[data-module-name="webwinkelkeur"]');
        await this.page.waitForSelector('#proceed-install-anyway', {visible: true});
        await this.page.click('#proceed-install-anyway');
        await this.page.waitForNavigation();
    }

    gotoModuleConfiguration() {
        return;
    }
}

exports.TestCase = Test16;
