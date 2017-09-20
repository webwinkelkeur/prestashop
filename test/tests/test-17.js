let BaseTest = require('./base-test').BaseTest;

class Test17 extends BaseTest {

    async installModule() {
        console.log('Going to modules page');
        await this.page.hover('#subtab-AdminParentModulesSf');
        await this.page.click('#subtab-AdminModulesSf a');
        await this.page.waitForNavigation();

        console.log('Uploading module: ' + this.getModuleFileName());
        await this.page.click('#page-header-desc-configuration-add_module');
        await this.page.waitForSelector('#importDropzone input[type="file"]');
        const fileUpload = await this.page.$('#importDropzone input[type="file"]');
        await fileUpload.uploadFile(this.getModuleFileName());
    }

    async gotoModuleConfiguration() {
        console.log('Going to installed modules page');
        await this.page.waitForSelector('a.module-import-success-configure', {visible: true});
        await this.page.click('#module-modal-import-closing-cross');
        await this.page.click('.page-head-tabs a.tab:nth-child(2)');
        await this.page.waitForNavigation();

        console.log('Going to module configuration page');
        await this.page.click(
            '[data-tech-name="webwinkelkeur"] [data-confirm_modal="module-modal-confirm-webwinkelkeur-configure"]'
        );
        await this.page.waitForNavigation();
    }
}

exports.TestCase = Test17;
