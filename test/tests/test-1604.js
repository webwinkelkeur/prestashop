let Test1603 = require('./test-1603').TestCase;

class Test1604 extends Test1603 {

    async selectShopToConfigure() {
        await this.page.waitFor(1000);
        await this.page._waitForVisible('select.shopList');
        return this.page._doAndWaitForNavigation(
            () => this.page.$eval('select.shopList', l => {
                l.value = 's-2';
                l.onchange();
            })
        );
    }

}

exports.TestCase = Test1604;
