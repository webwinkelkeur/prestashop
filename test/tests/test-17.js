let BaseTest = require('./base-test').BaseTest;

class Test17 extends BaseTest {

    async installModule() {
        console.log('Going to modules page');
        await this.page._waitForVisibleAndClick('#subtab-AdminParentModulesSf > a');

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
        await this.page.click('.page-head-tabs a.tab:nth-child(2)');

        console.log('Going to module configuration page');
        await this.page._waitForVisibleAndClick(
            '[data-tech-name="webwinkelkeur"] [data-confirm_modal="module-modal-confirm-webwinkelkeur-configure"]'
        );
        await this.page._waitForVisible('[name="shop_id"]');
    }
}

exports.TestCase = Test17;
