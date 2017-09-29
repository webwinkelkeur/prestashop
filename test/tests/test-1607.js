let Test1601 = require('./test-1601').TestCase;

class Test1607 extends Test1601 {

    async enableModule() {
        await this.page._waitForVisibleAndClick('a[data-module-name="WebwinkelKeur"]');
        await this.page._waitForVisibleAndClick('#proceed-install-anyway');
    }

}

exports.TestCase = Test1607;
