let Test1601 = require('./test-1601').TestCase;

class Test1603 extends Test1601 {

    async configureMultistore() {
        console.log('Enabling multistore');
        await this.page._waitForVisibleAndClick('a[href*="controller=AdminPreferences"]');
        await this.page._waitForVisibleAndClick('label[for="PS_MULTISHOP_FEATURE_ACTIVE_on"]');
        await this.page.waitFor(1000);
        await this.page.click('[name="submitOptionsconfiguration"]');
        await this.page._waitForVisible('.alert-success');

        console.log('Configuring multistore');
        await this.page._waitForVisibleAndClick('a[href*="controller=AdminInformation"]');
        await this.page.waitFor(500);
        await this.page._waitForVisibleAndClick('a[href*="controller=AdminShopGroup"]');
        await this.page.waitFor(500);
        await this.page._doAndWaitForNavigation(
            () => {
                return this.page._waitForVisibleAndClick(
                    'a[href*="controller=AdminShop"][href*="id_shop=1"],' +
                    'a[href*="controller=AdminShop"][href*="shop_id=1"]'
                )
            }
        );

        await this.page._waitForVisibleAndClick('#page-header-desc-shop-new');
        await this.page._waitForVisible('#name');
        await this.page._pointAndType('#name', this.params['shop2-name']);
        try {
            await this.page.click('#check-all-categories-tree');
        } catch (e) {}
        await this.page.click('#shop_form_submit_btn');
        await this.page._waitForVisibleAndClick(
            'a[href*="controller=AdminShopUrl"][href*="id_shop=2"][href*="addshop_url"],' +
            'a[href*="controller=AdminShopUrl"][href*="shop_id=2"][href*="addshop_url"]'
        );
        await this.page._waitForVisible('#virtual_uri');
        await this.page._pointAndType('#virtual_uri', this.params['shop2-virtual-url']);
        await this.page._waitForVisibleAndClick('#shop_url_form_submit_btn');
    }
}

exports.TestCase = Test1603;
