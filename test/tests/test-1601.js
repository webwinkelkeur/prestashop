let Test16 = require('./test-16').TestCase;

class Test1601 extends Test16 {

    async gotoModulesPage() {
        await this.page.click('.icon-AdminParentModules');
    }

    async enableModule() {
        await this.page._waitForVisibleAndClick('.btn-success[href*=webwinkelkeur]');
    }
}

exports.TestCase = Test1601;
