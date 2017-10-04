let Test1607 = require('./test-1607').TestCase;

class Test1610 extends Test1607 {

    async gotoTestOrder() {
        console.log('Going to test order');
        await this.page._waitForVisibleAndClick('#form-order a[title="View"]');
    }

    async selectShopToConfigure() {
        await this.page.waitFor(1000);
        await this.page._waitForVisibleAndClick('#header_shop');
        return this.page._doAndWaitForNavigation(
            () => this.page._waitForVisibleAndClick('#header_shop .dropdown-menu > li:last-child', {timeout: 60000})
        );
    }

}

exports.TestCase = Test1610;
