let BaseTest = require('./base-test').BaseTest;

class Test1601 extends BaseTest {

    passEnvPage() {
        return new Promise(resolve => resolve());
    }

    async waitForCreateDBButton() {
        let tries = 5;
        while (tries--) {
            try {
                await this.page.waitForSelector('#btCreateDB', {timeout: 1000});
                await this.page.waitFor(1000);
                return;
            } catch (e) {}
            await this.page._waitForVisibleAndClick('#btTestDB');
        }
        throw new Error('Create database button never appeared!');
    }

    async installModule() {
        console.log('Going to modules page');
        await this.gotoModulesPage();

        await this.disableMerchantExpertiseModule();

        console.log('Uploading module');
        await this.page._waitForVisibleAndClick('#desc-module-new');
        await this.page.waitForSelector('#module_install form');
        const fileUpload = await this.page.$('#file');
        await fileUpload.uploadFile(this.getModuleFileName());
        await this.page.click('#module_install form button[type="submit"]');

        console.log('Enabling module');
        await this.enableModule();
        await this.page._waitForVisible('[name="shop_id"]');
    }

    async disableMerchantExpertiseModule() {
        console.log('Disabling "Merchant Expert" (gamification) module');
        await this.page._waitForVisibleAndClick('a[href*="module_name=gamification"] + .dropdown-toggle');
        await this.page._waitForVisibleAndClick(
            'a[href*="module_name=gamification"] + .dropdown-toggle + .dropdown-menu > li:first-child > a'
        );
    }

    async configureMultistore() {
        console.log('Enabling multistore');
        await this.page._waitForVisibleAndClick('a[href*="controller=AdminPreferences"]');
        await this.page._waitForVisibleAndClick('label[for="PS_MULTISHOP_FEATURE_ACTIVE_on"]');
        await this.page.waitFor(1000);
        await this.page.click('#desc-configuration-save');
        await this.page.waitFor(1000);

        console.log('Configuring multistore');
        await this.page._waitForVisibleAndClick('a[href*="controller=AdminInformation"]');
        await this.page.waitFor(500);
        await this.page._waitForVisibleAndClick('a[href*="controller=AdminShopGroup"]');
        await this.page._waitForVisibleAndClick('#tree-group-1 > a');
        await this.page._waitForVisibleAndClick('#desc-shop-new');
        await this.page._waitForVisible('#name');
        await this.page._pointAndType('#name', this.params['shop2-name']);
        await this.page.click('#page-header-desc-shop-save');
        await this.page._waitForVisibleAndClick(
            'a[href*="controller=AdminShopUrl"][href*="id_shop=2"][href*="addshop_url"]'
        );
        await this.page._waitForVisible('#virtual_uri');
        await this.page._pointAndType('#virtual_uri', this.params['shop2-virtual-url']);
        await this.page._waitForVisibleAndClick('#page-header-desc-shop_url-save');
    }


    gotoModuleConfiguration() {}

    async gotoModulesPage() {
        await this.page._doAndWaitForNavigation(
            () => this.page.click('.icon-AdminParentModules'),
            {timeout: 120000}
        );
    }

    async enableModule() {
        await this.page._waitForVisibleAndClick('.btn-success[href*=webwinkelkeur]');
    }

    async finishTestOrder() {
        await this.gotoTestOrder();
        await this.setTestOrderStatus();
    }

    async gotoTestOrder() {
        console.log('Going to test order');
        await this.page._waitForVisibleAndClick('#order a[title="View"], .table.order a[title="View"]');
    }

    async setTestOrderStatus() {
        console.log('Finishing order');
        await this.page.waitFor('#id_order_state');
        await this.page.$eval('#id_order_state', (e) => e.value = 4);
        await this.page.click('button[name="submitState"]');
        await this.page.waitFor(1000);
    }

    async selectShopToConfigure() {
        await this.page._waitForVisibleAndClick('select.shopList + div');
        return this.page._doAndWaitForNavigation(
            () => this.page._waitForVisibleAndClick('select.shopList + div li:last-child')
        );
    }
}

exports.TestCase = Test1601;
