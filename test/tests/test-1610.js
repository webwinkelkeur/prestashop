let Test1607 = require('./test-1607').TestCase;

class Test1610 extends Test1607 {

    async gotoModulesPage() {
        await this.page.hover('#maintab-AdminParentModules');
        await this.page.click('#subtab-AdminModules a');
    }

    async enableModule() {
        await this.page._waitForVisibleAndClick('a[data-module-name="webwinkelkeur"]');
        await this.page._waitForVisibleAndClick('#proceed-install-anyway');
    }

    async gotoTestOrder() {
        console.log('Going to test order');
        await this.page._waitForVisibleAndClick('#form-order a[title="View"]');
    }

}

exports.TestCase = Test1610;
