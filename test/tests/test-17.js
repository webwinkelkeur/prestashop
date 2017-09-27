let BaseTest = require('./base-test').BaseTest;

class Test17 extends BaseTest {

    async installModule() {
        console.log('Going to modules page');
        //await this.sleep(500); // wait for an animation to finish
        await this.page._waitForVisibleAndClick('#subtab-AdminParentModulesSf > a');

        console.log('Uploading module: ' + this.getModuleFileName());
        await this.page._waitForVisibleAndClick('#page-header-desc-configuration-add_module');
        await this.page.screenshot({path: '1beforeUpload.jpg'});
        await this.page.waitForSelector('#importDropzone input[type="file"]');
        const fileUpload = await this.page.$('#importDropzone input[type="file"]');
        await fileUpload.uploadFile(this.getModuleFileName());
        await this.page.screenshot({path: '2uploading.jpg'});
    }

    async gotoModuleConfiguration() {
        console.log('Going to installed modules page');
        await this.page._waitForVisible('a.module-import-success-configure');
        await this.page.screenshot({path: '3afterUpload.jpg'});
        await this.page._waitForVisibleAndClick('#module-modal-import-closing-cross');
        await this.sleep(500);
        await this.page.screenshot({path: '4afterDialog.jpg'});
        await this.page.click('.page-head-tabs a.tab:nth-child(2)');

        console.log('Going to module configuration page');
        await this.page.screenshot({path: '5beforeRender.jpg'});
        await this.sleep(1000); // wait for scripts to render component
        await this.page.screenshot({path: '6afterRender.jpg'});
        await this.page._waitForVisibleAndClick(
            '[data-tech-name="webwinkelkeur"] [data-confirm_modal="module-modal-confirm-webwinkelkeur-configure"]'
        );
        await this.page._waitForVisible('[name="shop_id"]');
    }
}

exports.TestCase = Test17;
