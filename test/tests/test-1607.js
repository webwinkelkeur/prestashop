let Test1604 = require('./test-1604').TestCase;

class Test1607 extends Test1604 {

    disableMerchantExpertiseModule() {}

    async enableModule() {
        await this.page._waitForVisibleAndClick(
            'a[data-module-name="WebwinkelKeur"], a[data-module-name="webwinkelkeur"]'
        );
        await this.page._waitForVisibleAndClick('#proceed-install-anyway');
    }

    async setTestOrderStatus() {
        console.log('Finishing order');
        await this.page.waitFor(1000);
        await this.page._waitForVisibleAndClick('#id_order_state_chosen');
        await this.page._waitForVisible('#id_order_state_chosen input[type="text"]');
        await this.page.type('Shipped');
        await this.page._waitForVisibleAndClick('#id_order_state_chosen li');

        await this.page._waitForVisibleAndClick('button[name="submitState"]');
        await this.page.waitFor(1000);
    }


}

exports.TestCase = Test1607;
